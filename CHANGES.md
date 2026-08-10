# Changelog

## v0.9.4 (2026-08-10)

Addresses the review findings reported in issues #1 and #3–#6.

### Bug fixes
- Required-field validation now counts only checked entries whose key exists in the field options, so it agrees with what `instance_form_save()` stores (#3).
- `amd/build/options_editor.min.js` is a real Grunt build again, with a source map, instead of an unminified copy of the source (#1).

### Improvements
- `amd/src/options_editor.js` rewritten as an ES module, as expected by the Moodle JS build (#1).
- The options editor AMD module is loaded only from the field configuration form; the global `before_standard_head_html_generation` hook, `db/hooks.php` and `lib.php` were removed (#5).
- Configuration UI markup moved from `field_controller::config_form_definition()` into `templates/options_editor.mustache` (#6).
- Language strings are plain `$string['id'] = 'value';` assignments without concatenation (#4).
- GitHub Actions CI (`.github/workflows/moodle-ci.yml`) runs the Moodle plugin checks on every push (#2).

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
