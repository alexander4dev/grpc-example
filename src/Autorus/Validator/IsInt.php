<?php

declare(strict_types=1);

namespace App\Autorus\Validator;

use Zend\Validator\AbstractValidator;

class IsInt extends AbstractValidator
{
    const NOT_INT = 'notInt';

    protected $messageTemplates = [
        self::NOT_INT => 'The input must be integer',
    ];

    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        if (!is_int($value)) {
            $this->error(self::NOT_INT);
           
            return false;
        }

        return true;
    }
}
