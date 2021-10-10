<?php

declare(strict_types=1);

namespace App\Service\Exception;

use App\Autorus\Exception\ServiceExceptionInterface;
use RuntimeException;

class DeliveryServiceException extends RuntimeException implements ServiceExceptionInterface
{
}
