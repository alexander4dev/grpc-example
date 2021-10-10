<?php

declare(strict_types=1);

namespace App\Http\Controller\Offer;

use App\Autorus\Exception\RuntimeException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @OA\Delete(
 *   path="/v1/offer/cache",
 *   summary="Обновление кэша предложений поставщиков",
 *   tags={"Предложения поставщиков"},
 *   @OA\Response(
 *     response="200",
 *     description="Успешное выполнение",
 *     @OA\JsonContent(
 *       type="object",
 *       ref="#/components/schemas/ResponseOk",
 *     ),
 *   ),
 *   @OA\Response(
 *     response="500",
 *     description="Ошибка сервера",
 *     @OA\JsonContent(
 *       type="object",
 *       ref="#/components/schemas/ResponseError",
 *     ),
 *   ),
 * )
 *
 * @Route(
 *   id="offer.cache.delete",
 *   path="/v1/offer/cache",
 *   methods={"DELETE"}
 * )
 */
class CacheDeleteController extends OfferEndpoint
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $offerRepository = $this->getOfferRepository();

        try {
            $offerRepository->cacheRefresh();
        } catch(RuntimeException $e) {
            return $this->error($response, $e->getMessage(), 500);
        }

        return $this->ok($response);
    }
}
