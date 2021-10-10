<?php

declare(strict_types=1);

namespace App\Http\Controller\Sector;

use App\Database\Entity\Sector;
use App\Database\Entity\SectorDeliveryInterval;
use DateTime;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @OA\Post(
 *   path="/v1/sector/{uuid}/delivery/interval",
 *   summary="Создание интервала доставки сектора",
 *   tags={"Интервалы доставок секторов"},
 *   @OA\Parameter(
 *     name="uuid",
 *     description="UUID сектора",
 *     in="path",
 *     required=true,
 *     @OA\Schema(
 *       type="string",
 *       format="uuid",
 *     ),
 *   ),
 *   @OA\RequestBody(
 *     @OA\JsonContent(
 *       type="object",
 *       ref="#/components/schemas/SectorDeliveryInterval",
 *     ),
 *   ),
 *   @OA\Response(
 *     response="201",
 *     description="Успешное выполнение",
 *     @OA\JsonContent(
 *       type="object",
 *       ref="#/components/schemas/ResponseOk",
 *     ),
 *   ),
 *   @OA\Response(
 *     response="404",
 *     description="Запись не найдена",
 *     @OA\JsonContent(
 *       type="object",
 *       ref="#/components/schemas/ResponseError",
 *     ),
 *   ),
 *   @OA\Response(
 *     response="400",
 *     description="Ошибка запроса",
 *     @OA\JsonContent(
 *       type="object",
 *       ref="#/components/schemas/ResponseViolations",
 *     ),
 *   ),
 * )
 *
 * @Route(
 *   id="sector.delivery.interval.create",
 *   path="/v1/sector/{sectorUuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}/delivery/interval",
 *   methods={"POST"}
 * )
 */
class DeliveryIntervalCreateController extends DeliveryIntervalEndpoint
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $sectorUuid = $request->getAttribute('sectorUuid');
        $sectorRepository = $this->getSectorRepository();
        /* @var $sector Sector */
        $sector = $sectorRepository->findOneBy([
            'uuid' => $sectorUuid,
        ]);

        if (null === $sector) {
            $message = sprintf('An entity "%s" was not found.', $sectorUuid);
            return $this->error($response, $message, 400);
        }

        $inputFilter = $this->getInputFilter($request->getParsedBody());

        if (!$inputFilter->isValid()) {
            $requestVioldations = $this->createViolationList($inputFilter);

            return $this->violations($response, $requestVioldations);
        }

        $deliveryInterval = new SectorDeliveryInterval();
        $deliveryInterval->setSector($sector);

        $entityData = $inputFilter->getValues();
        $timeFormat = SectorDeliveryInterval::getTimeFormat();
        $timeFrom = DateTime::createFromFormat($timeFormat, $entityData['time_from']);
        $timeTo = DateTime::createFromFormat($timeFormat, $entityData['time_to']);

        $deliveryInterval->setSector($sector);
        $deliveryInterval->setTimeFrom($timeFrom);
        $deliveryInterval->setTimeTo($timeTo);

        $deliveryIntervalRepository = $this->getSectorDeliveryIntervalRepository();
        $entityViolations = $deliveryIntervalRepository->validate($deliveryInterval);

        if ($entityViolations->count()) {
            return $this->violations($response, $entityViolations);
        }

        $entityManager = $this->getEntityManager();
        $entityManager->persist($deliveryInterval);
        $entityManager->flush();

        return $this->ok($response, [], 201);
    }
}
