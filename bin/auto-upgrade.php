#!/usr/bin/env php
<?php
/**
 * Idempotent migration runner for container/CI deploys.
 *
 * Reads Conf.php for DB credentials, opens a Doctrine DBAL connection,
 * injects it into OrangeHRM\Installer\Util\Connection (bypassing the
 * normal session-backed StateContainer plumbing the web installer uses),
 * then asks AppSetupUtility for the current instance.version and runs
 * any newer migrations declared in MIGRATIONS_MAP.
 *
 * Idempotent: exits 0 with no changes if already at latest. Safe to
 * call on every container start.
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
use OrangeHRM\Framework\Framework;
use OrangeHRM\Installer\Util\AppSetupUtility;
use OrangeHRM\Installer\Util\Connection as InstallerConnection;

try {
    // Bootstrap minimal framework so service container is available
    // (StateContainer::getInstance() reaches for it during migration).
    new Framework('prod', false);

    // Build a DBAL connection straight from Conf.php credentials.
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

    // Inject into the installer's singleton so AbstractMigration::getConnection()
    // returns ours instead of going through StateContainer/session.
    $reflection = new ReflectionClass(InstallerConnection::class);
    $prop = $reflection->getProperty('connection');
    $prop->setAccessible(true);
    $prop->setValue(null, $dbalConnection);

    $util = new AppSetupUtility();
    $current = $util->getCurrentProductVersionFromDatabase();
    if ($current === null) {
        fwrite(STDERR, "auto-upgrade: instance.version not set — install incomplete, skipping.\n");
        exit(0);
    }

    $map = AppSetupUtility::MIGRATIONS_MAP;
    end($map);
    $latest = key($map);

    if ($current === $latest) {
        echo "auto-upgrade: already at latest (v$current), nothing to do.\n";
        exit(0);
    }

    echo "auto-upgrade: running migrations from v$current to v$latest...\n";
    $util->runMigrations($current, $latest);
    echo "auto-upgrade: done.\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "auto-upgrade FAILED: " . $e->getMessage() . "\n");
    fwrite(STDERR, $e->getTraceAsString() . "\n");
    exit(1);
}
