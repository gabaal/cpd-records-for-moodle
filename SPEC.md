# CPD Records for Moodle — v0.1 specification

**Status:** initial build specification, revised 18 November 2026 for profession-neutral CPD  
**Product:** CPD Records for Moodle  
**Moodle component:** `local_cpdrecords`  
**Future paid companion:** CPD Portfolio Pack (`local_cpdportfolio`)

## 1. Product decision

Build a free Moodle local plugin that turns course completions into structured continuing professional development records. It supports a configurable CPD scheme rather than assuming one profession. Include a verified GDC preset for dental professionals and a Custom CPD scheme for other training providers.

Give learners a cycle dashboard and CSV export. Establish product-led distribution through Moodle Marketplace before building the paid evidence and compliance companion.

This is not only a dental plugin, a generic points counter, another certificate generator, or a QR-attendance plugin. Its wedge is **configurable CPD cycles, categories and progress rules with a learner-owned history**.

## 2. Users and problem

### Buyer

A training provider, employer, professional body or membership organisation running self-hosted Moodle. The buyer needs credible CPD records without maintaining spreadsheets or implementing its professional scheme separately for each learner.

### Learner

A professional or member who completes Moodle courses and needs to know cycle progress, hours by category, any applicable multi-year minimum and the next declaration date.

### Administrator

Training or compliance staff who configure the CPD scheme, assign credit to courses, inspect records and export evidence.

### Problem

Moodle course completion and certificates do not, by themselves, total CPD hours against a learner's own cycle, split hours by professional-development categories, assess configurable consecutive-year rules, or produce a usable CPD transcript.

## 3. Competitive position

- Generic credit plugins can award and report CPD/CEU points. This product differentiates through configurable roles, cycle boundaries, categories, progress rules and declarations.
- General portfolio plugins can hold evidence. The free core deliberately does not rebuild a portfolio system; evidence and reflection belong to the paid companion.
- QR attendance and generic recertification are established, crowded categories and are out of scope.
- GDC is an included, tested preset—not a limitation of the underlying engine. Other named regulator presets are added only after their rules are separately verified.

## 4. Compatibility and constraints

- Moodle **4.5 LTS** and **5.3 LTS** are the primary test targets.
- Intermediate 5.x releases may work but are not release blockers until included in CI.
- Plugin PHP code must remain compatible with Moodle 4.5's supported PHP versions; no PHP 8.2+ only syntax.
- Self-hosted Moodle with a Moodle-supported database; no SQLite support assumption.
- No Moodle Workplace-only APIs or paid dependencies.
- No external service, tracking, licence server or network call in the free core.
- Zero upfront infrastructure cost: use existing local tooling and public-repository CI where possible.

## 5. Scheme model

A **CPD scheme** defines the rules used to calculate progress.

### Configurable properties

- Scheme name and description
- Cycle length in years: 1–10
- Default CPD-year start month/day
- Development categories with stable keys and labels
- Professional roles with stable keys, labels and required hours
- Optional role-specific CPD-year boundary override
- Optional consecutive-year minimum: window length in years and required hours
- Optional declaration/statement deadline expressed as days after CPD-year end
- Whether the scheme is active

### Included presets

**GDC (verified preset)**
- Five-year cycles
- Roles and required hours for dentists and dental care professionals
- January/August CPD-year boundaries as applicable
- Development outcomes A–D
- Ten-hour completed two-year-window rule and consecutive-zero-year warning
- Annual statement 28 days after CPD-year end

**Custom CPD**
- Starts with one generic `professional` role and configurable hours
- Starts with a one-year cycle and January CPD-year boundary
- Administrator edits roles, categories, cycle and rules before use
- No unverified regulatory claims

### v0.1 boundary

The free UI supports **one active scheme per Moodle site**. Tables use scheme IDs from the start so a later release can support multiple simultaneous schemes without redesigning every record.

Changing scheme configuration does not rewrite historical record snapshots. Administrators are warned to publish a new scheme/version rather than repurpose a scheme with existing records; full scheme versioning is deferred.

## 6. Free-core v0.1 scope

1. **Scheme settings** — choose GDC or Custom CPD and configure the active scheme.
2. **Course credit settings** — CPD hours and development categories on each course for the active scheme.
3. **Automatic completion records** — observe Moodle's `core\event\course_completed` event and create one record per learner/course/scheme.
4. **Learner setup** — role and the first day of the learner's current cycle.
5. **Learner dashboard** — cycle dates, total/required/remaining hours, progress, category totals, any consecutive-year rule, declaration date and record table.
6. **Learner CSV** — export the learner's own complete CPD history.
7. **Admin records report** — filter by learner/course and inspect recent records.
8. **Admin CSV** — export all records with the appropriate capability.
9. **Privacy API** — describe, locate, export and delete personal data through Moodle's privacy subsystem.
10. **Automated tests** — pure domain tests, Moodle PHPUnit integration tests and a browser smoke journey.

