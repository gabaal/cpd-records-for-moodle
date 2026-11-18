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
 * English language strings.
 *
 * @package    local_cpdrecords
 * @copyright  2026 CPD Records contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['active'] = 'Active scheme';
$string['active_help'] = 'Only one scheme is active at a time in this version. '
    . 'Activating this scheme deactivates the others. Existing records remain unchanged.';
$string['activity'] = 'Activity';
$string['allcourses'] = 'All courses';
$string['allusers'] = 'All learners';
$string['categories'] = 'Development categories';
$string['categories_help'] = 'One per line: KEY | Label. Use stable keys such as TECH or ETH. '
    . 'Labels may be edited later, but existing records retain their original label snapshots. '
    . 'The pipe character cannot be used inside a label.';
$string['clearfilters'] = 'Clear filters';
$string['coursecategories_help'] = 'Select every category covered by this course. '
    . 'A course may contribute its hours to more than one category.';
$string['coursecredit'] = 'CPD credit';
$string['coursecreditsaved'] = 'Course CPD credit saved.';
$string['cpdhours'] = 'CPD hours';
$string['cpdhours_help'] = 'Hours awarded when a learner completes this course. '
    . 'Use quarter-hour increments. Set zero to remove CPD credit.';
$string['cpdprofilesaved'] = 'Your CPD settings were saved.';
$string['cpdrecords:exportall'] = 'Export all CPD records';
$string['cpdrecords:managecourse'] = 'Assign CPD credit to courses';
$string['cpdrecords:manageprofiles'] = 'Edit learner CPD profiles';
$string['cpdrecords:managescheme'] = 'Configure CPD schemes';
$string['cpdrecords:viewall'] = 'View all CPD records';
$string['cpdrecordsreport'] = 'CPD records report';
$string['csvopenfailed'] = 'Could not open the CSV output stream.';
$string['custompreset'] = 'Custom CPD preset';
$string['cycle'] = 'CPD cycle';
$string['cyclelength'] = 'Cycle length (years)';
$string['cyclelength_help'] = 'Enter a whole number from 1 to 10. '
    . 'Each learner chooses the first day of their own current cycle.';
$string['cycleprogress'] = 'CPD cycle progress';
$string['cyclestart'] = 'Current cycle start date';
$string['cyclestart_help'] = 'Enter the first day as YYYY-MM-DD. When a consecutive-year minimum applies, '
    . 'this date must match the role’s CPD-year boundary, such as 2025-01-01 or 2025-08-01.';
$string['date'] = 'Date';
$string['declarationdays'] = 'Declaration deadline (days after CPD-year end)';
$string['declarationdays_help'] = 'Optional. For example, 28 means 28 days after the CPD year ends. '
    . 'Leave this blank when no declaration deadline applies.';
$string['downloadcsv'] = 'Download CSV';
$string['editcoursecredit'] = 'Edit course credit';
$string['editcpdprofile'] = 'Edit CPD profile';
$string['exportallcsv'] = 'Export all CPD records (CSV)';
$string['exportfilteredcsv'] = 'Export filtered CSV';
$string['gdcpreset'] = 'GDC preset';
$string['hours'] = 'hours';
$string['hoursbycategory'] = 'Hours by development category';
$string['hoursrecorded'] = 'hours recorded';
$string['hoursremaining'] = 'hours remaining';
$string['hoursrequired'] = 'hours required';
$string['invalidcyclestart'] = 'Enter a valid date in YYYY-MM-DD format.';
$string['invalidexportscope'] = 'Unknown CPD export scope.';
$string['invalidhours'] = 'Enter CPD hours between 0 and 1000.';
$string['invalidhoursteps'] = 'Use quarter-hour increments, such as 0.25, 0.5, 0.75 or 1.';
$string['invalidrole'] = 'Select a valid professional role.';
$string['learner'] = 'Learner';
$string['loadpreset'] = 'Load a starting preset:';
$string['minimumdetail'] = 'Target: {$a->hours} hours across each completed {$a->years}-year window.';
$string['minimumhours'] = 'Minimum hours per consecutive-year window';
$string['minimumhours_help'] = 'Optional. Set both minimum hours and window years. '
    . 'For example, 10 hours across 2 completed CPD years. Cycle starts must align '
    . 'with the applicable CPD-year boundary when this rule is enabled.';
$string['minimumstatus_fail'] = 'At least one completed window has fewer than the required hours.';
$string['minimumstatus_not_applicable'] = 'No consecutive-year minimum applies.';
$string['minimumstatus_not_yet_assessable'] = 'Not yet assessable. Complete the required CPD-year window first.';
$string['minimumstatus_pass'] = 'Every completed window meets the minimum hours.';
$string['minimumyears'] = 'Consecutive-year window (years)';
$string['minimumyears_help'] = 'Optional whole number from 2 to 10. Only completed windows are judged.';
$string['misalignedcycle'] = 'The cycle start must match the applicable CPD-year boundary for this role.';
$string['mycpdrecord'] = 'My CPD record';
$string['nextdeclaration'] = 'Next declaration';
$string['noactivescheme'] = 'No CPD scheme is active. '
    . 'Ask a site manager to configure and activate a scheme first.';
