<?php
// This file is part of CPD Records for Moodle.
//
// CPD Records for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Download CPD records as spreadsheet-safe CSV.
 *
 * @package    local_cpdrecords
 * @copyright  2026 CPD Records contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_cpdrecords\csv;
use local_cpdrecords\repository;
use local_cpdrecords\scheme;

require_login();
require_sesskey();

$scope = required_param('scope', PARAM_ALPHA);
$systemcontext = context_system::instance();
$scheme = repository::get_active_scheme();
if (!$scheme) {
    throw new moodle_exception('noactivescheme', 'local_cpdrecords');
}

$targetuser = null;
if ($scope === 'own') {
    $userid = (int)$USER->id;
    $targetuser = $USER;
    $records = repository::get_records_for_user($scheme['id'], $userid);
} else if ($scope === 'user') {
    $userid = required_param('userid', PARAM_INT);
    require_capability('local/cpdrecords:viewall', $systemcontext);
    require_capability('local/cpdrecords:exportall', $systemcontext);
    $targetuser = core_user::get_user($userid);
    $records = repository::get_records_for_user($scheme['id'], $userid);
} else if ($scope === 'all') {
    require_capability('local/cpdrecords:viewall', $systemcontext);
    require_capability('local/cpdrecords:exportall', $systemcontext);
    $filteruserid = optional_param('userid', 0, PARAM_INT);
    $filtercourseid = optional_param('courseid', 0, PARAM_INT);
    $records = repository::get_all_records($filteruserid, $filtercourseid);
} else {
    throw new moodle_exception('invalidexportscope', 'local_cpdrecords');
}

$filename = 'cpd-records-' . $scope . '-' . userdate(time(), '%Y-%m-%d', $CFG->timezone) . '.csv';
send_headers('text/csv; charset=UTF-8', false);
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
if ($output === false) {
    throw new moodle_exception('csvopenfailed', 'local_cpdrecords');
}

$headers = [
    'User ID',
    'First name',
    'Last name',
    'Email',
    'Scheme',
    'Role',
    'Activity',
    'Completion date',
    'Hours',
    'Categories',
    'Source',
    'Status',
];
fputcsv($output, $headers, ',', '"', '');

foreach ($records as $record) {
    if ($scope === 'all') {
        $targetuser = (object)[
            'id' => $record->userid,
            'firstname' => $record->firstname ?? '',
            'lastname' => $record->lastname ?? '',
            'email' => $record->email ?? '',
        ];
    }
    $role = !empty($record->rolekey) ? scheme::role($scheme, $record->rolekey) : null;
    $row = [
        $targetuser->id,
        $targetuser->firstname,
        $targetuser->lastname,
        $targetuser->email,
        $scheme['name'],
        $role ? $role['label'] : $record->rolekey,
        $record->activitytitle,
        $record->activity_date,
        $record->hours,
        $record->categorylabels,
        $record->source,
        $record->status,
    ];
    fputcsv($output, array_map([csv::class, 'safe_cell'], $row), ',', '"', '');
}

fclose($output);
exit;
