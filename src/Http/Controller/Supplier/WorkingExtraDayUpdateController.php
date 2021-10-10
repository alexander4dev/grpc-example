<?php

declare(strict_types=1);

namespace App\Http\Controller\Supplier;

use App\Database\Entity\Supplier;
use App\Database\Entity\WorkingExtraDay;
use App\Database\Entity\WorkingSchedule;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use DateTime;

use function sprintf;
use function array_key_exists;

/**
 * @OA\Patch(
 *   path="/v1/supplier/{uuid}/working/extraday/{date}",
 *   summary="Изменение дополнительного графика работы поставщика",
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
 *     name="date",
 *     description="Дата в формате: Y-m-d",
 *     in="path",
 *     required=true,
 *     @OA\Schema(
 *       type="string",
 *       format="date",
 *     ),
 *   ),
 *   @OA\RequestBody(
 *     @OA\JsonContent(
 *       type="object",
 *       ref="#/components/schemas/WorkingExtraDay",
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
 *   id="supplier.working.extraday.update",
 *   path="/v1/supplier/{supplierUuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}/working/extraday/{date<\d{4}-\d{2}-\d{2}>}",
 *   methods={"PATCH"}
 * )
 */
class WorkingExtraDayUpdateController extends WorkingExtraDayEndpoint
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

        $requestDate = $request->getAttribute('date');
        $dateFormat = WorkingExtraDay::getDateFormat();
        $requestDateTime = DateTime::createFromFormat($dateFormat, $requestDate);
        $extraDayRepository = $this->getWorkingExtraDayRepository();
        /* @var $extraDay WorkingExtraDay */
        $extraDay = $extraDayRepository->findOneBy([
            'working_place' => $supplier->getId(),
            'date' => $requestDateTime,
        ]);

        if (null === $extraDay) {
            $message = sprintf('An entity "%s" was not found.', $requestDate);
            return $this->error($response, $message, 404);
        }

        $requestData = $request->getParsedBody();
        $inputCheckData = $requestData;
        $timeFormat = '!' . WorkingExtraDay::getTimeFormat();

        if (array_key_exists('time_from', $inputCheckData) || array_key_exists('time_to', $inputCheckData)) {
            if (!array_key_exists('time_from', $inputCheckData)) {
                $inputCheckData['time_from'] = $extraDay->getTimeFrom()->format($timeFormat);
            }

            if (!array_key_exists('time_to', $inputCheckData)) {
                $inputCheckData['time_to'] = $extraDay->getTimeTo()->format($timeFormat);
            }
        }

        $inputFilter = $this->getInputFilter($inputCheckData);

        if (!$inputFilter->isValid()) {
            $requestVioldations = $this->createViolationList($inputFilter);

            return $this->violations($response, $requestVioldations);
        }

        $entityData = $inputFilter->getValues();

        if (array_key_exists('date', $requestData)) {
            $date = DateTime::createFromFormat($dateFormat, $entityData['date']);
            $extraDay->setDate($date);
        }

        if (array_key_exists('is_working', $entityData)) {
            $extraDay->setIsWorking($entityData['is_working']);
        }

        $timeFrom = null !== $entityData['time_from'] ? DateTime::createFromFormat($timeFormat, $entityData['time_from']) : null;
        $timeTo = null !== $entityData['time_to'] ? DateTime::createFromFormat($timeFormat, $entityData['time_to']) : null;

        if ($extraDay->getIsWorking() && (null === $timeFrom || null === $timeTo)) {
            $scheduleRepository = $this->getWorkingScheduleRepository();
            /* @var $schedule WorkingSchedule */
            $schedule = $scheduleRepository->findOneBy([
                'working_place' => $supplier->getId(),
                'day_number' => $extraDay->getDate()->format('N'),
            ]);

            if (null === $schedule) {
                return $this->error($response, 'The "time_from" and the "time_to" required', 400);
            }

            $timeFromCheck = $timeFrom ?? $schedule->getTimeFrom();
            $timeToCheck = $timeTo ?? $schedule->getTimeTo();

            if ($timeToCheck <= $timeFromCheck) {
                return $this->error($response, 'The "time_to" must be greater than the "time_from"', 400);
            }
        }

        if (array_key_exists('time_from', $requestData)) {
            $extraDay->setTimeFrom($timeFrom);
        }

        if (array_key_exists('time_to', $requestData)) {
            $extraDay->setTimeTo($timeTo);
        }

        $entityViolations = $extraDayRepository->validate($extraDay);

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
        $spec['date']['required'] = false;
        $spec['is_working']['required'] = false;
        $spec['time_from']['required'] = false;
        $spec['time_to']['required'] = false;

        return $spec;
    }
}
