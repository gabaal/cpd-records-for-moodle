# CPD Records for Moodle

A profession-neutral CPD tracker being built as Moodle local plugin `local_cpdrecords`.

The planned free core records CPD hours when learners complete courses, then presents cycle progress, category totals, configurable multi-year rules and CSV exports. It includes a verified **GDC** preset and a configurable **Custom CPD** scheme.

See **[SPEC.md](SPEC.md)** for the product scope, data model, security/privacy requirements and release gates.

## Current build status

**Stage 5: release validation and beta package.** The plugin includes Custom CPD/GDC schemes, course completion records, learner dashboard, admin report, CSV exports, privacy integration, CI workflow and Marketplace assets.

Verified on 18 November 2026:

- **115 domain checks**
- **29 Stage 2 structural checks**
- **34 Stage 3 structural checks**
- **33 Stage 4 structural checks**
- **21 Stage 5 release-asset checks**
- Moodle 5.3.1+: **27 Stage 3 browser checks**, **9 admin-report checks**, **23 privacy API checks** and **13 Custom CPD browser checks**
- Moodle 4.5.16+: the same **27 Stage 3**, **9 admin-report** and **23 privacy API checks**
- Install, alpha.3 → alpha.4 upgrade and complete uninstall verified on Moodle 4.5
- Actual beta ZIP upload through Moodle’s admin installer passed 5 checks on Moodle 4.5
- Moodle PHPCS, PHPDoc, PHP lint, plugin validation and Mustache/HTML validation pass locally

Remote GitHub Actions CI passed for both Moodle 4.5/PHP 8.3 and Moodle 5.3/PHP 8.4 in run 44384557551. PHPMD retains non-blocking complexity advisories for the calculator, scheme validator, repository and privacy exporter; these are future refactoring candidates.

## Design boundary

- Free core: one active CPD scheme per site, course credits, completion records, learner dashboard and CSV.
- Future paid companion: evidence, reflections, approvals, audit packs and scheduled reporting.
- No QR attendance, certificate generator, external service or Moodle Workplace dependency.

## Development requirements

For the standalone domain tests:

- PHP 8.1+ CLI

For Moodle integration testing in later stages:

- Moodle 4.5 LTS or 5.3 LTS
- A Moodle-supported database
- Moodle's normal PHP extension requirements

## Run the domain tests

From this project directory:

```bash
php tools/test-domain.php
php tools/test-stage2.php
php tools/test-stage3.php
php tools/test-stage4.php
php tools/test-stage5.php
```

The test scripts have no Moodle or Composer dependency. In this workspace, where PHP is not on `PATH`, run:

```powershell
..\plugin-scan\tools\php84\php.exe -n tools/test-domain.php
..\plugin-scan\tools\php84\php.exe -n tools/test-stage2.php
..\plugin-scan\tools\php84\php.exe -n tools/test-stage3.php
..\plugin-scan\tools\php84\php.exe -n tools/test-stage4.php
..\plugin-scan\tools\php84\php.exe -n tools/test-stage5.php
```

## Build a test ZIP

```powershell
powershell -NoProfile -File tools/package.ps1
```

This creates `dist/local_cpdrecords-0.1.0-beta.1.zip` with `cpdrecords/` at the archive root. The current package is an alpha scaffold, not a production-ready plugin.

## Install the eventual plugin

The source plugin directory is `cpdrecords/`. Once functional and tested, copy it to Moodle's `local/` directory so the component path is:

```text
moodle/local/cpdrecords/
```

Do not use this alpha scaffold on a production site.
