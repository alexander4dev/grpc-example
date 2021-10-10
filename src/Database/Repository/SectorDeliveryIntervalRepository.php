<?php

declare(strict_types=1);

namespace App\Database\Repository;

use App\Database\Entity\SectorDeliveryInterval;
use Arus\Doctrine\Helper\ValidationInjection;
use Datetime;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class SectorDeliveryIntervalRepository extends AbstractRepository
{
    use ValidationInjection {
        validate as protected _validate;
    }

    /**
     * @var ConstraintViolationListInterface|null
     */
    private $constraintViolationList;

    /**
     * @param SectorDeliveryInterval $deliveryInterval
     * @return bool
     */
    public function isUnique(SectorDeliveryInterval $deliveryInterval): bool
    {
        $timeFormat = SectorDeliveryInterval::getTimeFormat();
        $queryParams = [
            'time_from' => $deliveryInterval->getTimeFrom()->format($timeFormat),
        ];

        $sector = $deliveryInterval->getSector();
        $sectorId = $sector->getId();

        $qb = $this->createQueryBuilder('sdi');
        $qb->select([
                'sdi.time_from',
            ])
            ->andWhere(
                $qb->expr()->orX(
                    'sdi.time_from = :time_from'
                )
            )
            ->andWhere('sdi.sector = :sector_id')
            ->setParameters($queryParams)
            ->setParameter('sector_id', $sectorId)
        ;

        if ($deliveryInterval->getId()) {
            $qb->andWhere('sdi.id != :id')
                ->setParameter('id', $deliveryInterval->getId())
            ;
        }

        $queryResult = $qb->getQuery()->getResult();
        $isUnique = !$queryResult;

        if (!$isUnique && $this->constraintViolationList instanceof ConstraintViolationListInterface) {
            foreach ($queryResult as $existedEntity) {
                foreach ($queryParams as $searchedField => $searchedValue) {
                    $existedField = $existedEntity[$searchedField];

                    if ($existedField instanceof Datetime) {
                        $existedField = $existedField->format($timeFormat);
                    }

                    if (null !== $searchedValue && $searchedValue === $existedField) {
                        $message = sprintf('Entity already exists with same "%s" for sector[uuid=%s]: %s', $searchedField, $sector->getUuid(), $searchedValue);
                        $this->constraintViolationList->add(new ConstraintViolation($message, null, [], null, $searchedField, $searchedValue));
                    }
                }
            }
        }

        return $isUnique;
    }

    /**
     * @param SectorDeliveryInterval $deliveryInterval
     * @return ConstraintViolationListInterface
     */
    public function validate(SectorDeliveryInterval $deliveryInterval): ConstraintViolationListInterface
    {
        $this->constraintViolationList = $this->_validate($deliveryInterval);

        $this->isUnique($deliveryInterval);

        return $this->constraintViolationList;
    }
}
