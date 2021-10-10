<?php

declare(strict_types=1);

namespace App\Database\Repository;

use App\Database\Entity\DeliverySchedule;
use Arus\Doctrine\Helper\ValidationInjection;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class DeliveryScheduleRepository extends AbstractRepository
{
    use ValidationInjection {
        validate as protected _validate;
    }

    /**
     * @var ConstraintViolationListInterface|null
     */
    private $constraintViolationList;

    /**
     * @param DeliverySchedule $schedule
     * @return bool
     */
    public function isUnique(DeliverySchedule $schedule): bool
    {
        $timeFormat = DeliverySchedule::getTimeFormat();
        $queryParams = [
            'order_time' => $schedule->getOrderTime()->format($timeFormat),
        ];

        $offer = $schedule->getOffer();
        $offerId = $offer->getId();

        $qb = $this->createQueryBuilder('ds');
        $qb->select([
                'ds.order_time',
            ])
            ->andWhere(
                $qb->expr()->orX(
                    'ds.order_time = :order_time'
                )
            )
            ->andWhere('ds.offer = :offer_id')
            ->andWhere('ds.day_number = :day_number')
            ->setParameters($queryParams)
            ->setParameter('offer_id', $offerId)
            ->setParameter('day_number', $schedule->getDayNumber())
        ;

        if ($schedule->getId()) {
            $qb->andWhere('ds.id != :id')
                ->setParameter('id', $schedule->getId())
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
                        $message = sprintf('Entity already exists with same "%s" for offer[uuid=%s]: %s', $searchedField, $offer->getUuid(), $searchedValue);
                        $this->constraintViolationList->add(new ConstraintViolation($message, null, [], null, $searchedField, $searchedValue));
                    }
                }
            }
        }

        return $isUnique;
    }

    /**
     * @param DeliverySchedule $schedule
     * @return ConstraintViolationListInterface
     */
    public function validate(DeliverySchedule $schedule): ConstraintViolationListInterface
    {
        $this->constraintViolationList = $this->_validate($schedule);

        $this->isUnique($schedule);

        return $this->constraintViolationList;
    }

    /**
     * @param int $offerId
     * @param array $scheduleData
     * @return int
     */
    public function insertUpdate(int $offerId, array $scheduleData): int
    {
        $affectedRows = 0;
        $batchSize = 1000;
        $connection = $this->getEntityManager()->getConnection();
        $tableName = $this->getClassMetadata()->getTableName();

        while ($insertChunk = array_splice($scheduleData, 0, $batchSize)) {
            $values = '(' . implode('),(', array_map(function($input) use ($connection, $offerId) {
                return $offerId . ',' . (int)$input['day_number'] . ',' . $connection->quote($input['order_time']) . ',' . (int)$input['delivery_minutes'];
            }, $insertChunk)) . ')';

            $sql = "INSERT INTO $tableName (offer_id, day_number, order_time, delivery_minutes)"
                    . " VALUES $values"
                    . " ON DUPLICATE KEY UPDATE delivery_minutes = VALUES(delivery_minutes)"
            ;

            $execResult = $connection->exec($sql);
            $affectedRows += $execResult;
        }

        return $affectedRows;
    }
}
