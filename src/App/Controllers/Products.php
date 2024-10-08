<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repository\ProductRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Valitron\Validator;

class Products
{

    public function __construct(private ProductRepository $repository, private Validator $validator)
    {
        $this->validator->mapFieldsRules([
            "name" => ["required"],
            "size" => ["required", "integer", ["min", 1]]
        ]);
    }

    public function show(Request $request, Response $response): Response
    {
        $product = $request->getAttribute("product");
        $body = json_encode($product);

        $response->getBody()->write($body);

        return $response;
    }

    public function create(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        $this->validator = $this->validator->withData($body);

        if (!$this->validator->validate()) {
            $response->getBody()->write(json_encode($this->validator->errors()));
            return $response->withStatus(422);
        }

        $id = $this->repository->create($body);

        $body = json_encode(["message" => "Product was created!", "id" => $id]);

        $response->getBody()->write($body);

        return $response->withStatus(201);
    }
    public function update(Request $request, Response $response, string $id)
    {
        $body = $request->getParsedBody();

        $this->validator = $this->validator->withData($body);

        if (!$this->validator->validate()) {
            $response->getBody()->write(json_encode($this->validator->errors()));
            return $response->withStatus(422);
        }

        $rows = $this->repository->update((int) $id, $body);

        $body = json_encode(["message" => "Product was created!", "rows" => $rows]);

        $response->getBody()->write($body);

        return $response;
    }

    public function delete(Request $request, Response $response, string $id)
    {
        $rows = $this->repository->delete((int) $id);
        $response->getBody()->write(json_encode(["message" => "Product was deleted!", "rows" => $rows]));

        return $response;
    }
}
