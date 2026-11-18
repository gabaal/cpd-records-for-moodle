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
 * Local plugin callbacks.
 *
 * @package    local_cpdrecords
 * @copyright  2026 CPD Records contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add CPD credit settings to course administration.
 *
 * @param settings_navigation $settings Settings navigation.
 * @param context $context Current context.
 * @return void
 */
function local_cpdrecords_extend_settings_navigation($settings, $context) {
    if ($context->contextlevel !== CONTEXT_COURSE) {
        return;
    }
    if (!has_capability('local/cpdrecords:managecourse', $context)) {
        return;
    }

    $courseadmin = $settings->get('courseadmin');
    if (!$courseadmin) {
        return;
    }

    $courseadmin->add(
        get_string('coursecredit', 'local_cpdrecords'),
        new moodle_url('/local/cpdrecords/course.php', ['id' => $context->instanceid]),
        navigation_node::TYPE_SETTING,
        null,
        'local_cpdrecords_course'
    );
}

/**
 * Add the learner CPD dashboard to Moodle's global navigation.
 *
 * @param global_navigation $navigation Global navigation.
 * @return void
 */
function local_cpdrecords_extend_navigation($navigation) {
    if (!isloggedin() || isguestuser()) {
        return;
    }

    $navigation->add(
        get_string('mycpdrecord', 'local_cpdrecords'),
        new moodle_url('/local/cpdrecords/my.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'local_cpdrecords_my'
    );
}
