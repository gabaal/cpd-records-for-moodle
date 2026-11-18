<?php
/**
 * Standalone tests for CPD scheme validation and calculations.
 *
 * Run: php tools/test-domain.php
 */

declare(strict_types=1);

date_default_timezone_set('UTC');

require_once __DIR__ . '/../cpdrecords/classes/scheme.php';
require_once __DIR__ . '/../cpdrecords/classes/calculator.php';
require_once __DIR__ . '/../cpdrecords/classes/csv.php';

use local_cpdrecords\calculator;
use local_cpdrecords\csv;
use local_cpdrecords\scheme;

$passed = 0;
$failed = 0;

/**
 * Assert strict equality.
 *
 * @param string $label Test label.
 * @param mixed $expected Expected value.
 * @param mixed $actual Actual value.
 */
function check(string $label, $expected, $actual): void {
    global $passed, $failed;

    if ($expected === $actual) {
        ++$passed;
        echo "PASS {$label}\n";
        return;
    }

    ++$failed;
    echo "FAIL {$label}\n";
    echo '  expected: ' . var_export($expected, true) . "\n";
    echo '  actual:   ' . var_export($actual, true) . "\n";
}

/**
 * Assert approximate float equality.
 *
 * @param string $label Test label.
 * @param float $expected Expected value.
 * @param mixed $actual Actual value.
 */
function check_float(string $label, float $expected, $actual): void {
    check($label, true, is_numeric($actual) && abs($expected - (float)$actual) < 0.001);
}

/**
 * Assert that invalid configuration is rejected.
 *
 * @param string $label Test label.
 * @param callable $callback Operation expected to throw.
 */
function check_throws(string $label, callable $callback): void {
    try {
        $callback();
        check($label, true, false);
    } catch (InvalidArgumentException $exception) {
        check($label, true, true);
    }
}

/**
 * Build a record fixture.
 *
 * @param string $date Completion date.
 * @param float $hours CPD hours.
 * @param array $categories Category keys.
 * @return array
 */
function cpd_record(string $date, float $hours, array $categories = []): array {
    return [
        'activity_date' => $date,
        'hours' => $hours,
        'categories' => $categories,
    ];
}

$gdc = scheme::gdc();
$custom = scheme::custom();

// Included scheme definitions.
check('GDC dentist requires 100 hours', 100.0, $gdc['roles']['dentist']['hours']);
check('GDC hygienist requires 75 hours', 75.0, $gdc['roles']['hygienist']['hours']);
check('GDC dental nurse requires 50 hours', 50.0, $gdc['roles']['dental_nurse']['hours']);
check('GDC uses five-year cycles', 5, $gdc['cyclelength']);
check('GDC dentist starts CPD year in January', '01-01', $gdc['yearstart']);
check('GDC hygienist overrides CPD year to August', '08-01', $gdc['roles']['hygienist']['yearstart']);
check('GDC has four development outcomes', 4, count($gdc['categories']));
check('GDC minimum window is two years', 2, $gdc['minimumyears']);
check_float('GDC minimum window needs ten hours', 10.0, $gdc['minimumhours']);
check('GDC declaration offset is 28 days', 28, $gdc['declarationdays']);
check('Custom CPD uses a generic professional role', true, isset($custom['roles']['professional']));
check('Custom CPD has no mandatory categories', [], $custom['categories']);
check('Custom CPD has no consecutive-year rule', null, $custom['minimumhours']);
check('Unknown roles return null', null, scheme::role($gdc, 'unknown'));

// A representative custom profession with two roles and categories.
$custom['cyclelength'] = 3;
$custom['yearstart'] = '04-01';
$custom['roles'] = [
    'practitioner' => ['label' => 'Practitioner', 'hours' => 40],
    'supervisor' => ['label' => 'Supervisor', 'hours' => 60],
];
$custom['categories'] = [
    'TECH' => 'Technical knowledge',
    'ETH' => 'Ethics and professional practice',
];
$custom['minimumhours'] = 20;
$custom['minimumyears'] = 2;
$custom['declarationdays'] = 30;
$custom = scheme::validate($custom);

check('Custom cycle length is retained', 3, $custom['cyclelength']);
check('Custom year boundary is retained', '04-01', $custom['yearstart']);
check('Custom supervisor target is retained', 60.0, $custom['roles']['supervisor']['hours']);
check('Custom category labels are retained', 'Ethics and professional practice', $custom['categories']['ETH']);

