<?php

declare(strict_types=1);

namespace App\Http\Controller\Supplier;

use App\Autorus\Exception\RuntimeException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @OA\Delete(
 *   path="/v1/supplier/cache",
 *   summary="Обновление кэша поставщиков",
 *   tags={"Поставщики"},
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
 *   id="supplier.cache.delete",
 *   path="/v1/supplier/cache",
 *   methods={"DELETE"}
 * )
 */
class CacheDeleteController extends SupplierEndpoint
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
        $supplierRepository = $this->getSupplierRepository();

        try {
            $supplierRepository->cacheRefresh();
        } catch(RuntimeException $e) {
            return $this->error($response, $e->getMessage(), 500);
        }

        return $this->ok($response);
    }
}
