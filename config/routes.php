<?php

declare(strict_types=1);

use App\Middleware\GetProduct;
use App\Middleware\RequireApiKey;
use App\Controllers\ProductIndex;
use App\Controllers\Products;
use App\Controllers\Users;
use Slim\Routing\RouteCollectorProxy;

$app->post("/api/create-users",         [Users::class, "create"]);
$app->post("/api/account-activation",   [Users::class, "activateAccount"]);
$app->post("/api/login",                [Users::class, "login"]);
$app->post("/api/reset",                [Users::class, "sendMailToUser"]);
$app->post("/api/reset-password",       [Users::class, "resetPassword"]);

$app->group("/api", function (RouteCollectorProxy $group) {


    $group->get("/products", ProductIndex::class);
    $group->post("/products", [Products::class, "create"]);

    $group->group("", function (RouteCollectorProxy $group) {
        $group->get("/products/{id:[0-9]+}", [Products::class, "show"]);
        $group->patch("/products/{id:[0-9]+}", [Products::class, "update"]);
        $group->delete("/products/{id:[0-9]+}", [Products::class, "delete"]);
    })->add(GetProduct::class);
})->add(RequireApiKey::class);
