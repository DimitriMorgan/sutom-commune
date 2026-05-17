<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Charge .env puis .env.test pour forcer la base de test, même si Docker
// pré-injecte DATABASE_URL dans l'environnement du container.
$dotenv = new Dotenv();
$dotenv->overload(
    dirname(__DIR__).'/.env',
    dirname(__DIR__).'/.env.test',
);
