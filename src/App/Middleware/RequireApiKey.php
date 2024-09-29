<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Repository\UserRepository;
use Defuse\Crypto\Key;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Factory\ResponseFactory;

class RequireApiKey
{
    public function __construct(private ResponseFactory $factory, private UserRepository $repository) {}
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // $params = $request->getQueryParams();
        // if (!array_key_exists("api-key", $params)) {
        if (! $request->hasHeader("X-Api-key")) {
            $response = $this->factory->createResponse();

            $response->getBody()->write(json_encode("api-key missing!"));

            return $response->withStatus(400);
        }

        // if ($params["api-key"] !== "abc123") {
        $api_key = $request->getHeaderLine("X-Api-key");

        $api_key_hash = hash_hmac("sha256", $api_key, $_ENV["SECRET_KEY"]);

        $user = $this->repository->find("api_key_hash", $api_key_hash);

        if ($user === false) {
            $response = $this->factory->createResponse();
            $response->getBody()->write(json_encode("Invalid api key"));

            return $response->withStatus(401);
        }
        $response = $handler->handle($request);
        return $response;
    }
}
