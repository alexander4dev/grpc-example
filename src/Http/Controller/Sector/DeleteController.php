<?php

declare(strict_types=1);

namespace App\Http\Controller\Sector;

use App\Database\Entity\Sector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function sprintf;

/**
 * @OA\Delete(
 *   path="/v1/sector/{uuid}",
 *   summary="Удаление сектора доставки поставщика",
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
 *       ref="#/components/schemas/ResponseOk",
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
 *   id="sector.delete",
 *   path="/v1/sector/{uuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}",
 *   methods={"DELETE"}
 * )
 */
class DeleteController extends SectorEndpoint
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $response = $handler->handle($request);
        $uuid = $request->getAttribute('uuid');
        $sectorRepository = $this->getSectorRepository();
        /* @var $entity Sector */
        $entity = $sectorRepository->findOneBy([
            'uuid' => $uuid,
        ]);

        if (null === $entity) {
            $message = sprintf('An entity "%s" was not found.', $uuid);
            return $this->error($response, $message, 404);
        }

        $entityManager = $this->getEntityManager();
        $entityManager->remove($entity);
        $entityManager->flush();

        return $this->ok($response);
    }
}
