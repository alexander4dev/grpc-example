<?php

declare(strict_types=1);

namespace App\Http\Controller\Offer;

use App\Database\Entity\Offer;
use App\Database\Entity\Supplier;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @OA\Post(
 *   path="/v1/offer",
 *   summary="Создание предложения поставщика",
 *   tags={"Предложения поставщиков"},
 *   @OA\RequestBody(
 *     @OA\JsonContent(
 *       type="object",
 *       ref="#/components/schemas/Offer",
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
 *   id="offer.create",
 *   path="/v1/offer",
 *   methods={"POST"}
 * )
 */
class CreateController extends OfferEndpoint
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

        /* @var $supplierFrom Supplier */
        $supplierFrom = $supplierRepository->findOneBy([
            'uuid' => $entityData['supplier_from'],
        ]);

        if (null === $supplierFrom) {
            $message = sprintf('An entity "%s" was not found.', $entityData['supplier_from']);
            return $this->error($response, $message, 400);
        }

        /* @var $supplierTo Supplier */
        $supplierTo = $supplierRepository->findOneBy([
            'uuid' => $entityData['supplier_to'],
        ]);

        if (null === $supplierTo) {
            $message = sprintf('An entity "%s" was not found.', $entityData['supplier_to']);
            return $this->error($response, $message, 400);
        }

        $offer = new Offer();
        $offer->setUuid($entityData['uuid']);
        $offer->setSupplierFrom($supplierFrom);
        $offer->setSupplierTo($supplierTo);
        $offer->setOrderInitializingMinutes($entityData['order_initializing_minutes']);
        $offer->setIsEnabled($entityData['is_enabled']);

        $offerRepository = $this->getOfferRepository();
        $entityViolations = $offerRepository->validate($offer);

        if ($entityViolations->count()) {
            return $this->violations($response, $entityViolations);
        }

        $entityManager = $this->getEntityManager();
        $entityManager->persist($offer);
        $entityManager->flush();

        return $this->ok($response, [], 201);
    }
}
