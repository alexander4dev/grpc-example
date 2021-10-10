<?php declare(strict_types=1);

use App\Autorus\OffersCacheRedis;
use App\Autorus\SuppliersCacheRedis;
use Arus\Middleware\DoctrinePersistentEntityManagerMiddleware;
use Arus\Middleware\PayloadValidationMiddleware;
use Arus\Doctrine\RepositoryFactory\InjectableRepositoryFactory;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ArrayCache as DoctineArrayCache;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Sunrise\Http\Factory\ResponseFactory;
use Sunrise\Http\Factory\ServerRequestFactory;
use Sunrise\Http\Factory\StreamFactory;
use Sunrise\Http\Factory\UriFactory;
use Sunrise\Http\Router\AnnotationRouteLoader;
use Sunrise\Http\Router\Router;
use Sunrise\Http\Router\RouterInterface;

return
[
    /**
     * Monolog
     *
     * @link https://github.com/Seldaek/monolog
     */
    LoggerInterface::class => function () : LoggerInterface {
        $logger = new Monolog\Logger(gethostname());

        $handler = new Monolog\Handler\ErrorLogHandler();
        $formatter = new Monolog\Formatter\LogstashFormatter($logger->getName());
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);

        return $logger;
    },

    /**
     * Sunrise HTTP Router
     *
     * @link https://github.com/sunrise-php/http-router
     * @link https://github.com/sunrise-php/http-router-annotations-support
     * @link https://github.com/middlewares/utils/pull/11
     */
    RouterInterface::class => function (ContainerInterface $container) : RouterInterface {
        Middlewares\Utils\Factory::setResponseFactory(new ResponseFactory);
        Middlewares\Utils\Factory::setServerRequestFactory(new ServerRequestFactory);
        Middlewares\Utils\Factory::setStreamFactory(new StreamFactory);
        Middlewares\Utils\Factory::setUriFactory(new UriFactory);

        $router = new Router();

        // See: https://github.com/middlewares/encoder
        $router->addMiddleware(new Middlewares\GzipEncoder);

        // See: https://github.com/middlewares/payload
        $router->addMiddleware(new Middlewares\JsonPayload);

        // See: http://gitlab.voshod.local/web/payload-validation-middleware/tree/master
        $router->addMiddleware(new PayloadValidationMiddleware(__DIR__ . '/../http/schema', new ResponseFactory()));

        // See: http://gitlab.voshod.local/web/doctrine-persistent-entity-manager-middleware/tree/master
        $router->addMiddleware($container->get(DoctrinePersistentEntityManagerMiddleware::class));

        $loader = new AnnotationRouteLoader();
        $routes = $loader->load(__DIR__ . '/../src/Http/Controller', [$container, 'get']);
        $router->addRoutes($routes);

        return $router;
    },

    /**
     * Doctrine Entity Manager
     *
     * @link https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/advanced-configuration.html
     */
    EntityManagerInterface::class => function (ContainerInterface $container) : EntityManagerInterface {
        Doctrine\Common\Annotations\AnnotationRegistry::registerLoader('class_exists');

        $dest = [__DIR__ . '/../src/Database/Entity'];

        $debug = in_array($container->get('env'), ['local', 'development', 'test']);

        $config = Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration($dest, $debug, null, new DoctineArrayCache(), false);

        $config->setRepositoryFactory(new InjectableRepositoryFactory($container));

        // Ignore openapi annotations
        AnnotationReader::addGlobalIgnoredNamespace('OA');

        return EntityManager::create($container->get('database'), $config);
    },

    SuppliersCacheRedis::class => function(ContainerInterface $container): SuppliersCacheRedis {
        $redisConfig = $container->get('redis')['suppliers_cache'];
        $redis = new SuppliersCacheRedis(
            $redisConfig['host'],
            $redisConfig['port'],
            $redisConfig['password'],
            $redisConfig['db']
        );

        return $redis;
    },

    OffersCacheRedis::class => function(ContainerInterface $container): OffersCacheRedis {
        $redisConfig = $container->get('redis')['offers_cache'];
        $redis = new OffersCacheRedis(
            $redisConfig['host'],
            $redisConfig['port'],
            $redisConfig['password'],
            $redisConfig['db']
        );

        return $redis;
    },
];
