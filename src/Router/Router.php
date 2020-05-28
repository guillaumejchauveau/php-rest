<?php

namespace GECU\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Router implements MiddlewareInterface
{
    public const ROUTE_REQUEST_ATTRIBUTE = '_route';
    /**
     * The list of all the routes.
     * @var Route[]
     */
    protected $routes;

    /**
     * Router constructor.
     * @param string[] $resources A list of class names
     */
    public function __construct(iterable $resources)
    {
        $this->routes = [];
        foreach ($resources as $resourceClassName) {
            $resourceRoutes = $this->getResourceClassRoutes($resourceClassName);
            foreach ($resourceRoutes as $route) {
                $this->routes[] = $route;
            }
        }
    }

    /**
     * Computes the routes of a given resource using {@see RoutableInterface}
     * if available.
     * @param string $resourceClassName
     * @return Route[] An iterable containing {@see Route} instances
     */
    protected function getResourceClassRoutes(string $resourceClassName): iterable
    {
        if (!is_a($resourceClassName, RoutableInterface::class, true)) {
            return [];
        }
        /** @var RoutableInterface $resourceClassName */
        foreach ($resourceClassName::getRoutes() as $route) {
            if ($route instanceof Route) {
                yield $route;
            } elseif (is_array($route)) {
                yield Route::fromArray(
                  $route,
                  $resourceClassName,
                  $route['action'] ?? null
                );
            } else {
                throw new InvalidArgumentException('Invalid route');
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function process(
      ServerRequestInterface $request,
      RequestHandlerInterface $handler
    ): ResponseInterface {
        $match = null;
        foreach ($this->routes as $route) {
            $match = $route->match($request);
            if (is_array($match)) {
                $request = $request->withAttribute(self::ROUTE_REQUEST_ATTRIBUTE, $route);
                foreach ($match as $key => $value) {
                    $request = $request->withAttribute($key, $value);
                }
                break;
            }
        }

        if ($match === null) {
            throw new NotFoundHttpException('No resources corresponding');
        }
        return $handler->handle($request);
    }
}
