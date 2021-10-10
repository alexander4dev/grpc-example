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

/**
 * @OA\Post(
 *   path="/v1/supplier/{uuid}/working/extraday",
 *   summary="Создание дополнительного графика работы поставщика",
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
 *   @OA\RequestBody(
 *     @OA\JsonContent(
 *       type="object",
 *       ref="#/components/schemas/WorkingExtraDay",
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
 *   id="supplier.working.extraday.create",
 *   path="/v1/supplier/{supplierUuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}/working/extraday",
 *   methods={"POST"}
 * )
 */
class WorkingExtraDayCreateController extends WorkingExtraDayEndpoint
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

        $extraDay = new WorkingExtraDay();
        $extraDay->setWorkingPlace($supplier);

        $inputFilter = $this->getInputFilter($request->getParsedBody());

        if (!$inputFilter->isValid()) {
            $requestVioldations = $this->createViolationList($inputFilter);

            return $this->violations($response, $requestVioldations);
        }

        $entityData = $inputFilter->getValues();
        $dateFormat = WorkingExtraDay::getDateFormat();
        $timeFormat = WorkingExtraDay::getTimeFormat();
        $date = DateTime::createFromFormat($dateFormat, $entityData['date']);
        $isWorking = $entityData['is_working'];
        $timeFrom = null !== $entityData['time_from'] ? DateTime::createFromFormat($timeFormat, $entityData['time_from']) : null;
        $timeTo = null !== $entityData['time_to'] ? DateTime::createFromFormat($timeFormat, $entityData['time_to']) : null;

        if ($isWorking && (null === $timeFrom || null === $timeTo)) {
            $scheduleRepository = $this->getWorkingScheduleRepository();
            /* @var $schedule WorkingSchedule */
            $schedule = $scheduleRepository->findOneBy([
                'working_place' => $supplier->getId(),
                'day_number' => (int)$date->format('N'),
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

        $extraDay->setDate($date);
        $extraDay->setIsWorking($isWorking);
        $extraDay->setTimeFrom($timeFrom);
        $extraDay->setTimeTo($timeTo);

        $extraDayRepository = $this->getWorkingExtraDayRepository();
        $entityViolations = $extraDayRepository->validate($extraDay);

        if ($entityViolations->count()) {
            return $this->violations($response, $entityViolations);
        }

        $entityManager = $this->getEntityManager();
        $entityManager->persist($extraDay);
        $entityManager->flush();

        return $this->ok($response, [], 201);
    }
}
