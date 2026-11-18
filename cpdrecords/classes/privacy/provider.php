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
 * Privacy subsystem provider.
 *
 * @package    local_cpdrecords
 * @copyright  2026 CPD Records contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cpdrecords\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use local_cpdrecords\repository;
use local_cpdrecords\scheme;

/**
 * Describes, exports and deletes CPD personal data.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describe personal data stored by the plugin.
     *
     * @param collection $items Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $items): collection {
        $items->add_database_table('local_cpdrecords_user', [
            'userid' => 'privacy:metadata:user:userid',
            'schemeid' => 'privacy:metadata:user:schemeid',
            'rolekey' => 'privacy:metadata:user:rolekey',
            'cyclestart' => 'privacy:metadata:user:cyclestart',
            'timecreated' => 'privacy:metadata:timecreated',
            'timemodified' => 'privacy:metadata:timemodified',
        ], 'privacy:metadata:user');

        $items->add_database_table('local_cpdrecords_record', [
            'userid' => 'privacy:metadata:record:userid',
            'courseid' => 'privacy:metadata:record:courseid',
            'rolekey' => 'privacy:metadata:record:rolekey',
            'activitytitle' => 'privacy:metadata:record:activitytitle',
            'completiontime' => 'privacy:metadata:record:completiontime',
            'hours' => 'privacy:metadata:record:hours',
            'categories' => 'privacy:metadata:record:categories',
            'timecreated' => 'privacy:metadata:timecreated',
        ], 'privacy:metadata:record');

        $items->add_database_table('local_cpdrecords_course', [
            'courseid' => 'privacy:metadata:course:courseid',
            'hours' => 'privacy:metadata:course:hours',
            'categories' => 'privacy:metadata:course:categories',
            'usermodified' => 'privacy:metadata:course:usermodified',
            'timecreated' => 'privacy:metadata:timecreated',
            'timemodified' => 'privacy:metadata:timemodified',
        ], 'privacy:metadata:course');

        return $items;
    }

    /**
     * Find user and course contexts containing a user's data.
     *
     * @param int $userid User ID.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();
        if ($DB->record_exists('local_cpdrecords_user', ['userid' => $userid])) {
            $contextlist->add_user_context($userid);
        }

        $params = ['contextlevel' => CONTEXT_COURSE, 'userid' => $userid];
        $contextlist->add_from_sql(
            "SELECT DISTINCT ctx.id
               FROM {local_cpdrecords_record} r
               JOIN {context} ctx ON ctx.instanceid = r.courseid AND ctx.contextlevel = :contextlevel
              WHERE r.userid = :userid",
            $params
        );
        $contextlist->add_from_sql(
            "SELECT DISTINCT ctx.id
               FROM {local_cpdrecords_course} c
               JOIN {context} ctx ON ctx.instanceid = c.courseid AND ctx.contextlevel = :contextlevel
              WHERE c.usermodified = :userid",
            $params
        );

        return $contextlist;
    }

    /**
     * Find users with plugin data in a context.
     *
     * @param userlist $userlist User list to populate.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if ($context instanceof \context_user) {
            if ($DB->record_exists('local_cpdrecords_user', ['userid' => $context->instanceid])) {
                $userlist->add_user((int)$context->instanceid);
            }
            return;
        }
        if (!$context instanceof \context_course) {
            return;
        }

        $params = ['courseid' => $context->instanceid];
        $userlist->add_from_sql(
            'userid',
            "SELECT userid FROM {local_cpdrecords_record} WHERE courseid = :courseid",
            $params
        );
        $userlist->add_from_sql(
            'userid',
            "SELECT usermodified AS userid FROM {local_cpdrecords_course}
              WHERE courseid = :courseid AND usermodified > 0",
            $params
        );
    }

    /**
     * Export a user's approved CPD profile, records and course-editor attribution.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        foreach ($contextlist->get_contextids() as $contextid) {
            $context = \context::instance_by_id($contextid);

            if ($context instanceof \context_user && (int)$context->instanceid === (int)$user->id) {
                $profiles = $DB->get_records('local_cpdrecords_user', ['userid' => $user->id]);
                foreach ($profiles as $profile) {
                    $scheme = repository::get_scheme($profile->schemeid);
                    $role = $scheme ? scheme::role($scheme, $profile->rolekey) : null;
                    $data = (object)[
                        'scheme' => $scheme ? $scheme['name'] : $profile->schemeid,
                        'role' => $role ? $role['label'] : $profile->rolekey,
                        'cyclestart' => $profile->cyclestart,
                        'timecreated' => transform::datetime($profile->timecreated),
                        'timemodified' => transform::datetime($profile->timemodified),
                    ];
                    writer::with_context($context)->export_related_data(
                        [get_string('privacyprofilepath', 'local_cpdrecords')],
                        'scheme-' . $profile->schemeid,
                        $data
                    );
                }
            }

            if (!$context instanceof \context_course) {
                continue;
            }

            $records = repository::get_filtered_records($user->id, $context->instanceid);
            foreach ($records as $record) {
                $scheme = repository::get_scheme($record->schemeid);
                $role = $scheme && !empty($record->rolekey) ? scheme::role($scheme, $record->rolekey) : null;
                $data = (object)[
                    'scheme' => $scheme ? $scheme['name'] : $record->schemeid,
                    'activity' => $record->activitytitle,
                    'completiondate' => $record->activity_date,
                    'hours' => $record->hours,
                    'categories' => $record->categorylabels,
                    'role' => $role ? $role['label'] : $record->rolekey,
                    'source' => $record->source,
                    'status' => $record->status,
                    'timecreated' => transform::datetime($record->timecreated),
                ];
                writer::with_context($context)->export_related_data(
                    [get_string('privacyrecordspath', 'local_cpdrecords')],
                    'record-' . $record->id,
                    $data
                );
            }

            $credit = $DB->get_record('local_cpdrecords_course', [
                'courseid' => $context->instanceid,
                'usermodified' => $user->id,
            ]);
            if ($credit) {
                $data = (object)[
                    'hours' => (float)$credit->hours,
                    'categories' => json_decode($credit->categories, true) ?: [],
                    'timecreated' => transform::datetime($credit->timecreated),
                    'timemodified' => transform::datetime($credit->timemodified),
                ];
                writer::with_context($context)->export_related_data(
                    [get_string('privacycoursepath', 'local_cpdrecords')],
                    'edited-course-credit',
                    $data
                );
            }
        }
    }

    /**
     * Delete all plugin personal data in a context.
     *
     * @param \context $context Context.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context instanceof \context_user) {
            $DB->delete_records('local_cpdrecords_user', ['userid' => $context->instanceid]);
            return;
        }
        if (!$context instanceof \context_course) {
            return;
        }

        $DB->delete_records('local_cpdrecords_record', ['courseid' => $context->instanceid]);
        $DB->set_field('local_cpdrecords_course', 'usermodified', 0, ['courseid' => $context->instanceid]);
    }

    /**
     * Delete one user's data in approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        foreach ($contextlist->get_contextids() as $contextid) {
            $context = \context::instance_by_id($contextid);
            if ($context instanceof \context_user && (int)$context->instanceid === (int)$user->id) {
                $DB->delete_records('local_cpdrecords_user', ['userid' => $user->id]);
                continue;
            }
            if (!$context instanceof \context_course) {
                continue;
            }

            self::delete_user_in_course($user->id, $context->instanceid);
        }
    }

    /**
     * Delete selected users' data in a context.
     *
     * @param approved_userlist $userlist Approved users.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $userids = array_map('intval', $userlist->get_userids());
        if ($context instanceof \context_user) {
            foreach ($userids as $userid) {
                if ($userid === (int)$context->instanceid) {
                    $DB->delete_records('local_cpdrecords_user', ['userid' => $userid]);
                }
            }
            return;
        }
        if (!$context instanceof \context_course) {
            return;
        }

        foreach ($userids as $userid) {
            self::delete_user_in_course($userid, $context->instanceid);
        }
    }

    /**
     * Delete a learner's course records and anonymise their editor attribution.
     *
     * @param int $userid User ID.
     * @param int $courseid Course ID.
     * @return void
     */
    private static function delete_user_in_course(int $userid, int $courseid): void {
        global $DB;

        $DB->delete_records('local_cpdrecords_record', ['userid' => $userid, 'courseid' => $courseid]);
        $DB->set_field(
            'local_cpdrecords_course',
            'usermodified',
            0,
            ['courseid' => $courseid, 'usermodified' => $userid]
        );
    }
}
