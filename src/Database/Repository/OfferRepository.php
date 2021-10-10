<?php

declare(strict_types=1);

namespace App\Database\Repository;

use App\Autorus\OffersCacheRedis;
use App\Database\Entity\Offer;
use Arus\Doctrine\Helper\ValidationInjection;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class OfferRepository extends AbstractRepository
{
    use ValidationInjection {
        validate as protected _validate;
    }

    /**
     * @var string
     */
    protected $rootAlias = 'o';

    /**
     * @Inject
     *
     * @var OffersCacheRedis
     */
    private $offersCacheRedis;

    /**
     * @var ConstraintViolationListInterface|null
     */
    private $constraintViolationList;

    /**
     * @param Offer $offer
     * @return bool
     */
    public function isUnique(Offer $offer): bool
    {
        $queryParams = [
            'uuid' => $offer->getUuid(),
        ];

        $qb = $this->createQueryBuilder('o');
        $qb->select([
                'o.id',
                'o.uuid',
            ])
            ->andWhere(
                $qb->expr()->orX(
                    'o.uuid = :uuid'
                )
            )
            ->setParameters($queryParams)
        ;

        if ($offer->getId()) {
            $qb->andWhere('o.id != :id')
                ->setParameter('id', $offer->getId())
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
     * @param Offer $offer
     * @return ConstraintViolationListInterface
     */
    public function validate(Offer $offer): ConstraintViolationListInterface
    {
        $this->constraintViolationList = $this->_validate($offer);

        $this->isUnique($offer);

        return $this->constraintViolationList;
    }

    /**
     * @return array
     */
    public function getListFromCache(): array
    {
        $redis = $this->offersCacheRedis->getConnection();
        $offersCache = $redis->hGetAll(Offer::getHashKey());
        $offersResult = [];

        foreach ($offersCache as $offerJson) {
            $offersResult[] = json_decode($offerJson, true);
        }

        return $offersResult;
    }

    /**
     * @return void
     * @throws RuntimeException
     */
    public function cacheRefresh(): void
    {
        $listParams = [
            'select' => [
                'uuid AS id',
                'supplier_from' => [
                    'title',
                ],
            ],
            'where' => [
                'is_enabled' => true,
            ],
            'orderBy' => [
                'id' => 'DESC',
            ],
        ];
        $offers = $this->getList($listParams);
        $redis = $this->offersCacheRedis->getConnection();
        $redisMulti = $redis->multi();

        try {
            $offersHashKey = Offer::getHashKey();
            $redisMulti->del($offersHashKey);

            foreach ($offers as $offer) {
                $redisMulti->hMSet($offersHashKey, [$offer['id'] => json_encode($offer)]);
            }

            $redisMulti->exec();
        } catch(Exception $e) {
            $redisMulti->discard();

            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
