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
 * @copyright 2018 Toni Barbera <toni@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace customfield_checkbox_multi;

defined('MOODLE_INTERNAL') || die;

/**
 * Data controller for multiselect custom field.
 *
 * @package   customfield_checkbox_multi
 * @copyright 2018 Toni Barbera <toni@moodle.com>
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
        $values = preg_split("/\s*\n\s*/", trim($defaultvalue));

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

        if ($field->get_configdata_property('required')) {
            foreach ($options as $key => $option) {
                if (trim($option) !== '') {
                    $mform->addRule($elementname . '[' . $key . ']', null, 'required', null, 'client');
                }
            }
        }
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
     * Set value for current data field.
     *
     * @param mixed $value
     * @return \core_customfield\data
     */
    public function set_value($value) {
        if (is_array($value)) {
            $encoded = json_encode(array_values($value));
            $value = $encoded === false ? '[]' : $encoded;
        }
        return $this->set($this->datafield(), $value);
    }

    /**
     * Checks if the value is empty.
     *
     * @param mixed $value
     * @return bool
     */
    #[\Override]
    protected function is_empty($value): bool {
        return trim((string)$value) === '';
    }
}