### Deliberate v0.1 choices

- Course completion only; individual activity credits are deferred.
- One active scheme per site; simultaneous multi-scheme membership is deferred.
- One record per learner/course/scheme. Recompletion does not create another v0.1 record.
- Hours greater than zero create a record; zero-hour courses are skipped.
- A missing category does not prevent a record, but the course settings UI warns when the active scheme expects categorisation.
- Learners self-select their role and cycle start. Administrators can correct them in v0.1; approval workflows are paid scope.
- When a consecutive-year minimum is enabled, the cycle start must align with the applicable CPD-year boundary. Setup validation must explain this rather than counting a partial first year as zero hours.
- Records are retained until an authorised privacy deletion or explicit administrative action; no automatic cycle cleanup.
- Historical completions are not backfilled in v0.1.

## 7. Paid companion boundary

The free plugin must remain useful without the companion. No artificial record limits or disabled exports.

CPD Portfolio Pack candidates, to be built only after usage or upgrade signals:

- Evidence uploads and secure evidence download
- Structured reflective accounts
- Manager/assessor review and sign-off
- Audit-ready PDF/ZIP portfolio
- Declaration and multi-year-risk reminders
- Scheduled manager reports
- Additional separately verified professional presets
- Multiple simultaneous schemes and organisation/group reporting
- Bulk cycle import

**Pricing hypothesis:** USD 99–149 per production site/year for the companion. Validate before treating this as established demand.

## 8. User journeys and screens

### 8.1 Scheme settings

Route: `/local/cpdrecords/scheme.php`

Access: system context and `local/cpdrecords:managescheme`.

Features:

- Select GDC preset or Custom CPD
- Edit cycle, roles, categories, consecutive-year rule and declaration offset
- Validate unique stable keys, positive targets and valid dates/rules
- Preview example cycle and CPD-year dates before saving
- Warn before structural changes when records already exist

### 8.2 Course credit settings

Route: `/local/cpdrecords/course.php?id=<courseid>`

Access: course context plus `local/cpdrecords:managecourse`.

A link named **CPD credit** is added to course administration using a Moodle-version-compatible local-plugin navigation callback.

Fields:

- CPD hours: decimal, 0–1000, increments of 0.25
- Development categories supplied by the active scheme
- Explanation that completion creates one learner/course/scheme record

Saving uses a Moodle form, session key, capability check and redirects back with a success notification.

### 8.3 Learner setup and dashboard

Route: `/local/cpdrecords/my.php`

Access: authenticated user; a user can only view their own dashboard.

Without a valid role and cycle start, display the setup form. Afterwards display:

- Hours recorded, required and remaining
- Accessible progress indicator with text equivalent
- Cycle dates and selected role
- Next declaration/statement date when configured
- Consecutive-year minimum status when configured, including when not yet assessable
- Hours by development category
- Records in the current cycle
- Download-own-CSV action

### 8.4 Admin report

Route: `/local/cpdrecords/admin.php`

Access: system context and `local/cpdrecords:viewall`; export additionally requires `local/cpdrecords:exportall`.

Features:

- Recent records table
- Learner and course filters
- Singular/plural result counts
- Export-all CSV
- Link to course credit settings where authorised

## 9. Data model

All tables are installed through `db/install.xml` and accessed through Moodle DML. Names and keys must fit Moodle/XMLDB cross-database restrictions.

### `local_cpdrecords_scheme`

Stores the active scheme and leaves room for future schemes.

| Field | Type | Purpose |
|---|---|---|
| `id` | integer PK | Internal scheme ID |
| `shortname` | char(100), unique | Stable scheme identifier |
| `name` | char(255) | Display name |
| `description` | text | Optional explanation |
| `cyclelength` | integer | 1–10 years |
| `yearstart` | char(5) | Default `MM-DD` boundary |
| `roles` | text | JSON role definitions and required hours |
| `categories` | text | JSON category definitions |
| `minimumhours` | number(6,2), nullable | Consecutive-window minimum |
| `minimumyears` | integer, nullable | Window length |
| `declarationdays` | integer, nullable | Days after CPD-year end |
| `enabled` | integer | Active/inactive |
| `timecreated` / `timemodified` | integer | Audit timestamps |

