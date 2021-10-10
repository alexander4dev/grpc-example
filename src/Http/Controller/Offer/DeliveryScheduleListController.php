<?php

declare(strict_types=1);

namespace App\Http\Controller\Offer;

use App\Database\Entity\DeliverySchedule;
use App\Database\Entity\Offer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_key_exists;

/**
 * @OA\Get(
 *   path="/v1/offer/{uuid}/delivery/schedule",
 *   summary="График доставки предложения поставщика",
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
 *           @OA\Items(ref="#/components/schemas/DeliverySchedule"),
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
 *   id="offer.delivery.schedule.list",
 *   path="/v1/offer/{offerUuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}/delivery/schedule",
 *   methods={"GET"}
 * )
 */
class DeliveryScheduleListController extends DeliveryScheduleEndpoint
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

        $queryParams = $request->getQueryParams();
        $page = array_key_exists('page', $queryParams) ? (int)$queryParams['page'] : 1;
        $limit = array_key_exists('limit', $queryParams) ? (int)$queryParams['limit'] : 50;

        $repository = $this->getDeliveryScheduleRepository();
        $listParams = [
            'select' => [
                'day_number',
                'order_time',
                'delivery_minutes',
            ],
            'where' => [
                'offer' => $offer->getId(),
            ],
            'orderBy' => [
                'id' => 'DESC',
            ],
            'limit' => $limit,
            'page' => $page,
        ];

        $entities = $repository->getList($listParams);
        $timeFormat = DeliverySchedule::getTimeFormat();

        foreach ($entities as $entity) {
            $entity['order_time'] = $entity['order_time']->format($timeFormat);
            $responseData['items'][] = $entity;
        }

        $responseData['total_count'] = $repository->count([
            'offer' => $offer->getId(),
        ]);

        return $this->ok($response, $responseData);
    }
}