$string['nocategorywarning'] = 'This course has CPD hours but no development category selected. '
    . 'Check whether your CPD scheme requires at least one.';
$string['norecordscycle'] = 'No CPD records in this cycle yet. Complete a course with CPD hours assigned.';
$string['norecordsfound'] = 'No CPD records match these filters.';
$string['optionalrules'] = 'Optional scheme rules';
$string['pluginname'] = 'CPD Records';
$string['presetnotice'] = 'The preset is loaded into the form but has not been saved yet. '
    . 'Review every value and select Save scheme.';
$string['privacy:metadata:course'] = 'CPD course credit configuration edited by a user.';
$string['privacy:metadata:course:categories'] = 'Development categories assigned by the editor.';
$string['privacy:metadata:course:courseid'] = 'Course containing the CPD credit configuration.';
$string['privacy:metadata:course:hours'] = 'CPD hours assigned by the editor.';
$string['privacy:metadata:course:usermodified'] = 'User who last edited the course CPD credit.';
$string['privacy:metadata:record'] = 'Historical CPD course-completion records.';
$string['privacy:metadata:record:activitytitle'] = 'Course title at the time of completion.';
$string['privacy:metadata:record:categories'] = 'Development category snapshots assigned to the record.';
$string['privacy:metadata:record:completiontime'] = 'Date and time the learner completed the course.';
$string['privacy:metadata:record:courseid'] = 'Course completed by the learner.';
$string['privacy:metadata:record:hours'] = 'CPD hours awarded to the learner.';
$string['privacy:metadata:record:rolekey'] = 'Learner role snapshot when available.';
$string['privacy:metadata:record:userid'] = 'Learner who earned the CPD record.';
$string['privacy:metadata:timecreated'] = 'Time the record was created.';
$string['privacy:metadata:timemodified'] = 'Time the record was last modified.';
$string['privacy:metadata:user'] = 'Learner CPD role and cycle configuration.';
$string['privacy:metadata:user:cyclestart'] = 'First day of the learner’s current CPD cycle.';
$string['privacy:metadata:user:rolekey'] = 'Professional role selected by the learner.';
$string['privacy:metadata:user:schemeid'] = 'CPD scheme associated with the learner profile.';
$string['privacy:metadata:user:userid'] = 'Learner whose CPD profile is stored.';
$string['privacycoursepath'] = 'CPD course settings edited by you';
$string['privacyprofilepath'] = 'CPD profile';
$string['privacyrecordspath'] = 'CPD completion records';
$string['professionalrole'] = 'Professional role';
$string['recordcountmany'] = '{$a} records';
$string['recordcountone'] = '1 record';
$string['recordsincycle'] = 'Records in this cycle';
$string['roles'] = 'Professional roles';
$string['roles_help'] = 'One per line: role_key | Label | required hours | optional MM-DD year boundary. '
    . 'Use lowercase stable keys. The optional fourth value overrides the scheme CPD-year boundary for that role.';
$string['savecoursecredit'] = 'Save course credit';
$string['savecpdprofile'] = 'Save my CPD details';
$string['savescheme'] = 'Save scheme';
$string['scheme'] = 'CPD scheme';
$string['schemechangewarning'] = 'This scheme already has learner records. Historical snapshots will not change, '
    . 'but avoid repurposing stable role or category keys for different meanings.';
$string['schemename'] = 'Scheme name';
$string['schemesaved'] = 'CPD scheme saved.';
$string['schemesettings'] = 'CPD scheme settings';
$string['schemeshortname'] = 'Scheme shortname';
$string['schemeshortname_help'] = 'A lowercase stable identifier using letters, numbers and underscores. '
    . 'Use a new shortname for a materially different scheme rather than repurposing one with existing records.';
$string['setupcpd'] = 'Set up your CPD record';
$string['setupcpdintro'] = 'Choose your professional role and the first day of your current CPD cycle for {$a}.';
$string['source'] = 'Source';
$string['status'] = 'Status';
$string['unknownuser'] = 'Unknown or deleted user';
$string['viewcpdrecords'] = 'View CPD records';
$string['yearstart'] = 'Default CPD-year start (MM-DD)';
$string['yearstart_help'] = 'For example, 01-01 for January or 08-01 for August. '
    . 'Individual roles can override this in the role definitions.';
$string['zeroyearwarning'] = 'At least one completed window contains no recorded hours in any of its CPD years.';
