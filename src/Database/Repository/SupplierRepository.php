<?php

declare(strict_types=1);

namespace App\Database\Repository;

use App\Autorus\SuppliersCacheRedis;
use App\Autorus\Exception\RuntimeException;
use App\Database\Entity\Supplier;
use Arus\Doctrine\Helper\ValidationInjection;
use Exception;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class SupplierRepository extends AbstractRepository
{
    use ValidationInjection {
        validate as protected _validate;
    }

    /**
     * @var string
     */
    protected $rootAlias = 's';

    /**
     * @Inject
     *
     * @var SuppliersCacheRedis
     */
    private $suppliersCacheRedis;

    /**
     * @var ConstraintViolationListInterface|null
     */
    private $constraintViolationList;

    /**
     * @param Supplier $supplier
     * @return bool
     */
    public function isUnique(Supplier $supplier): bool
    {
        $queryParams = [
            'uuid' => $supplier->getUuid(),
        ];

        $qb = $this->createQueryBuilder('s');
        $qb->select([
                's.id',
                's.uuid',
            ])
            ->andWhere(
                $qb->expr()->orX(
                    's.uuid = :uuid'
                )
            )
            ->setParameters($queryParams)
        ;

        if ($supplier->getId()) {
            $qb->andWhere('s.id != :id')
                ->setParameter('id', $supplier->getId())
            ;
        }

        $queryResult = $qb->getQuery()->getResult();
        $isUnique = !$queryResult;

        if (!$isUnique && $this->constraintViolationList instanceof ConstraintViolationListInterface) {
            foreach ($queryResult as $existedEntity) {
                foreach ($queryParams as $searchedField => $searchedValue) {
                    if (null !== $searchedValue && $searchedValue === $existedEntity[$searchedField]) {
                        $message = sprintf('Entity already exists with same "%s": %s', $searchedField, $searchedValue);
                        $this->constraintViolationList->add(new ConstraintViolation($message, null, [], null, $searchedField, $searchedValue));
                    }
                }
            }
        }

        return $isUnique;
    }

    /**
     * @param Supplier $supplier
     * @return ConstraintViolationListInterface
     */
    public function validate(Supplier $supplier): ConstraintViolationListInterface
    {
        $this->constraintViolationList = $this->_validate($supplier);

        $this->isUnique($supplier);

        return $this->constraintViolationList;
    }

    /**
     * @return array
     */
    public function getListFromCache(): array
    {
        $redis = $this->suppliersCacheRedis->getConnection();
        $suppliersCache = $redis->hGetAll(Supplier::getHashKey());
        $suppliersResult = [];

        foreach ($suppliersCache as $supplierJson) {
            $suppliersResult[] = json_decode($supplierJson, true);
        }

        return $suppliersResult;
    }

    /**
     * @return void
     * @throws RuntimeException
     */
    public function cacheRefresh(): void
    {
        $listParams = [
            'select' => [
                'uuid',
                'title',
                'public_title',
                'is_autorus',
                'delivery_accepting_minutes',
            ],
            'orderBy' => [
                'id' => 'DESC',
            ],
        ];
        $suppliers = $this->getList($listParams);
        $redis = $this->suppliersCacheRedis->getConnection();
        $redisMulti = $redis->multi();

        try {
            $suppliersHashKey = Supplier::getHashKey();
            $redisMulti->del($suppliersHashKey);

            foreach ($suppliers as $supplier) {
                $redisMulti->hMSet($suppliersHashKey, [$supplier['uuid'] => json_encode($supplier)]);
            }

            $redisMulti->exec();
        } catch(Exception $e) {
            $redisMulti->discard();

            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
