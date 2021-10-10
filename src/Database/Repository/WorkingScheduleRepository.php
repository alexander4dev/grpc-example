<?php

declare(strict_types=1);

namespace App\Database\Repository;

use App\Database\Entity\WorkingSchedule;
use Arus\Doctrine\Helper\ValidationInjection;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class WorkingScheduleRepository extends AbstractRepository
{
    use ValidationInjection {
        validate as protected _validate;
    }

    /**
     * @var ConstraintViolationListInterface|null
     */
    private $constraintViolationList;

    /**
     * @param WorkingSchedule $schedule
     * @return bool
     */
    public function isUnique(WorkingSchedule $schedule): bool
    {
        $queryParams = [
            'day_number' => $schedule->getDayNumber(),
        ];

        $place = $schedule->getWorkingPlace();
        $placeId = $place->getId();

        $qb = $this->createQueryBuilder('s');
        $qb->select([
                's.day_number',
            ])
            ->andWhere(
                $qb->expr()->orX(
                    's.day_number = :day_number'
                )
            )
            ->andWhere('s.working_place = :working_place_id')
            ->setParameters($queryParams)
            ->setParameter('working_place_id', $placeId)
        ;

        if ($schedule->getId()) {
            $qb->andWhere('s.id != :id')
                ->setParameter('id', $schedule->getId())
            ;
        }

        $queryResult = $qb->getQuery()->getResult();
        $isUnique = !$queryResult;

        if (!$isUnique && $this->constraintViolationList instanceof ConstraintViolationListInterface) {
            foreach ($queryResult as $existedEntity) {
                foreach ($queryParams as $searchedField => $searchedValue) {
                    if (null !== $searchedValue && $searchedValue === $existedEntity[$searchedField]) {
                        $message = sprintf('Entity already exists with same "%s" for working place [id=%s]: %s', $searchedField, $placeId, $searchedValue);
                        $this->constraintViolationList->add(new ConstraintViolation($message, null, [], null, $searchedField, $searchedValue));
                    }
                }
            }
        }

        return $isUnique;
    }

    /**
     * @param WorkingSchedule $schedule
     * @return ConstraintViolationListInterface
     */
    public function validate(WorkingSchedule $schedule): ConstraintViolationListInterface
    {
        $this->constraintViolationList = $this->_validate($schedule);

        $this->isUnique($schedule);

        return $this->constraintViolationList;
    }

    /**
     * @param int $workingPlaceId
     * @param array $scheduleData
     * @return int
     */
    public function insertUpdate(int $workingPlaceId, array $scheduleData): int
    {
        $affectedRows = 0;
        $batchSize = 1000;
        $connection = $this->getEntityManager()->getConnection();
        $tableName = $this->getClassMetadata()->getTableName();

        while ($insertChunk = array_splice($scheduleData, 0, $batchSize)) {
            $values = '(' . implode('),(', array_map(function($input) use ($connection, $workingPlaceId) {
                return $workingPlaceId . ',' . (int)$input['day_number'] . ',' . $connection->quote($input['time_from']) . ',' . $connection->quote($input['time_to']);
            }, $insertChunk)) . ')';

            $sql = "INSERT INTO $tableName (working_place_id, day_number, time_from, time_to)"
                    . " VALUES $values"
                    . " ON DUPLICATE KEY UPDATE time_from = VALUES(time_from), time_to = VALUES(time_to)"
            ;

            $execResult = $connection->exec($sql);
            $affectedRows += $execResult;
        }

        return $affectedRows;
    }
}
