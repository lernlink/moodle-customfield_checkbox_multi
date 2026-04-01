<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Customfield checkbox multi plugin
 *
 * @package   customfield_checkbox_multi
 * @copyright  2026 Boxuan Liu <boxuan.liu@tu-dresden.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['defaultvalue'] = 'Default values';
$string['defaultvalue_help'] = 'Enter default values, one per line. These options will be pre-selected when creating new '
    . 'instances. For backward compatibility, you can also use comma-separated values.';
$string['defaultvalue_note'] = 'You can enter default values one per line, or use comma-separated values for backward '
    . 'compatibility.';
$string['errorconfigunique'] = 'The multiselect field cannot be defined as unique.';
$string['errordefaultvaluenotinlist'] = 'The default value "{$a}" is not one of the available options.';
$string['errornotenoughoptions'] = 'You must provide at least two options.';
$string['errorrequiredatleastone'] = 'Please select at least one option.';
$string['menuoptions'] = 'Menu options';
$string['pluginname'] = 'Multi-select';
$string['pluginname_help'] = 'Store multiple values by selecting one or more checkboxes.';
$string['privacy:metadata'] = 'The Multi-select field type plugin doesn\'t store any personal data; it uses tables '
    . 'defined in core.';
$string['specificsettings'] = 'Multi-select field settings';
$string['option'] = 'Option';
$string['menuoptionsdesc'] = 'Enter one option per field. You can add or remove options as needed.';
$string['errorduplicateoptions'] = 'Duplicate options are not allowed';
$string['default_option'] = 'Default option';
$string['addoptions'] = 'Add options';
$string['menuoptions_help'] = 'Enter one option per row and mark default options with the checkbox at the left.';
$string['contactadminforchange'] = 'Please contact the administrator for this change.';
