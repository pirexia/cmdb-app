<?php
// cron/deactivate_inactive_users.php

/**
 * Este script está diseñado para ser ejecutado por un cron job del servidor.
 * Su propósito es desactivar usuarios locales que han estado inactivos
 * por un período prolongado (ej. 180 días).
 */

require __DIR__ . '/../vendor/autoload.php';

// Inicializar la aplicación Slim para tener acceso al contenedor de dependencias
$app = require __DIR__ . '/../app/app.php';
$container = $app->getContainer();

/** @var \Psr\Log\LoggerInterface $logger */
$logger = $container->get(\Psr\Log\LoggerInterface::class);

/** @var \App\Services\AuthService $authService */
$authService = $container->get(\App\Services\AuthService::class);

echo "Iniciando tarea de desactivación de usuarios inactivos...\n";
$logger->info("CRON JOB: Iniciando tarea de desactivación de usuarios inactivos.");

$result = $authService->deactivateInactiveUsers();

$summary = sprintf("Tarea finalizada. Usuarios desactivados: %d, Fallos: %d.", $result['deactivated_count'], $result['failed_count']);
echo $summary . "\n";
$logger->info("CRON JOB: " . $summary);