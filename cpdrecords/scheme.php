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
 * Manage the active CPD scheme.
 *
 * @package    local_cpdrecords
 * @copyright  2026 CPD Records contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

use local_cpdrecords\form\scheme_form;
use local_cpdrecords\repository;
use local_cpdrecords\scheme;

require_login();
$context = context_system::instance();
require_capability('local/cpdrecords:managescheme', $context);

$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_url(new moodle_url('/local/cpdrecords/scheme.php'));
$PAGE->set_title(get_string('schemesettings', 'local_cpdrecords'));
$PAGE->set_heading(get_string('schemesettings', 'local_cpdrecords'));

$active = repository::get_active_scheme();
$preset = optional_param('preset', '', PARAM_ALPHANUMEXT);
$definition = $active;
if ($preset === 'gdc') {
    $definition = scheme::gdc();
} else if ($preset === 'custom') {
    $definition = scheme::custom();
}
if (!$definition) {
    $definition = scheme::custom();
    $definition['enabled'] = 1;
}

$formdata = (object)[
    'shortname' => $definition['shortname'],
    'name' => $definition['name'],
    'description' => $definition['description'],
    'cyclelength' => $definition['cyclelength'],
    'yearstart' => $definition['yearstart'],
    'roles' => scheme::roles_to_text($definition['roles']),
    'categories' => scheme::categories_to_text($definition['categories']),
    'minimumhours' => $definition['minimumhours'] === null ? '' : $definition['minimumhours'],
    'minimumyears' => $definition['minimumyears'] === null ? '' : $definition['minimumyears'],
    'declarationdays' => $definition['declarationdays'] === null ? '' : $definition['declarationdays'],
    'enabled' => $definition['enabled'],
];

$form = new scheme_form($PAGE->url->out(false));
$form->set_data($formdata);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/index.php'));
}

if ($data = $form->get_data()) {
    $definition = scheme_form::definition_from_data((array)$data);
    repository::save_scheme($definition);
    \core\notification::success(get_string('schemesaved', 'local_cpdrecords'));
    redirect(new moodle_url('/local/cpdrecords/scheme.php'));
}

echo $OUTPUT->header();

echo html_writer::start_div('mb-3');
echo html_writer::span(get_string('loadpreset', 'local_cpdrecords') . ' ');
echo html_writer::link(
    new moodle_url('/local/cpdrecords/scheme.php', ['preset' => 'gdc']),
    get_string('gdcpreset', 'local_cpdrecords')
);
echo ' | ';
echo html_writer::link(
    new moodle_url('/local/cpdrecords/scheme.php', ['preset' => 'custom']),
    get_string('custompreset', 'local_cpdrecords')
);
echo html_writer::end_div();

if ($preset !== '') {
    \core\notification::warning(get_string('presetnotice', 'local_cpdrecords'));
}
if ($active && repository::has_scheme_records($active['id'])) {
    \core\notification::warning(get_string('schemechangewarning', 'local_cpdrecords'));
}
if ($active && has_capability('local/cpdrecords:viewall', $context)) {
    echo html_writer::link(
        new moodle_url('/local/cpdrecords/admin.php'),
        get_string('viewcpdrecords', 'local_cpdrecords'),
        ['class' => 'btn btn-secondary mb-3 me-2']
    );
}
if (
    $active
    && has_capability('local/cpdrecords:viewall', $context)
    && has_capability('local/cpdrecords:exportall', $context)
) {
    $exporturl = new moodle_url('/local/cpdrecords/export.php', [
        'scope' => 'all',
        'sesskey' => sesskey(),
    ]);
    echo html_writer::link(
        $exporturl,
        get_string('exportallcsv', 'local_cpdrecords'),
        ['class' => 'btn btn-secondary mb-3']
    );
}

$form->display();
echo $OUTPUT->footer();
