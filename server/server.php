<?php

require_once '../src/includes/constants.php';
require_once '../src/Tokyo/Server.php';

use Tokyo\Server;

$config = json_decode(file_get_contents(TOKYO_ROOT . '/config.json'), true);

function show404(): never
{
    http_response_code(404);
    include __DIR__ . '/../src/templates/404.php';
    exit();
}

$server = new Server($config);
$uri = $server->uriFromRequestUri($_SERVER['REQUEST_URI']);
$siteName = $server->siteNameFromHttpHost($_SERVER['HTTP_HOST']);
$sitePath = $server->sitePath($siteName);

if (in_array($siteName, ['127.0.0.1', 'localhost'])) {
    include __DIR__ . '/../src/templates/index.php';

    exit(0);
}

if (is_null($sitePath)) {
    show404();
}

$sitePath = realpath($sitePath);

/**
 * Determine if the incoming request is for a static file.
 */
$isPhpFile = in_array(pathinfo($uri, PATHINFO_EXTENSION), ['php', 'html', 'phtml']);

if ($uri !== '/' && !$isPhpFile && pathinfo($uri, PATHINFO_EXTENSION) !== '') {
    if ($staticFilePath = $server->isStaticFile($sitePath, $uri)) {
        $server->serveStaticFile($staticFilePath);
        exit(0);
    }
}

$path = $server->frontControllerPath($sitePath, $uri);

if (!file_exists($path)) {
    show404();
}

require $path;
