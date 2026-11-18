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
 * Moodle event observers.
 *
 * @package    local_cpdrecords
 * @copyright  2026 CPD Records contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cpdrecords;

/**
 * Converts Moodle course completion events into CPD records.
 */
class observer {
    /**
     * Handle a completed course without interrupting Moodle's completion flow.
     *
     * @param \core\event\course_completed $event Completion event.
     * @return void
     */
    public static function course_completed(\core\event\course_completed $event): void {
        try {
            repository::record_completion(
                (int)$event->courseid,
                (int)$event->relateduserid,
                (int)$event->timecreated
            );
        } catch (\Throwable $exception) {
            debugging(
                'CPD Records could not record course completion: ' . $exception->getMessage(),
                DEBUG_DEVELOPER
            );
        }
    }
}
