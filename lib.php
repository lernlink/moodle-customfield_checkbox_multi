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
 * Library functions for the multi-select custom field.
 *
 * @package    customfield_checkbox_multi
 * @copyright  2026 Boxuan Liu <boxuan.liu@tu-dresden.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Register the options editor AMD module on the current page.
 *
 * @package customfield_checkbox_multi
 *
 * @return void
 */
function customfield_checkbox_multi_require_options_editor(): void {
    global $PAGE;

    static $initialised = false;
    if ($initialised) {
        return;
    }

    $PAGE->requires->js_call_amd(
        'customfield_checkbox_multi/options_editor',
        'init',
        [[
            'defaultoptiontitle' => get_string('default_option', 'customfield_checkbox_multi'),
            'defaultoptionlabel' => get_string('default_option', 'customfield_checkbox_multi'),
            'errornotenoughoptions' => get_string('errornotenoughoptions', 'customfield_checkbox_multi'),
            'debugenabled' => false,
        ]]
    );

    $initialised = true;
}
