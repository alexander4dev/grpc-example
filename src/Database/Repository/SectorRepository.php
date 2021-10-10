<?php

declare(strict_types=1);

namespace App\Database\Repository;

use App\Database\Entity\Sector;
use Arus\Doctrine\Helper\ValidationInjection;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class SectorRepository extends AbstractRepository
{
    use ValidationInjection {
        validate as protected _validate;
    }

    /**
     * @var ConstraintViolationListInterface|null
     */
    private $constraintViolationList;

    /**
     * @param Sector $sector
     * @return bool
     */
    public function isUnique(Sector $sector): bool
    {
        $queryParams = [
            'uuid' => $sector->getUuid(),
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

        if ($sector->getId()) {
            $qb->andWhere('s.id != :id')
                ->setParameter('id', $sector->getId())
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
     * @param Sector $sector
     * @return ConstraintViolationListInterface
     */
    public function validate(Sector $sector): ConstraintViolationListInterface
    {
        $this->constraintViolationList = $this->_validate($sector);

        $this->isUnique($sector);

        return $this->constraintViolationList;
    }
}
