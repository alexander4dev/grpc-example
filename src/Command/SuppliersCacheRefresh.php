<?php

declare(strict_types=1);

namespace App\Command;

use App\Autorus\Exception\RuntimeException;
use App\Database\Entity\Supplier;
use App\Database\Repository\SupplierRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SuppliersCacheRefresh extends Command
{
    /**
     * {@inheritdoc}
     */
    protected static $defaultName = 'app:suppliers-cache-refresh';

    /**
     * @Inject
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Cache suppliers to redis');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $outputStyle = new SymfonyStyle($input, $output);
        $outputStyle->title('Suppliers cache refreshing');

        /* @var $entityManager EntityManager */
        $entityManager = $this->container->get(EntityManagerInterface::class);
        /* @var $supplierRepository SupplierRepository */
        $supplierRepository = $entityManager->getRepository(Supplier::class);

        try {
            $supplierRepository->cacheRefresh();
        } catch(RuntimeException $e) {
            $outputStyle->error(sprintf('Exception: %s', $e->getMessage()));

            return 1;
        }

        $outputStyle->success('Suppliers cache refresh finished');

        return 0;
    }
}
