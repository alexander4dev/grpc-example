<?php

declare(strict_types=1);

namespace App\Autorus\Validator;

use Zend\Validator\AbstractValidator;

class IsBool extends AbstractValidator
{
    const NOT_BOOL = 'notBool';

    protected $messageTemplates = [
        self::NOT_BOOL => 'The input must be boolean',
    ];

    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        if (!is_bool($value)) {
            $this->error(self::NOT_BOOL);
           
            return false;
        }

        return true;
    }
}
