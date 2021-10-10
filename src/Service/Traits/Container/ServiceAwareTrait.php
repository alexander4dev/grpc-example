<?php

declare(strict_types=1);

namespace App\Service\Traits\Container;

use App\Service\DeliveryService;
use Psr\Container\ContainerInterface;

trait ServiceAwareTrait
{
    /**
     * @return DeliveryService
     */
    protected function getDeliveryService(): DeliveryService
    {
        return $this->getService(DeliveryService::class);
    }

    /**
     * @param string $id
     * @return mixed
     */
    protected function getService(string $id)
    {
        /* @var $container ContainerInterface */
        $container = $this->getContainer();

        return $container->get($id);
    }
}
