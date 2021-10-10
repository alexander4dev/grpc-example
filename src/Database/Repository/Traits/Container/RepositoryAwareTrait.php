<?php

declare(strict_types=1);

namespace App\Database\Repository\Traits\Container;

use App\Autorus\Traits\Container\DoctrineAwareTrait;
use App\Database\Entity\DeliveryExtra;
use App\Database\Entity\DeliverySchedule;
use App\Database\Entity\Offer;
use App\Database\Entity\Sector;
use App\Database\Entity\SectorDeliveryInterval;
use App\Database\Entity\Supplier;
use App\Database\Entity\WorkingSchedule;
use App\Database\Entity\WorkingExtraDay;
use App\Database\Repository\DeliveryExtraRepository;
use App\Database\Repository\DeliveryScheduleRepository;
use App\Database\Repository\OfferRepository;
use App\Database\Repository\SectorDeliveryIntervalRepository;
use App\Database\Repository\SectorRepository;
use App\Database\Repository\SupplierRepository;
use App\Database\Repository\WorkingExtraDayRepository;
use App\Database\Repository\WorkingScheduleRepository;
use Doctrine\Common\Persistence\ObjectRepository;

trait RepositoryAwareTrait
{
    use DoctrineAwareTrait;

    /**
     * @return DeliveryExtraRepository
     */
    protected function getDeliveryExtraRepository(): DeliveryExtraRepository
    {
        return $this->getRepository(DeliveryExtra::class);
    }

    /**
     * @return DeliveryScheduleRepository
     */
    protected function getDeliveryScheduleRepository(): DeliveryScheduleRepository
    {
        return $this->getRepository(DeliverySchedule::class);
    }

    /**
     * @return OfferRepository
     */
    protected function getOfferRepository(): OfferRepository
    {
        return $this->getRepository(Offer::class);
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    protected function getRepository(string $className): ObjectRepository
    {
        return $this->getEntityManager()->getRepository($className);
    }

    /**
     * @return SectorDeliveryIntervalRepository
     */
    protected function getSectorDeliveryIntervalRepository(): SectorDeliveryIntervalRepository
    {
        return $this->getRepository(SectorDeliveryInterval::class);
    }

    /**
     * @return SectorRepository
     */
    protected function getSectorRepository(): SectorRepository
    {
        return $this->getRepository(Sector::class);
    }

    /**
     * @return SupplierRepository
     */
    protected function getSupplierRepository(): SupplierRepository
    {
        return $this->getRepository(Supplier::class);
    }

    /**
     * @return WorkingExtraDayRepository
     */
    protected function getWorkingExtraDayRepository(): WorkingExtraDayRepository
    {
        return $this->getRepository(WorkingExtraDay::class);
    }

    /**
     * @return WorkingScheduleRepository
     */
    protected function getWorkingScheduleRepository(): WorkingScheduleRepository
    {
        return $this->getRepository(WorkingSchedule::class);
    }
}
