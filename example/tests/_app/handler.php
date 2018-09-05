<?php
/** @var \Psr\Container\ContainerInterface $di */
$di = require __DIR__ . '/../../config/container.php';

return $di->get('rootRequestHandler');
