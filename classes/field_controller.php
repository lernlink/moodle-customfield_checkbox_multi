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
 * Field controller for multiselect custom field.
 *
 * @package   customfield_checkbox_multi
 * @copyright  2026 Boxuan Liu <boxuan.liu@tu-dresden.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class field_controller extends \core_customfield\field_controller {
    /** @var string Plugin type */
    const TYPE = 'multiselect';

    /**
     * Add specific settings to the field configuration form.
     *
     * @param \MoodleQuickForm $mform
     */
    #[\Override]
    public function config_form_definition(\MoodleQuickForm $mform) {
        $mform->addElement('header', 'header_specificsettings',
            get_string('specificsettings', 'customfield_checkbox_multi'));
        $mform->setExpanded('header_specificsettings', true);

        $submittedconfig = optional_param_array('configdata', null, PARAM_RAW);
        $submittedoptionsui = optional_param_array('configdata_options_ui', null, PARAM_RAW);
        if (is_array($submittedconfig) && array_key_exists('options', $submittedconfig)) {
            $submittedoptions = $this->parse_submitted_options($submittedconfig['options']);
        } elseif (is_array($submittedoptionsui)) {
            $submittedoptions = $this->parse_options_array($submittedoptionsui);
        } else {
            $submittedoptions = $this->get_options();
        }

        $defaultarray = $this->parse_default_values((string)$this->get_configdata_property('defaultvalue'));
        $optioncount = max(count($submittedoptions), 2);

        $mform->addElement('hidden', 'configdata[options]', '');
        $mform->setType('configdata[options]', PARAM_RAW);
        $mform->addElement('hidden', 'configdata[defaultvalue]', '');
        $mform->setType('configdata[defaultvalue]', PARAM_RAW);

        if ($submittedoptions !== []) {
            $mform->setDefault('configdata[options]', implode("\n", $submittedoptions));
        }

        $alloptionshtml = '<div class="customfield-checkbox-multi-wrapper">';
        $alloptionshtml .= '<div class="customfield-checkbox-multi-options-container">';

        for ($i = 0; $i < $optioncount; $i++) {
            $optiontext = $submittedoptions[$i] ?? '';
            $optionvalue = s($optiontext);
            $isdefault = in_array($optiontext, $defaultarray, true) ? 'checked' : '';
            $defaultoptiontext = s(get_string('default_option', 'customfield_checkbox_multi'));

            $alloptionshtml .= '<div class="option-item customfield-checkbox-multi-option-item" data-index="' . $i . '">';
            $alloptionshtml .= '<div class="form-check customfield-checkbox-multi-check">';
            $alloptionshtml .= '<input type="checkbox" class="form-check-input default-checkbox" ';
            $alloptionshtml .= 'id="default_' . $i . '" ' . $isdefault . ' data-index="' . $i . '" ';
            $alloptionshtml .= 'title="' . $defaultoptiontext . '" />';
            $alloptionshtml .= '<label class="form-check-label accesshide" for="default_' . $i . '">';
            $alloptionshtml .= $defaultoptiontext;
            $alloptionshtml .= '</label>';
            $alloptionshtml .= '</div>';

            $alloptionshtml .= '<input type="text" class="form-control option-input customfield-checkbox-multi-option-input" ';
            $alloptionshtml .= 'data-index="' . $i . '" name="configdata_options_ui[]" value="' . $optionvalue . '" />';
            $alloptionshtml .= '<button type="button" class="btn btn-danger btn-sm remove-option ';
            $alloptionshtml .= 'customfield-checkbox-multi-remove-option">';
            $alloptionshtml .= '<i class="fa fa-times"></i>';
            $alloptionshtml .= '</button>';
            $alloptionshtml .= '</div>';
        }

        $alloptionshtml .= '</div>';
        $alloptionshtml .= '<div class="text-danger small customfield-checkbox-multi-message"></div>';
        $alloptionshtml .= '<div class="customfield-checkbox-multi-actions">';
        $alloptionshtml .= '<button type="button" class="btn btn-secondary btn-sm customfield-checkbox-multi-add-option">';
        $alloptionshtml .= '<i class="fa fa-plus"></i> ' . get_string('addoptions', 'customfield_checkbox_multi');
        $alloptionshtml .= '</button>';
        $alloptionshtml .= '</div>';
        $alloptionshtml .= '</div>';

        $requiredsuffix = ' (' . get_string('required') . ')';
        $mform->addElement(
            'static',
            'options_label',
            get_string('menuoptions', 'customfield_checkbox_multi') . $requiredsuffix,
            $alloptionshtml
        );
        $mform->addHelpButton('options_label', 'menuoptions', 'customfield_checkbox_multi');

    }

    /**
     * Register dynamic-form JavaScript requirements.
     *
     * @param \MoodleQuickForm $mform
     */
    public function config_form_dynamic_requirements(\MoodleQuickForm $_mform): void {
        $this->initialise_options_editor();
    }

    /**
     * Load AMD module for options editor.
     */
    private function initialise_options_editor(): void {
        global $PAGE;

        $PAGE->requires->js_call_amd(
            'customfield_checkbox_multi/options_editor',
            'init',
            [[
                'defaultoptiontitle' => get_string('default_option', 'customfield_checkbox_multi'),
                'defaultoptionlabel' => get_string('default_option', 'customfield_checkbox_multi'),
                'errornotenoughoptions' => get_string('errornotenoughoptions', 'customfield_checkbox_multi'),
            ]]
        );
    }

    /**
     * Get options array.
     *
     * @return array
     */
    public function get_options(): array {
        $options = $this->get_configdata_property('options');
        return $this->parse_submitted_options($options);
    }

    /**
     * Split multiline options into a clean array.
     *
     * @param string $optionsstring
     * @return array
     */
    private function parse_options(string $optionsstring): array {
        if (trim($optionsstring) === '') {
            return [];
        }

        $options = preg_split("/\s*\n\s*/", trim($optionsstring));
        if ($options === false) {
            return [];
        }

        $options = array_filter($options, static function(string $option): bool {
            return trim($option) !== '';
        });

        return array_values($options);
    }

    /**
     * Parse options from array input.
     *
     * @param array $options
     * @return array
     */
    private function parse_options_array(array $options): array {
        $cleanoptions = array_map(static function($option): string {
            return trim((string)$option);
        }, $options);

        $cleanoptions = array_filter($cleanoptions, static function(string $option): bool {
            return $option !== '';
        });

        return array_values($cleanoptions);
    }

    /**
     * Parse options from either string or array form submissions.
     *
     * @param mixed $options
     * @return array
     */
    private function parse_submitted_options($options): array {
        if (is_array($options)) {
            return $this->parse_options_array($options);
        }
        return $this->parse_options((string)$options);
    }

    /**
     * Parse default values from newline or comma-separated format.
     *
     * @param string $defaultvaluestring
     * @return array
     */
    private function parse_default_values(string $defaultvaluestring): array {
        if (trim($defaultvaluestring) === '') {
            return [];
        }

        $values = preg_split("/\\s*(?:\\n|,)\\s*/", trim($defaultvaluestring));
        if ($values === false) {
            return [];
        }

        $values = array_filter($values, static function(string $value): bool {
            return trim($value) !== '';
        });

        return array_values($values);
    }

    /**
     * Static helper to get options.
     *
     * @param \core_customfield\field_controller $field
     * @return array
     */
    public static function get_options_array(\core_customfield\field_controller $field): array {
        return $field->get_options();
    }

    /**
     * Validate the data on the field configuration form.
     *
     * @param array $data from the add/edit profile field form
     * @param array $files
     * @return array associative array of error messages
     */
    #[\Override]
    public function config_form_validation(array $data, $files = array()): array {
        $errors = parent::config_form_validation($data, $files);

        $options = [];
        if (isset($data['configdata']['options'])) {
            $options = $this->parse_submitted_options($data['configdata']['options']);
        }
        if ($options === [] && isset($data['configdata_options_ui']) && is_array($data['configdata_options_ui'])) {
            $options = $this->parse_options_array($data['configdata_options_ui']);
        }

        if (count($options) < 2) {
            $errors['options_label'] = get_string('errornotenoughoptions', 'customfield_checkbox_multi');
        }

        $uniqueoptions = array_unique(array_map('trim', $options));
        if (count($uniqueoptions) < count($options)) {
            $errors['options_label'] = get_string('errorduplicateoptions', 'customfield_checkbox_multi');
        }

        if (!empty($data['configdata']['defaultvalue'])) {
            $defaultvalues = $this->parse_default_values(trim($data['configdata']['defaultvalue']));
            $cleanoptions = array_map('trim', $options);

            foreach ($defaultvalues as $value) {
                $value = trim($value);
                if ($value !== '' && !in_array($value, $cleanoptions, true)) {
                    $errors['options_label'] = get_string(
                        'errordefaultvaluenotinlist',
                        'customfield_checkbox_multi',
                        $value
                    );
                    break;
                }
            }
        }

        return $errors;
    }
}
