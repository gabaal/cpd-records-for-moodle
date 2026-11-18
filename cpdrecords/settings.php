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
 * Administration settings links.
 *
 * @package    local_cpdrecords
 * @copyright  2026 CPD Records contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_cpdrecords_scheme',
        get_string('schemesettings', 'local_cpdrecords'),
        new moodle_url('/local/cpdrecords/scheme.php'),
        'local/cpdrecords:managescheme'
    ));
    $ADMIN->add('reports', new admin_externalpage(
        'local_cpdrecords_report',
        get_string('cpdrecordsreport', 'local_cpdrecords'),
        new moodle_url('/local/cpdrecords/admin.php'),
        'local/cpdrecords:viewall'
    ));
}
