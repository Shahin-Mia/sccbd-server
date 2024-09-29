<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repository\ProductRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProductIndex
{

    public function __construct(private ProductRepository $repository) {}

    public function __invoke(Request $request, Response $response): Response
    {

        $data = $this->repository->getAll();

        $body = json_encode($data);

        $response->getBody()->write($body);

        return $response;
    }
}
