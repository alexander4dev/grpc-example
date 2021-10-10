<?php

declare(strict_types=1);

namespace App\Http\Controller\Supplier;

use App\Database\Entity\Supplier;
use App\Database\Entity\WorkingSchedule;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use DateTime;

use function sprintf;
use function array_key_exists;

/**
 * @OA\Patch(
 *   path="/v1/supplier/{uuid}/working/schedule/{dayNumber}",
 *   summary="Изменение графика работы поставщика",
 *   tags={"График работы поставщиков"},
 *   @OA\Parameter(
 *     name="uuid",
 *     description="UUID поставщика",
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
 *   @OA\RequestBody(
 *     @OA\JsonContent(
 *       type="object",
 *       ref="#/components/schemas/WorkingSchedule",
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
 *   id="supplier.working.schedule.update",
 *   path="/v1/supplier/{supplierUuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}/working/schedule/{dayNumber<\d+>}",
 *   methods={"PATCH"}
 * )
 */
class WorkingScheduleUpdateController extends WorkingScheduleEndpoint
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $supplierUuid = $request->getAttribute('supplierUuid');
        $supplierRepository = $this->getSupplierRepository();
        /* @var $supplier Supplier */
        $supplier = $supplierRepository->findOneBy([
            'uuid' => $supplierUuid,
        ]);

        if (null === $supplier) {
            $message = sprintf('An entity "%s" was not found.', $supplierUuid);
            return $this->error($response, $message, 404);
        }

        $dayNumber = $request->getAttribute('dayNumber');
        $scheduleRepository = $this->getWorkingScheduleRepository();
        /* @var $schedule WorkingSchedule */
        $schedule = $scheduleRepository->findOneBy([
            'working_place' => $supplier->getId(),
            'day_number' => $dayNumber,
        ]);

        if (null === $schedule) {
            $message = sprintf('An entity #%d was not found.', $dayNumber);
            return $this->error($response, $message, 404);
        }

        $requestData = $request->getParsedBody();
        $inputCheckData = $requestData;
        $timeFormat = WorkingSchedule::getTimeFormat();

        if (array_key_exists('time_from', $inputCheckData) || array_key_exists('time_to', $inputCheckData)) {
            if (!array_key_exists('time_from', $inputCheckData)) {
                $inputCheckData['time_from'] = $schedule->getTimeFrom()->format($timeFormat);
            }

            if (!array_key_exists('time_to', $inputCheckData)) {
                $inputCheckData['time_to'] = $schedule->getTimeTo()->format($timeFormat);
            }
        }

        $inputFilter = $this->getInputFilter($inputCheckData);

        if (!$inputFilter->isValid()) {
            $requestVioldations = $this->createViolationList($inputFilter);

            return $this->violations($response, $requestVioldations);
        }

        $entityData = $inputFilter->getValues();

        if (array_key_exists('day_number', $requestData)) {
            $schedule->setDayNumber($entityData['day_number']);
        }

        if (array_key_exists('time_from', $requestData)) {
            $timeFrom = DateTime::createFromFormat($timeFormat, $entityData['time_from']);
            $schedule->setTimeFrom($timeFrom);
        }

        if (array_key_exists('time_to', $requestData)) {
            $timeTo = DateTime::createFromFormat($timeFormat, $entityData['time_to']);
            $schedule->setTimeTo($timeTo);
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
        $spec['time_from']['required'] = false;
        $spec['time_to']['required'] = false;

        return $spec;
    }
}
