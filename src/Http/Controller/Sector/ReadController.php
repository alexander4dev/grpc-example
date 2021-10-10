<?php

declare(strict_types=1);

namespace App\Http\Controller\Sector;

use App\Database\Entity\Sector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function sprintf;

/**
 * @OA\Get(
 *   path="/v1/sector/{uuid}",
 *   summary="Чтение сектора доставки поставщика",
 *   tags={"Секторы поставщиков"},
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
 *         ref="#/components/schemas/Sector",
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
 *   id="sector.read",
 *   path="/v1/sector/{uuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}",
 *   methods={"GET"}
 * )
 */
class ReadController extends SectorEndpoint
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
        $repository = $this->getSectorRepository();
        /* @var $sector Sector */
        $sector = $repository->findOneBy([
            'uuid' => $uuid,
        ]);

        if (null === $sector) {
            $message = sprintf('An entity "%s" was not found.', $uuid);
            return $this->error($response, $message, 404);
        }

        $data = [
            'uuid' => $sector->getUuid(),
            'title' => $sector->getTitle(),
            'supplier' => $sector->getSupplier()->getUuid(),
            'delivery_accepting_minutes' => $sector->getDeliveryAcceptingMinutes(),
        ];

        return $this->ok($response, $data);
    }
}
