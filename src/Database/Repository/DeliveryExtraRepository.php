<?php

declare(strict_types=1);

namespace App\Database\Repository;

use App\Database\Entity\DeliveryExtra;
use Arus\Doctrine\Helper\ValidationInjection;
use Datetime;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class DeliveryExtraRepository extends AbstractRepository
{
    use ValidationInjection {
        validate as protected _validate;
    }

    /**
     * @var ConstraintViolationListInterface|null
     */
    private $constraintViolationList;

    /**
     * @param DeliveryExtra $deliveryExtra
     * @return bool
     */
    public function isUnique(DeliveryExtra $deliveryExtra): bool
    {
        $dateFormat = DeliveryExtra::getDateFormat();
        $queryParams = [
            'order_date' => $deliveryExtra->getOrderDate()->format($dateFormat),
        ];

        $offer = $deliveryExtra->getOffer();
        $offerId = $offer->getId();

        $qb = $this->createQueryBuilder('de');
        $qb->select([
                'de.order_date',
            ])
            ->andWhere(
                $qb->expr()->orX(
                    'de.order_date = :order_date'
                )
            )
            ->andWhere('de.offer = :offer_id')
            ->setParameters($queryParams)
            ->setParameter('offer_id', $offerId)
        ;

        if ($deliveryExtra->getId()) {
            $qb->andWhere('de.id != :id')
                ->setParameter('id', $deliveryExtra->getId())
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
                        $message = sprintf('Entity already exists with same "%s" for offer[uuid=%s]: %s', $searchedField, $offer->getUuid(), $searchedValue);
                        $this->constraintViolationList->add(new ConstraintViolation($message, null, [], null, $searchedField, $searchedValue));
                    }
                }
            }
        }

        return $isUnique;
    }

    /**
     * @param DeliveryExtra $deliveryExtra
     * @return ConstraintViolationListInterface
     */
    public function validate(DeliveryExtra $deliveryExtra): ConstraintViolationListInterface
    {
        $this->constraintViolationList = $this->_validate($deliveryExtra);

        $this->isUnique($deliveryExtra);

        return $this->constraintViolationList;
    }

    /**
     * @param int $offerId
     * @param array $deliveryExtraData
     * @return int
     */
    public function insertUpdate(int $offerId, array $deliveryExtraData): int
    {
        $affectedRows = 0;
        $batchSize = 1000;
        $connection = $this->getEntityManager()->getConnection();
        $tableName = $this->getClassMetadata()->getTableName();

        while ($insertChunk = array_splice($deliveryExtraData, 0, $batchSize)) {
            $values = '(' . implode('),(', array_map(function($input) use ($connection, $offerId) {
                return $offerId . ',' . $connection->quote($input['order_date']) . ',' . (int)$input['is_supply'] . ',' . $connection->quote($input['delivery_date']);
            }, $insertChunk)) . ')';

            $sql = "INSERT INTO $tableName (offer_id, order_date, is_supply, delivery_date)"
                    . " VALUES $values"
                    . " ON DUPLICATE KEY UPDATE is_supply = VALUES(is_supply), delivery_date = VALUES(delivery_date)"
            ;

            $execResult = $connection->exec($sql);
            $affectedRows += $execResult;
        }

        return $affectedRows;
    }
}