JSON is decoded and validated in one domain class; no other code trusts raw stored configuration.

### `local_cpdrecords_course`

| Field | Type | Purpose |
|---|---|---|
| `id` | integer PK | Internal ID |
| `schemeid` | integer | CPD scheme |
| `courseid` | integer | Moodle course |
| `hours` | number(6,2) | CPD hours |
| `categories` | text | JSON stable category keys |
| `usermodified` | integer | Last editor |
| `timecreated` / `timemodified` | integer | Audit timestamps |

Unique key: `(schemeid, courseid)`.

### `local_cpdrecords_record`

| Field | Type | Purpose |
|---|---|---|
| `id` | integer PK | Internal ID |
| `schemeid` | integer, indexed | CPD scheme |
| `userid` | integer, indexed | Learner |
| `courseid` | integer, indexed | Completed course |
| `rolekey` | char(40) | Learner role snapshot when available |
| `activitytitle` | char(255) | Course full name at completion |
| `completiontime` | integer | Moodle completion timestamp |
| `hours` | number(6,2) | Historical awarded hours |
| `categories` | text | Historical JSON category keys and labels |
| `source` | char(20) | `moodle` |
| `status` | char(20) | `approved` |
| `dedupekey` | char(150), unique | `scheme:<id>:course:<id>:user:<id>` |
| `timecreated` | integer | Record creation timestamp |

Historical values do not change when course credit settings, titles or scheme labels later change.

### `local_cpdrecords_user`

| Field | Type | Purpose |
|---|---|---|
| `id` | integer PK | Internal ID |
| `schemeid` | integer | CPD scheme |
| `userid` | integer | Moodle user |
| `rolekey` | char(40) | Stable role key |
| `cyclestart` | char(10) | Date-only `Y-m-d` |
| `timecreated` / `timemodified` | integer | Audit timestamps |

Unique key: `(schemeid, userid)`. Date-only cycle values are not Unix timestamps, avoiding midnight/timezone drift.

## 10. Completion integration

Register an event observer for `core\event\course_completed` in `db/events.php`.

Observer flow:

1. Read the course ID and `relateduserid` from the event.
2. Load the active scheme and that course's credit settings for the scheme.
3. Return without a record when no settings exist or hours are zero.
4. Sanitise categories against the scheme's stable keys and snapshot their current labels.
5. Snapshot scheme ID, learner role when available, course title, hours, categories and completion timestamp.
6. Insert using the unique dedupe key; an existing key returns the existing record without error.
7. Never block or roll back Moodle course completion because a CPD insert fails. Log the failure for diagnosis.

A repeated event must not create a duplicate record.

## 11. Calculation engine

The calculator accepts a validated scheme definition, role key, cycle start, records and calculation date. It contains no Moodle database calls.

### Common calculations

- Find the cycle containing the calculation date; dates before the first cycle use the first cycle.
- End a cycle after `cyclelength` years minus one day.
- Find the CPD year containing a date using the role's boundary override or scheme default.
- Sum hours and per-category hours within the current cycle.
- Calculate remaining hours and cap displayed progress at 100%.
- Exclude records outside the current cycle.
- Populate empty CPD years so zero-hour years remain visible.

### Optional consecutive-year rule

- Combine adjacent CPD years into windows of the configured length.
- Judge only windows whose final year has ended.
- Flag windows below the configured hours.
- Separately flag the configured number of consecutive zero-hour years.
- When no rule is configured, return `not_applicable`, not a pass/fail result.

### Optional declaration date

Return CPD-year end plus the configured number of days. When unset, return no declaration date.

Completion timestamps are converted to the Moodle site's timezone before assigning a completion date and CPD year.

## 12. Capabilities and security

Capabilities in `db/access.php`:

- `local/cpdrecords:managescheme` — system context; manager by default
- `local/cpdrecords:managecourse` — course context; manager and teacher archetypes by default
- `local/cpdrecords:manageprofiles` — system context; manager by default; required to edit another learner's role/cycle
- `local/cpdrecords:viewall` — system context; manager by default
- `local/cpdrecords:exportall` — system context; manager by default

Security requirements:

- `require_login()` and explicit context/capability checks on every page and export
- `require_sesskey()` for state-changing POST requests
- Moodle forms and parameter cleaning for input
- Moodle DML parameter binding; no interpolated SQL values
- Escaped output through renderers/templates
- CSV formula-injection protection for cells beginning with `=`, `+`, `-`, `@`, tab or carriage return
- No direct access to PHP files
- No bearer tokens or external data transmission in the free core

## 13. Privacy, retention and accessibility

