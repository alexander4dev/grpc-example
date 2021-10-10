<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Graph\Algorithm\Dijkstra;
use App\Model\Graph\Edge;
use App\Model\Graph\Graph;
use App\Model\Graph\GraphInterface;
use App\Model\Graph\Node;
use App\Service\Exception\DeliveryServiceException;
use App\Autorus\Exception\InvalidArgumentException;
use DateInterval;
use DateTimeImmutable;
use PDO;
use SplStack;

class DeliveryService extends AbstractService
{
    public const SHIPPING_METHOD_PICKUP = 'pickup';
    public const SHIPPING_METHOD_DELIVERY_AUTORUS = 'delivery_autorus';
    public const SHIPPING_METHOD_DELIVERY_TRANSPORT_COMPANY = 'delivery_transport_company';

    public const SHIPPING_METHODS = [
        self::SHIPPING_METHOD_PICKUP,
        self::SHIPPING_METHOD_DELIVERY_AUTORUS,
        self::SHIPPING_METHOD_DELIVERY_TRANSPORT_COMPANY,
    ];

    /**
     * @param string $offerUuid
     * @param string $targetSupplierUuid
     * @param DateTimeImmutable $closestDateTime
     * @param string $shippingMethod
     * @param string|null $sectorUuid
     * @param DateTimeImmutable|null $sectorDeliveryDate
     * @return array
     */
    public function estimateDelivery(
        string $offerUuid,
        string $targetSupplierUuid,
        DateTimeImmutable $closestDateTime,
        string $shippingMethod,
        ?string $sectorUuid,
        ?DateTimeImmutable $sectorDeliveryDate
    ): array {
        $currentClosestDateTime = clone $closestDateTime;
        $closestDeliveryData = $this->getClosestDeliveryData($offerUuid, $targetSupplierUuid, $currentClosestDateTime);
        $nextClosestDateTime = $closestDeliveryData['orderDate'];

        do {
            $nextClosestDeliveryData = $this->getClosestDeliveryData($offerUuid, $targetSupplierUuid, $nextClosestDateTime);
            $nextClosestDateTime = $nextClosestDeliveryData['orderDate'];

            if ($closestDeliveryData['deliveryDate'] == $nextClosestDeliveryData['deliveryDate']) {
                $closestDeliveryData['orderDate'] = $nextClosestDeliveryData['orderDate'];
            }
        } while ($closestDeliveryData['deliveryDate'] == $nextClosestDeliveryData['deliveryDate']);

        $resultData = [
            'order_date' => $closestDeliveryData['orderDate']->format('Y-m-d H:i'),
            'delivery_date' => $closestDeliveryData['deliveryDate']->format('Y-m-d H:i'),
        ];

        if (self::SHIPPING_METHOD_DELIVERY_AUTORUS === $shippingMethod) {
            if (null === $sectorUuid) {
                throw new InvalidArgumentException('The sector uuid must not be null');
            }

            if (null === $sectorDeliveryDate) {
                throw new InvalidArgumentException('The sector delivery date must not be null');
            }

            $resultData['delivery_intervals'] = $this->getDeliveryIntervals($targetSupplierUuid, $sectorUuid, $closestDeliveryData['deliveryDate'], $sectorDeliveryDate);
        } else {
            $resultData['delivery_intervals'] = [];
        }

        return $resultData;
    }

