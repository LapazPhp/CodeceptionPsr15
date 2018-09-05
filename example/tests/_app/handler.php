<?php

use Aura\Di\Container;

/** @var Container $di */
$di = require __DIR__ . '/../../config/container.php';

return $di->get('rootRequestHandler');
