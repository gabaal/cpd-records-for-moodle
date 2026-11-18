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
 * Profession-neutral CPD calculations.
 *
 * @package    local_cpdrecords
 * @copyright  2026 CPD Records contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cpdrecords;

/**
 * Calculates CPD cycles, years, progress and optional minimum rules.
 */
class calculator {
    /**
     * Validate a Y-m-d date.
     *
     * @param mixed $date Date value.
     * @return bool
     */
    public static function is_valid_date($date): bool {
        return self::to_date($date) !== null;
    }

    /**
     * Find the cycle containing a date.
     *
     * @param int $cyclelength Cycle length in years.
     * @param string|\DateTimeInterface $cyclestart First day of the first cycle.
     * @param string|\DateTimeInterface|null $asof Calculation date.
     * @return array|null
     */
    public static function cycle_bounds(int $cyclelength, $cyclestart, $asof = null): ?array {
        $start = self::to_date($cyclestart);
        $asof = $asof === null ? self::now() : self::to_date($asof);

        if (!$start || !$asof || $cyclelength < 1 || $cyclelength > 10) {
            return null;
        }

        if ($asof < $start) {
            return [
                'start' => $start,
                'end' => $start->modify("+{$cyclelength} years")->modify('-1 day'),
                'index' => 0,
            ];
        }

        $diff = $start->diff($asof);
        $index = intdiv((int)$diff->y, $cyclelength);
        $cycle = $start->modify('+' . ($index * $cyclelength) . ' years');

        while ($asof < $cycle && $index > 0) {
            --$index;
            $cycle = $start->modify('+' . ($index * $cyclelength) . ' years');
        }

        return [
            'start' => $cycle,
            'end' => $cycle->modify("+{$cyclelength} years")->modify('-1 day'),
            'index' => $index,
        ];
    }

    /**
     * Find the CPD year containing a date.
     *
     * @param array $definition Scheme definition.
     * @param string $rolekey Role key.
     * @param string|\DateTimeInterface|null $asof Calculation date.
     * @return array|null
     */
    public static function cpd_year_bounds(array $definition, string $rolekey, $asof = null): ?array {
        $scheme = scheme::validate($definition);
        $role = scheme::role($scheme, $rolekey);
        $asof = $asof === null ? self::now() : self::to_date($asof);

        if (!$role || !$asof) {
            return null;
        }

        return self::year_bounds($scheme, $role, $asof);
    }

    /**
     * Return the next declaration due date, or null when not configured.
     *
     * @param array $definition Scheme definition.
     * @param string $rolekey Role key.
     * @param string|\DateTimeInterface|null $asof Calculation date.
     * @return \DateTimeImmutable|null
     */
    public static function declaration_due(array $definition, string $rolekey, $asof = null): ?\DateTimeImmutable {
        $scheme = scheme::validate($definition);
        $year = self::cpd_year_bounds($scheme, $rolekey, $asof);

        if (!$year || $scheme['declarationdays'] === null) {
            return null;
        }

        return $year['end']->modify('+' . $scheme['declarationdays'] . ' days');
    }

    /**
     * Summarise a learner's records for their current cycle.
     *
     * Records contain activity_date, hours and an optional categories array.
     *
     * @param array $records CPD record rows.
     * @param array $definition Scheme definition.
     * @param string $rolekey Learner role.
     * @param string|\DateTimeInterface $cyclestart First day of the learner's first cycle.
     * @param string|\DateTimeInterface|null $asof Calculation date.
     * @return array|null
     */
    public static function summarise(array $records, array $definition, string $rolekey, $cyclestart, $asof = null): ?array {
        $scheme = scheme::validate($definition);
        $role = scheme::role($scheme, $rolekey);
        $cycle = self::cycle_bounds($scheme['cyclelength'], $cyclestart, $asof);
        $asofdate = $asof === null ? self::now() : self::to_date($asof);

        if (!$role || !$cycle || !$asofdate) {
            return null;
        }

        $firstyear = self::year_bounds($scheme, $role, $cycle['start']);
        if ($scheme['minimumhours'] !== null && $firstyear['start']->format('Y-m-d') !== $cycle['start']->format('Y-m-d')) {
            throw new \InvalidArgumentException(
                'Cycle start must align with the CPD-year boundary when a minimum rule is enabled.'
            );
        }

        $required = (float)$role['hours'];
        $total = 0.0;
        $categoryhours = [];
        foreach (array_keys($scheme['categories']) as $category) {
            $categoryhours[$category] = 0.0;
        }
        $byyear = [];
        $incycle = [];

        foreach ($records as $record) {
            $record = (array)$record;
            $date = self::to_date($record['activity_date'] ?? null);

            if (!$date || $date < $cycle['start'] || $date > $cycle['end']) {
                continue;
            }

            $hours = isset($record['hours']) ? max(0.0, (float)$record['hours']) : 0.0;
            $incycle[] = $record;
            $total += $hours;

            foreach (array_keys($categoryhours) as $category) {
                if (self::record_has_category($record, $category)) {
                    $categoryhours[$category] += $hours;
                }
            }

            $year = self::year_bounds($scheme, $role, $date);
            $key = $year['start']->format('Y-m-d');
            if (!isset($byyear[$key])) {
                $byyear[$key] = [
                    'start' => $year['start'],
                    'end' => $year['end'],
                    'hours' => 0.0,
                ];
            }
            $byyear[$key]['hours'] += $hours;
        }

        $lastdate = $asofdate < $cycle['end'] ? $asofdate : $cycle['end'];
        $lastyear = self::year_bounds($scheme, $role, $lastdate);

        $cursor = $firstyear['start'];
        while ($cursor <= $lastyear['start']) {
            $key = $cursor->format('Y-m-d');
            if (!isset($byyear[$key])) {
                $byyear[$key] = [
                    'start' => $cursor,
                    'end' => $cursor->modify('+1 year')->modify('-1 day'),
                    'hours' => 0.0,
                ];
            }
            $cursor = $cursor->modify('+1 year');
        }

        ksort($byyear);
        $years = array_values($byyear);
        [$windows, $minimumstatus, $minimumpass, $consecutivezero] = self::minimum_rule($scheme, $years, $asofdate);
        $due = self::declaration_due($scheme, $rolekey, $asofdate);
        $assessable = $minimumstatus === 'pass' || $minimumstatus === 'fail';

        return [
            'scheme' => $scheme['shortname'],
            'scheme_name' => $scheme['name'],
            'role' => $rolekey,
            'role_label' => $role['label'],
            'required_hours' => $required,
            'total_hours' => $total,
            'remaining_hours' => max(0, $required - $total),
            'percent' => $required > 0 ? min(100, round($total / $required * 100, 1)) : 0,
            'category_hours' => $categoryhours,
            'years' => $years,
            'windows' => $windows,
            'minimum_status' => $minimumstatus,
            'minimum_pass' => $minimumpass,
            'minimum_assessable' => $assessable,
            'consecutive_zero' => $consecutivezero,
            'cycle_start' => $cycle['start']->format('Y-m-d'),
            'cycle_end' => $cycle['end']->format('Y-m-d'),
            'declaration_due' => $due ? $due->format('Y-m-d') : '',
            'record_count' => count($incycle),
            'records' => $incycle,
        ];
    }

