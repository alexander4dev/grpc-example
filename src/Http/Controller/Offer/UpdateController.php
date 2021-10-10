<?php

declare(strict_types=1);

namespace App\Http\Controller\Offer;

use App\Database\Entity\Offer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function sprintf;
use function array_key_exists;

/**
 * @OA\Patch(
 *   path="/v1/offer/{uuid}",
 *   summary="Изменение предложения поставщика",
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
 *   @OA\RequestBody(
 *     @OA\JsonContent(
 *       type="object",
 *       ref="#/components/schemas/Offer",
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
 *   id="offer.update",
 *   path="/v1/offer/{uuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}",
 *   methods={"PATCH"}
 * )
 */
class UpdateController extends OfferEndpoint
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
        $offerRepository = $this->getOfferRepository();
        /* @var $offer Offer */
        $offer = $offerRepository->findOneBy([
            'uuid' => $uuid,
        ]);

        if (null === $offer) {
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

        $supplierRepository = $this->getSupplierRepository();

        if (array_key_exists('uuid', $requestBody)) {
            $offer->setUuid($entityData['uuid']);
        }

        if (array_key_exists('supplier_from', $requestBody)) {
            /* @var $supplierFrom Supplier */
            $supplierFrom = $supplierRepository->findOneBy([
                'uuid' => $entityData['supplier_from'],
            ]);

            if (null === $supplierFrom) {
                $message = sprintf('An entity "%s" was not found.', $entityData['supplier_from']);
                return $this->error($response, $message, 400);
            }

            $offer->setSupplierFrom($supplierFrom);
        }

        if (array_key_exists('supplier_to', $requestBody)) {
            /* @var $supplierTo Supplier */
            $supplierTo = $supplierRepository->findOneBy([
                'uuid' => $entityData['supplier_to'],
            ]);

            if (null === $supplierTo) {
                $message = sprintf('An entity "%s" was not found.', $entityData['supplier_to']);
                return $this->error($response, $message, 400);
            }

            $offer->setSupplierTo($supplierTo);
        }

        if (array_key_exists('order_initializing_minutes', $requestBody)) {
            $offer->setOrderInitializingMinutes($entityData['order_initializing_minutes']);
        }

        if (array_key_exists('is_enabled', $requestBody)) {
            $offer->setIsEnabled($entityData['is_enabled']);
        }

        $entityViolations = $offerRepository->validate($offer);

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
        $spec['supplier_from']['required'] = false;
        $spec['supplier_to']['required'] = false;
        $spec['order_initializing_minutes']['required'] = false;
        $spec['is_enabled']['required'] = false;

        return $spec;
    }
}
