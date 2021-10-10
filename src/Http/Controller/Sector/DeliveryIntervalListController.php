<?php

declare(strict_types=1);

namespace App\Http\Controller\Sector;

use App\Database\Entity\SectorDeliveryInterval;
use App\Database\Entity\Sector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_key_exists;

/**
 * @OA\Get(
 *   path="/v1/sector/{uuid}/delivery/interval",
 *   summary="Список интервалов доставки сектора",
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
 *           @OA\Items(ref="#/components/schemas/SectorDeliveryInterval"),
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
 *   id="sector.delivery.interval.list",
 *   path="/v1/sector/{sectorUuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}/delivery/interval",
 *   methods={"GET"}
 * )
 */
class DeliveryIntervalListController extends DeliveryIntervalEndpoint
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

        $queryParams = $request->getQueryParams();
        $page = array_key_exists('page', $queryParams) ? (int)$queryParams['page'] : 1;
        $limit = array_key_exists('limit', $queryParams) ? (int)$queryParams['limit'] : 50;

        $repository = $this->getSectorDeliveryIntervalRepository();
        $listParams = [
            'select' => [
                'time_from',
                'time_to',
            ],
            'where' => [
                'sector' => $sector->getId(),
            ],
            'orderBy' => [
                'id' => 'DESC',
            ],
            'limit' => $limit,
            'page' => $page,
        ];

        $entities = $repository->getList($listParams);
        $timeFormat = SectorDeliveryInterval::getTimeFormat();

        foreach ($entities as $entity) {
            $entity['time_from'] = $entity['time_from']->format($timeFormat);
            $entity['time_to'] = $entity['time_to']->format($timeFormat);
            $responseData['items'][] = $entity;
        }

        $responseData['total_count'] = $repository->count([
            'sector' => $sector->getId(),
        ]);

        return $this->ok($response, $responseData);
    }
}
