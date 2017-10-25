<?php

use Orchestra\Testbench\OrchestraServer;

// Simple server script, which pulls in large part from the framework.
// It has been adapted so we can reconstruct the application to the
// required state on each request (based on the calling Test Class)

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

// =========================================================================================
// Laravel routes through public/index.php but we need to build the application differently.
// =========================================================================================

define('LARAVEL_START', microtime(true));

require is_file(__DIR__.'/../vendor/autoload.php')
    ? __DIR__.'/../vendor/autoload.php'
    : __DIR__.'/../../../autoload.php';

// Identify the calling test class based on the content of the stash,
// use it to retrieve the built application as specificed in all
// the setup methods for the test class
$orchestraServer = new OrchestraServer($_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT']);
$originatingTestClass = $orchestraServer->getStash('class');

$app = (new $originatingTestClass())->getFreshApplicationToServe($orchestraServer);

// Process the request as per a normal Laravel request
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
