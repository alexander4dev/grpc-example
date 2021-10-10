<?php

declare(strict_types=1);

namespace App\Http\Controller\Offer;

use App\Autorus\Validator\IsBool;
use App\Autorus\Validator\IsString;
use App\Database\Entity\DeliveryExtra;
use App\Http\Controller\AbstractController;
use DateTime;
use Zend\Validator\Callback;
use Zend\Validator\Date;

abstract class DeliveryExtraEndpoint extends AbstractController
{
    /**
     * @return array
     */
    protected function getInputFilterSpecification(): array
    {
        $dateFormat = '!' . DeliveryExtra::getDateFormat();

        $spec = [
            'order_date' => [
                'name' => 'order_date',
                'required' => true,
                'validators' => [
                    [
                        'name' => IsString::class,
                        'break_chain_on_failure' => true,
                    ],
                    [
                        'name' => Date::class,
                        'options' => [
                            'format' => $dateFormat,
                        ],
                    ],
                ],
            ],
            'is_supply' => [
                'name' => 'is_supply',
                'required' => true,
                'allow_empty' => true,
                'validators' => [
                    [
                        'name' => IsBool::class,
                    ],
                ],
            ],
            'delivery_date' => [
                'name' => 'delivery_date',
                'required' => true,
                'allow_empty' => true,
                'validators' => [
                    [
                        'name' => IsString::class,
                        'break_chain_on_failure' => true,
                    ],
                    [
                        'name' => Date::class,
                        'options' => [
                            'format' => $dateFormat,
                        ],
                        'break_chain_on_failure' => true,
                    ],
                    [
                        'name' => Callback::class,
                        'options' => [
                            'messages' => [
                                Callback::INVALID_VALUE => 'The "delivery_date" must be greater than the "order_date"',
                            ],
                            'callback' => function(?string $value, array $context) use($dateFormat): bool {
                                if (!$context['is_supply']) {
                                    return true;
                                }

                                $orderDate = DateTime::createFromFormat($dateFormat, $context['order_date']);
                                $deliveryDate = DateTime::createFromFormat($dateFormat, $value);

                                return $deliveryDate > $orderDate;
                            },
                        ],
                    ],
                ],
            ],
        ];

        return $spec;
    }
}
