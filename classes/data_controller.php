<?php
namespace customfield_checkbox_multi;

use core_customfield\data;

defined('MOODLE_INTERNAL') || die;

class data_controller extends \core_customfield\data_controller {

    public function datafield(): string {
        return 'value';
    }

    public function get_default_value() {
        $defaultvalue = $this->get_field()->get_configdata_property('defaultvalue');
        if (empty($defaultvalue)) {
            return '';
        }

        $options = $this->get_field()->get_options();
        $defaultvaluesarray = [];

        $values = preg_split("/\s*\n\s*/", trim($defaultvalue));
        $values = array_filter($values, function($val) {
            return trim($val) !== '';
        });

        foreach ($values as $val) {
            $val = trim($val);
            if (!empty($val)) {
                $index = array_search($val, $options);
                if ($index !== false) {
                    $defaultvaluesarray[] = intval($index);
                }
            }
        }
        return implode(',', $defaultvaluesarray);
    }

    public function instance_form_definition(\MoodleQuickForm $mform) {
        // Get the system context
        $systemcontext = \context_system::instance();

        // Get common variables
        $elementname = $this->get_form_element_name();
        $field_label = $this->get_field()->get_formatted_name();

        $field = $this->get_field();
        $options = $field->get_options();
        $context = $this->get_field()->get_handler()->get_configuration_context();

        // Add a label for the field.
        $mform->addElement('static', $elementname . '_label', $field_label, '');

        // Add each checkbox individually.
        foreach ($options as $key => $option) {
            $option = trim($option);
            if (!empty($option)) {
                $formattedoption = format_string($option, true, ['context' => $context]);
                $mform->addElement('checkbox', $elementname . '[' . $key . ']', '', $formattedoption);
            }
        }


        if ($field->get_configdata_property('required')) {
            // Add validation rules for each checkbox.
            foreach ($options as $key => $option) {
                if (!empty(trim($option))) {
                    $mform->addRule($elementname . '[' . $key . ']', null, 'required', null, 'client');
                }
            }
        }
    }

    public function instance_form_before_set_data(\stdClass $instance) {
        $elementname = $this->get_form_element_name();
        $checkboxdata = [];

        // edited record
        if ($this->get('id')) {
            $json_value = $this->get($this->datafield());

            $saved_values = json_decode($json_value, true);

            if (is_array($saved_values)) {
                $current_options = $this->get_field()->get_options();

                foreach ($saved_values as $val) {
                    $index = array_search($val, $current_options);

                    if ($index !== false) {
                        $checkboxdata[$index] = 1;
                    }
                }
            }
        }
        // new record
        else {
            $defaultvalue = $this->get_default_value();

            if ($defaultvalue !== '') {
                $defaultarray = explode(',', $defaultvalue);
                foreach ($defaultarray as $index) {
                    $index = intval(trim($index));
                    if ($index >= 0) {
                        $checkboxdata[$index] = 1;
                    }
                }
            }
        }

        $instance->{$elementname} = $checkboxdata;
    }

    public function instance_form_save(\stdClass $datanew) {

        $elementname = $this->get_form_element_name();

        $fieldoptions = $this->get_field()->get_options();
        $selectedvalues = [];

        if (property_exists($datanew, $elementname)) {
            $formvalue = $datanew->$elementname;
            if (is_array($formvalue)) {
                foreach ($formvalue as $key => $checked) {
                    if ($checked && isset($fieldoptions[$key])) {
                        $selectedvalues[] = $fieldoptions[$key];
                    }
                }
            }
        }

        $value = json_encode(array_values($selectedvalues));

        $this->data->set($this->datafield(), $value);
        $this->save();
    }

    public function get_value() {
        // If it is a new record, return the default value.
        if (!$this->get('id')) {
            return $this->get_default_value();
        }

        // For existing records, directly return the value from the database.
        $value = $this->get($this->datafield());

        return $value !== null ? $value : '';
    }

    public function set_value($value) {
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        return $this->set($this->datafield(), $value);
    }

    protected function is_empty($value): bool {
        return trim($value) === "";
    }
}
