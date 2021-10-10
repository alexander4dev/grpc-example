<?php

declare(strict_types=1);

namespace App\Http\Controller\Sector;

use App\Database\Entity\Sector;
use App\Database\Entity\Supplier;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @OA\Post(
 *   path="/v1/sector",
 *   summary="Создание сектора доставки поставщика",
 *   tags={"Секторы поставщиков"},
 *   @OA\RequestBody(
 *     @OA\JsonContent(
 *       type="object",
 *       ref="#/components/schemas/Sector",
 *     ),
 *   ),
 *   @OA\Response(
 *     response="201",
 *     description="Успешное выполнение",
 *     @OA\JsonContent(
 *       type="object",
 *       ref="#/components/schemas/ResponseOk",
 *     ),
 *   ),
 *   @OA\Response(
 *     response="400",
 *     description="Ошибка запроса",
 *     @OA\JsonContent(
 *       type="object",
 *       ref="#/components/schemas/ResponseViolations",
 *     ),
 *   ),
 * )
 *
 * @Route(
 *   id="sector.create",
 *   path="/v1/sector",
 *   methods={"POST"}
 * )
 */
class CreateController extends SectorEndpoint
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $inputFilter = $this->getInputFilter($request->getParsedBody());

        if (!$inputFilter->isValid()) {
            $requestVioldations = $this->createViolationList($inputFilter);

            return $this->violations($response, $requestVioldations);
        }

        $entityData = $inputFilter->getValues();

        $supplierRepository = $this->getSupplierRepository();

        /* @var $supplier Supplier */
        $supplier = $supplierRepository->findOneBy([
            'uuid' => $entityData['supplier'],
        ]);

        if (null === $supplier) {
            $message = sprintf('An entity "%s" was not found.', $entityData['supplier']);
            return $this->error($response, $message, 400);
        }

        $sector = new Sector();
        $sector->setUuid($entityData['uuid']);
        $sector->setSupplier($supplier);
        $sector->setTitle($entityData['title']);
        $sector->setDeliveryAcceptingMinutes($entityData['delivery_accepting_minutes']);

        $sectorRepository = $this->getSectorRepository();
        $entityViolations = $sectorRepository->validate($sector);

        if ($entityViolations->count()) {
            return $this->violations($response, $entityViolations);
        }

        $entityManager = $this->getEntityManager();
        $entityManager->persist($sector);
        $entityManager->flush();

        return $this->ok($response, [], 201);
    }
}
