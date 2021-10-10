<?php

declare(strict_types=1);

namespace App\Service;

use App\Autorus\ServiceInterface;
use App\Autorus\Traits\Container\ContainerInjectableTrait;
use App\Database\Repository\Traits\Container\RepositoryAwareTrait;
use App\Service\Traits\Container\ServiceAwareTrait;

abstract class AbstractService implements ServiceInterface
{
    use ContainerInjectableTrait;

    use RepositoryAwareTrait;

    use ServiceAwareTrait;
}
