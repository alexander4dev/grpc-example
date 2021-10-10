<?php

declare(strict_types=1);

namespace App\Http\Controller\Offer;

use App\Database\Entity\Offer;
use App\Database\Entity\DeliveryExtra;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use DateTime;

use function sprintf;

/**
 * @OA\Post(
 *   path="/v1/offer/{uuid}/delivery/extra",
 *   summary="Создание дополнительного графика доставки предложения поставщика",
 *   tags={"График доставок предложений"},
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
 *       ref="#/components/schemas/DeliveryExtra",
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
 *   id="offer.delivery.extra.create",
 *   path="/v1/offer/{offerUuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}/delivery/extra",
 *   methods={"POST"}
 * )
 */
class DeliveryExtraCreateController extends DeliveryExtraEndpoint
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $offerUuid = $request->getAttribute('offerUuid');
        $offerRepository = $this->getOfferRepository();
        /* @var $offer Offer */
        $offer = $offerRepository->findOneBy([
            'uuid' => $offerUuid,
        ]);

        if (null === $offer) {
            $message = sprintf('An entity "%s" was not found.', $offerUuid);
            return $this->error($response, $message, 404);
        }

        $extra = new DeliveryExtra();
        $extra->setOffer($offer);

        $inputFilter = $this->getInputFilter($request->getParsedBody());

        if (!$inputFilter->isValid()) {
            $requestVioldations = $this->createViolationList($inputFilter);

            return $this->violations($response, $requestVioldations);
        }

        $entityData = $inputFilter->getValues();
        $dateFormat = DeliveryExtra::getDateFormat();
        $orderDate = DateTime::createFromFormat($dateFormat, $entityData['order_date']);
        $deliveryDate = null !== $entityData['delivery_date'] ? DateTime::createFromFormat($dateFormat, $entityData['delivery_date']) : null;
        $isSupply = $entityData['is_supply'];

        if ($isSupply && null === $deliveryDate) {
            return $this->error($response, 'The "delivery_date" required', 400);
        }

        $extra->setOrderDate($orderDate);
        $extra->setIsSupply($isSupply);
        $extra->setDeliveryDate($deliveryDate);

        $extraRepository = $this->getDeliveryExtraRepository();
        $entityViolations = $extraRepository->validate($extra);

        if ($entityViolations->count()) {
            return $this->violations($response, $entityViolations);
        }

        $entityManager = $this->getEntityManager();
        $entityManager->persist($extra);
        $entityManager->flush();

        return $this->ok($response, [], 201);
    }
}
