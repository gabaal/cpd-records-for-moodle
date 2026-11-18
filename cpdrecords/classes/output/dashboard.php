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
 * Learner dashboard renderable.
 *
 * @package    local_cpdrecords
 * @copyright  2026 CPD Records contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cpdrecords\output;

/**
 * Export a calculated CPD summary for the dashboard template.
 */
class dashboard implements \renderable, \templatable {
    /** @var array Validated scheme. */
    private $scheme;

    /** @var array Calculated summary. */
    private $summary;

    /** @var \moodle_url Export URL. */
    private $exporturl;

    /**
     * Constructor.
     *
     * @param array $scheme Validated scheme.
     * @param array $summary Calculated summary.
     * @param \moodle_url $exporturl Learner record export URL.
     */
    public function __construct(array $scheme, array $summary, \moodle_url $exporturl) {
        $this->scheme = $scheme;
        $this->summary = $summary;
        $this->exporturl = $exporturl;
    }

    /**
     * Export template data.
     *
     * @param \renderer_base $output Renderer.
     * @return array
     */
    public function export_for_template(\renderer_base $output): array {
        unset($output); // Required by the templatable interface; this exporter needs no renderer services.
        $categories = [];
        foreach ($this->scheme['categories'] as $key => $label) {
            $categories[] = [
                'key' => $key,
                'label' => $label,
                'hours' => format_float($this->summary['category_hours'][$key] ?? 0, 2),
            ];
        }

        $records = [];
        foreach ($this->summary['records'] as $record) {
            $record = (array)$record;
            $records[] = [
                'date' => self::display_date($record['activity_date']),
                'title' => $record['activitytitle'],
                'hours' => format_float($record['hours'], 2),
                'categories' => $record['categorylabels'],
            ];
        }

        $status = $this->summary['minimum_status'];
        $statusclasses = [
            'pass' => 'alert-success',
            'fail' => 'alert-danger',
            'not_yet_assessable' => 'alert-info',
            'not_applicable' => 'alert-secondary',
        ];

        return [
            'schemename' => $this->scheme['name'],
            'rolelabel' => $this->summary['role_label'],
            'totalhours' => format_float($this->summary['total_hours'], 2),
            'requiredhours' => format_float($this->summary['required_hours'], 0),
            'remaininghours' => format_float($this->summary['remaining_hours'], 2),
            'percent' => $this->summary['percent'],
            'percentlabel' => format_float($this->summary['percent'], 1),
            'cyclestart' => self::display_date($this->summary['cycle_start']),
            'cycleend' => self::display_date($this->summary['cycle_end']),
            'showdeclaration' => $this->summary['declaration_due'] !== '',
            'declarationdue' => self::display_date($this->summary['declaration_due']),
            'showminimum' => $this->scheme['minimumhours'] !== null,
            'minimumclass' => $statusclasses[$status],
            'minimumstatus' => get_string('minimumstatus_' . $status, 'local_cpdrecords'),
            'minimumdetail' => get_string('minimumdetail', 'local_cpdrecords', [
                'hours' => format_float($this->scheme['minimumhours'] ?? 0, 2),
                'years' => $this->scheme['minimumyears'] ?? 0,
            ]),
            'showzerowarning' => $this->summary['consecutive_zero'],
            'showcategories' => $categories !== [],
            'categories' => $categories,
            'hasrecords' => $records !== [],
            'records' => $records,
            'recordcount' => $this->summary['record_count'],
            'exporturl' => $this->exporturl->out(false),
        ];
    }

    /**
     * Format a date-only value for display.
     *
     * @param string $date Y-m-d date or empty string.
     * @return string
     */
    private static function display_date(string $date): string {
        global $CFG;
        if ($date === '') {
            return '';
        }
        return userdate(strtotime($date . ' UTC'), '%d %B %Y', $CFG->timezone);
    }
}
