# Moodle Marketplace listing draft

## Title

CPD Records

## Short description

Turn Moodle course completions into configurable CPD records, learner progress dashboards and spreadsheet-safe CSV reports.

## Full description

CPD Records helps training providers, employers and membership organisations track continuing professional development without maintaining separate spreadsheets.

Configure your own professional roles, hour targets, development categories, CPD-year boundaries and multi-year cycles. A GDC Enhanced CPD preset is included for dental professionals, while Custom CPD supports other professional-development schemes.

Learners receive a personal dashboard showing cycle progress, hours by category, declaration dates and their completion history. Administrators can assign credit to courses, filter the records report and export CSV files.

The free plugin stores data locally and supports Moodle's Privacy API. It does not contact an external service.

## Feature list

- Custom CPD and GDC presets
- Configurable roles, categories, hour targets and cycles
- Optional consecutive-year minimum and declaration deadline
- Automatic course-completion records with duplicate prevention
- Historical title, hours and category snapshots
- Learner dashboard and own CSV export
- Filterable admin report and filtered/all CSV exports
- Moodle Privacy API export and deletion
- Responsive, keyboard-accessible Moodle UI

## Requirements

- Moodle 4.5 LTS or 5.3 LTS
- PHP supported by the selected Moodle release
- A Moodle-supported database

## Installation

Upload the ZIP under **Site administration → Plugins → Install plugins**, then complete installation under **Notifications**. See the included README for manual installation on Moodle 4.5/5.3 and scheme configuration.

## Privacy

The plugin stores learner IDs, professional role, cycle start, course-completion records and the last course-credit editor. It does not transmit data to an external service. Privacy export and deletion are supported through Moodle's privacy subsystem.

Uninstalling permanently deletes plugin tables and configuration; take a backup if the records must be retained.

## Support and licence

Free, GPL-3.0-or-later. Use the issue tracker associated with the published listing for bug reports. No paid companion is included in this package.

## Screenshots

1. **Scheme settings** — load GDC or configure a custom CPD scheme.
2. **Course credit** — assign hours and development categories to a Moodle course.
3. **GDC learner dashboard** — cycle progress, outcomes, declaration date and records.
4. **Custom CPD dashboard** — the same engine with custom roles and categories.
5. **Admin records report** — filter learners/courses, export CSV and edit related settings.

## Review note

Local Stage 5 checks and both Moodle LTS browser/API suites pass. Remote GitHub Actions CI has not run yet. Do not submit until the repository workflow passes and the uploaded package is the tested `0.1.0-beta.1` build.
