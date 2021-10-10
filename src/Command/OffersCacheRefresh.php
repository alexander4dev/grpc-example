<?php

declare(strict_types=1);

namespace App\Command;

use App\Autorus\Exception\RuntimeException;
use App\Database\Entity\Offer;
use App\Database\Repository\OfferRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class OffersCacheRefresh extends Command
{
    /**
     * {@inheritdoc}
     */
    protected static $defaultName = 'app:offers-cache-refresh';

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
        $this->setDescription('Cache offers to redis');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $outputStyle = new SymfonyStyle($input, $output);
        $outputStyle->title('Offers cache refreshing');

        /* @var $entityManager EntityManager */
        $entityManager = $this->container->get(EntityManagerInterface::class);
        /* @var $offerRepository OfferRepository */
        $offerRepository = $entityManager->getRepository(Offer::class);

        try {
            $offerRepository->cacheRefresh();
        } catch(RuntimeException $e) {
            $outputStyle->error(sprintf('Exception: %s', $e->getMessage()));

            return 1;
        }

        $outputStyle->success('Offers cache refresh finished');

        return 0;
    }
}
