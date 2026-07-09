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
 * @copyright 2026 Boxuan Liu <boxuan.liu@tu-dresden.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace customfield_checkbox_multi;

defined('MOODLE_INTERNAL') || die;

/**
 * Data controller for multiselect custom field.
 *
 * @package   customfield_checkbox_multi
 * @copyright 2026 Boxuan Liu <boxuan.liu@tu-dresden.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class data_controller extends \core_customfield\data_controller {

    /**
     * Return storage field name.
     *
     * @return string
     */
    #[\Override]
    public function datafield(): string {
        return 'value';
    }

    /**
     * Return default value in storage format.
     *
     * @return string
     */
    #[\Override]
    public function get_default_value() {
        $defaultvalue = $this->get_field()->get_configdata_property('defaultvalue');
        if (empty($defaultvalue)) {
            return '';
        }

        $options = $this->get_field()->get_options();
        $defaultvaluesarray = [];
        $values = preg_split("/\s*(?:\n|,)\s*/", trim($defaultvalue));

        if ($values === false) {
            return '';
        }

        $values = array_filter($values, static function(string $value): bool {
            return trim($value) !== '';
        });

        foreach ($values as $value) {
            $value = trim($value);
            if ($value === '') {
                continue;
            }
            $index = array_search($value, $options, true);
            if ($index !== false) {
                $defaultvaluesarray[] = (int)$index;
            }
        }

        return implode(',', $defaultvaluesarray);
    }

    /**
     * Add a field to the instance edit form.
     *
     * @param \MoodleQuickForm $mform
     */
    #[\Override]
    public function instance_form_definition(\MoodleQuickForm $mform) {
        $elementname = $this->get_form_element_name();
        $fieldlabel = $this->get_field()->get_formatted_name();
        $field = $this->get_field();
        $options = $field->get_options();
        $context = $field->get_handler()->get_configuration_context();

        $mform->addElement('static', $elementname . '_label', $fieldlabel, '');

        foreach ($options as $key => $option) {
            $option = trim($option);
            if ($option === '') {
                continue;
            }
            $formattedoption = format_string($option, true, ['context' => $context]);
            $mform->addElement('checkbox', $elementname . '[' . $key . ']', '', $formattedoption);
        }

    }

    /**
     * Validate submitted data.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    #[\Override]
    public function instance_form_validation(array $data, array $files): array {
        $errors = parent::instance_form_validation($data, $files);

        if (!$this->get_field()->get_configdata_property('required')) {
            return $errors;
        }

        $elementname = $this->get_form_element_name();
        $submittedvalues = $data[$elementname] ?? [];
        $hasselection = false;

        if (is_array($submittedvalues)) {
            foreach ($submittedvalues as $checked) {
                if (!empty($checked)) {
                    $hasselection = true;
                    break;
                }
            }
        } else if (!empty($submittedvalues)) {
            $hasselection = true;
        }

        if (!$hasselection) {
            $errors[$elementname . '[0]'] = get_string('errorrequiredatleastone', 'customfield_checkbox_multi');
        }

        return $errors;
    }

    /**
     * Prepare custom field data before calling set_data() on form.
     *
     * @param \stdClass $instance
     */
    #[\Override]
    public function instance_form_before_set_data(\stdClass $instance) {
        $elementname = $this->get_form_element_name();
        $checkboxdata = [];

        if ($this->get('id')) {
            $jsonvalue = $this->get($this->datafield());
            $savedvalues = json_decode((string)$jsonvalue, true);
            if (is_array($savedvalues)) {
                $currentoptions = $this->get_field()->get_options();
                foreach ($savedvalues as $value) {
                    $index = array_search($value, $currentoptions, true);
                    if ($index !== false) {
                        $checkboxdata[$index] = 1;
                    }
                }
            }
        } else {
            $defaultvalue = $this->get_default_value();
            if ($defaultvalue !== '') {
                $defaultarray = explode(',', $defaultvalue);
                foreach ($defaultarray as $index) {
                    $index = (int)trim($index);
                    if ($index >= 0) {
                        $checkboxdata[$index] = 1;
                    }
                }
            }
        }

        $instance->{$elementname} = $checkboxdata;
    }

    /**
     * Save submitted data.
     *
     * @param \stdClass $datanew data coming from form
     */
    #[\Override]
    public function instance_form_save(\stdClass $datanew) {
        $elementname = $this->get_form_element_name();
        $fieldoptions = $this->get_field()->get_options();
        $selectedvalues = [];

        if (property_exists($datanew, $elementname) && is_array($datanew->{$elementname})) {
            foreach ($datanew->{$elementname} as $key => $checked) {
                if ($checked && isset($fieldoptions[$key])) {
                    $selectedvalues[] = $fieldoptions[$key];
                }
            }
        }

        $value = json_encode(array_values($selectedvalues));
        if ($value === false) {
            $value = '[]';
        }

        if (!$this->get('contextid') && $this->get('instanceid')) {
            $this->data->set('contextid', $this->get_context()->id);
        }

        $this->data->set($this->datafield(), $value);
        $this->save();
    }

    /**
     * Returns the value as it is stored in the database or default value if data record is not present.
     *
     * @return string
     */
    #[\Override]
    public function get_value() {
        if (!$this->get('id')) {
            return $this->get_default_value();
        }

        $value = $this->get($this->datafield());
        return $value !== null ? (string)$value : '';
    }

    /**
     * Returns value in a human-readable format.
     *
     * @return string|null
     */
    #[\Override]
    public function export_value() {
        $value = $this->get_value();
        if ($this->is_empty($value)) {
            return null;
        }

        $selectedvalues = [];
        if ($this->get('id')) {
            $selectedvalues = $this->normalise_values_for_storage($value);
        } else {
            $options = $this->get_field()->get_options();
            foreach (explode(',', (string)$value) as $index) {
                $cleanindex = trim($index);
                if ($cleanindex === '' || !is_numeric($cleanindex)) {
                    continue;
                }
                $optionindex = (int)$cleanindex;
                if (array_key_exists($optionindex, $options)) {
                    $selectedvalues[] = $options[$optionindex];
                }
            }
        }

        if ($selectedvalues === []) {
            return null;
        }

        $context = $this->get_field()->get_handler()->get_configuration_context();
        $selectedvalues = array_map(static function(string $option) use ($context): string {
            return format_string($option, true, ['context' => $context]);
        }, $selectedvalues);

        return implode(', ', $selectedvalues);
    }

    /**
     * Set value for current data field.
     *
     * @param mixed $value
     * @return \core_customfield\data
     */
    public function set_value($value) {
        $normalisedvalues = $this->normalise_values_for_storage($value);
        $encoded = json_encode($normalisedvalues);
        if ($encoded === false) {
            $encoded = '[]';
        }
        return $this->set($this->datafield(), $encoded);
    }

    /**
     * Checks if the value is empty.
     *
     * @param mixed $value
     * @return bool
     */
    #[\Override]
    protected function is_empty($value): bool {
        if (is_array($value)) {
            return $value === [];
        }

        $stringvalue = trim((string)$value);
        if ($stringvalue === '') {
            return true;
        }

        $decoded = json_decode($stringvalue, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded === [];
        }

        return false;
    }

    /**
     * Convert supported input formats into a clean list of values.
     *
     * @param mixed $value
     * @return array
     */
    private function normalise_values_for_storage($value): array {
        if (is_array($value)) {
            return $this->filter_values($value);
        }

        $stringvalue = trim((string)$value);
        if ($stringvalue === '') {
            return [];
        }

        $decoded = json_decode($stringvalue, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $this->filter_values($decoded);
        }

        return $this->filter_values(explode(',', $stringvalue));
    }

    /**
     * Remove empty values and normalise indexes.
     *
     * @param array $values
     * @return array
     */
    private function filter_values(array $values): array {
        $cleanvalues = array_map(static function($value): string {
            return trim((string)$value);
        }, $values);

        $cleanvalues = array_filter($cleanvalues, static function(string $value): bool {
            return $value !== '';
        });

        return array_values($cleanvalues);
    }
}
