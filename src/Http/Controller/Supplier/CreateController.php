<?php

declare(strict_types=1);

namespace App\Http\Controller\Supplier;

use App\Database\Entity\Supplier;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @OA\Post(
 *   path="/v1/supplier",
 *   summary="Создание поставщика",
 *   tags={"Поставщики"},
 *   @OA\RequestBody(
 *     @OA\JsonContent(
 *       type="object",
 *       ref="#/components/schemas/Supplier",
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
 *   id="supplier.create",
 *   path="/v1/supplier",
 *   methods={"POST"}
 * )
 */
class CreateController extends SupplierEndpoint
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

        $supplier = new Supplier();
        $supplier->setUuid($entityData['uuid']);
        $supplier->setTitle($entityData['title']);
        $supplier->setPublicTitle($entityData['public_title'] ?? $entityData['title']);
        $supplier->setIsAutorus($entityData['is_autorus']);
        $supplier->setDeliveryAcceptingMinutes($entityData['delivery_accepting_minutes']);

        $supplierRepository = $this->getSupplierRepository();
        $entityViolations = $supplierRepository->validate($supplier);

        if ($entityViolations->count()) {
            return $this->violations($response, $entityViolations);
        }

        $entityManager = $this->getEntityManager();
        $entityManager->persist($supplier);
        $entityManager->flush();

        return $this->ok($response, [], 201);
    }
}
