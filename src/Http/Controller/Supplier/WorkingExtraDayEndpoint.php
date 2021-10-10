<?php

declare(strict_types=1);

namespace App\Http\Controller\Supplier;

use App\Autorus\Validator\IsBool;
use App\Autorus\Validator\IsString;
use App\Database\Entity\WorkingExtraDay;
use App\Http\Controller\AbstractController;
use DateTime;
use Zend\Validator\Callback;
use Zend\Validator\Date;

abstract class WorkingExtraDayEndpoint extends AbstractController
{
    /**
     * @return array
     */
    protected function getInputFilterSpecification(): array
    {
        $dateFormat = WorkingExtraDay::getDateFormat();
        $timeFormat = '!' . WorkingExtraDay::getTimeFormat();

        $spec = [
            'date' => [
                'name' => 'date',
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
            'is_working' => [
                'name' => 'is_working',
                'required' => true,
                'allow_empty' => true,
                'validators' => [
                    [
                        'name' => IsBool::class,
                    ],
                ],
            ],
            'time_from' => [
                'name' => 'time_from',
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
                            'format' => $timeFormat,
                        ],
                    ],
                ],
            ],
            'time_to' => [
                'name' => 'time_to',
                'required' => true,
                'allow_empty' => true,
                'validators' => [
                    [
                        'name' => IsString::class,
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
                            'callback' => function(?string $value, array $context) use($timeFormat): bool {
                                if (null === $value || null === $context['time_from']) {
                                    return true;
                                }

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
