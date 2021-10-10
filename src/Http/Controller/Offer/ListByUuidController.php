<?php

declare(strict_types=1);

namespace App\Http\Controller\Offer;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\InputFilter\ArrayInput;
use Zend\Validator\Uuid;

use function array_key_exists;

/**
 * @OA\Post(
 *   path="/v1/offer/list",
 *   summary="Список предложений поставщиков",
 *   tags={"Предложения поставщиков"},
 *   @OA\Parameter(ref="#/components/parameters/page"),
 *   @OA\Parameter(ref="#/components/parameters/limit"),
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
 *           property="items",
 *           type="array",
 *           @OA\Items(ref="#/components/schemas/Offer"),
 *         ),
 *         @OA\Property(
 *           property="total_count",
 *           type="integer",
 *         ),
 *       ),
 *     ),
 *   ),
 * )
 * 
 * @Route(
 *   id="offer.list.by.uuid",
 *   path="/v1/offer/list",
 *   methods={"POST"}
 * )
 */
class ListByUuidController extends OfferEndpoint
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
            return $this->violations($response, $this->createViolationList($inputFilter));
        }

        $inputData = $inputFilter->getValues();
        $repository = $this->getOfferRepository();
        $listParams = [
            'select' => [
                'uuid',
                'supplier_from' => [
                    'uuid AS supplier_from',
                ],
                'supplier_to' => [
                    'uuid AS supplier_to',
                ],
                'order_initializing_minutes',
                'is_enabled',
            ],
            'whereIn' => [
                'uuid' => $inputData['uuid'],
            ],
            'orderBy' => [
                'id' => 'DESC',
            ],
            'indexBy' => 'uuid',
        ];

        $entities = $repository->getList($listParams);

        return $this->ok($response, $entities);
    }
    /**
     * @return array
     */
    protected function getInputFilterSpecification(): array
    {
        return [
            'uuid' => [
                'name' => 'uuid',
                'type' => ArrayInput::class,
                'required' => true,
                'validators' => [
                    [
                        'name' => Uuid::class,
                    ],
                ],
            ],
        ];
    }
}
