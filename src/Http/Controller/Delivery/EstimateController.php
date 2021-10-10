<?php

declare(strict_types=1);

namespace App\Http\Controller\Delivery;

use App\Http\Controller\AbstractController;
use App\Service\DeliveryService;
use App\Service\Exception\DeliveryServiceException;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Validator\Date;
use Zend\Validator\InArray;
use Zend\Validator\Uuid;

/**
 * @OA\Get(
 *   path="/v1/delivery/estimate",
 *   summary="Ближайшая дата доставки",
 *   tags={"Доставка заказа"},
 *   @OA\Parameter(
 *     name="offer_uuid",
 *     description="UUID предложения",
 *     in="query",
 *     required=true,
 *     @OA\Schema(
 *       type="string",
 *       format="uuid",
 *     ),
 *   ),
 *   @OA\Parameter(
 *     name="supplier_uuid",
 *     description="UUID поставщика - получателя",
 *     in="query",
 *     required=true,
 *     @OA\Schema(
 *       type="string",
 *       format="uuid",
 *     ),
 *   ),
 *   @OA\Parameter(
 *     name="shipping_method",
 *     description="Способ получения заказа",
 *     in="query",
 *     required=true,
 *     @OA\Schema(
 *       type="string",
 *       enum={"pickup", "delivery_autorus", "delivery_transport_company"},
 *     ),
 *   ),
 *   @OA\Parameter(
 *     name="sector_uuid",
 *     description="UUID сектора доставки",
 *     in="query",
 *     @OA\Schema(
 *       type="string",
 *       format="uuid",
 *     ),
 *   ),
 *   @OA\Parameter(
 *     name="delivery_date",
 *     description="Дата доставки в сектор",
 *     in="query",
 *     @OA\Schema(
 *       type="string",
 *       format="date",
 *       example="2019-05-25",
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
 *         type="object",
 *         @OA\Property(
 *           property="order_date",
 *           type="string",
 *           format="datetime",
 *           example="2019-05-25 10:00",
 *         ),
 *         @OA\Property(
 *           property="delivery_date",
 *           type="string",
 *           format="datetime",
 *           example="2019-05-25 10:00",
 *        ),
 *         @OA\Property(
 *           property="delivery_intervals",
 *           type="array",
 *           @OA\Items(
 *             @OA\Property(
 *               property="time_from",
 *               type="string",
 *               format="time-hour",
 *               example="10:00",
 *             ),
 *             @OA\Property(
 *               property="time_to",
 *               type="string",
 *               format="time-hour",
 *               example="12:00",
 *             ),
 *           ),
 *         ),
 *       ),
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
 *   @OA\Response(
 *     response="503",
 *     description="Ошибка сервиса",
 *     @OA\JsonContent(
 *       type="object",
 *       ref="#/components/schemas/ResponseError",
 *     ),
 *   ),
 * )
 * 
 * @Route(
 *   id="delivery.estimate",
 *   path="/v1/delivery/estimate",
 *   methods={"GET"}
 * )
 */
class EstimateController extends AbstractController
{
    private const DELIVERY_DATE_FORMAT = 'Y-m-d';

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $inputFilter = $this->getInputFilter($request->getQueryParams());

        if (!$inputFilter->isValid()) {
            $requestVioldations = $this->createViolationList($inputFilter);

            return $this->violations($response, $requestVioldations);
        }

        $inputData = $inputFilter->getValues();
        $minimalOrderDateTime = new DateTimeImmutable();
        $deliveryDateTime = !empty($inputData['delivery_date']) ? DateTimeImmutable::createFromFormat(self::DELIVERY_DATE_FORMAT, $inputData['delivery_date']) : null;

        if (DeliveryService::SHIPPING_METHOD_DELIVERY_AUTORUS === $inputData['shipping_method']) {
            if (null === $inputData['sector_uuid']) {
                return $this->error($response, 'The sector_uuid is required', 400);
            }

            if (null === $deliveryDateTime) {
                return $this->error($response, 'The delivery_date is required', 400);
            }
        }

        try {
            $deliveryData = $this->getDeliveryService()->estimateDelivery(
                $inputData['offer_uuid'],
                $inputData['supplier_uuid'],
                $minimalOrderDateTime,
                $inputData['shipping_method'],
                $inputData['sector_uuid'],
                $deliveryDateTime
            );
        } catch (DeliveryServiceException $e) {
            return $this->error($response, $e->getMessage());
        }

        return $this->ok($response, $deliveryData);
    }

    /**
     * @return array
     */
    protected function getInputFilterSpecification(): array
    {
        $spec = [
            'offer_uuid' => [
                'name' => 'offer_uuid',
                'required' => true,
                'validators' => [
                    [
                        'name' => Uuid::class,
                    ],
                ],
            ],
            'supplier_uuid' => [
                'name' => 'supplier_uuid',
                'required' => true,
                'validators' => [
                    [
                        'name' => Uuid::class,
                    ],
                ],
            ],
            'shipping_method' => [
                'name' => 'shipping_method',
                'required' => true,
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => DeliveryService::SHIPPING_METHODS,
                        ],
                    ],
                ],
            ],
            'sector_uuid' => [
                'required' => false,
                'name' => 'sector_uuid',
                'validators' => [
                    [
                        'name' => Uuid::class,
                    ],
                ],
            ],
            'delivery_date' => [
                'name' => 'delivery_date',
                'required' => false,
                'validators' => [
                    [
                        'name' => Date::class,
                        'options' => [
                            'format' => self::DELIVERY_DATE_FORMAT,
                        ],
                    ],
                ],
            ],
        ];

        return $spec;
    }
}