Implement `local_cpdrecords\privacy\provider` as a real metadata/request provider, not `null_provider`.

- Declare stored user IDs, course IDs, roles, cycle starts, completion records and editor IDs.
- Locate data in user, course and system contexts as appropriate.
- Export learner profile and records in a structured, readable form.
- Delete plugin-owned user profile and record data for an authorised deletion request.
- Do not delete scheme or course credit configuration when only a learner is deleted.
- Use Moodle output components, semantic tables, visible labels and text equivalents for colour/progress.
- Ensure dashboard and forms remain usable at mobile width and with keyboard navigation.

## 14. Testing and acceptance

### Fast domain tests

Test both Custom CPD and the GDC preset:

- Role hours and category definitions
- Cycle/year boundaries, including leap dates
- Role-specific year-boundary overrides
- Declaration offsets
- Category totals and out-of-cycle exclusion
- Configurable consecutive-year windows and zero-hour years
- Unconfigured rules returning `not_applicable`
- Invalid scheme definitions, keys and dates

### Moodle PHPUnit tests

- Installation creates the four tables and defaults capabilities correctly
- GDC and Custom CPD presets validate correctly
- Course settings can be saved by an authorised teacher and rejected for a learner
- A completion event writes a historical record for the active scheme
- Repeated events are idempotent
- Zero-hour courses and inactive schemes are skipped
- Dashboard calculations use the correct learner, scheme and cycle
- Own/all CSV permissions and content are correct
- CSV formula cells are neutralised
- Privacy export and deletion return/remove the expected data

### Browser smoke journeys

**Custom CPD journey**
1. Admin configures a custom two-year cycle, two roles and custom categories.
2. Admin assigns 6.25 hours and categories to a course.
3. A learner selects a role and cycle start.
4. Course completion produces one correctly categorised record and CSV row.

**GDC preset journey**
1. Admin enables the GDC preset on a separate fixture.
2. Admin assigns 6.25 hours and outcomes A/C to a course.
3. A dentist completes the course.
4. The dashboard shows 6.25 hours, A/C, 100-hour target and the expected declaration date.

Both journeys check repeat-completion idempotency, permissions and the absence of plugin-caused JavaScript errors/debug notices.

### Release gate

- Moodle Plugin CI passes on 4.5 LTS and 5.3 LTS
- Moodle coding style and PHPDoc checks pass
- Installation, upgrade and uninstall are tested
- ZIP contains only `cpdrecords/` under the Moodle `local/` plugin installation convention

## 15. Project layout

```text
moodle-cpd/
├── SPEC.md
├── README.md
├── cpdrecords/
│   ├── version.php
│   ├── lib.php
│   ├── settings.php
│   ├── scheme.php
│   ├── course.php
│   ├── my.php
│   ├── admin.php
│   ├── export.php
│   ├── classes/
│   │   ├── scheme.php
│   │   ├── calculator.php
│   │   ├── repository.php
│   │   ├── observer.php
│   │   ├── form/...
│   │   ├── output/...
│   │   └── privacy/provider.php
│   ├── db/
│   │   ├── access.php
│   │   ├── events.php
│   │   ├── install.xml
│   │   └── upgrade.php
│   ├── lang/en/local_cpdrecords.php
│   ├── templates/...
│   ├── styles.css
│   └── tests/...
└── tools/
    ├── test-domain.php
    └── package.ps1
```

This is the intended final layout; files are created only when their corresponding build stage is implemented.

## 16. Build sequence

- **Stage 0:** specification, README and installable plugin skeleton
- **Stage 1:** standalone scheme validation, calculation engine and regression tests
- **Stage 2:** XMLDB schema, scheme/course settings and completion observer
- **Stage 3:** learner setup/dashboard and CSV exports
- **Stage 4:** admin report, privacy provider, templates and accessibility
- **Stage 5:** Moodle integration/browser tests, Plugin CI, Marketplace assets and release ZIP

The paid companion is a separate project and does not enter Stages 0–5.

## 17. Validation and stop criteria

Measure free-core adoption and requests for the paid workflow rather than assuming revenue.

- **Go signal:** within 90 days of listing, at least 25 active installations and three separate requests for evidence/portfolio, approval or scheduled reporting
- **Stronger signal:** any organisation willing to pay the proposed annual companion price
- **Reassess:** installations but no paid-workflow requests; generic credit tracking may be sufficient
- **Stop/pivot:** fewer than 10 active installations after 90 days of Marketplace availability, unless direct qualified demand says otherwise

Do not submit the Moodle plugin until existing marketplace/review commitments allow it and the release gates pass.
