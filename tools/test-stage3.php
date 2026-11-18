<?php
/**
 * Standalone structural checks for Stage 3 dashboard and exports.
 *
 * Run: php tools/test-stage3.php
 */

declare(strict_types=1);

$root = __DIR__ . '/../cpdrecords';
$passed = 0;
$failed = 0;

/**
 * Check a Stage 3 structural assertion.
 *
 * @param string $label Test label.
 * @param bool $condition Condition.
 */
function stage3_check(string $label, bool $condition): void {
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
    'my.php',
    'export.php',
    'styles.css',
    'classes/form/profile_form.php',
    'classes/output/dashboard.php',
    'classes/csv.php',
    'templates/dashboard.mustache',
] as $file) {
    stage3_check("Stage 3 file {$file} exists", is_file($root . '/' . $file));
}

define('MOODLE_INTERNAL', true);
$string = [];
require $root . '/lang/en/local_cpdrecords.php';
foreach ([
    'mycpdrecord',
    'setupcpd',
    'professionalrole',
    'cyclestart',
    'downloadcsv',
    'exportallcsv',
    'minimumstatus_pass',
    'minimumstatus_fail',
    'minimumstatus_not_yet_assessable',
    'norecordscycle',
] as $key) {
    stage3_check("Language string {$key} exists", isset($string[$key]));
}

$lang = file_get_contents($root . '/lang/en/local_cpdrecords.php');
preg_match_all('/^\$string\[\'([^\']+)\'\]/m', $lang, $matches);
$counts = array_count_values($matches[1]);
stage3_check('Language file has no duplicate keys', array_filter($counts, static function ($count) {
    return $count > 1;
}) === []);

$template = file_get_contents($root . '/templates/dashboard.mustache');
stage3_check('Dashboard has a named test region', strpos($template, 'data-region="cpd-dashboard"') !== false);
stage3_check('Dashboard progress is accessible', strpos($template, 'role="progressbar"') !== false);
stage3_check('Dashboard table has scoped headers', strpos($template, 'scope="col"') !== false);
stage3_check('Dashboard table has a caption', strpos($template, '<caption') !== false);
stage3_check('Dashboard has a no-records state', strpos($template, '{{^hasrecords}}') !== false);
stage3_check('Dashboard does not use raw triple-brace output', strpos($template, '{{{') === false);

$export = file_get_contents($root . '/export.php');
stage3_check('Export requires login', strpos($export, 'require_login()') !== false);
stage3_check('Export requires a session key', strpos($export, 'require_sesskey()') !== false);
stage3_check('Export supports own scope', strpos($export, "\$scope === 'own'") !== false);
stage3_check('Export supports all scope', strpos($export, "\$scope === 'all'") !== false);
stage3_check('Export applies CSV safety to every row', strpos($export, "array_map([csv::class, 'safe_cell']") !== false);
stage3_check('Export uses Moodle no-cache headers API', strpos($export, "send_headers('text/csv; charset=UTF-8', false)") !== false);
stage3_check('Export does not use unavailable nocache_headers API', strpos($export, 'nocache_headers()') === false);

// Stub only the Moodle base class so the static category parser can be tested.
class moodleform {}
require_once $root . '/classes/scheme.php';
require_once $root . '/classes/form/course_form.php';
$categorydata = (object)['A' => 1, 'B' => 0, 'C' => 1, 'D' => 0];
$selected = local_cpdrecords\form\course_form::selected_categories(
    $categorydata,
    local_cpdrecords\scheme::gdc()
);
stage3_check('QuickForm direct category properties are parsed', $selected === ['A', 'C']);

$coursepage = file_get_contents($root . '/course.php');
stage3_check('Course form action preserves the course ID', strpos($coursepage, 'new course_form($PAGE->url->out(false)') !== false);
$mypage = file_get_contents($root . '/my.php');
stage3_check('Default learner ID is cast before ownership comparison', strpos($mypage, "(int)optional_param('userid'") !== false);

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
