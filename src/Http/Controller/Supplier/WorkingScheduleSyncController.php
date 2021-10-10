<?php

declare(strict_types=1);

namespace App\Http\Controller\Supplier;

use App\Database\Entity\Supplier;
use App\Database\Entity\WorkingSchedule;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function sprintf;

/**
 * @OA\Post(
 *   path="/v1/supplier/{uuid}/working/schedule/sync",
 *   summary="Синхронизация графика работы поставщика",
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
 *       type="array",
 *       @OA\Items(ref="#/components/schemas/WorkingSchedule"),
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
 *   id="supplier.working.schedule.sync",
 *   path="/v1/supplier/{supplierUuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}/working/schedule/sync",
 *   methods={"POST"}
 * )
 */
class WorkingScheduleSyncController extends WorkingScheduleEndpoint
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

        $requestData = $request->getParsedBody();
        $scheduleData = [];
        $scheduleDayNumbers = [];

        foreach ($requestData as $sheduleItemData) {
            $inputFilter = $this->getInputFilter($sheduleItemData);

            if (!$inputFilter->isValid()) {
                $requestVioldations = $this->createViolationList($inputFilter);

                return $this->violations($response, $requestVioldations);
            }

            $scheduleData[] = $inputFilter->getValues();
            $scheduleDayNumbers[] = $sheduleItemData['day_number'];
        }

        $scheduleIdsToDelete = [];

        foreach ($supplier->getWorkingSchedule() as $supplierSchedule) {
            /* @var $supplierSchedule WorkingSchedule */
            if (!in_array($supplierSchedule->getDayNumber(), $scheduleDayNumbers)) {
                $scheduleIdsToDelete[] = $supplierSchedule->getId();
            }
        }

        $scheduleRepository = $this->getWorkingScheduleRepository();

        if ($scheduleIdsToDelete) {
            $scheduleRepository->deleteById($scheduleIdsToDelete);
        }

        $scheduleRepository->insertUpdate($supplier->getId(), $scheduleData);

        return $this->ok($response);
    }
}
