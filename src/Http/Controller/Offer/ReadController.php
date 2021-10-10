<?php

declare(strict_types=1);

namespace App\Http\Controller\Offer;

use App\Database\Entity\Offer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function sprintf;

/**
 * @OA\Get(
 *   path="/v1/offer/{uuid}",
 *   summary="Чтение предложения поставщика",
 *   tags={"Предложения поставщиков"},
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
 *         ref="#/components/schemas/Offer",
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
 *   id="offer.read",
 *   path="/v1/offer/{uuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}",
 *   methods={"GET"}
 * )
 */
class ReadController extends OfferEndpoint
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
        $repository = $this->getOfferRepository();
        /* @var $offer Offer */
        $offer = $repository->findOneBy([
            'uuid' => $uuid,
        ]);

        if (null === $offer) {
            $message = sprintf('An entity "%s" was not found.', $uuid);
            return $this->error($response, $message, 404);
        }

        $data = [
            'uuid' => $offer->getUuid(),
            'supplier_from' => $offer->getSupplierFrom()->getUuid(),
            'supplier_to' => $offer->getSupplierTo()->getUuid(),
            'order_initializing_minutes' => $offer->getOrderInitializingMinutes(),
            'is_enabled' => $offer->getIsEnabled(),
        ];

        return $this->ok($response, $data);
    }
}
