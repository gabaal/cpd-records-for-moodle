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
 * Learner CPD profile form.
 *
 * @package    local_cpdrecords
 * @copyright  2026 CPD Records contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cpdrecords\form;

use local_cpdrecords\calculator;
use local_cpdrecords\scheme;

/**
 * Select a learner's professional role and current cycle start.
 */
class profile_form extends \moodleform {
    /**
     * Define the form.
     *
     * @return void
     */
    protected function definition(): void {
        $mform = $this->_form;
        $scheme = $this->_customdata['scheme'];

        $mform->addElement('header', 'profileheader', get_string('setupcpd', 'local_cpdrecords'));
        $mform->addElement('static', 'scheme', get_string('scheme', 'local_cpdrecords'), s($scheme['name']));

        $roles = [];
        foreach ($scheme['roles'] as $key => $role) {
            $roles[$key] = $role['label'] . ' (' . format_float($role['hours'], 0) . ' '
                . get_string('hours', 'local_cpdrecords') . ')';
        }
        $mform->addElement('select', 'rolekey', get_string('professionalrole', 'local_cpdrecords'), $roles);
        $mform->addRule('rolekey', null, 'required', null, 'client');

        $mform->addElement('text', 'cyclestart', get_string('cyclestart', 'local_cpdrecords'), [
            'size' => 12,
            'placeholder' => 'YYYY-MM-DD',
        ]);
        $mform->setType('cyclestart', PARAM_RAW);
        $mform->addRule('cyclestart', null, 'required', null, 'client');
        $mform->addHelpButton('cyclestart', 'cyclestart', 'local_cpdrecords');

        $this->add_action_buttons(true, get_string('savecpdprofile', 'local_cpdrecords'));
    }

    /**
     * Validate role, date and CPD-year alignment.
     *
     * @param array $data Submitted values.
     * @param array $files Uploaded files.
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $scheme = $this->_customdata['scheme'];
        $rolekey = (string)($data['rolekey'] ?? '');
        $cyclestart = trim((string)($data['cyclestart'] ?? ''));

        if (!scheme::role($scheme, $rolekey)) {
            $errors['rolekey'] = get_string('invalidrole', 'local_cpdrecords');
        }
        if (!calculator::is_valid_date($cyclestart)) {
            $errors['cyclestart'] = get_string('invalidcyclestart', 'local_cpdrecords');
        } else if ($scheme['minimumhours'] !== null && scheme::role($scheme, $rolekey)) {
            $year = calculator::cpd_year_bounds($scheme, $rolekey, $cyclestart);
            if (!$year || $year['start']->format('Y-m-d') !== $cyclestart) {
                $errors['cyclestart'] = get_string('misalignedcycle', 'local_cpdrecords');
            }
        }

        return $errors;
    }
}
