<?php

declare(strict_types=1);

namespace App\Http\Controller\Offer;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_key_exists;

/**
 * @OA\Get(
 *   path="/v1/offer",
 *   summary="Список предложений поставщиков",
 *   tags={"Предложения поставщиков"},
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
 *           @OA\Items(ref="#/components/schemas/Offer"),
 *         ),
 *         @OA\Property(
 *           property="total_count",
 *           type="integer",
 *         ),
 *       ),
 *     ),
 *   ),
 * )
 * 
 * @Route(
 *   id="offer.list",
 *   path="/v1/offer",
 *   methods={"GET"}
 * )
 */
class ListController extends OfferEndpoint
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $responseData = [];

        $queryParams = $request->getQueryParams();
        $page = array_key_exists('page', $queryParams) ? (int)$queryParams['page'] : 1;
        $limit = array_key_exists('limit', $queryParams) ? (int)$queryParams['limit'] : 50;

        $repository = $this->getOfferRepository();
        $listParams = [
            'select' => [
                'uuid',
                'supplier_from' => [
                    'uuid AS supplier_from',
                ],
                'supplier_to' => [
                    'uuid AS supplier_to',
                ],
                'order_initializing_minutes',
                'is_enabled',
            ],
            'orderBy' => [
                'id' => 'DESC',
            ],
            'limit' => $limit,
            'page' => $page,
        ];

        $entities = $repository->getList($listParams);
        $responseData['items'] = $entities;
        $responseData['total_count'] = $repository->count([]);

        return $this->ok($response, $responseData);
    }
}