    /**
     * @param string $supplierUuid
     * @param string $sectorUuid
     * @param DateTimeImmutable $orderDeliveryDate
     * @param DateTimeImmutable $sectorDeliveryDate
     * @return array
     * @throws DeliveryServiceException
     */
    private function getDeliveryIntervals(
        string $supplierUuid,
        string $sectorUuid,
        DateTimeImmutable $orderDeliveryDate,
        DateTimeImmutable $sectorDeliveryDate
    ): array {
        $resultData = [];
        $intervalsSelect = [
            'select' => [
                'delivery_accepting_minutes AS deliveryAcceptingMinutes',
                'deliveryIntervals' => [
                    'time_from as timeFrom',
                    'time_to as timeTo',
                ],
            ],
            'where' => [
                'uuid' => $sectorUuid,
                'supplier' => [
                    'uuid' => $supplierUuid,
                ],
            ],
        ];

        $sectorRepo = $this->getSectorRepository();
        $intervalsData = $sectorRepo->getList($intervalsSelect);

        if (!$intervalsData) {
            $exMessage = sprintf('Can\'t get delivery interval for sector "%s" supplier "%s"', $sectorUuid, $supplierUuid);
            throw new DeliveryServiceException($exMessage);
        }

        foreach ($intervalsData as $intervalData) {
            $deliveryAcceptingInterval = new DateInterval(sprintf('PT%dM', $intervalData['deliveryAcceptingMinutes']));
            $deliveryAcceptingDate = $orderDeliveryDate->add($deliveryAcceptingInterval);
            $intervalTimeFrom = $intervalData['timeFrom'];
            $intervalTimeFrom->setDate((int)$sectorDeliveryDate->format('Y'), (int)$sectorDeliveryDate->format('n'), (int)$sectorDeliveryDate->format('j'));

            if ($intervalTimeFrom > $deliveryAcceptingDate) {
                $resultData[] = [
                    'time_from' => $intervalData['timeFrom']->format('H:i'),
                    'time_to' => $intervalData['timeTo']->format('H:i'),
                ];
            }
        }

        return $resultData;
    }

    /**
     * @param string $offerUuid
     * @param string $targetSupplierUuid
     * @param DateTimeImmutable $closestDateTime
     * @return array
     * @throws DeliveryServiceException
     */
    private function getClosestDeliveryData(
        string $offerUuid,
        string $targetSupplierUuid,
        DateTimeImmutable $closestDateTime
    ): array {
        $sourceOfferData = $this->getSourceOfferData($offerUuid);
        $sourceSupplierUuid = $sourceOfferData['sourceSupplierUuid'];
        $path = $this->getDeliveryPath($sourceSupplierUuid, $targetSupplierUuid);

        if ($path->isEmpty()) {
            $exMessage = sprintf('Can\'t get delivery path from supplier "%s" to supplier "%s"', $sourceSupplierUuid, $targetSupplierUuid);
            throw new DeliveryServiceException($exMessage);
        }

        $sourcePathSupplierUuid = $sourceSupplierUuid;
        $deliveryOffersData = [];

        while ($path->count()) {
            $targetPathSupplierUuid = $path->pop();

            if ($targetPathSupplierUuid === $sourcePathSupplierUuid) {
                continue;
            }

            $currentOfferData = $this->getDeliveryOfferData($sourcePathSupplierUuid, $targetPathSupplierUuid);
            $deliveryOffersData[] = $currentOfferData;
            $sourcePathSupplierUuid = $targetPathSupplierUuid;
        }

        $orderInitializingInterval = new DateInterval(sprintf('PT%dM', $sourceOfferData['orderInitializingMinutes']));
        $currentClosestDateTime = $closestDateTime->add($orderInitializingInterval);
        $deliveryData = [];

        foreach ($deliveryOffersData as $deliveryOfferData) {
            $currentDeliveryData = $this->getOfferClosestDeliveryData($deliveryOfferData['offerUuid'], $currentClosestDateTime);
            $currentDeliveryDate = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $currentDeliveryData['deliveryDate']);
            $currentClosestDateTime = $currentDeliveryDate;

            if ($deliveryOfferData['offerUuid'] === $offerUuid) {
                $orderDate = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $currentDeliveryData['orderDate']);
                $deliveryData['orderDate'] = $orderDate->sub($orderInitializingInterval);
            }

