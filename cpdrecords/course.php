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
 * Assign CPD credit to a course.
 *
 * @package    local_cpdrecords
 * @copyright  2026 CPD Records contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

use local_cpdrecords\form\course_form;
use local_cpdrecords\repository;

$courseid = required_param('id', PARAM_INT);
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('local/cpdrecords:managecourse', $context);

$scheme = repository::get_active_scheme();
if (!$scheme) {
    throw new moodle_exception('noactivescheme', 'local_cpdrecords');
}

$PAGE->set_course($course);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_url(new moodle_url('/local/cpdrecords/course.php', ['id' => $courseid]));
$PAGE->set_title(get_string('coursecredit', 'local_cpdrecords'));
$PAGE->set_heading($course->fullname);

$form = new course_form($PAGE->url->out(false), ['scheme' => $scheme, 'course' => $course]);
$credit = repository::get_course_credit($courseid, $scheme['id']);
$formdata = (object)[
    'courseid' => $courseid,
    'hours' => $credit ? $credit->hours : 0,
];
foreach (array_keys($scheme['categories']) as $key) {
    $formdata->{$key} = $credit && in_array($key, $credit->categories, true) ? 1 : 0;
}
$form->set_data($formdata);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

if ($data = $form->get_data()) {
    $categories = course_form::selected_categories($data, $scheme);
    repository::save_course_credit(
        $courseid,
        $scheme['id'],
        (float)$data->hours,
        $categories,
        (int)$USER->id
    );
    \core\notification::success(get_string('coursecreditsaved', 'local_cpdrecords'));
    if ((float)$data->hours > 0 && $scheme['categories'] !== [] && $categories === []) {
        \core\notification::warning(get_string('nocategorywarning', 'local_cpdrecords'));
    }
    redirect(new moodle_url('/local/cpdrecords/course.php', ['id' => $courseid]));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('coursecredit', 'local_cpdrecords'), 2);
$form->display();
echo $OUTPUT->footer();
