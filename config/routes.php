<?php

declare(strict_types=1);

use App\Controllers\Destinations;
use App\Middleware\GetProduct;
use App\Middleware\RequireApiKey;
use App\Controllers\ProductIndex;
use App\Controllers\Products;
use App\Controllers\Users;
use App\Middleware\GetUser;
use Slim\Routing\RouteCollectorProxy;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


$app->get("/", function (Request $reqeuest, Response $response) {
    $response->getBody()->write(json_encode("Applicaiton is running"));
    return $response;
});

$app->group("/api", function (RouteCollectorProxy $group) {
    $group->post("/create-users",            [Users::class, "create"]);
    $group->post("/account-activation",      [Users::class, "activateAccount"]);
    $group->post("/login",                   [Users::class, "login"]);
    $group->post("/reset",                   [Users::class, "sendMailToUser"]);
    $group->post("/reset-password",          [Users::class, "resetPassword"]);
    $group->get("/destinations",             [Destinations::class, "getAll"]);
    $group->get("/destinations/{id:[0-9]+}", [Destinations::class, "getById"]);

    $group->group("", function (RouteCollectorProxy $group) {
        $group->get("/users",                    [Users::class, "getAllUsers"]);
        $group->group("", function (RouteCollectorProxy $group) {
            $group->delete("/users/{id:[0-9]+}", [Users::class, "delete"]);
        })->add(GetUser::class);

        $group->post("/destinations",               [Destinations::class, "create"]);
        $group->post("/destinations/{id:[0-9]+}",   [Destinations::class, "update"]);
        $group->delete("/destinations/{id:[0-9]+}", [Destinations::class, "delete"]);

        $group->get("/products",   ProductIndex::class);
        $group->post("/products",  [Products::class, "create"]);
        $group->group("", function (RouteCollectorProxy $group) {
            $group->get("/products/{id:[0-9]+}",    [Products::class, "show"]);
            $group->patch("/products/{id:[0-9]+}",  [Products::class, "update"]);
            $group->delete("/products/{id:[0-9]+}", [Products::class, "delete"]);
        })->add(GetProduct::class);
    })->add(RequireApiKey::class);
});
