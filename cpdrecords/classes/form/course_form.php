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
 * Course CPD credit form.
 *
 * @package    local_cpdrecords
 * @copyright  2026 CPD Records contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cpdrecords\form;

/**
 * Assign hours and scheme categories to a course.
 */
class course_form extends \moodleform {
    /**
     * Define the form.
     *
     * @return void
     */
    protected function definition(): void {
        $mform = $this->_form;
        $scheme = $this->_customdata['scheme'];
        $course = $this->_customdata['course'];

        $mform->addElement('hidden', 'courseid', $course->id);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('static', 'scheme', get_string('scheme', 'local_cpdrecords'), s($scheme['name']));
        $mform->addElement('static', 'coursename', get_string('course'), s($course->fullname));

        $mform->addElement('text', 'hours', get_string('cpdhours', 'local_cpdrecords'), ['size' => 8]);
        $mform->setType('hours', PARAM_RAW);
        $mform->setDefault('hours', '0');
        $mform->addRule('hours', null, 'required', null, 'client');
        $mform->addHelpButton('hours', 'cpdhours', 'local_cpdrecords');

        if ($scheme['categories'] !== []) {
            $checkboxes = [];
            foreach ($scheme['categories'] as $key => $label) {
                $checkboxes[] = $mform->createElement(
                    'advcheckbox',
                    $key,
                    '',
                    s($key . ' — ' . $label),
                    [],
                    [0, 1]
                );
            }
            $mform->addGroup($checkboxes, 'categories', get_string('categories', 'local_cpdrecords'), null, false);
            $mform->addHelpButton('categories', 'coursecategories', 'local_cpdrecords');
        }

        $this->add_action_buttons(true, get_string('savecoursecredit', 'local_cpdrecords'));
    }

    /**
     * Validate CPD hours.
     *
     * @param array $data Submitted values.
     * @param array $files Uploaded files.
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $hours = $data['hours'] ?? '';

        if (!is_numeric($hours) || (float)$hours < 0 || (float)$hours > 1000) {
            $errors['hours'] = get_string('invalidhours', 'local_cpdrecords');
        } else if (abs((float)$hours * 4 - round((float)$hours * 4)) > 0.00001) {
            $errors['hours'] = get_string('invalidhoursteps', 'local_cpdrecords');
        }

        return $errors;
    }

    /**
     * Return selected category keys from submitted form data.
     *
     * @param object $data Submitted data.
     * @param array $scheme Validated scheme.
     * @return array
     */
    public static function selected_categories(object $data, array $scheme): array {
        $submitted = isset($data->categories) && is_array($data->categories) ? $data->categories : [];
        $selected = [];
        foreach (array_keys($scheme['categories']) as $key) {
            // QuickForm group members are submitted as direct properties in supported Moodle versions.
            if (!empty($submitted[$key]) || !empty($data->{$key})) {
                $selected[] = $key;
            }
        }
        return $selected;
    }
}
