<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

if (class_exists(Dotenv::class) && is_file(dirname(__DIR__) . '/.env.test')) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env.test');
}

if (!isset($_SERVER['APP_SECRET'])) {
    $_SERVER['APP_SECRET'] = 'test-secret';
}

if (!isset($_SERVER['KERNEL_CLASS'])) {
    $_SERVER['KERNEL_CLASS'] = Kernel::class;
}
