<?php
/**
 * db-manager.php
 *
 * Script de línea de comandos para gestionar la base de datos.
 * Proporciona comandos para migraciones, volcados (dump) y restauraciones (import).
 *
 * Uso:
 * php db-manager.php migrate          -> Aplica nuevas migraciones.
 * php db-manager.php create <Nombre>  -> Crea un nuevo fichero de migración.
 * php db-manager.php rollback         -> Revierte la última migración.
 * php db-manager.php dump:schema      -> Exporta solo la estructura de la BBDD.
 * php db-manager.php dump:full        -> Exporta estructura y datos.
 * php db-manager.php import <fichero> -> Importa un fichero .sql a la BBDD.
 */

require_once __DIR__ . '/vendor/autoload.php';

// Cargar variables de entorno
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    $dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);
} catch (Exception $e) {
    echo "Error: No se pudo cargar el fichero .env. " . $e->getMessage() . "\n";
    exit(1);
}

$dbHost = $_ENV['DB_HOST'];
$dbName = $_ENV['DB_NAME'];
$dbUser = $_ENV['DB_USER'];
$dbPass = $_ENV['DB_PASS'];

// Definimos el entorno a usar. Coincide con el definido en phinx.php
$phinxEnv = 'production';

if ($argc < 2) {
    echo "Uso: php db-manager.php <comando> [argumentos]\n";
    echo "Comandos disponibles: migrate, create, rollback, seed:run, dump:schema, dump:full, import\n";
    exit(1);
}

$command = $argv[1];
$outputDir = __DIR__ . '/database/dumps';

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0775, true);
}

switch ($command) {
    case 'migrate':
        echo "Aplicando migraciones...\n";
        passthru('vendor/bin/phinx migrate -e ' . escapeshellarg($phinxEnv));
        break;

    case 'create':
        if (!isset($argv[2])) {
            echo "Error: Debes proporcionar un nombre para la migración.\n";
            echo "Ejemplo: php db-manager.php create AddUserLastLogin\n";
            exit(1);
        }
        $migrationName = $argv[2];
        echo "Creando nueva migración: $migrationName\n";
        passthru('vendor/bin/phinx create ' . escapeshellarg($migrationName) . ' -e ' . escapeshellarg($phinxEnv));
        break;

    case 'rollback':
        echo "Revirtiendo la última migración...\n";
        passthru('vendor/bin/phinx rollback -e ' . escapeshellarg($phinxEnv));
        break;

    case 'seed:run':
        echo "Poblando la base de datos con datos iniciales...\n";
        passthru('vendor/bin/phinx seed:run -e ' . escapeshellarg($phinxEnv));
        break;

    case 'dump:schema':
        $fileName = $outputDir . '/schema_' . date('Y-m-d_His') . '.sql';
        $dumpCommand = sprintf(
            'mysqldump --no-data -h %s -u %s -p%s %s > %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbName),
            escapeshellarg($fileName)
        );
        echo "Exportando esquema a: $fileName\n";
        passthru($dumpCommand);
        break;

    case 'dump:full':
        $fileName = $outputDir . '/full_dump_' . date('Y-m-d_His') . '.sql';
        $dumpCommand = sprintf(
            'mysqldump -h %s -u %s -p%s %s > %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbName),
            escapeshellarg($fileName)
        );
        echo "Exportando base de datos completa a: $fileName\n";
        passthru($dumpCommand);
        break;

    case 'import':
        if (!isset($argv[2])) {
            echo "Error: Debes proporcionar la ruta al fichero .sql para importar.\n";
            exit(1);
        }
        $fileToImport = $argv[2];
        if (!file_exists($fileToImport)) {
            echo "Error: El fichero '$fileToImport' no existe.\n";
            exit(1);
        }
        $importCommand = sprintf('mysql -h %s -u %s -p%s %s < %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbName),
            escapeshellarg($fileToImport));
        echo "Importando '$fileToImport' a la base de datos '$dbName'...\n";
        passthru($importCommand);
        break;

    default:
        echo "Comando '$command' no reconocido.\n";
        break;
}

echo "Operación finalizada.\n";