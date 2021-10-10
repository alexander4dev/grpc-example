<?php

declare(strict_types=1);

use App\Command\GenerateSystemdUnitFile;
use App\Command\SuppliersCacheRefresh;
use App\Command\OffersCacheRefresh;

return
[
    /**
     * The application name
     *
     * @var string
     */
    'name' => 'suppliers',

    /**
     * The application commands
     *
     * @var array
     */
    'commands' => [
        GenerateSystemdUnitFile::class,
        SuppliersCacheRefresh::class,
        OffersCacheRefresh::class,
    ],

    'delivery_estimation_max_days_interval' => 365,
];