            if ($deliveryOfferData['targetSupplierUuid'] === $targetSupplierUuid) {
                $deliveryData['deliveryDate'] = $currentDeliveryDate;
            }
        }

        return $deliveryData;
    }

    /**
     * @param string $offerUuid
     * @param DateTimeImmutable $closestDateTime
     * @return array
     * @throws DeliveryServiceException
     */
    private function getOfferClosestDeliveryData(string $offerUuid, DateTimeImmutable $closestDateTime): array
    {
        $maxDays = $this->getContainer()->get('delivery_estimation_max_days_interval');
        $pdoConnection = $this->getEntityManager()->getConnection()->getWrappedConnection();
        $minimalDate = $closestDateTime->format('Y-m-d H:i:s');

        $callQuery = $pdoConnection->prepare('CALL get_delivery_closest_date(:offerUuid, :minimalDate, :maxDays, @orderDate, @deliveryDate, @errorMessage)');
        $callQuery->bindParam('offerUuid', $offerUuid, PDO::PARAM_STR);
        $callQuery->bindParam('minimalDate', $minimalDate, PDO::PARAM_STR);
        $callQuery->bindParam('maxDays', $maxDays, PDO::PARAM_INT);
        $callQuery->execute();

        $resultQuery = $pdoConnection->query('SELECT @orderDate, @deliveryDate, @errorMessage');
        $queryResult = $resultQuery->fetch(PDO::FETCH_ASSOC);

        $orderDate = $queryResult['@orderDate'] ?? null;
        $deliveryDate = $queryResult['@deliveryDate'] ?? null;
        $errorMessage = $queryResult['@errorMessage'] ?? null;

        if (null !== $errorMessage) {
            throw new DeliveryServiceException($errorMessage);
        } elseif (null === $orderDate || null === $deliveryDate) {
            $exMessage = sprintf('Can\'t get delivery closest date for offer "%s" date "%s"', $offerUuid, $minimalDate);
            throw new DeliveryServiceException($exMessage);
        }

        $result = [
            'orderDate' => $orderDate,
            'deliveryDate' => $deliveryDate,
        ];

        return $result;
    }

    /**
     * @param string $offerUuid
     * @return array
     * @throws DeliveryServiceException
     */
    private function getSourceOfferData(string $offerUuid): array
    {
        $offerSelect = [
            'select' => [
                'order_initializing_minutes AS orderInitializingMinutes',
                'supplier_from' => [
                    'uuid AS sourceSupplierUuid',
                ],
            ],
            'where' => [
                'uuid' => $offerUuid,
            ],
        ];

        $offerRepo = $this->getOfferRepository();
        $selectResult = $offerRepo->getList($offerSelect);

        if (!$selectResult) {
            $exMessage = sprintf('Can\'t get offer by uuid "%s"', $offerUuid);
            throw new DeliveryServiceException($exMessage);
        }

        return $selectResult[0];
    }

    /**
     * @param string $supplierUuidFrom
     * @param string $supplierUuidTo
     * @return array
     * @throws DeliveryServiceException
     */
    private function getDeliveryOfferData(string $supplierUuidFrom, string $supplierUuidTo): array
    {
        $selectCriteria = [
            'select' => [
                'uuid AS offerUuid',
                'supplier_to' => [
                    'uuid AS targetSupplierUuid',
                ],
            ],
            'where' => [
                'supplier_from' => [
                    'uuid' => $supplierUuidFrom,
                ],
                'supplier_to' => [
                    'uuid' => $supplierUuidTo,
                ],
            ],
            'orderBy' => [
                'id' => 'DESC',
            ],
            'limit' => 1,
        ];

        $offerRepo = $this->getOfferRepository();
        $resultData = $offerRepo->getList($selectCriteria);

        if (!$resultData) {
            $exMessage = sprintf('Can\'t get offer from "%s" to "%s"', $supplierUuidFrom, $supplierUuidTo);
            throw new DeliveryServiceException($exMessage);
        }

        return $resultData[0];
    }

    /**
     * @param string $source
     * @param string $target
     * @return SplStack
     */
    private function getDeliveryPath(string $source, string $target): SplStack
    {
        $directions = $this->getDeliveryDirections();
        $graph = $this->buildGraph($directions);
        $dijkstra = new Dijkstra($graph);

        return $dijkstra->getPath($source, $target);
    }

    /**
     * @param array $edgesData
     * @return GraphInterface
     */
    private function buildGraph(array $edgesData): GraphInterface
    {
        $graph = new Graph();

        foreach ($edgesData  as $edgeData) {
            $sourceNodeId = $edgeData['source'];
            $targetNodeId = $edgeData['target'];

            foreach ([$sourceNodeId, $targetNodeId] as $nodeId) {
                if (!$graph->hasNode($nodeId)) {
                    $graph->addNode(new Node($nodeId));
                }
            }

            $graph->addEdge(new Edge($sourceNodeId, $targetNodeId, 1));
        }

        return $graph;
    }

    /**
     * @return array
     */
    private function getDeliveryDirections(): array
    {
        $selectCriteria = [
            'distinct' => true,
            'select' => [
                'supplier_from' => [
                    'uuid AS source',
                ],
                'supplier_to' => [
                    'uuid AS target',
                ],
            ],
        ];

        $offerRepo = $this->getOfferRepository();
        $deliveryDirections = $offerRepo->getList($selectCriteria);

        return $deliveryDirections;
    }
}
