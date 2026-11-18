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
 * Learner CPD setup and dashboard.
 *
 * @package    local_cpdrecords
 * @copyright  2026 CPD Records contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

use local_cpdrecords\calculator;
use local_cpdrecords\form\profile_form;
use local_cpdrecords\output\dashboard;
use local_cpdrecords\repository;
use local_cpdrecords\scheme;

require_login();
$userid = (int)optional_param('userid', $USER->id, PARAM_INT);
$own = (int)$USER->id === $userid;
$systemcontext = context_system::instance();
if (!$own) {
    require_capability('local/cpdrecords:viewall', $systemcontext);
    require_capability('local/cpdrecords:manageprofiles', $systemcontext);
}
$targetuser = $own ? $USER : core_user::get_user($userid);

$pageparams = $own ? [] : ['userid' => $userid];
$pageurl = new moodle_url('/local/cpdrecords/my.php', $pageparams);
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('my');
$PAGE->set_url($pageurl);
$PAGE->set_title(get_string('mycpdrecord', 'local_cpdrecords'));
$PAGE->set_heading(get_string('mycpdrecord', 'local_cpdrecords'));
$PAGE->requires->css('/local/cpdrecords/styles.css');

$scheme = repository::get_active_scheme();
if (!$scheme) {
    throw new moodle_exception('noactivescheme', 'local_cpdrecords');
}
$profile = repository::get_user_scheme($scheme['id'], $userid);
$form = new profile_form($pageurl->out(false), ['scheme' => $scheme]);
$formdata = (object)[
    'rolekey' => $profile ? $profile->rolekey : '',
    'cyclestart' => $profile ? $profile->cyclestart : '',
];
$form->set_data($formdata);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/my/'));
}

if ($data = $form->get_data()) {
    repository::save_user_scheme(
        $scheme['id'],
        $userid,
        $data->rolekey,
        trim($data->cyclestart)
    );
    \core\notification::success(get_string('cpdprofilesaved', 'local_cpdrecords'));
    redirect($pageurl);
}

echo $OUTPUT->header();
if (!$own) {
    echo $OUTPUT->heading(fullname($targetuser), 3);
}

$validprofile = $profile
    && scheme::role($scheme, $profile->rolekey)
    && calculator::is_valid_date($profile->cyclestart);

if (!$validprofile) {
    echo $OUTPUT->box(
        get_string('setupcpdintro', 'local_cpdrecords', s($scheme['name'])),
        'generalbox'
    );
    $form->display();
} else {
    $records = repository::get_records_for_user($scheme['id'], $userid);
    $timezone = !empty($CFG->timezone) && $CFG->timezone !== '99' ? $CFG->timezone : date_default_timezone_get();
    $asof = new DateTimeImmutable('today', new DateTimeZone($timezone));
    $summary = calculator::summarise(
        $records,
        $scheme,
        $profile->rolekey,
        $profile->cyclestart,
        $asof
    );
    $exportparams = ['sesskey' => sesskey()];
    if ($own) {
        $exportparams['scope'] = 'own';
    } else {
        $exportparams['scope'] = 'user';
        $exportparams['userid'] = $userid;
    }
    $exporturl = new moodle_url('/local/cpdrecords/export.php', $exportparams);
    $dashboard = new dashboard($scheme, $summary, $exporturl);
    echo $OUTPUT->render_from_template(
        'local_cpdrecords/dashboard',
        $dashboard->export_for_template($OUTPUT)
    );
}

echo $OUTPUT->footer();
