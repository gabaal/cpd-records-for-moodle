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
 * Administrative CPD records report.
 *
 * @package    local_cpdrecords
 * @copyright  2026 CPD Records contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_cpdrecords\repository;

require_login();
$context = context_system::instance();
require_capability('local/cpdrecords:viewall', $context);

$userid = optional_param('userid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$page = max(0, optional_param('page', 0, PARAM_INT));
$perpage = 50;
$filterparams = [];
if ($userid > 0) {
    $filterparams['userid'] = $userid;
}
if ($courseid > 0) {
    $filterparams['courseid'] = $courseid;
}
$pageurl = new moodle_url('/local/cpdrecords/admin.php', $filterparams);

$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_url($pageurl);
$PAGE->set_title(get_string('cpdrecordsreport', 'local_cpdrecords'));
$PAGE->set_heading(get_string('cpdrecordsreport', 'local_cpdrecords'));

$total = repository::count_filtered_records($userid, $courseid);
$lastpage = max(0, (int)ceil($total / $perpage) - 1);
$page = min($page, $lastpage);
$records = repository::get_filtered_records($userid, $courseid, $page * $perpage, $perpage);
$filterusers = repository::get_filter_users();
$filtercourses = repository::get_filter_courses();

echo $OUTPUT->header();

echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => $pageurl->out_omit_querystring(),
    'class' => 'd-flex flex-wrap align-items-end gap-3 mb-3',
]);

$useroptions = [0 => get_string('allusers', 'local_cpdrecords')];
foreach ($filterusers as $user) {
    $useroptions[$user->id] = fullname($user) . ' (ID ' . $user->id . ')';
}
echo html_writer::label(get_string('learner', 'local_cpdrecords'), 'cpd-records-user-filter', true);
echo html_writer::select($useroptions, 'userid', $userid, false, ['id' => 'cpd-records-user-filter']);

$courseoptions = [0 => get_string('allcourses', 'local_cpdrecords')];
foreach ($filtercourses as $course) {
    $courseoptions[$course->id] = $course->fullname;
}
echo html_writer::label(get_string('course'), 'cpd-records-course-filter', true);
echo html_writer::select($courseoptions, 'courseid', $courseid, false, ['id' => 'cpd-records-course-filter']);

echo html_writer::tag('button', get_string('filter'), [
    'type' => 'submit',
    'class' => 'btn btn-secondary',
]);
echo html_writer::link(
    new moodle_url('/local/cpdrecords/admin.php'),
    get_string('clearfilters', 'local_cpdrecords'),
    ['class' => 'btn btn-link']
);
echo html_writer::end_tag('form');

if (has_capability('local/cpdrecords:exportall', $context)) {
    $exportparams = $filterparams + ['scope' => 'all', 'sesskey' => sesskey()];
    echo html_writer::link(
        new moodle_url('/local/cpdrecords/export.php', $exportparams),
        get_string('exportfilteredcsv', 'local_cpdrecords'),
        ['class' => 'btn btn-secondary mb-3']
    );
}

$countstring = $total === 1
    ? get_string('recordcountone', 'local_cpdrecords')
    : get_string('recordcountmany', 'local_cpdrecords', $total);
echo html_writer::tag('p', $countstring, ['class' => 'fw-bold']);

if ($total === 0) {
    echo html_writer::div(get_string('norecordsfound', 'local_cpdrecords'), 'alert alert-info');
} else {
    $rows = '';
    foreach ($records as $record) {
        $actions = [];
        if (!empty($record->firstname) && has_capability('local/cpdrecords:manageprofiles', $context)) {
            $actions[] = html_writer::link(
                new moodle_url('/local/cpdrecords/my.php', ['userid' => $record->userid]),
                get_string('editcpdprofile', 'local_cpdrecords')
            );
        }
        $coursecontext = !empty($record->coursename) ? context_course::instance($record->courseid) : null;
        if ($coursecontext && has_capability('local/cpdrecords:managecourse', $coursecontext)) {
            $actions[] = html_writer::link(
                new moodle_url('/local/cpdrecords/course.php', ['id' => $record->courseid]),
                get_string('editcoursecredit', 'local_cpdrecords')
            );
        }

        $rows .= html_writer::tag(
            'tr',
            html_writer::tag('td', userdate($record->completiontime, '%d %B %Y', $CFG->timezone))
            . html_writer::tag('td', s(fullname($record) ?: get_string('unknownuser', 'local_cpdrecords'))
                . '<br>' . html_writer::span(s($record->email), 'small'))
            . html_writer::tag('td', s($record->activitytitle))
            . html_writer::tag('td', format_float($record->hours, 2))
            . html_writer::tag('td', s($record->categorylabels))
            . html_writer::tag('td', s($record->source))
            . html_writer::tag('td', s($record->status))
            . html_writer::tag('td', implode('<br>', $actions))
        );
    }

    $header = html_writer::tag(
        'tr',
        html_writer::tag('th', get_string('date', 'local_cpdrecords'), ['scope' => 'col'])
        . html_writer::tag('th', get_string('learner', 'local_cpdrecords'), ['scope' => 'col'])
        . html_writer::tag('th', get_string('activity', 'local_cpdrecords'), ['scope' => 'col'])
        . html_writer::tag('th', get_string('cpdhours', 'local_cpdrecords'), ['scope' => 'col'])
        . html_writer::tag('th', get_string('categories', 'local_cpdrecords'), ['scope' => 'col'])
        . html_writer::tag('th', get_string('source', 'local_cpdrecords'), ['scope' => 'col'])
        . html_writer::tag('th', get_string('status', 'local_cpdrecords'), ['scope' => 'col'])
        . html_writer::tag('th', get_string('actions'), ['scope' => 'col'])
    );
    echo html_writer::div(
        html_writer::tag(
            'table',
            html_writer::tag('caption', get_string('cpdrecordsreport', 'local_cpdrecords'), ['class' => 'visually-hidden'])
            . html_writer::tag('thead', $header)
            . html_writer::tag('tbody', $rows),
            ['class' => 'table table-striped generaltable']
        ),
        'table-responsive'
    );
    echo $OUTPUT->paging_bar($total, $page, $perpage, $pageurl);
}

echo $OUTPUT->footer();
