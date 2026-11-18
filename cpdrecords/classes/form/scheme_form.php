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
 * CPD scheme settings form.
 *
 * @package    local_cpdrecords
 * @copyright  2026 CPD Records contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cpdrecords\form;

use local_cpdrecords\scheme;

/**
 * Edit a profession-neutral CPD scheme.
 */
class scheme_form extends \moodleform {
    /**
     * Define the form.
     *
     * @return void
     */
    protected function definition(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'schemeheader', get_string('schemesettings', 'local_cpdrecords'));

        $mform->addElement('text', 'shortname', get_string('schemeshortname', 'local_cpdrecords'), ['size' => 30]);
        $mform->setType('shortname', PARAM_ALPHANUMEXT);
        $mform->addRule('shortname', null, 'required', null, 'client');
        $mform->addHelpButton('shortname', 'schemeshortname', 'local_cpdrecords');

        $mform->addElement('text', 'name', get_string('schemename', 'local_cpdrecords'), ['size' => 60]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('textarea', 'description', get_string('description'), ['rows' => 3, 'cols' => 80]);
        $mform->setType('description', PARAM_RAW);

        $mform->addElement('text', 'cyclelength', get_string('cyclelength', 'local_cpdrecords'), ['size' => 5]);
        $mform->setType('cyclelength', PARAM_INT);
        $mform->addRule('cyclelength', null, 'required', null, 'client');
        $mform->addHelpButton('cyclelength', 'cyclelength', 'local_cpdrecords');

        $mform->addElement('text', 'yearstart', get_string('yearstart', 'local_cpdrecords'), ['size' => 8]);
        $mform->setType('yearstart', PARAM_RAW);
        $mform->addRule('yearstart', null, 'required', null, 'client');
        $mform->addHelpButton('yearstart', 'yearstart', 'local_cpdrecords');

        $mform->addElement(
            'textarea',
            'roles',
            get_string('roles', 'local_cpdrecords'),
            ['rows' => 9, 'cols' => 100]
        );
        $mform->setType('roles', PARAM_RAW);
        $mform->addRule('roles', null, 'required', null, 'client');
        $mform->addHelpButton('roles', 'roles', 'local_cpdrecords');

        $mform->addElement(
            'textarea',
            'categories',
            get_string('categories', 'local_cpdrecords'),
            ['rows' => 7, 'cols' => 100]
        );
        $mform->setType('categories', PARAM_RAW);
        $mform->addHelpButton('categories', 'categories', 'local_cpdrecords');

        $mform->addElement('header', 'rulesheader', get_string('optionalrules', 'local_cpdrecords'));

        $mform->addElement('text', 'minimumhours', get_string('minimumhours', 'local_cpdrecords'), ['size' => 8]);
        $mform->setType('minimumhours', PARAM_RAW);
        $mform->addHelpButton('minimumhours', 'minimumhours', 'local_cpdrecords');

        $mform->addElement('text', 'minimumyears', get_string('minimumyears', 'local_cpdrecords'), ['size' => 5]);
        $mform->setType('minimumyears', PARAM_RAW);
        $mform->addHelpButton('minimumyears', 'minimumyears', 'local_cpdrecords');

        $mform->addElement('text', 'declarationdays', get_string('declarationdays', 'local_cpdrecords'), ['size' => 5]);
        $mform->setType('declarationdays', PARAM_RAW);
        $mform->addHelpButton('declarationdays', 'declarationdays', 'local_cpdrecords');

        $mform->addElement('advcheckbox', 'enabled', get_string('active', 'local_cpdrecords'));
        $mform->setDefault('enabled', 1);
        $mform->addHelpButton('enabled', 'active', 'local_cpdrecords');

        $this->add_action_buttons(true, get_string('savescheme', 'local_cpdrecords'));
    }

    /**
     * Validate the complete scheme and attach errors to fields.
     *
     * @param array $data Submitted values.
     * @param array $files Uploaded files.
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        try {
            self::definition_from_data($data);
        } catch (\InvalidArgumentException $exception) {
            $errors['shortname'] = $exception->getMessage();
        }

        return $errors;
    }

    /**
     * Convert submitted form data to a validated scheme definition.
     *
     * @param array $data Submitted values.
     * @return array
     */
    public static function definition_from_data(array $data): array {
        return scheme::validate([
            'shortname' => $data['shortname'] ?? '',
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'cyclelength' => $data['cyclelength'] ?? null,
            'yearstart' => $data['yearstart'] ?? '',
            'roles' => scheme::roles_from_text((string)($data['roles'] ?? '')),
            'categories' => scheme::categories_from_text((string)($data['categories'] ?? '')),
            'minimumhours' => $data['minimumhours'] ?? null,
            'minimumyears' => $data['minimumyears'] ?? null,
            'declarationdays' => $data['declarationdays'] ?? null,
            'enabled' => !empty($data['enabled']) ? 1 : 0,
        ]);
    }
}
