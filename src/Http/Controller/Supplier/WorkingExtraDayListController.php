<?php

declare(strict_types=1);

namespace App\Http\Controller\Supplier;

use App\Database\Entity\Supplier;
use App\Database\Entity\WorkingExtraDay;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_key_exists;

/**
 * @OA\Get(
 *   path="/v1/supplier/{uuid}/working/extraday",
 *   summary="Дополнительный график работы поставщика",
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
 *   @OA\Parameter(ref="#/components/parameters/page"),
 *   @OA\Parameter(ref="#/components/parameters/limit"),
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
 *         type="object",
 *         @OA\Property(
 *           property="items",
 *           type="array",
 *           @OA\Items(ref="#/components/schemas/WorkingExtraDay"),
 *         ),
 *         @OA\Property(
 *           property="total_count",
 *           type="integer",
 *         ),
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
 *   id="supplier.working.extraday.list",
 *   path="/v1/supplier/{supplierUuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}/working/extraday",
 *   methods={"GET"}
 * )
 */
class WorkingExtraDayListController extends WorkingScheduleEndpoint
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $responseData = [
            'items' => [],
        ];

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

        $queryParams = $request->getQueryParams();
        $page = array_key_exists('page', $queryParams) ? (int)$queryParams['page'] : 1;
        $limit = array_key_exists('limit', $queryParams) ? (int)$queryParams['limit'] : 50;

        $repository = $this->getWorkingExtraDayRepository();
        $listParams = [
            'select' => [
                'date',
                'is_working',
                'time_from',
                'time_to',
            ],
            'where' => [
                'working_place' => $supplier->getId(),
            ],
            'orderBy' => [
                'id' => 'DESC',
            ],
            'limit' => $limit,
            'page' => $page,
        ];

        $entities = $repository->getList($listParams);
        $dateFormat = WorkingExtraDay::getDateFormat();
        $timeFormat = WorkingExtraDay::getTimeFormat();

        foreach ($entities as $entity) {
            $entity['date'] = $entity['date']->format($dateFormat);
            $entity['time_from'] = null !== $entity['time_from'] ? $entity['time_from']->format($timeFormat) : null;
            $entity['time_to'] = null !== $entity['time_to'] ? $entity['time_to']->format($timeFormat) : null;
            $responseData['items'][] = $entity;
        }

        $responseData['total_count'] = $repository->count([
            'working_place' => $supplier->getId(),
        ]);

        return $this->ok($response, $responseData);
    }
}
