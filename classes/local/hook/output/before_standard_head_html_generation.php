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

namespace customfield_checkbox_multi\local\hook\output;

/**
 * Hook to add the options editor AMD module to the page head.
 *
 * @package   customfield_checkbox_multi
 * @copyright 2026 Boxuan Liu <boxuan.liu@tu-dresden.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class before_standard_head_html_generation {
    /**
     * Register the options editor AMD module.
     *
     * @param \core\hook\output\before_standard_head_html_generation $hook
     * @return void
     */
    public static function callback(\core\hook\output\before_standard_head_html_generation $hook): void {
        require_once(__DIR__ . '/../../../../lib.php');
        customfield_checkbox_multi_require_options_editor();
    }
}
