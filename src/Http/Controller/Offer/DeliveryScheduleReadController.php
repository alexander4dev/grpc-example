<?php

declare(strict_types=1);

namespace App\Http\Controller\Offer;

use App\Database\Entity\Offer;
use App\Database\Entity\DeliverySchedule;
use DateTime;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @OA\Get(
 *   path="/v1/offer/{uuid}/delivery/schedule/{dayNumber}/time/{orderTime}",
 *   summary="Чтение графика доставки предложения поставщика",
 *   tags={"График доставок предложений"},
 *   @OA\Parameter(
 *     name="uuid",
 *     description="UUID предложения",
 *     in="path",
 *     required=true,
 *     @OA\Schema(
 *       type="string",
 *       format="uuid",
 *     ),
 *   ),
 *   @OA\Parameter(
 *     name="dayNumber",
 *     description="Номер дня недели",
 *     in="path",
 *     required=true,
 *     @OA\Schema(
 *       type="integer",
 *       minimum=1,
 *       maximum=7,
 *     ),
 *   ),
 *   @OA\Parameter(
 *     name="orderTime",
 *     description="Время заказа в формате: H:i",
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
 *         ref="#/components/schemas/DeliverySchedule",
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
 *   id="offer.delivery.schedule.read",
 *   path="/v1/offer/{offerUuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}/delivery/schedule/{dayNumber<\d+>}/time/{orderTime<\d{2}%3A\d{2}>}",
 *   methods={"GET"}
 * )
 */
class DeliveryScheduleReadController extends DeliveryScheduleEndpoint
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $offerUuid = $request->getAttribute('offerUuid');
        $offerRepository = $this->getOfferRepository();
        /* @var $offer Offer */
        $offer = $offerRepository->findOneBy([
            'uuid' => $offerUuid,
        ]);

        if (null === $offer) {
            $message = sprintf('An entity "%s" was not found.', $offerUuid);
            return $this->error($response, $message, 404);
        }

        $dayNumber = $request->getAttribute('dayNumber');
        $orderTime = urldecode($request->getAttribute('orderTime'));
        $timeFormat = DeliverySchedule::getTimeFormat();
        $orderDateTime = DateTime::createFromFormat($timeFormat, $orderTime);
        $scheduleRepository = $this->getDeliveryScheduleRepository();
        /* @var $schedule DeliverySchedule */
        $schedule = $scheduleRepository->findOneBy([
            'offer' => $offer->getId(),
            'day_number' => $dayNumber,
            'order_time' => $orderDateTime,
        ]);

        if (null === $schedule) {
            $message = sprintf('An entity #%d was not found for order time %s.', $dayNumber, $orderTime);
            return $this->error($response, $message, 404);
        }

        $data = [
            'day_number' => $schedule->getDayNumber(),
            'order_time' => $schedule->getOrderTime()->format($timeFormat),
            'delivery_minutes' => $schedule->getDeliveryMinutes(),
        ];

        return $this->ok($response, $data);
    }
}
