<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Repository\UserRepository;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpNotFoundException;
use Slim\Routing\RouteContext;

class GetUser
{
    public function __construct(private UserRepository $repository) {}

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $context = RouteContext::fromRequest($request);
        $route = $context->getRoute();
        $id = $route->getArgument("id");

        $product = $this->repository->getById((int) $id);

        if ($product === false) {
            throw new HttpNotFoundException($request, "user not found");
        }

        $request = $request->withAttribute("user", $product);

        return $handler->handle($request);
    }
}
