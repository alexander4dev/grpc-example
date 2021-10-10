<?php

declare(strict_types=1);

namespace App\Http\Controller\Supplier;

use App\Autorus\Validator\IsBool;
use App\Autorus\Validator\IsInt;
use App\Autorus\Validator\IsString;
use App\Http\Controller\AbstractController;
use Zend\Filter\StringTrim;
use Zend\Validator\GreaterThan;
use Zend\Validator\Uuid;

abstract class SupplierEndpoint extends AbstractController
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
            'title' => [
                'name' => 'title',
                'required' => true,
                'validators' => [
                    [
                        'name' => IsString::class,
                    ],
                ],
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
            ],
            'public_title' => [
                'name' => 'public_title',
                'required' => false,
                'allow_empty' => true,
                'validators' => [
                    [
                        'name' => IsString::class,
                    ],
                ],
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
            ],
            'is_autorus' => [
                'name' => 'is_autorus',
                'required' => true,
                'allow_empty' => true,
                'validators' => [
                    [
                        'name' => IsBool::class,
                    ],
                ],
            ],
            'delivery_accepting_minutes' => [
                'name' => 'delivery_accepting_minutes',
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
        ];

        return $spec;
    }
}
