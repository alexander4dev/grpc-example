<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Autorus\Traits\Container\ContainerInjectableTrait;
use App\Database\Repository\Traits\Container\RepositoryAwareTrait;
use App\Service\Traits\Container\ServiceAwareTrait;
use Arus\ApiFoundation\Http\ResponderInjection;
use Psr\Http\Server\MiddlewareInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\Factory as InputFilterFactory;

abstract class AbstractController implements MiddlewareInterface
{
    use ContainerInjectableTrait;

    use RepositoryAwareTrait;

    use ResponderInjection;

    use ServiceAwareTrait;

    /**
     * @param array $inputData
     * @return InputFilterInterface
     */
    protected function getInputFilter(array $inputData = []): InputFilterInterface
    {
        $inputFilterFactory = new InputFilterFactory();
        /* @var $inputFilter InputFilterInterface */
        $inputFilter = $inputFilterFactory->createInputFilter($this->getInputFilterSpecification());
        $inputFilter->setData($inputData);

        return $inputFilter;
    }

    /**
     * @return array
     */
    protected function getInputFilterSpecification(): array
    {
        return [];
    }

    /**
     * @param InputFilterInterface $inputFilter
     * @return ConstraintViolationListInterface
     */
    protected function createViolationList(InputFilterInterface $inputFilter): ConstraintViolationListInterface
    {
        $violationList = new ConstraintViolationList();

        foreach ($inputFilter->getMessages() as $inputName => $inputMessages) {
            foreach ($inputMessages as $inputMessage) {
                $violation = new ConstraintViolation($inputMessage, null, [], null, $inputName, $inputFilter->getValue($inputName));
                $violationList->add($violation);
            }
        }

        return $violationList;
    }
}
