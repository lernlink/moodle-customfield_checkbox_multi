# Changelog

## v0.9.3 (2026-07-13)

First public release.

### Bug fixes
- Fatal error on field creation form caused by invalid `get_string()` identifier (space in key).
- `is_empty()` always returned `false` for an empty selection stored as `[]`.
- `set_value()` stored data in CSV format instead of JSON, causing inconsistency with `instance_form_save()`.
- Required field validation incorrectly required all checkboxes to be checked; replaced with server-side `instance_form_validation()` checking "at least one selected".
- Direct `$_POST` access replaced with `optional_param_array()`.

### Improvements
- Inline JavaScript migrated to a proper AMD module (`amd/src/options_editor.js`).
- Inline CSS moved to `styles.css`.
- Privacy API provider added (`classes/privacy/provider.php`).
- `version.php` metadata corrected (component, copyright, maturity, release).
- PHPUnit tests added (8 tests, 30 assertions) — pass on Moodle 4.5 and 5.0.
- Behat tests added (3 scenarios, 41 steps).
- Moodle Code Checker (phpcs, `moodle` standard) reports zero issues.
- Dead code and unused imports removed.
- Language strings sorted alphabetically; missing strings added (`pluginname_help`, `errorrequiredatleastone`).
