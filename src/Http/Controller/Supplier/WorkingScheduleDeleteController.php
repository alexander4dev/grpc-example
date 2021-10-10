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
 * @OA\Delete(
 *   path="/v1/supplier/{uuid}/working/schedule/{dayNumber}",
 *   summary="Удаление графика работы поставщика",
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
 * )
 *
 * @Route(
 *   id="supplier.working.schedule.delete",
 *   path="/v1/supplier/{supplierUuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}/working/schedule/{dayNumber<\d+>}",
 *   methods={"DELETE"}
 * )
 */
class WorkingScheduleDeleteController extends SupplierEndpoint
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

        $entityManager = $this->getEntityManager();
        $entityManager->remove($schedule);
        $entityManager->flush();

        return $this->ok($response);
    }
}
