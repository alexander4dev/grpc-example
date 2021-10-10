<?php

declare(strict_types=1);

namespace App\Http\Controller\Offer;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @OA\Get(
 *   path="/v1/offer/cache",
 *   summary="Список предложений поставщиков, используя кэш",
 *   tags={"Предложения поставщиков"},
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
 *       ),
 *     ),
 *   ),
 * )
 * 
 * @Route(
 *   id="offer.list.cache",
 *   path="/v1/offer/cache",
 *   methods={"GET"}
 * )
 */
class ListCacheController extends OfferEndpoint
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $offerRepository = $this->getOfferRepository();
        $entities = $offerRepository->getListFromCache();
        $responseData = [
            'items' => $entities,
        ];

        return $this->ok($response, $responseData);
    }
}
