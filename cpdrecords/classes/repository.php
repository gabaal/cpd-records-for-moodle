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
 * Database persistence for schemes, course credit and completion records.
 *
 * @package    local_cpdrecords
 * @copyright  2026 CPD Records contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cpdrecords;

/**
 * Moodle DML repository.
 */
class repository {
    /**
     * Insert or update a scheme by shortname.
     *
     * @param array $definition Scheme definition.
     * @return int Scheme ID.
     */
    public static function save_scheme(array $definition): int {
        global $DB;

        $scheme = scheme::validate($definition);
        $existing = $DB->get_record('local_cpdrecords_scheme', ['shortname' => $scheme['shortname']]);
        $now = time();
        $record = (object)[
            'shortname' => $scheme['shortname'],
            'name' => $scheme['name'],
            'description' => $scheme['description'],
            'cyclelength' => $scheme['cyclelength'],
            'yearstart' => $scheme['yearstart'],
            'roles' => self::json_encode($scheme['roles']),
            'categories' => self::json_encode($scheme['categories']),
            'minimumhours' => $scheme['minimumhours'],
            'minimumyears' => $scheme['minimumyears'],
            'declarationdays' => $scheme['declarationdays'],
            'enabled' => $scheme['enabled'],
            'timemodified' => $now,
        ];

        $transaction = $DB->start_delegated_transaction();
        if ($scheme['enabled']) {
            $DB->set_field('local_cpdrecords_scheme', 'enabled', 0, ['enabled' => 1]);
        }

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('local_cpdrecords_scheme', $record);
            $schemeid = $existing->id;
        } else {
            $record->timecreated = $now;
            $schemeid = $DB->insert_record('local_cpdrecords_scheme', $record);
        }
        $transaction->allow_commit();