// Invalid scheme definitions.
check_throws('Invalid scheme shortname rejected', static function () use ($custom): void {
    scheme::validate(array_merge($custom, ['shortname' => 'Bad Name']));
});
check_throws('Zero-length cycle rejected', static function () use ($custom): void {
    scheme::validate(array_merge($custom, ['cyclelength' => 0]));
});
check_throws('Impossible year boundary rejected', static function () use ($custom): void {
    scheme::validate(array_merge($custom, ['yearstart' => '02-30']));
});
$leapscheme = scheme::validate(array_merge($custom, ['yearstart' => '02-29']));
check('Leap-day year boundary accepted', '02-29', $leapscheme['yearstart']);

check_throws('Empty roles rejected', static function () use ($custom): void {
    scheme::validate(array_merge($custom, ['roles' => []]));
});
check_throws('Non-positive role target rejected', static function () use ($custom): void {
    $invalid = $custom;
    $invalid['roles']['practitioner']['hours'] = 0;
    scheme::validate($invalid);
});
check_throws('Invalid role key rejected', static function () use ($custom): void {
    $invalid = $custom;
    $invalid['roles'] = ['Practitioner' => ['label' => 'Practitioner', 'hours' => 40]];
    scheme::validate($invalid);
});
check_throws('Missing category label rejected', static function () use ($custom): void {
    $invalid = $custom;
    $invalid['categories']['EMPTY'] = '';
    scheme::validate($invalid);
});
check_throws('Partial minimum rule rejected', static function () use ($custom): void {
    scheme::validate(array_merge($custom, ['minimumyears' => null]));
});
check_throws('One-year minimum window rejected', static function () use ($custom): void {
    scheme::validate(array_merge($custom, ['minimumyears' => 1]));
});
check_throws('Excessive declaration offset rejected', static function () use ($custom): void {
    scheme::validate(array_merge($custom, ['declarationdays' => 366]));
});

// Editable role/category configuration lines.
$roletext = scheme::roles_to_text($gdc['roles']);
$categorytext = scheme::categories_to_text($gdc['categories']);
check('Role text round-trip preserves role count', count($gdc['roles']), count(scheme::roles_from_text($roletext)));
check('Role text round-trip preserves year override', '08-01', scheme::roles_from_text($roletext)['hygienist']['yearstart']);
check('Category text round-trip preserves GDC categories', $gdc['categories'], scheme::categories_from_text($categorytext));

$parsedroles = scheme::roles_from_text("member | Member | 30\nfellow | Fellow | 50 | 04-01");
check('Role lines parse custom labels', 'Fellow', $parsedroles['fellow']['label']);
check_float('Role lines parse custom hours', 30.0, $parsedroles['member']['hours']);
check('Role lines parse custom year override', '04-01', $parsedroles['fellow']['yearstart']);
check('Blank configuration lines are ignored', 1, count(scheme::categories_from_text("TECH | Technical\n\n")));
check('Optional numeric values are trimmed', 28, scheme::validate(array_merge($custom, ['declarationdays' => ' 28 ']))['declarationdays']);

check_throws('Malformed role line rejected', static function (): void {
    scheme::roles_from_text('member | Member');
});
check_throws('Duplicate role key rejected', static function (): void {
    scheme::roles_from_text("member | Member | 30\nmember | Duplicate | 40");
});
check_throws('Malformed category line rejected', static function (): void {
    scheme::categories_from_text('TECH');
});
check_throws('Duplicate category key rejected', static function (): void {
    scheme::categories_from_text("TECH | Technical\nTECH | Duplicate");
});

// Cycle boundaries.
$cycle = calculator::cycle_bounds(5, '2025-08-01', '2026-11-18');
check('Five-year cycle start', '2025-08-01', $cycle['start']->format('Y-m-d'));
check('Five-year cycle end', '2030-07-31', $cycle['end']->format('Y-m-d'));
check('Current cycle index', 0, $cycle['index']);

$cycle = calculator::cycle_bounds(5, '2025-08-01', '2031-01-01');
check('Next five-year cycle start', '2030-08-01', $cycle['start']->format('Y-m-d'));
check('Next five-year cycle end', '2035-07-31', $cycle['end']->format('Y-m-d'));
check('Next cycle index', 1, $cycle['index']);

$cycle = calculator::cycle_bounds(3, '2025-01-01', '2024-01-01');
check('Date before first cycle uses first cycle', '2025-01-01', $cycle['start']->format('Y-m-d'));

