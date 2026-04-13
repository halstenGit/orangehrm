#!/usr/bin/env php
<?php
/**
 * One-time script to seed pt_BR translations into existing OrangeHRM install.
 * Run via: php bin/seed-pt-br.php
 */

require_once __DIR__ . '/../src/vendor/autoload.php';
require_once __DIR__ . '/../lib/confs/Conf.php';

use Symfony\Component\Yaml\Yaml;

$conf = new Conf();
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $conf->getDbHost(), $conf->getDbPort(), $conf->getDbName());
$pdo = new PDO($dsn, $conf->getDbUser(), $conf->getDbPass());
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$langCode = 'pt_BR';

// Get pt_BR language ID
$stmt = $pdo->prepare('SELECT id FROM ohrm_i18n_language WHERE code = ?');
$stmt->execute([$langCode]);
$langId = $stmt->fetchColumn();

if (!$langId) {
    echo "Language pt_BR not found in database.\n";
    exit(1);
}

// Mark pt_BR as added + enabled
$pdo->prepare('UPDATE ohrm_i18n_language SET added = 1, enabled = 1 WHERE id = ?')->execute([$langId]);
echo "pt_BR language enabled (ID: $langId)\n\n";

$migrationDirs = [
    'V5_0_0', 'V5_1_0', 'V5_2_0', 'V5_3_0',
    'V5_4_0', 'V5_5_0', 'V5_6_0', 'V5_7_0', 'V5_8_0',
];

$insertStmt = $pdo->prepare(
    'INSERT IGNORE INTO ohrm_i18n_translate (lang_string_id, language_id, value) VALUES (?, ?, ?)'
);

$lookupStmt = $pdo->prepare(
    'SELECT ls.id FROM ohrm_i18n_lang_string ls
     INNER JOIN ohrm_i18n_group g ON ls.group_id = g.id
     WHERE ls.unit_id = ? AND g.name = ?'
);

$total = 0;
$inserted = 0;
$skipped = 0;

foreach ($migrationDirs as $version) {
    $file = __DIR__ . "/../installer/Migration/$version/translation/pt_BR.yaml";
    if (!file_exists($file)) {
        continue;
    }

    $yml = Yaml::parseFile($file);
    $translations = $yml['translations'] ?? [];
    $count = count($translations);
    $vInserted = 0;

    foreach ($translations as $t) {
        $total++;
        $lookupStmt->execute([$t['unitId'], $t['group']]);
        $langStringId = $lookupStmt->fetchColumn();

        if (!$langStringId) {
            $skipped++;
            continue;
        }

        $insertStmt->execute([$langStringId, $langId, $t['target']]);
        if ($insertStmt->rowCount() > 0) {
            $vInserted++;
        }
    }
    $inserted += $vInserted;
    echo "  $version: $count strings, $vInserted inserted\n";
}

echo "\nDone: $total total, $inserted inserted, $skipped skipped (not found in DB)\n";
echo "\nNow go to: Admin > Configuration > Localization > Language > Portuguese (Brazil)\n";
