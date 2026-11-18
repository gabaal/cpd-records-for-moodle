<?php
/**
 * Standalone structural checks for Stage 4 admin report and privacy provider.
 *
 * Run: php tools/test-stage4.php
 */

declare(strict_types=1);

$root = __DIR__ . '/../cpdrecords';
$passed = 0;
$failed = 0;

/**
 * Check a Stage 4 assertion.
 *
 * @param string $label Test label.
 * @param bool $condition Condition.
 */
function stage4_check(string $label, bool $condition): void {
    global $passed, $failed;
    if ($condition) {
        ++$passed;
        echo "PASS {$label}\n";
    } else {
        ++$failed;
        echo "FAIL {$label}\n";
    }
}

stage4_check('Admin report exists', is_file($root . '/admin.php'));
stage4_check('Privacy provider exists', is_file($root . '/classes/privacy/provider.php'));

$admin = file_get_contents($root . '/admin.php');
stage4_check('Admin report requires viewall capability', strpos($admin, "require_capability('local/cpdrecords:viewall'") !== false);
stage4_check('Admin report has learner filter', strpos($admin, "optional_param('userid'") !== false);
stage4_check('Admin report has course filter', strpos($admin, "optional_param('courseid'") !== false);
stage4_check('Admin report is paginated', strpos($admin, '$OUTPUT->paging_bar') !== false);
stage4_check('Admin report has singular wording', strpos($admin, "'recordcountone'") !== false);
stage4_check('Admin report has plural wording', strpos($admin, "'recordcountmany'") !== false);
stage4_check('Admin report checks export capability', strpos($admin, "has_capability('local/cpdrecords:exportall'") !== false);
stage4_check('Admin report checks profile-edit capability', strpos($admin, "has_capability('local/cpdrecords:manageprofiles'") !== false);
stage4_check('Admin report escapes historical activity titles', strpos($admin, 's($record->activitytitle)') !== false);

$provider = file_get_contents($root . '/classes/privacy/provider.php');
stage4_check('Provider implements metadata API', strpos($provider, '\core_privacy\local\metadata\provider') !== false);
stage4_check('Provider implements request API', strpos($provider, '\core_privacy\local\request\plugin\provider') !== false);
stage4_check('Provider implements userlist API', strpos($provider, '\core_privacy\local\request\core_userlist_provider') !== false);

foreach ([
    'get_metadata',
    'get_contexts_for_userid',
    'get_users_in_context',
    'export_user_data',
    'delete_data_for_all_users_in_context',
    'delete_data_for_user',
    'delete_data_for_users',
] as $method) {
    stage4_check("Privacy method {$method} exists", preg_match('/function\s+' . $method . '\s*\(/', $provider) === 1);
}
foreach (['local_cpdrecords_user', 'local_cpdrecords_record', 'local_cpdrecords_course'] as $table) {
    stage4_check("Privacy metadata declares {$table}", strpos($provider, "add_database_table('{$table}'") !== false);
}
stage4_check('Privacy provider exports through Moodle writer', strpos($provider, 'export_related_data') !== false);
stage4_check('Privacy provider anonymises editor attribution', strpos($provider, "'usermodified',\n            0") !== false);
stage4_check('Privacy provider handles user contexts', strpos($provider, '\context_user') !== false);
stage4_check('Privacy provider handles course contexts', strpos($provider, '\context_course') !== false);

$repository = file_get_contents($root . '/classes/repository.php');
stage4_check('Repository supports filtered counts', strpos($repository, 'function count_filtered_records') !== false);
stage4_check('Repository supports filtered pages', strpos($repository, 'function get_filtered_records') !== false);
stage4_check('Repository uses named filter parameters', strpos($repository, 'r.userid = :userid') !== false);

define('MOODLE_INTERNAL', true);
$string = [];
require $root . '/lang/en/local_cpdrecords.php';
stage4_check('Privacy metadata language strings exist', isset($string['privacy:metadata:record']));
stage4_check('Admin report language strings exist', isset($string['cpdrecordsreport'], $string['recordcountone']));

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