$cycle = calculator::cycle_bounds(5, '2024-02-29', '2029-02-28');
check('Leap-day first cycle end', '2029-02-28', $cycle['end']->format('Y-m-d'));
$cycle = calculator::cycle_bounds(5, '2024-02-29', '2029-03-01');
check('Leap-day next cycle start', '2029-03-01', $cycle['start']->format('Y-m-d'));

// CPD-year boundaries and declarations.
$year = calculator::cpd_year_bounds($gdc, 'dental_nurse', '2026-11-18');
check('GDC August CPD-year start', '2026-08-01', $year['start']->format('Y-m-d'));
check('GDC August CPD-year end', '2027-07-31', $year['end']->format('Y-m-d'));
$year = calculator::cpd_year_bounds($gdc, 'dental_nurse', '2026-07-31');
check('GDC July date uses previous August boundary', '2025-08-01', $year['start']->format('Y-m-d'));
$year = calculator::cpd_year_bounds($gdc, 'dentist', '2026-11-18');
check('GDC dentist CPD-year start', '2026-01-01', $year['start']->format('Y-m-d'));
$year = calculator::cpd_year_bounds($custom, 'practitioner', '2026-11-18');
check('Custom April CPD-year start', '2026-04-01', $year['start']->format('Y-m-d'));
check('Custom April CPD-year end', '2027-03-31', $year['end']->format('Y-m-d'));

$due = calculator::declaration_due($gdc, 'dentist', '2026-11-18');
check('GDC dentist declaration date', '2027-01-28', $due->format('Y-m-d'));
$due = calculator::declaration_due($gdc, 'dental_nurse', '2026-11-18');
check('GDC dental nurse declaration date', '2027-08-28', $due->format('Y-m-d'));
$due = calculator::declaration_due($custom, 'practitioner', '2026-11-18');
check('Custom declaration date', '2027-04-30', $due->format('Y-m-d'));
check('Unconfigured declaration returns null', null, calculator::declaration_due(scheme::custom(), 'professional', '2026-11-18'));

// Custom-scheme summary with categories and a completed passing window.
$records = [
    cpd_record('2025-05-10', 12, ['TECH']),
    cpd_record('2026-05-01', 8, ['ETH']),
    cpd_record('2026-06-15', 10, ['TECH', 'ETH']),
];
$summary = calculator::summarise($records, $custom, 'practitioner', '2025-04-01', '2027-06-01');

check_float('Custom summary total', 30.0, $summary['total_hours']);
check_float('Custom required hours', 40.0, $summary['required_hours']);
check_float('Custom remaining hours', 10.0, $summary['remaining_hours']);
check_float('Custom progress percent', 75.0, $summary['percent']);
check_float('Custom technical category hours', 22.0, $summary['category_hours']['TECH']);
check_float('Custom ethics category hours', 18.0, $summary['category_hours']['ETH']);
check('Custom summary record count', 3, $summary['record_count']);
check('Custom completed minimum passes', 'pass', $summary['minimum_status']);
check('Custom minimum pass boolean', true, $summary['minimum_pass']);
check('Custom minimum is assessable', true, $summary['minimum_assessable']);
check('Custom passing window has no consecutive-zero flag', false, $summary['consecutive_zero']);
check('Custom cycle start in summary', '2025-04-01', $summary['cycle_start']);
check('Custom cycle end in summary', '2028-03-31', $summary['cycle_end']);
check('Custom declaration in summary', '2028-04-30', $summary['declaration_due']);
check('Custom window hours', 30.0, $summary['windows'][0]['hours']);

// GDC summary uses the same engine and outcome categories.
$records = [
    cpd_record('2025-01-10', 12, ['A', 'C']),
    cpd_record('2026-02-01', 8, ['B']),
    cpd_record('2026-03-15', 10, ['C', 'D']),
];
$summary = calculator::summarise($records, $gdc, 'dentist', '2025-01-01', '2027-06-01');
check_float('GDC summary total', 30.0, $summary['total_hours']);
check_float('GDC outcome A hours', 12.0, $summary['category_hours']['A']);
check_float('GDC outcome B hours', 8.0, $summary['category_hours']['B']);
check_float('GDC outcome C hours', 22.0, $summary['category_hours']['C']);
check_float('GDC outcome D hours', 10.0, $summary['category_hours']['D']);
check('GDC completed minimum passes', 'pass', $summary['minimum_status']);
check('GDC cycle end in summary', '2029-12-31', $summary['cycle_end']);
check('GDC declaration in summary', '2028-01-28', $summary['declaration_due']);

