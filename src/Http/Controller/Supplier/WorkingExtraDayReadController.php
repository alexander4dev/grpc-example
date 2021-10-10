<?php

declare(strict_types=1);

namespace App\Http\Controller\Supplier;

use App\Database\Entity\Supplier;
use App\Database\Entity\WorkingExtraDay;
use DateTime;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function sprintf;

/**
 * @OA\Get(
 *   path="/v1/supplier/{uuid}/working/extraday/{date}",
 *   summary="Чтение дополнительного графика работы поставщика",
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
 *         ref="#/components/schemas/WorkingExtraDay",
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
 *   id="supplier.working.extraday.read",
 *   path="/v1/supplier/{supplierUuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}/working/extraday/{date<\d{4}-\d{2}-\d{2}>}",
 *   methods={"GET"}
 * )
 */
class WorkingExtraDayReadController extends SupplierEndpoint
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

        $date = $request->getAttribute('date');
        $dateFormat = WorkingExtraDay::getDateFormat();
        $dateTime = DateTime::createFromFormat($dateFormat, $date);

        $extraDayRepository = $this->getWorkingExtraDayRepository();
        /* @var $extraDay WorkingExtraDay */
        $extraDay = $extraDayRepository->findOneBy([
            'working_place' => $supplier->getId(),
            'date' => $dateTime,
        ]);

        if (null === $extraDay) {
            $message = sprintf('An entity "%s" was not found.', $date);
            return $this->error($response, $message, 404);
        }

        $timeFormat = WorkingExtraDay::getTimeFormat();

        $data = [
            'date' => $extraDay->getDate()->format($dateFormat),
            'is_working' => $extraDay->getIsWorking(),
            'time_from' => $extraDay->getTimeFrom() ? $extraDay->getTimeFrom()->format($timeFormat) : null,
            'time_to' => $extraDay->getTimeTo() ? $extraDay->getTimeTo()->format($timeFormat) : null,
        ];

        return $this->ok($response, $data);
    }
}
