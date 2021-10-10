<?php

declare(strict_types=1);

namespace App\Http\Controller\Supplier;

use App\Database\Entity\Supplier;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function sprintf;

/**
 * @OA\Get(
 *   path="/v1/supplier/{uuid}",
 *   summary="Чтение поставщика",
 *   tags={"Поставщики"},
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
 *         ref="#/components/schemas/Supplier",
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
 *   id="supplier.read",
 *   path="/v1/supplier/{uuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}",
 *   methods={"GET"}
 * )
 */
class ReadController extends SupplierEndpoint
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $uuid = $request->getAttribute('uuid');
        $repository = $this->getSupplierRepository();
        /* @var $supplier Supplier */
        $supplier = $repository->findOneBy([
            'uuid' => $uuid,
        ]);

        if (null === $supplier) {
            $message = sprintf('An entity "%s" was not found.', $uuid);
            return $this->error($response, $message, 404);
        }

        $data = [
            'uuid' => $supplier->getUuid(),
            'title' => $supplier->getTitle(),
            'public_title' => $supplier->getPublicTitle(),
            'is_autorus' => $supplier->getIsAutorus(),
            'delivery_accepting_minutes' => $supplier->getDeliveryAcceptingMinutes(),
        ];

        return $this->ok($response, $data);
    }
}