// Incomplete and failing consecutive-year windows.
$records = [
    cpd_record('2025-05-01', 5),
    cpd_record('2026-05-01', 4),
];
$summary = calculator::summarise($records, $custom, 'practitioner', '2025-04-01', '2026-12-01');
check('In-progress minimum is not judged', 'not_yet_assessable', $summary['minimum_status']);
check('In-progress minimum pass is null', null, $summary['minimum_pass']);
check('In-progress minimum is not assessable', false, $summary['minimum_assessable']);
check_float('In-progress window hours', 9.0, $summary['windows'][0]['hours']);

$summary = calculator::summarise($records, $custom, 'practitioner', '2025-04-01', '2027-04-01');
check('Completed low-hours minimum fails', 'fail', $summary['minimum_status']);
check('Failed minimum pass boolean', false, $summary['minimum_pass']);

$summary = calculator::summarise([], $custom, 'practitioner', '2025-04-01', '2027-04-01');
check('Empty completed years fail minimum', 'fail', $summary['minimum_status']);
check('Empty completed years flag consecutive zero', true, $summary['consecutive_zero']);
check('Empty summary has no records', 0, $summary['record_count']);

// No rule, out-of-cycle exclusion and associative category snapshots.
$norule = scheme::custom();
$summary = calculator::summarise([], $norule, 'professional', '2025-01-01', '2026-06-01');
check('Unconfigured minimum is not applicable', 'not_applicable', $summary['minimum_status']);
check('Unconfigured minimum has no windows', [], $summary['windows']);

$records = [
    cpd_record('2024-12-31', 50, ['TECH']),
    cpd_record('2025-06-01', 10, ['TECH']),
];
$summary = calculator::summarise($records, $custom, 'practitioner', '2025-04-01', '2026-12-01');
check_float('Out-of-cycle hours excluded', 10.0, $summary['total_hours']);
check('Out-of-cycle record excluded', 1, $summary['record_count']);

$records = [[
    'activity_date' => '2025-06-01',
    'hours' => 3.5,
    'categories' => ['TECH' => 'Technical knowledge', 'ETH' => ''],
]];
$summary = calculator::summarise($records, $custom, 'practitioner', '2025-04-01', '2026-06-01');
check_float('Associative category snapshot counted', 3.5, $summary['category_hours']['TECH']);
check_float('Empty associative category not counted', 0.0, $summary['category_hours']['ETH']);

// Validation and invalid identities.
check('Valid date accepted', true, calculator::is_valid_date('2026-11-18'));
check('Impossible date rejected', false, calculator::is_valid_date('2026-02-30'));
check('Invalid date format rejected', false, calculator::is_valid_date('18/11/2026'));
check('Empty date rejected', false, calculator::is_valid_date(''));
check('Date object accepted', true, calculator::is_valid_date(new DateTimeImmutable('2026-11-18')));
check('Unknown role summary returns null', null, calculator::summarise([], $custom, 'unknown', '2025-01-01', '2026-01-01'));
check('Invalid cycle-start summary returns null', null, calculator::summarise([], $custom, 'practitioner', 'bad', '2026-01-01'));
check_throws('Misaligned cycle rejected when minimum rule is enabled', static function () use ($custom): void {
    calculator::summarise([], $custom, 'practitioner', '2025-01-01', '2026-01-01');
});

// Spreadsheet formula protection.
check('Plain CSV cell unchanged', 'Normal text', csv::safe_cell('Normal text'));
check('Empty CSV cell unchanged', '', csv::safe_cell(''));
check('Numeric CSV cell preserved', '6.25', csv::safe_cell(6.25));
check('Equals formula neutralised', "'=SUM(1,1)", csv::safe_cell('=SUM(1,1)'));
check('Plus formula neutralised', "'+1", csv::safe_cell('+1'));
check('Minus formula neutralised', "'-1", csv::safe_cell('-1'));
check('At formula neutralised', "'@cmd", csv::safe_cell('@cmd'));
check('Tab-prefixed formula neutralised', "'\t=1", csv::safe_cell("\t=1"));
check('CR-prefixed formula neutralised', "'\r=1", csv::safe_cell("\r=1"));

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
