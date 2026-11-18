# CPD Records for Moodle changelog

## 0.1.0-beta.1 — release validation

### Added

- Profession-neutral Custom CPD scheme and verified GDC preset
- Configurable roles, categories, cycle length, CPD-year boundary, consecutive-year minimum and declaration offset
- XMLDB schema, capabilities and Moodle 4.5/5.3 layouts
- Course credit settings and real `course_completed` event observer
- Learner setup and responsive CPD dashboard
- Own, individual-admin, filtered and all-record CSV exports
- Paginated/filterable admin records report
- Privacy metadata, user/context discovery, ZIP export and deletion paths
- Moodle Plugin CI GitHub Actions workflow
- Marketplace listing draft and reviewed screenshots

### Fixed during integration testing

- Read Moodle QuickForm grouped category values correctly
- Preserve course ID in the course-credit form action
- Normalise learner IDs before own-user permission checks
- Use Moodle's supported no-cache header API for CSV responses
- Handle Moodle's `99` server-timezone setting
- Reject misaligned cycle starts when a consecutive-year rule is enabled
- Apply Moodle PHPCS boilerplate and formatting rules
- Add a complete Mustache example context for validation

### Verification

Standalone suites cover domain rules, CSV safety and plugin structure. Real Moodle browser/API tests cover GDC and Custom CPD, course completion, duplicate prevention, learner/admin permissions, dashboard rendering, filtered reports, privacy export and deletion. Installation, upgrade and uninstall are also exercised on disposable local sites.

Refer to the project README for the final check counts and any remaining release limitations.
