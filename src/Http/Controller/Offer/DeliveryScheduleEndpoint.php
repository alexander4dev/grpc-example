<?php

declare(strict_types=1);

namespace App\Http\Controller\Offer;

use App\Autorus\Validator\IsInt;
use App\Autorus\Validator\IsString;
use App\Database\Entity\DeliverySchedule;
use App\Http\Controller\AbstractController;
use Zend\Validator\Between;
use Zend\Validator\Date;
use Zend\Validator\GreaterThan;

abstract class DeliveryScheduleEndpoint extends AbstractController
{
    /**
     * @return array
     */
    protected function getInputFilterSpecification(): array
    {
        $timeFormat = '!' . DeliverySchedule::getTimeFormat();

        $spec = [
            'day_number' => [
                'name' => 'day_number',
                'required' => true,
                'validators' => [
                    [
                        'name' => IsInt::class,
                        'break_chain_on_failure' => true,
                    ],
                    [
                        'name' => Between::class,
                        'options' => [
                            'min' => 1,
                            'max' => 7,
                        ],
                    ],
                ],
            ],
            'order_time' => [
                'name' => 'order_time',
                'required' => true,
                'validators' => [
                    [
                        'name' => IsString::class,
                        'break_chain_on_failure' => true,
                    ],
                    [
                        'name' => Date::class,
                        'options' => [
                            'format' => $timeFormat,
                        ],
                    ],
                ],
            ],
            'delivery_minutes' => [
                'name' => 'delivery_minutes',
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
