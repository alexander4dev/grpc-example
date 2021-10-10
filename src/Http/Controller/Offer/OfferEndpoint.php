<?php

declare(strict_types=1);

namespace App\Http\Controller\Offer;

use App\Autorus\Validator\IsBool;
use App\Autorus\Validator\IsInt;
use App\Http\Controller\AbstractController;
use Zend\Validator\Callback;
use Zend\Validator\GreaterThan;
use Zend\Validator\Uuid;

abstract class OfferEndpoint extends AbstractController
{
    /**
     * @return array
     */
    protected function getInputFilterSpecification(): array
    {
        $spec = [
            'uuid' => [
                'name' => 'uuid',
                'required' => true,
                'validators' => [
                    [
                        'name' => Uuid::class,
                    ],
                ],
            ],
            'supplier_from' => [
                'name' => 'supplier_from',
                'required' => true,
                'validators' => [
                    [
                        'name' => Uuid::class,
                    ],
                ],
            ],
            'supplier_to' => [
                'name' => 'supplier_to',
                'required' => true,
                'validators' => [
                    [
                        'name' => Uuid::class,
                    ],
                    [
                        'name' => Callback::class,
                        'options' => [
                            'messages' => [
                                Callback::INVALID_VALUE => 'The "supplier_from" must not be equal to the "supplier_to"',
                            ],
                            'callback' => function(string $value, array $context): bool {
                                return $value !== $context['supplier_from'];
                            },
                        ],
                    ],
                ],
            ],
            'order_initializing_minutes' => [
                'name' => 'order_initializing_minutes',
                'required' => true,
                'validators' => [
                    [
                        'name' => IsInt::class,
                        'break_chain_on_failure' => true,
                    ],
                    [
                        'name' => GreaterThan::class,
                        'options' => [
                            'min' => 0,
                            'inclusive' => true,
                        ],
                    ],
                ],
            ],
            'is_enabled' => [
                'name' => 'is_enabled',
                'required' => true,
                'allow_empty' => true,
                'validators' => [
                    [
                        'name' => IsBool::class,
                    ],
                ],
            ],
        ];

        return $spec;
    }
}