    /**
     * Calculate an optional consecutive-year minimum.
     *
     * @param array $scheme Validated scheme.
     * @param array $years Ordered CPD years.
     * @param \DateTimeImmutable $asof Calculation date.
     * @return array Windows, status, pass value and zero-year flag.
     */
    private static function minimum_rule(array $scheme, array $years, \DateTimeImmutable $asof): array {
        if ($scheme['minimumhours'] === null) {
            return [[], 'not_applicable', null, false];
        }

        $windows = [];
        $pass = true;
        $consecutivezero = false;
        $completecount = 0;
        $windowyears = $scheme['minimumyears'];
        $yearcount = count($years);

        for ($i = 0; $i <= $yearcount - $windowyears; $i++) {
            $windowyearsdata = array_slice($years, $i, $windowyears);
            $hours = array_sum(array_column($windowyearsdata, 'hours'));
            $windowpass = $hours >= $scheme['minimumhours'];
            $lastyear = end($windowyearsdata);
            $complete = $lastyear['end'] <= $asof;
            $zerohours = count(array_filter($windowyearsdata, static function ($year) {
                return $year['hours'] <= 0;
            })) === $windowyears;

            $windows[] = [
                'start' => $windowyearsdata[0]['start']->format('Y-m-d'),
                'end' => $lastyear['end']->format('Y-m-d'),
                'hours' => $hours,
                'pass' => $windowpass,
                'complete' => $complete,
            ];

            if ($complete) {
                ++$completecount;
                if (!$windowpass) {
                    $pass = false;
                }
                if ($zerohours) {
                    $consecutivezero = true;
                }
            }
        }

        if ($completecount === 0) {
            return [$windows, 'not_yet_assessable', null, false];
        }

        return [$windows, $pass ? 'pass' : 'fail', $pass, $consecutivezero];
    }

    /**
     * Find a CPD year using an already validated scheme and role.
     *
     * @param array $scheme Validated scheme.
     * @param array $role Role definition.
     * @param \DateTimeImmutable $asof Calculation date.
     * @return array
     */
    private static function year_bounds(array $scheme, array $role, \DateTimeImmutable $asof): array {
        $yearstart = $role['yearstart'] ?? $scheme['yearstart'];
        [$month, $day] = array_map('intval', explode('-', $yearstart));
        $year = (int)$asof->format('Y');
        $candidate = \DateTimeImmutable::createFromFormat(
            '!Y-n-j',
            sprintf('%04d-%02d-%02d', $year, $month, $day)
        );

        if ($asof < $candidate) {
            --$year;
        }

        $start = \DateTimeImmutable::createFromFormat(
            '!Y-n-j',
            sprintf('%04d-%02d-%02d', $year, $month, $day)
        );

        return [
            'start' => $start,
            'end' => $start->modify('+1 year')->modify('-1 day'),
        ];
    }

    /**
     * Check a record's category list, accepting indexed or associative arrays.
     *
     * @param array $record CPD record.
     * @param string $category Category key.
     * @return bool
     */
    private static function record_has_category(array $record, string $category): bool {
        $categories = $record['categories'] ?? [];
        if (!is_array($categories)) {
            return false;
        }
        if (array_key_exists($category, $categories)) {
            return !empty($categories[$category]);
        }
        return in_array($category, $categories, true);
    }

    /**
     * Return today at midnight.
     *
     * @return \DateTimeImmutable
     */
    private static function now(): \DateTimeImmutable {
        return new \DateTimeImmutable('today');
    }

    /**
     * Convert a date-only value to midnight.
     *
     * @param mixed $value Date string or date object.
     * @return \DateTimeImmutable|null
     */
    private static function to_date($value): ?\DateTimeImmutable {
        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }
        if (!is_string($value) || $value === '') {
            return null;
        }

        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$parsed || $parsed->format('Y-m-d') !== $value) {
            return null;
        }
        return $parsed;
    }
}
