<?php

declare(strict_types=1);

namespace App\Http\Controller\Sector;

use App\Database\Entity\SectorDeliveryInterval;
use App\Database\Entity\Sector;
use DateTime;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @OA\Get(
 *   path="/v1/sector/{uuid}/delivery/interval/{timeFrom}",
 *   summary="Чтение интервала доставки сектора",
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
 *   @OA\Parameter(
 *     name="timeFrom",
 *     description="Время начала интервала в формате: H:i",
 *     in="path",
 *     required=true,
 *     @OA\Schema(
 *       type="string",
 *       format="time-hour",
 *     ),
 *   ),
 *   @OA\Response(
 *     response="200",
 *     description="Успешное выполнение",
 *     @OA\JsonContent(
 *       type="object",
 *       @OA\Property(
 *         property="status",
 *         ref="#/components/schemas/ResponseStatusOk"
 *       ),
 *       @OA\Property(
 *         property="data",
 *         ref="#/components/schemas/SectorDeliveryInterval",
 *       ),
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
 * )
 *
 * @Route(
 *   id="sector.delivery.interval.read",
 *   path="/v1/sector/{sectorUuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}/delivery/interval/{timeFrom<\d{2}%3A\d{2}>}",
 *   methods={"GET"}
 * )
 */
class DeliveryIntervalReadController extends DeliveryIntervalEndpoint
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
            return $this->error($response, $message, 404);
        }

        $timeFrom = urldecode($request->getAttribute('timeFrom'));
        $timeFormat = SectorDeliveryInterval::getTimeFormat();
        $dateTime = DateTime::createFromFormat($timeFormat, $timeFrom);
        $deliveryIntervalRepository = $this->getSectorDeliveryIntervalRepository();
        /* @var $deliveryInterval SectorDeliveryInterval */
        $deliveryInterval = $deliveryIntervalRepository->findOneBy([
            'sector' => $sector->getId(),
            'time_from' => $dateTime,
        ]);

        if (null === $deliveryInterval) {
            $message = sprintf('An entity "%s" was not found.', $timeFrom);
            return $this->error($response, $message, 404);
        }

        $data = [
            'time_from' => $deliveryInterval->getTimeFrom()->format($timeFormat),
            'time_to' => $deliveryInterval->getTimeTo()->format($timeFormat),
        ];

        return $this->ok($response, $data);
    }
}
