<?php

declare(strict_types=1);

namespace App\Http\Controller\Supplier;

use App\Autorus\Validator\IsInt;
use App\Autorus\Validator\IsString;
use App\Database\Entity\WorkingSchedule;
use App\Http\Controller\AbstractController;
use DateTime;
use Zend\Validator\Between;
use Zend\Validator\Callback;
use Zend\Validator\Date;

abstract class WorkingScheduleEndpoint extends AbstractController
{
    /**
     * @return array
     */
    protected function getInputFilterSpecification(): array
    {
        $timeFormat = '!' . WorkingSchedule::getTimeFormat();

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
            'time_from' => [
                'name' => 'time_from',
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
            'time_to' => [
                'name' => 'time_to',
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
                        'break_chain_on_failure' => true,
                    ],
                    [
                        'name' => Callback::class,
                        'options' => [
                            'messages' => [
                                Callback::INVALID_VALUE => 'The "time_to" must be greater than the "time_from"',
                            ],
                            'callback' => function(string $value, array $context) use($timeFormat): bool {
                                $timeFrom = DateTime::createFromFormat($timeFormat, $context['time_from']);
                                $timeTo = DateTime::createFromFormat($timeFormat, $value);

                                return $timeTo > $timeFrom;
                            },
                        ],
                    ],
                ],
            ],
        ];

        return $spec;
    }
}
