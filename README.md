# Multi-select custom field (`customfield_checkbox_multi`)

[![Moodle Plugin CI](https://github.com/lernlink/moodle-customfield_checkbox_multi/actions/workflows/moodle-ci.yml/badge.svg)](https://github.com/lernlink/moodle-customfield_checkbox_multi/actions/workflows/moodle-ci.yml)

This plugin adds a custom field type for Moodle that lets users select multiple values using checkboxes.

## Features

- Define multiple options in customfield configuration.
- Mark one or more options as defaults.
- Validate required fields server-side (at least one option must be selected).
- Store selected values as JSON for a consistent data format.

## Requirements

- Moodle `4.5+` (based on `version.php` requirement).
- PHP version supported by your Moodle version.

## Installation

1. Copy this plugin to:
   `customfield/field/checkbox_multi`
2. Visit `Site administration > Notifications`.
3. Complete the upgrade steps.

## Usage

1. Go to an area that supports custom fields (for example, course custom fields).
2. Add a new field of type **Multi-select**.
3. Add at least two options.
4. Tick the checkbox next to any option that should be selected by default.
5. Save changes.

## Data format

- Submitted values are stored in `customfield_data.value` as a JSON array.
- Empty selections are stored as `[]`.

## Privacy

This plugin includes a privacy provider and relies on core customfield tables.

## Testing

- PHPUnit tests: `customfield/field/checkbox_multi/tests/plugin_test.php`
- Behat tests: `customfield/field/checkbox_multi/tests/behat/field.feature`

## Author

- Boxuan Liu `<boxuan.liu@tu-dresden.de>`

## License

GNU GPL v3 or later.
