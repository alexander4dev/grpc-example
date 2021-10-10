<?php

declare(strict_types=1);

namespace App\Autorus\Validator;

use Zend\Validator\AbstractValidator;

class IsString extends AbstractValidator
{
    const NOT_STRING = 'notString';

    protected $messageTemplates = [
        self::NOT_STRING => 'The input must be string',
    ];

    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        if (!is_string($value)) {
            $this->error(self::NOT_STRING);
           
            return false;
        }

        return true;
    }
}
