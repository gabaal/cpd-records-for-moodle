<?php
/**
 * Standalone structural checks for the Stage 2 Moodle plugin files.
 *
 * Run: php tools/test-stage2.php
 */

declare(strict_types=1);

$root = __DIR__ . '/../cpdrecords';
$passed = 0;
$failed = 0;

/**
 * Check a Stage 2 structural assertion.
 *
 * @param string $label Test label.
 * @param bool $condition Condition.
 */
function stage2_check(string $label, bool $condition): void {
    global $passed, $failed;
    if ($condition) {
        ++$passed;
        echo "PASS {$label}\n";
    } else {
        ++$failed;
        echo "FAIL {$label}\n";
    }
}

$document = new DOMDocument();
$previous = libxml_use_internal_errors(true);
$loaded = $document->load($root . '/db/install.xml');
libxml_clear_errors();
libxml_use_internal_errors($previous);
stage2_check('XMLDB install file is well formed', $loaded);

$xpath = new DOMXPath($document);
$tables = $xpath->query('//TABLES/TABLE');
stage2_check('XMLDB defines four tables', $tables->length === 4);

$tablenames = [];
foreach ($tables as $table) {
    $tablenames[] = $table->getAttribute('NAME');
}
stage2_check('Expected scheme table exists', in_array('local_cpdrecords_scheme', $tablenames, true));
stage2_check('Expected course table exists', in_array('local_cpdrecords_course', $tablenames, true));
stage2_check('Expected record table exists', in_array('local_cpdrecords_record', $tablenames, true));
stage2_check('Expected user table exists', in_array('local_cpdrecords_user', $tablenames, true));

$requiredfields = [
    'local_cpdrecords_scheme' => ['id', 'shortname', 'roles', 'categories', 'enabled'],
    'local_cpdrecords_course' => ['id', 'schemeid', 'courseid', 'hours', 'categories'],
    'local_cpdrecords_record' => ['id', 'schemeid', 'userid', 'courseid', 'activitytitle', 'dedupekey'],
    'local_cpdrecords_user' => ['id', 'schemeid', 'userid', 'rolekey', 'cyclestart'],
];
foreach ($requiredfields as $table => $fields) {
    $fieldnodes = $xpath->query('//TABLE[@NAME="' . $table . '"]/FIELDS/FIELD');
    $actual = [];
    foreach ($fieldnodes as $field) {
        $actual[] = $field->getAttribute('NAME');
    }
    stage2_check("{$table} contains required fields", array_diff($fields, $actual) === []);
}

$uniquecourse = $xpath->query('//TABLE[@NAME="local_cpdrecords_course"]/KEYS/KEY[@TYPE="unique" and @FIELDS="schemeid, courseid"]');
$uniquerecord = $xpath->query('//TABLE[@NAME="local_cpdrecords_record"]/KEYS/KEY[@TYPE="unique" and @FIELDS="dedupekey"]');
$uniqueuser = $xpath->query('//TABLE[@NAME="local_cpdrecords_user"]/KEYS/KEY[@TYPE="unique" and @FIELDS="schemeid, userid"]');
stage2_check('Course credit has scheme/course unique key', $uniquecourse->length === 1);
stage2_check('Completion record has dedupe unique key', $uniquerecord->length === 1);
stage2_check('Learner profile has scheme/user unique key', $uniqueuser->length === 1);

// Load Moodle registration files with constant stubs; no Moodle code is executed.
define('MOODLE_INTERNAL', true);
define('CONTEXT_SYSTEM', 10);
define('CONTEXT_COURSE', 50);
define('RISK_CONFIG', 1);
define('RISK_PERSONAL', 2);
define('CAP_ALLOW', 1);
define('MATURITY_ALPHA', 50);
define('MATURITY_BETA', 100);

$capabilities = [];
require $root . '/db/access.php';
stage2_check('Five capabilities registered', count($capabilities) === 5);
stage2_check('Course capability uses course context', $capabilities['local/cpdrecords:managecourse']['contextlevel'] === CONTEXT_COURSE);
stage2_check('Scheme capability is system-only', $capabilities['local/cpdrecords:managescheme']['contextlevel'] === CONTEXT_SYSTEM);

$observers = [];
require $root . '/db/events.php';
stage2_check('One completion observer registered', count($observers) === 1);
stage2_check('Observer listens to Moodle course completion', $observers[0]['eventname'] === '\core\event\course_completed');
stage2_check('Observer callback points to plugin handler', $observers[0]['callback'] === '\local_cpdrecords\observer::course_completed');

$plugin = new stdClass();
require $root . '/version.php';
stage2_check('Moodle component name is correct', $plugin->component === 'local_cpdrecords');
stage2_check('Plugin supports Moodle 4.5 or later', $plugin->requires === 2024100700);
stage2_check('Plugin is beta maturity at the release gate', $plugin->maturity === MATURITY_BETA);

require $root . '/db/upgrade.php';
stage2_check('Initial upgrade entry point returns success', xmldb_local_cpdrecords_upgrade(0) === true);

foreach (['scheme.php', 'course.php', 'lib.php', 'settings.php'] as $file) {
    stage2_check("Moodle page/callback {$file} exists", is_file($root . '/' . $file));
}
foreach (['scheme_form.php', 'course_form.php'] as $file) {
    stage2_check("Moodle form {$file} exists", is_file($root . '/classes/form/' . $file));
}

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
