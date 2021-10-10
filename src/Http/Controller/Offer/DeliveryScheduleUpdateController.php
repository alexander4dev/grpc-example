<?php

declare(strict_types=1);

namespace App\Http\Controller\Offer;

use App\Database\Entity\Offer;
use App\Database\Entity\DeliverySchedule;
use DateTime;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function sprintf;
use function array_key_exists;

/**
 * @OA\Patch(
 *   path="/v1/offer/{uuid}/delivery/schedule/{dayNumber}/time/{orderTime}",
 *   summary="Изменение графика доставки предложения поставщика",
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
 *   @OA\RequestBody(
 *     @OA\JsonContent(
 *       type="object",
 *       ref="#/components/schemas/DeliverySchedule",
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
 *   id="offer.delivery.schedule.update",
 *   path="/v1/offer/{offerUuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}/delivery/schedule/{dayNumber<\d+>}/time/{orderTime<\d{2}%3A\d{2}>}",
 *   methods={"PATCH"}
 * )
 */
class DeliveryScheduleUpdateController extends DeliveryScheduleEndpoint
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
        $requestOrderTime = urldecode($request->getAttribute('orderTime'));
        $timeFormat = DeliverySchedule::getTimeFormat();
        $requestOrderDateTime = DateTime::createFromFormat($timeFormat, $requestOrderTime);
        $scheduleRepository = $this->getDeliveryScheduleRepository();
        /* @var $schedule DeliverySchedule */
        $schedule = $scheduleRepository->findOneBy([
            'offer' => $offer->getId(),
            'day_number' => $dayNumber,
            'order_time' => $requestOrderDateTime,
        ]);

        if (null === $schedule) {
            $message = sprintf('An entity #%d was not found for order time %s.', $dayNumber, $requestOrderTime);
            return $this->error($response, $message, 404);
        }

        $requestData = $request->getParsedBody();
        $inputFilter = $this->getInputFilter($requestData);

        if (!$inputFilter->isValid()) {
            $requestVioldations = $this->createViolationList($inputFilter);

            return $this->violations($response, $requestVioldations);
        }

        $entityData = $inputFilter->getValues();

        if (array_key_exists('day_number', $requestData)) {
            $schedule->setDayNumber($entityData['day_number']);
        }

        if (array_key_exists('order_time', $requestData)) {
            $orderTime = DateTime::createFromFormat($timeFormat, $entityData['order_time']);
            $schedule->setOrderTime($orderTime);
        }

        if (array_key_exists('delivery_minutes', $requestData)) {
            $schedule->setDeliveryMnutes($entityData['delivery_minutes']);
        }

        $entityViolations = $scheduleRepository->validate($schedule);

        if ($entityViolations->count()) {
            return $this->violations($response, $entityViolations);
        }

        $entityManager = $this->getEntityManager();
        $entityManager->flush();

        return $this->ok($response);
    }

    /**
     * @return array
     */
    protected function getInputFilterSpecification(): array
    {
        $spec = parent::getInputFilterSpecification();
        $spec['day_number']['required'] = false;
        $spec['order_time']['required'] = false;
        $spec['delivery_minutes']['required'] = false;

        return $spec;
    }
}
