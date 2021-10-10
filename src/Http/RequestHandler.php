<?php declare(strict_types=1);

namespace App\Http;

/**
 * Import classes
 */
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sunrise\Http\Factory\ResponseFactory;
use Sunrise\Http\Router\Exception\MethodNotAllowedException;
use Sunrise\Http\Router\Exception\RouteNotFoundException;

/**
 * RequestHandler
 */
class RequestHandler implements RequestHandlerInterface
{

    /**
     * The application logger
     *
     * @var \Psr\Log\LoggerInterface
     *
     * @Inject
     */
    protected $logger;

    /**
     * The application router
     *
     * @var \Sunrise\Http\Router\RouterInterface
     *
     * @Inject
     */
    protected $router;

    /**
     * Handles the given request
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        try {
            $response = $this->router->handle($request);
        } catch (RouteNotFoundException $e) {
            $response = (new ResponseFactory)->createResponse(404);
        } catch (MethodNotAllowedException $e) {
            $response = (new ResponseFactory)->createResponse(405)
            ->withHeader('allow', \implode(', ', $e->getAllowedMethods()));
        } catch (\Throwable $e) {
            $response = (new ResponseFactory)->createResponse(500);
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }

        return $response;
    }
}
