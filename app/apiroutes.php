<?php

// RestApi
$router->addRoute("/client", 
function(Container $container): Response {
        $controller = $container->make(\App\Http\Controllers\Integration\GuzzleClientController::class);
        return $controller->callApi();
}, []);

$router->addRoute("/api/endpoint", 
function(Container $container): Response {
        $controller = $container->make(\App\Http\Controllers\Integration\EndpointServerApiController::class);
        return $controller->process();
}, [], ['method' => 'POST']);
