<?php

declare(strict_types=1);

namespace App\Http\Controller\Supplier;

use App\Database\Entity\Supplier;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function sprintf;
use function array_key_exists;

/**
 * @OA\Patch(
 *   path="/v1/supplier/{uuid}",
 *   summary="Изменение поставщика",
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
 *   @OA\RequestBody(
 *     @OA\JsonContent(
 *       type="object",
 *       ref="#/components/schemas/Supplier",
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
 *   id="supplier.update",
 *   path="/v1/supplier/{uuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}",
 *   methods={"PATCH"}
 * )
 */
class UpdateController extends SupplierEndpoint
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
        $supplierRepository = $this->getSupplierRepository();
        /* @var $supplier Supplier */
        $supplier = $supplierRepository->findOneBy([
            'uuid' => $uuid,
        ]);

        if (null === $supplier) {
            $message = sprintf('An entity "%s" was not found.', $uuid);
            return $this->error($response, $message, 404);
        }

        $requestBody = $request->getParsedBody();
        $inputFilter = $this->getInputFilter($requestBody);

        if (!$inputFilter->isValid()) {
            $requestVioldations = $this->createViolationList($inputFilter);

            return $this->violations($response, $requestVioldations);
        }

        $entityData = $inputFilter->getValues();

        if (!$entityData) {
            return $this->ok($response);
        }

        if (array_key_exists('uuid', $requestBody)) {
            $supplier->setUuid($entityData['uuid']);
        }

        if (array_key_exists('title', $requestBody)) {
            $supplier->setTitle($entityData['title']);
        }

        if (array_key_exists('public_title', $requestBody)) {
            $supplier->setTitle($entityData['public_title']);
        }

        if (array_key_exists('is_autorus', $requestBody)) {
            $supplier->setIsAutorus($entityData['is_autorus']);
        }

        if (array_key_exists('delivery_accepting_minutes', $requestBody)) {
            $supplier->setDeliveryAcceptingMinutes($entityData['delivery_accepting_minutes']);
        }

        $entityViolations = $supplierRepository->validate($supplier);

        if ($entityViolations->count()) {
            return $this->violations($response, $entityViolations);
        }        

        $entityManager = $this->getEntityManager();
        $entityManager->flush();

        return $this->ok($response);
    }

    /**
     * @return array
     */
    protected function getInputFilterSpecification(): array
    {
        $spec = parent::getInputFilterSpecification();
        $spec['uuid']['required'] = false;
        $spec['title']['required'] = false;
        $spec['is_autorus']['required'] = false;
        $spec['delivery_accepting_minutes']['required'] = false;

        return $spec;
    }
}
