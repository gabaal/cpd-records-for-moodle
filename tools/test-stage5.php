<?php
/**
 * Standalone release-asset checks for Stage 5.
 *
 * Run: php tools/test-stage5.php
 */

declare(strict_types=1);

$root = __DIR__ . '/..';
$passed = 0;
$failed = 0;

function stage5_check(string $label, bool $condition): void {
    global $passed, $failed;
    if ($condition) {
        ++$passed;
        echo "PASS {$label}\n";
    } else {
        ++$failed;
        echo "FAIL {$label}\n";
    }
}

foreach ([
    '.github/workflows/plugin-ci.yml',
    'MARKETPLACE.md',
    'CHANGELOG.md',
    'cpdrecords/README.md',
    'cpdrecords/LICENSE.md',
] as $file) {
    stage5_check("Release file {$file} exists", is_file($root . '/' . $file));
}

$screenshots = glob($root . '/marketplace/screenshot-*.png');
stage5_check('Five reviewed Marketplace screenshots exist', count($screenshots) === 5);
foreach ($screenshots as $screenshot) {
    $imageinfo = getimagesize($screenshot);
    stage5_check(basename($screenshot) . ' is a valid PNG', $imageinfo && $imageinfo['mime'] === 'image/png');
}

$template = file_get_contents($root . '/cpdrecords/templates/dashboard.mustache');
stage5_check('Mustache template declares its template name', strpos($template, '@template local_cpdrecords/dashboard') !== false);
stage5_check('Mustache template includes example JSON', strpos($template, 'Example context (json):') !== false);

$version = file_get_contents($root . '/cpdrecords/version.php');
stage5_check('Release version is beta.1', strpos($version, "0.1.0-beta.1") !== false);
stage5_check('Release maturity is beta', strpos($version, 'MATURITY_BETA') !== false);

$workflow = file_get_contents($root . '/.github/workflows/plugin-ci.yml');
stage5_check('CI includes Moodle 4.5', strpos($workflow, 'MOODLE_405_STABLE') !== false);
stage5_check('CI includes Moodle 5.3', strpos($workflow, 'MOODLE_503_STABLE') !== false);
stage5_check('CI uses MariaDB 11', strpos($workflow, 'mariadb:11') !== false);
stage5_check('CI includes PHPCS with zero warnings', strpos($workflow, 'phpcs --max-warnings 0') !== false);
stage5_check('CI includes PHPUnit warnings as failures', strpos($workflow, 'phpunit --fail-on-warning') !== false);

$lang = file_get_contents($root . '/cpdrecords/lang/en/local_cpdrecords.php');
preg_match_all('/^\$string\[\'([^\']+)\'\]/m', $lang, $matches);
$sortedkeys = $matches[1];
$sortedcopy = $sortedkeys;
sort($sortedcopy, SORT_STRING);
stage5_check('Moodle language strings are ordered by key', $sortedkeys === $sortedcopy);

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
