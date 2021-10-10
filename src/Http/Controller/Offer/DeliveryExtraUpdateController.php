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
use function array_key_exists;

/**
 * @OA\Patch(
 *   path="/v1/offer/{uuid}/delivery/extra/{date}",
 *   summary="Изменение дополнительного графика доставки предложения поставщика",
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
 *   @OA\Parameter(
 *     name="date",
 *     description="Дата в формате: Y-m-d H:i",
 *     in="path",
 *     required=true,
 *     @OA\Schema(
 *       type="string",
 *       format="date",
 *     ),
 *   ),
 *   @OA\RequestBody(
 *     @OA\JsonContent(
 *       type="object",
 *       ref="#/components/schemas/DeliveryExtra",
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
 *   id="offer.delivery.extra.update",
 *   path="/v1/offer/{offerUuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}/delivery/extra/{date<\d{4}-\d{2}-\d{2}%20\d{2}%3A\d{2}>}",
 *   methods={"PATCH"}
 * )
 */
class DeliveryExtraUpdateController extends DeliveryExtraEndpoint
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

        $date = urldecode($request->getAttribute('date'));
        $dateFormat = DeliveryExtra::getDateFormat();
        $dateTime = DateTime::createFromFormat($dateFormat, $date);
        $extraRepository = $this->getDeliveryExtraRepository();
        /* @var $extra DeliveryExtra */
        $extra = $extraRepository->findOneBy([
            'offer' => $offer->getId(),
            'order_date' => $dateTime,
        ]);

        if (null === $extra) {
            $message = sprintf('An entity "%s" was not found.', $date);
            return $this->error($response, $message, 404);
        }

        $requestData = $request->getParsedBody();
        $inputFilter = $this->getInputFilter($requestData);


        if (!$inputFilter->isValid()) {
            $requestVioldations = $this->createViolationList($inputFilter);

            return $this->violations($response, $requestVioldations);
        }

        $entityData = $inputFilter->getValues();

        if (array_key_exists('order_date', $requestData)) {
            $orderDate = DateTime::createFromFormat($dateFormat, $entityData['order_date']);
            $extra->setOrderDate($orderDate);
        }

        if (array_key_exists('is_supply', $requestData)) {
            $extra->setIsSupply($entityData['is_supply']);
        }

        if (array_key_exists('delivery_date', $requestData)) {
            $deliveryDate = null !== $entityData['delivery_date'] ? DateTime::createFromFormat($dateFormat, $entityData['delivery_date']) : null;

            if ($extra->getIsSupply() && null === $deliveryDate) {
                return $this->error($response, 'The "delivery_date" required', 400);
            }

            $extra->setDeliveryDate($deliveryDate);
        }

        $entityViolations = $extraRepository->validate($extra);

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
        $spec['order_date']['required'] = false;
        $spec['is_supply']['required'] = false;
        $spec['delivery_date']['required'] = false;

        return $spec;
    }
}
