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

namespace customfield_checkbox_multi\privacy;

use core_customfield\data_controller;
use core_customfield\privacy\customfield_provider;
use core_privacy\local\metadata\null_provider;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for customfield_checkbox_multi.
 *
 * @package    customfield_checkbox_multi
 * @copyright  2026 Boxuan Liu <boxuan.liu@tu-dresden.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements null_provider, customfield_provider {

    /**
     * Language string identifier for null-provider reason.
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }

    /**
     * Preprocess customfield data before export.
     *
     * @param data_controller $data
     * @param \stdClass $exportdata
     * @param array $subcontext
     */
    public static function export_customfield_data(data_controller $data, \stdClass $exportdata, array $subcontext): void {
        $exportdata->value = $data->export_value();
        writer::with_context($data->get_context())->export_data($subcontext, $exportdata);
    }

    /**
     * Callback before deleting related data.
     *
     * @param string $select
     * @param array $params
     * @param array $contextids
     */
    public static function before_delete_data(string $select, array $params, array $contextids): void {
    }

    /**
     * Callback before deleting related field config.
     *
     * @param string $select
     * @param array $params
     * @param array $contextids
     */
    public static function before_delete_fields(string $select, array $params, array $contextids): void {
    }
}
