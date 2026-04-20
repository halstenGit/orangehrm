#!/usr/bin/env php
<?php
/**
 * Idempotent migration runner for container/CI deploys.
 *
 * Self-contained: bypasses AppSetupUtility::runMigrations (which depends
 * on the session-backed StateContainer) and instead:
 *   1. Opens a Doctrine DBAL connection from Conf.php.
 *   2. Injects it into OrangeHRM\Installer\Util\Connection via
 *      reflection so AbstractMigration::getConnection() returns it.
 *   3. Reads instance.version straight from hs_hr_config.
 *   4. Iterates AppSetupUtility::MIGRATIONS_MAP, runs any migration
 *      newer than the current version, and updates instance.version
 *      after each one.
 *
 * Idempotent: exits 0 with no action if already at latest. Exits 0 and
 * warns if Conf.php / instance.version is missing (install incomplete).
 *
 * Usage: php bin/auto-upgrade.php
 */

require_once __DIR__ . '/../src/vendor/autoload.php';

$confFile = __DIR__ . '/../lib/confs/Conf.php';
if (!file_exists($confFile)) {
    fwrite(STDERR, "auto-upgrade: Conf.php not found — instance not installed yet, skipping.\n");
    exit(0);
}
require_once $confFile;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Types;
use OrangeHRM\Installer\Util\AppSetupUtility;
use OrangeHRM\Installer\Util\Connection as InstallerConnection;
use OrangeHRM\Installer\Util\MigrationHelper;
use OrangeHRM\Installer\Util\V1\AbstractMigration;

try {
    $conf = new Conf();
    $dbalConnection = DriverManager::getConnection([
        'dbname' => $conf->getDbName(),
        'user' => $conf->getDbUser(),
        'password' => $conf->getDbPass(),
        'host' => $conf->getDbHost(),
        'port' => $conf->getDbPort(),
        'driver' => 'pdo_mysql',
        'charset' => 'utf8mb4',
    ]);
    $dbalConnection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', Types::STRING);

    $reflection = new ReflectionClass(InstallerConnection::class);
    $prop = $reflection->getProperty('connection');
    $prop->setAccessible(true);
    $prop->setValue(null, $dbalConnection);

    $instanceVersion = $dbalConnection->createQueryBuilder()
        ->select('value')
        ->from('hs_hr_config')
        ->where('name = :name')
        ->setParameter('name', 'instance.version')
        ->executeQuery()
        ->fetchOne();

    if ($instanceVersion === false || $instanceVersion === null || $instanceVersion === '') {
        fwrite(STDERR, "auto-upgrade: instance.version not set — install incomplete, skipping.\n");
        exit(0);
    }

    $map = AppSetupUtility::MIGRATIONS_MAP;
    $currentKey = null;
    foreach ($map as $key => $migrationClasses) {
        $classes = is_array($migrationClasses) ? $migrationClasses : [$migrationClasses];
        foreach ($classes as $class) {
            $m = new $class();
            if ($m instanceof AbstractMigration && $m->getVersion() === $instanceVersion) {
                $currentKey = $key;
                break 2;
            }
        }
    }

    if ($currentKey === null) {
        fwrite(STDERR, "auto-upgrade: could not match instance.version '$instanceVersion' to any entry in MIGRATIONS_MAP. Skipping to avoid wrong upgrade path.\n");
        exit(0);
    }

    end($map);
    $latestKey = key($map);

    if ($currentKey === $latestKey) {
        echo "auto-upgrade: already at latest (v$currentKey), nothing to do.\n";
        exit(0);
    }

    $keys = array_keys($map);
    $currentIdx = array_search($currentKey, $keys, true);
    $pending = array_slice($keys, $currentIdx + 1);

    echo "auto-upgrade: running migrations from v$currentKey to v$latestKey (" . count($pending) . " step(s))...\n";

    $migrationHelper = new MigrationHelper($dbalConnection);

    foreach ($pending as $key) {
        $migrationClasses = $map[$key];
        $classes = is_array($migrationClasses) ? $migrationClasses : [$migrationClasses];
        foreach ($classes as $class) {
            $migration = new $class();
            if (!($migration instanceof AbstractMigration)) {
                throw new RuntimeException("Invalid migration class `$class`");
            }
            $version = $migration->getVersion();
            echo "auto-upgrade:  -> applying v$version ($class)\n";
            $migrationHelper->logMigrationStarted($version);
            set_time_limit(0);
            $migration->up();
            $dbalConnection->createQueryBuilder()
                ->update('hs_hr_config')
                ->set('value', ':value')
                ->where('name = :name')
                ->setParameter('name', 'instance.version')
                ->setParameter('value', $version)
                ->executeStatement();
            $migrationHelper->logMigrationFinished($version);
        }
    }

    echo "auto-upgrade: done.\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "auto-upgrade FAILED: " . $e->getMessage() . "\n");
    fwrite(STDERR, $e->getTraceAsString() . "\n");
    exit(1);
}
