<?php

declare(strict_types=1);

namespace App\Database\Repository;

use App\Database\Entity\WorkingExtraDay;
use Arus\Doctrine\Helper\ValidationInjection;
use Datetime;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class WorkingExtraDayRepository extends AbstractRepository
{
    use ValidationInjection {
        validate as protected _validate;
    }

    /**
     * @var ConstraintViolationListInterface|null
     */
    private $constraintViolationList;

    /**
     * @param WorkingExtraDay $extraDay
     * @return bool
     */
    public function isUnique(WorkingExtraDay $extraDay): bool
    {
        $dateFormat = WorkingExtraDay::getDateFormat();
        $queryParams = [
            'date' => $extraDay->getDate()->format($dateFormat),
        ];

        $place = $extraDay->getWorkingPlace();
        $placeId = $place->getId();

        $qb = $this->createQueryBuilder('e');
        $qb->select([
                'e.date',
            ])
            ->andWhere(
                $qb->expr()->orX(
                    'e.date = :date'
                )
            )
            ->andWhere('e.working_place = :working_place_id')
            ->setParameters($queryParams)
            ->setParameter('working_place_id', $placeId)
        ;

        if ($extraDay->getId()) {
            $qb->andWhere('e.id != :id')
                ->setParameter('id', $extraDay->getId())
            ;
        }

        $queryResult = $qb->getQuery()->getResult();
        $isUnique = !$queryResult;

        if (!$isUnique && $this->constraintViolationList instanceof ConstraintViolationListInterface) {
            foreach ($queryResult as $existedEntity) {
                foreach ($queryParams as $searchedField => $searchedValue) {
                    $existedField = $existedEntity[$searchedField];

                    if ($existedField instanceof Datetime) {
                        $existedField = $existedField->format($dateFormat);
                    }

                    if (null !== $searchedValue && $searchedValue === $existedField) {
                        $message = sprintf('Entity already exists with same "%s" for working place[id=%s]: %s', $searchedField, $placeId, $searchedValue);
                        $this->constraintViolationList->add(new ConstraintViolation($message, null, [], null, $searchedField, $searchedValue));
                    }
                }
            }
        }

        return $isUnique;
    }

    /**
     * @param WorkingExtraDay $extraDay
     * @return ConstraintViolationListInterface
     */
    public function validate(WorkingExtraDay $extraDay): ConstraintViolationListInterface
    {
        $this->constraintViolationList = $this->_validate($extraDay);

        $this->isUnique($extraDay);

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
                return $workingPlaceId . ','
                        . $connection->quote($input['date']) . ','
                        . (int)$input['is_working'] . ','
                        . (null !== $input['time_from'] ? $connection->quote($input['time_from']) : 'null') . ','
                        . (null !== $input['time_to'] ? $connection->quote($input['time_to']) : 'null');
            }, $insertChunk)) . ')';

            $sql = "INSERT INTO $tableName (working_place_id, date, is_working, time_from, time_to)"
                    . " VALUES $values"
                    . " ON DUPLICATE KEY UPDATE is_working = VALUES(is_working), time_from = VALUES(time_from), time_to = VALUES(time_to)"
            ;

            $execResult = $connection->exec($sql);
            $affectedRows += $execResult;
        }

        return $affectedRows;
    }
}
