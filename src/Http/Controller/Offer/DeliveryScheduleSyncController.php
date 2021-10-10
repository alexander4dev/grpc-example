<?php

declare(strict_types=1);

namespace App\Http\Controller\Offer;

use App\Database\Entity\Offer;
use App\Database\Entity\DeliverySchedule;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function sprintf;

/**
 * @OA\Post(
 *   path="/v1/offer/{uuid}/delivery/schedule/sync",
 *   summary="Синхронизация графика доставки предложения поставщика",
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
 *   @OA\RequestBody(
 *     @OA\JsonContent(
 *       type="array",
 *       @OA\Items(ref="#/components/schemas/DeliverySchedule"),
 *     ),
 *   ),
 *   @OA\Response(
 *     response="200",
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
 *   id="offer.delivery.schedule.sync",
 *   path="/v1/offer/{offerUuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}/delivery/schedule/sync",
 *   methods={"POST"}
 * )
 */
class DeliveryScheduleSyncController extends DeliveryScheduleEndpoint
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

        $requestData = $request->getParsedBody();
        $scheduleData = [];
        $scheduleByDay = [];

        foreach ($requestData as $sheduleItemData) {
            $inputFilter = $this->getInputFilter($sheduleItemData);

            if (!$inputFilter->isValid()) {
                $requestVioldations = $this->createViolationList($inputFilter);

                return $this->violations($response, $requestVioldations);
            }

            $scheduleData[] = $inputFilter->getValues();
            $scheduleByDay[$sheduleItemData['day_number']][] = $sheduleItemData['order_time'];
        }

        $scheduleIdsToDelete = [];

        foreach ($offer->getDeliverySchedule() as $deliverySchedule) {
            /* @var $deliverySchedule DeliverySchedule */
            if (!in_array($deliverySchedule->getOrderTime()->format(DeliverySchedule::getTimeFormat()), $scheduleByDay[$deliverySchedule->getDayNumber()] ?? [])) {
                $scheduleIdsToDelete[] = $deliverySchedule->getId();
            }
        }

        $scheduleRepository = $this->getDeliveryScheduleRepository();

        if ($scheduleIdsToDelete) {
            $scheduleRepository->deleteById($scheduleIdsToDelete);
        }

        $scheduleRepository->insertUpdate($offer->getId(), $scheduleData);

        return $this->ok($response);
    }
}
