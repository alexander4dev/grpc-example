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
 * @OA\Patch(
 *   path="/v1/sector/{uuid}/delivery/interval/{timeFrom}",
 *   summary="Изменение интервала доставки сектора",
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
 *   @OA\RequestBody(
 *     @OA\JsonContent(
 *       type="object",
 *       ref="#/components/schemas/SectorDeliveryInterval",
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
 *   id="sector.delivery.interval.update",
 *   path="/v1/sector/{sectorUuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}/delivery/interval/{timeFrom<\d{2}%3A\d{2}>}",
 *   methods={"PATCH"}
 * )
 */
class DeliveryIntervalUpdateController extends DeliveryIntervalEndpoint
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

        $requestBody = $request->getParsedBody();
        $inputFilter = $this->getInputFilter($requestBody);

        if (!$inputFilter->isValid()) {
            $requestVioldations = $this->createViolationList($inputFilter);

            return $this->violations($response, $requestVioldations);
        }

        $entityData = $inputFilter->getValues();

        if (!$entityData) {
            return $this->ok($response);
        }

        if (array_key_exists('time_from', $requestBody)) {
            $timeFrom = DateTime::createFromFormat($timeFormat, $entityData['time_from']);
            $deliveryInterval->setTimeFrom($timeFrom);
        }

        if (array_key_exists('time_to', $requestBody)) {
            $timeTo = DateTime::createFromFormat($timeFormat, $entityData['time_to']);
            $deliveryInterval->setTimeTo($timeTo);
        }

        $entityViolations = $deliveryIntervalRepository->validate($deliveryInterval);

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
        $spec['time_from']['required'] = false;
        $spec['time_to']['required'] = false;

        return $spec;
    }
}
