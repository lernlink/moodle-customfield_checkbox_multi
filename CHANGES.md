# Changelog

## v0.9.4 (2026-08-10)

Addresses the review findings reported in issues #1 and #3–#6.

### Bug fixes
- Required-field validation now counts only checked entries whose key exists in the field options, so it agrees with what `instance_form_save()` stores (#3).
- `amd/build/options_editor.min.js` is a real Grunt build again, with a source map, instead of an unminified copy of the source (#1).

### Improvements
- `amd/src/options_editor.js` rewritten as an ES module, as expected by the Moodle JS build (#1).
- Removed the dead `config_form_dynamic_requirements()` method, which core never calls, and documented why the `before_standard_head_html_generation` hook is required (#5).
- Configuration UI markup moved from `field_controller::config_form_definition()` into `templates/options_editor.mustache` (#6).
- Language strings are plain `$string['id'] = 'value';` assignments without concatenation (#4).
- GitHub Actions CI (`.github/workflows/moodle-ci.yml`) runs the Moodle plugin checks on every push, green on Moodle 4.5, 5.0, 5.1 and 5.2 (#2).
- Behat coverage no longer depends on the core field management controls, which differ between Moodle versions. It now checks the plugin itself: creating a field through the options editor, storing and reloading a selection on the course settings form, and the required-field rule.
- Single `main` branch replaces the identical per-version branches; supported Moodle versions are declared in `version.php`.

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
