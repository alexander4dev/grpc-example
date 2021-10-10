<?php declare(strict_types=1);

namespace App\Http\Controller;

/**
 * Import classes
 */
use Arus\ApiFoundation\Http\ResponderInjection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sunrise\Http\Header\HeaderAllow;
use Sunrise\Http\Header\HeaderCollection;
use Sunrise\Http\Router\RouterInterface;

/**
 * Import functions
 */
use function array_keys;
use function Sunrise\Http\Router\route_regex;

/**
 * @Route(id="bare", path="/", methods={"OPTIONS"})
 */
class BareController implements MiddlewareInterface
{
    use ResponderInjection;

    /**
     * @Inject
     *
     * @var \Psr\Container\ContainerInterface
     */
    private $container;

    /**
     * {@inheritDoc}
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $response = $handler->handle($request);
        $router = $this->container->get(RouterInterface::class);
        $map = [];

        foreach ($router->getRoutes() as $route) {
            $routeId = $route->getId();
            $routePath = $route->getPath();
            $routeMethods = $route->getMethods();
            $routePatterns = $route->getPatterns();
            $routeRegex = route_regex($routePath, $routePatterns);

            foreach ($routeMethods as $routeMethod) {
                $map[$routeMethod][] = [
                    'id' => $routeId,
                    'path' => $routePath,
                    'regex' => $routeRegex,
                ];
            }
        }

        $headers = new HeaderCollection([
            new HeaderAllow(...array_keys($map)),
        ]);

        $response = $headers->setToMessage($response);

        return $this->ok($response, [
            'map' => $map,
        ]);
    }
}
