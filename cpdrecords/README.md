# CPD Records for Moodle

CPD Records turns Moodle course completions into structured continuing professional development records.

It supports a configurable **Custom CPD** scheme and an included **GDC Enhanced CPD** preset. The engine is profession-neutral: GDC is only one preset.

## Features

- Configurable roles, hour targets, development categories, CPD-year boundaries and 1–10-year cycles
- Optional consecutive-year minimum and declaration deadline
- Course-level CPD hours and categories
- Automatic historical records from Moodle course-completion events
- Learner dashboard with progress, category totals, declaration date and record history
- Learner, individual-admin and filtered/all-admin CSV exports
- Filterable, paginated admin records report
- Moodle Privacy API metadata, context discovery, ZIP export and deletion
- Responsive dashboard and spreadsheet-formula protection
- No external service or tracking

## Requirements

- Moodle 4.5 LTS or 5.3 LTS
- PHP versions supported by the selected Moodle release
- A Moodle-supported database

## Installation

Use **Site administration → Plugins → Install plugins** and upload the release ZIP.

For manual installation, place the `cpdrecords` folder at:

- Moodle 4.5: `<moodle>/local/cpdrecords`
- Moodle 5.3: `<moodle>/public/local/cpdrecords`

Then open **Site administration → Notifications** to complete installation.

## Configure a scheme

1. Open **Site administration → Plugins → CPD scheme settings**.
2. Load **Custom CPD preset** or **GDC preset**.
3. Review roles, categories, cycle and optional rules.
4. Select **Active scheme** and save.

The free version uses one active scheme per site. Existing historical records are not rewritten when configuration later changes.

Custom role lines use:

```text
role_key | Role label | required hours | optional MM-DD year boundary
```

For example:

```text
member | Member | 30
lead | Lead | 50 | 04-01
```

Category lines use:

```text
KEY | Category label
```

When a consecutive-year minimum is enabled, each learner's cycle start must match the applicable CPD-year boundary.

## Assign course credit

Open a course and select **CPD credit** in course administration. Enter hours and select all applicable development categories. Zero hours removes CPD credit from the course.

A learner receives one historical record per course/scheme when Moodle records course completion. Recompletion does not create a duplicate in this version.

## Learner dashboard

Open **My CPD record**, select a professional role and enter the first day of the current cycle as `YYYY-MM-DD`.

The learner can download their own records as CSV. Site managers with the appropriate capabilities can open another learner's profile from the admin report.

## Admin report and CSV

Open **Site administration → Reports → CPD records report**. Filter by learner or course, edit related settings where authorised, or export the filtered records as CSV.

## Privacy and retention

The plugin declares its stored data through Moodle's Privacy API. Authorised privacy exports include learner profiles, CPD records and course-editor attribution. Deletion requests can remove learner data and anonymise editor attribution.

Uninstalling the plugin through Moodle permanently deletes its database tables and configuration. Remove the plugin folder when Moodle asks, to prevent automatic reinstallation. Take a backup first if you need the records later.

## Status

This is a beta release candidate. Test upgrades and your own CPD rules on a staging site before production use.

## Licence

GNU GPL v3 or later. See `LICENSE.md`.
