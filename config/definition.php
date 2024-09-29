<?php

use App\Database;
use App\Mailer;

return [
    Database::class => function () {
        return new Database(
            $_ENV["DATABASE_HOST"],
            $_ENV["DATABASE_NAME"],
            $_ENV["DATABASE_USERNAME"],
            $_ENV["DATABASE_PASSWORD"]
        );
    },

    Mailer::class => function () {
        return new Mailer(
            $_ENV["MAILER_HOST"],
            $_ENV["MAILER_PORT"],
            $_ENV["MAILER_USERNAME"],
            $_ENV["MAILER_PASSWORD"]
        );
    }
];