        return (int)$schemeid;
    }

    /**
     * Return the active scheme, including its database ID.
     *
     * @return array|null
     */
    public static function get_active_scheme(): ?array {
        global $DB;

        $record = $DB->get_record('local_cpdrecords_scheme', ['enabled' => 1], '*', IGNORE_MULTIPLE);
        return $record ? self::scheme_from_record($record) : null;
    }

    /**
     * Return a scheme by ID.
     *
     * @param int $schemeid Scheme ID.
     * @return array|null
     */
    public static function get_scheme(int $schemeid): ?array {
        global $DB;

        $record = $DB->get_record('local_cpdrecords_scheme', ['id' => $schemeid]);
        return $record ? self::scheme_from_record($record) : null;
    }

    /**
     * Save a course's credit settings for a scheme.
     *
     * @param int $courseid Course ID.
     * @param int $schemeid Scheme ID.
     * @param float $hours CPD hours; zero removes the course configuration.
     * @param array $categories Selected category keys.
     * @param int $userid Editing user ID.
     * @return void
     */
    public static function save_course_credit(int $courseid, int $schemeid, float $hours, array $categories, int $userid): void {
        global $DB;

        if ($hours < 0 || $hours > 1000) {
            throw new \InvalidArgumentException('CPD hours must be between 0 and 1000.');
        }

        $conditions = ['schemeid' => $schemeid, 'courseid' => $courseid];
        if ($hours == 0.0) {
            $DB->delete_records('local_cpdrecords_course', $conditions);
            return;
        }

        $scheme = self::get_scheme($schemeid);
        if (!$scheme) {
            throw new \InvalidArgumentException('Unknown CPD scheme.');
        }
        $categories = self::normalise_categories($scheme, $categories);
        $existing = $DB->get_record('local_cpdrecords_course', $conditions);
        $now = time();
        $record = (object)[
            'schemeid' => $schemeid,
            'courseid' => $courseid,
            'hours' => round($hours, 2),
            'categories' => self::json_encode($categories),
            'usermodified' => $userid,
            'timemodified' => $now,
        ];

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('local_cpdrecords_course', $record);
        } else {
            $record->timecreated = $now;
            $DB->insert_record('local_cpdrecords_course', $record);
        }
    }

    /**
     * Return course credit settings with decoded category keys.
     *
     * @param int $courseid Course ID.
     * @param int $schemeid Scheme ID.
     * @return object|null
     */
    public static function get_course_credit(int $courseid, int $schemeid): ?object {
        global $DB;

        $record = $DB->get_record('local_cpdrecords_course', [
            'courseid' => $courseid,
            'schemeid' => $schemeid,
        ]);
        if (!$record) {
            return null;
        }
        $record->hours = (float)$record->hours;
        $record->categories = json_decode($record->categories, true) ?: [];
        return $record;
    }

    /**
     * Record a course completion for the active scheme.
     *
     * @param int $courseid Course ID.
     * @param int $userid Learner ID.
     * @param int $completiontime Completion timestamp.
     * @return object|null Existing/new record, or null when no credit applies.
     * @throws \dml_exception When an actual database operation fails.
     */
    public static function record_completion(int $courseid, int $userid, int $completiontime): ?object {
        global $DB;

        $scheme = self::get_active_scheme();
        if (!$scheme) {
            return null;
        }
        $credit = self::get_course_credit($courseid, $scheme['id']);
        if (!$credit || $credit->hours <= 0) {
            return null;
        }

        $dedupekey = "scheme:{$scheme['id']}:course:{$courseid}:user:{$userid}";
        $existing = $DB->get_record('local_cpdrecords_record', ['dedupekey' => $dedupekey]);
        if ($existing) {
            return $existing;
        }

        $course = get_course($courseid);
        $profile = $DB->get_record('local_cpdrecords_user', [
            'schemeid' => $scheme['id'],
            'userid' => $userid,
        ]);
        $labels = [];
        foreach ($credit->categories as $category) {
            if (isset($scheme['categories'][$category])) {
                $labels[$category] = $scheme['categories'][$category];
            }
        }

        $record = (object)[
            'schemeid' => $scheme['id'],
            'userid' => $userid,
            'courseid' => $courseid,
            'rolekey' => $profile ? $profile->rolekey : null,
            'activitytitle' => $course->fullname,
            'completiontime' => $completiontime,
            'hours' => $credit->hours,
            'categories' => self::json_encode($labels),
            'source' => 'moodle',
            'status' => 'approved',
            'dedupekey' => $dedupekey,
            'timecreated' => time(),
        ];

        try {
            $record->id = $DB->insert_record('local_cpdrecords_record', $record);
            return $record;
        } catch (\dml_exception $exception) {
            // Another request may have inserted the same unique key first.
            $existing = $DB->get_record('local_cpdrecords_record', ['dedupekey' => $dedupekey]);
            if ($existing) {
                return $existing;
            }
            throw $exception;
        }
    }

    /**
     * Save a learner's role and cycle start for a scheme.
     *
     * @param int $schemeid Scheme ID.
     * @param int $userid Learner ID.
     * @param string $rolekey Role key.
     * @param string $cyclestart Date-only Y-m-d cycle start.
     * @return void
     */
    public static function save_user_scheme(int $schemeid, int $userid, string $rolekey, string $cyclestart): void {
        global $DB;

        $scheme = self::get_scheme($schemeid);
        if (!$scheme || !scheme::role($scheme, $rolekey) || !calculator::is_valid_date($cyclestart)) {
            throw new \InvalidArgumentException('Invalid CPD role or cycle start.');
        }
        // This also enforces CPD-year alignment when a minimum rule is enabled.
        calculator::summarise([], $scheme, $rolekey, $cyclestart, $cyclestart);

        $conditions = ['schemeid' => $schemeid, 'userid' => $userid];
        $existing = $DB->get_record('local_cpdrecords_user', $conditions);
        $now = time();
        $record = (object)[
            'schemeid' => $schemeid,
            'userid' => $userid,
            'rolekey' => $rolekey,
            'cyclestart' => $cyclestart,
            'timemodified' => $now,
        ];
        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('local_cpdrecords_user', $record);
        } else {
            $record->timecreated = $now;
            $DB->insert_record('local_cpdrecords_user', $record);
        }
    }

    /**
     * Return a learner's scheme profile.
     *
     * @param int $schemeid Scheme ID.
     * @param int $userid Learner ID.
     * @return object|null
     */
    public static function get_user_scheme(int $schemeid, int $userid): ?object {
        global $DB;
        $record = $DB->get_record('local_cpdrecords_user', ['schemeid' => $schemeid, 'userid' => $userid]);
        return $record ?: null;
    }

    /**
     * Return a learner's CPD records, newest first.
     *
     * @param int $schemeid Scheme ID.
     * @param int $userid Learner ID.
     * @return array
     */
    public static function get_records_for_user(int $schemeid, int $userid): array {
        global $DB;
        $records = $DB->get_records(
            'local_cpdrecords_record',
            ['schemeid' => $schemeid, 'userid' => $userid],
            'completiontime DESC, id DESC'
        );
        return self::decode_records($records);
    }

    /**
     * Count CPD records matching optional learner/course filters.
     *
     * @param int $userid Optional learner ID.
     * @param int $courseid Optional course ID.
     * @return int
     */
    public static function count_filtered_records(int $userid = 0, int $courseid = 0): int {
        global $DB;
        [$where, $params] = self::filter_conditions($userid, $courseid);
        return $DB->count_records_sql(
            "SELECT COUNT(*) FROM {local_cpdrecords_record} r {$where}",
            $params
        );
    }

    /**
     * Return filtered CPD records with user/course display data.
     *
     * @param int $userid Optional learner ID.
     * @param int $courseid Optional course ID.
     * @param int $offset Zero-based result offset.
     * @param int $limit Maximum rows; zero means no limit.
     * @return array
     */
    public static function get_filtered_records(
        int $userid = 0,
        int $courseid = 0,
        int $offset = 0,
        int $limit = 0
    ): array {
        global $DB;
        [$where, $params] = self::filter_conditions($userid, $courseid);
        $sql = "SELECT r.*, u.firstname, u.lastname, u.email, c.fullname AS coursename
                  FROM {local_cpdrecords_record} r
             LEFT JOIN {user} u ON u.id = r.userid
             LEFT JOIN {course} c ON c.id = r.courseid
                  {$where}
              ORDER BY r.completiontime DESC, r.id DESC";
        return self::decode_records($DB->get_records_sql($sql, $params, $offset, $limit));
    }

    /**
     * Return all CPD records for export, optionally filtered.
     *
     * @param int $userid Optional learner ID.
     * @param int $courseid Optional course ID.
     * @return array
     */
    public static function get_all_records(int $userid = 0, int $courseid = 0): array {
        return self::get_filtered_records($userid, $courseid);
    }

    /**
     * Return courses currently represented in CPD records.
     *
     * @return array
     */
    public static function get_filter_courses(): array {
        global $DB;
        return $DB->get_records_sql(
            "SELECT DISTINCT c.id, c.fullname
               FROM {local_cpdrecords_record} r
               JOIN {course} c ON c.id = r.courseid
           ORDER BY c.fullname"
        );
    }

    /**
     * Return users currently represented in CPD records.
     *
     * @return array
     */
    public static function get_filter_users(): array {
        global $DB;
        return $DB->get_records_sql(
            "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email
               FROM {local_cpdrecords_record} r
               JOIN {user} u ON u.id = r.userid
           ORDER BY u.lastname, u.firstname, u.id"
        );
    }

    /**
     * Check whether a scheme already has learner records.
     *
     * @param int $schemeid Scheme ID.
     * @return bool
     */
    public static function has_scheme_records(int $schemeid): bool {
        global $DB;
        return $DB->record_exists('local_cpdrecords_record', ['schemeid' => $schemeid]);
    }

    /**
     * Build a parameterised optional record filter.
     *
     * @param int $userid Optional learner ID.
     * @param int $courseid Optional course ID.
     * @return array WHERE clause and named parameters.
     */
    private static function filter_conditions(int $userid, int $courseid): array {
        $conditions = [];
        $params = [];
        if ($userid > 0) {
            $conditions[] = 'r.userid = :userid';
            $params['userid'] = $userid;
        }
        if ($courseid > 0) {
            $conditions[] = 'r.courseid = :courseid';
            $params['courseid'] = $courseid;
        }
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        return [$where, $params];
    }

    /**
     * Decode a list of database record rows.
     *
     * @param array $records Database rows.
     * @return array
     */
    private static function decode_records(array $records): array {
        global $CFG;
        $timezone = !empty($CFG->timezone) && $CFG->timezone !== '99'
            ? $CFG->timezone
            : date_default_timezone_get();

        foreach ($records as $record) {
            $record->hours = (float)$record->hours;
            $record->categories = json_decode($record->categories, true) ?: [];
            $record->categorylabels = implode(', ', array_values($record->categories));
            $date = (new \DateTimeImmutable('@' . $record->completiontime))
                ->setTimezone(new \DateTimeZone($timezone));
            $record->activity_date = $date->format('Y-m-d');
        }
        return $records;
    }

    /**
     * Convert a database row to a validated scheme definition.
     *
     * @param object $record Database row.
     * @return array
     */
    private static function scheme_from_record(object $record): array {
        $definition = [
            'shortname' => $record->shortname,
            'name' => $record->name,
            'description' => $record->description,
            'cyclelength' => (int)$record->cyclelength,
            'yearstart' => $record->yearstart,
            'roles' => json_decode($record->roles, true),
            'categories' => json_decode($record->categories, true),
            'minimumhours' => $record->minimumhours === null ? null : (float)$record->minimumhours,
            'minimumyears' => $record->minimumyears === null ? null : (int)$record->minimumyears,
            'declarationdays' => $record->declarationdays === null ? null : (int)$record->declarationdays,
            'enabled' => (int)$record->enabled,
        ];
        $scheme = scheme::validate($definition);
        $scheme['id'] = (int)$record->id;
        return $scheme;
    }

    /**
     * Filter selected categories against a validated scheme.
     *
     * @param array $scheme Validated scheme.
     * @param array $categories Submitted category keys.
     * @return array
     */
    private static function normalise_categories(array $scheme, array $categories): array {
        $selected = [];
        foreach (array_keys($scheme['categories']) as $category) {
            if (in_array($category, $categories, true)) {
                $selected[] = $category;
            }
        }
        return $selected;
    }

    /**
     * Encode JSON consistently.
     *
     * @param mixed $value Value to encode.
     * @return string
     */
    private static function json_encode($value): string {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
