<?php

declare(strict_types=1);

namespace App\Http\Controller\Supplier;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @OA\Get(
 *   path="/v1/supplier/cache",
 *   summary="Список поставщиков, используя кэш",
 *   tags={"Поставщики"},
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
 *           @OA\Items(ref="#/components/schemas/Supplier"),
 *         ),
 *       ),
 *     ),
 *   ),
 * )
 * 
 * @Route(
 *   id="supplier.list.cache",
 *   path="/v1/supplier/cache",
 *   methods={"GET"}
 * )
 */
class ListCacheController extends SupplierEndpoint
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $supplierRepository = $this->getSupplierRepository();
        $entities = $supplierRepository->getListFromCache();
        $responseData = [
            'items' => $entities,
        ];

        return $this->ok($response, $responseData);
    }
}
