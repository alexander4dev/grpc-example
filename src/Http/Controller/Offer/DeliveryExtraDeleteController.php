<?php

declare(strict_types=1);

namespace App\Http\Controller\Offer;

use App\Database\Entity\DeliveryExtra;
use App\Database\Entity\Offer;
use DateTime;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @OA\Delete(
 *   path="/v1/offer/{uuid}/delivery/extra/{date}",
 *   summary="Удаление дополнительного графика доставки предложения поставщика",
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
 *   id="offer.delivery.extra.delete",
 *   path="/v1/offer/{offerUuid<[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}>}/delivery/extra/{date<\d{4}-\d{2}-\d{2}%20\d{2}%3A\d{2}>}",
 *   methods={"DELETE"}
 * )
 */
class DeliveryExtraDeleteController extends DeliveryExtraEndpoint
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

        $entityManager = $this->getEntityManager();
        $entityManager->remove($extra);
        $entityManager->flush();

        return $this->ok($response);
    }
}
