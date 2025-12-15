<?php
namespace customfield_checkbox_multi;

defined('MOODLE_INTERNAL') || die;

class field_controller extends \core_customfield\field_controller {
    const TYPE = 'multiselect';

    public function config_form_definition(\MoodleQuickForm $mform) {
        global $_POST;

        $mform->addElement('header', 'header_specificsettings', get_string('specificsettings', 'customfield_checkbox_multi'));
        $mform->setExpanded('header_specificsettings', true);

        // Try to get submitted options first (for validation errors)
        $submittedOptions = [];
        $hasSubmittedData = false;

        // Check if form was submitted and has validation errors
        if (!empty($_POST) && isset($_POST['configdata']['options'])) {
            // Get options from the hidden field
            $submittedOptionsString = $_POST['configdata']['options'];
            if (!empty($submittedOptionsString)) {
                $submittedOptions = preg_split("/\s*\n\s*/", trim($submittedOptionsString));
                $submittedOptions = array_filter($submittedOptions, function($option) {
                    return trim($option) !== '';
                });
                $hasSubmittedData = true;
            }
        }

        // If no submitted data, get existing saved options
        if (!$hasSubmittedData) {
            $submittedOptions = $this->get_options();
        }

        // Get existing default values
        $existingDefaults = $this->get_configdata_property('defaultvalue');
        $defaultArray = [];
        if (!empty($existingDefaults)) {
            $defaultArray = preg_split("/\s*\n\s*/", trim($existingDefaults));
        }

        // Ensure at least 2 option fields
        $optioncount = max(count($submittedOptions), 2);

        // Hidden field to store all options
        $mform->addElement('hidden', 'configdata[options]', '');
        $mform->setType('configdata[options]', PARAM_RAW);

        // Hidden field for default values
        $mform->addElement('hidden', 'configdata[defaultvalue]', '');
        $mform->setType('configdata[defaultvalue]', PARAM_TEXT);

        // Set default value for hidden field
        if (!empty($submittedOptions)) {
            $mform->setDefault('configdata[options]', implode("\n", $submittedOptions));
        }

        // Build all options container
        $allOptionsHtml = '<div id="customfield-options-wrapper" style="max-width: 600px;">';

        // Container for all options
        $allOptionsHtml .= '<div id="options-container">';

        // Generate option fields with submitted or existing values
        for ($i = 0; $i < $optioncount; $i++) {
            $optionvalue = isset($submittedOptions[$i]) ? htmlspecialchars($submittedOptions[$i]) : '';
            $isDefault = in_array($optionvalue, $defaultArray) ? 'checked' : '';

            $allOptionsHtml .= '<div class="option-item d-flex align-items-center mb-2" data-index="' . $i . '">';

            // Add checkbox for default value
            $allOptionsHtml .= '<div class="form-check ml-2 mr-2" style="margin-bottom: 0;">';
            $allOptionsHtml .= '<input type="checkbox" class="form-check-input default-checkbox" ';
            $allOptionsHtml .= 'id="default_' . $i . '" ' . $isDefault . ' ';
            $allOptionsHtml .= 'data-index="' . $i . '" ';
            $allOptionsHtml .= 'title="' . get_string('default option', 'customfield_checkbox_multi') . '" />';
            $allOptionsHtml .= '<label class="form-check-label" for="default_' . $i . '" style="display: none;"></label>';
            $allOptionsHtml .= '</div>';

            $allOptionsHtml .= '<input type="text" class="form-control option-input" ';
            $allOptionsHtml .= 'data-index="' . $i . '" ';
            $allOptionsHtml .= 'value="' . $optionvalue . '" />';

            $allOptionsHtml .= '<button type="button" class="btn btn-danger btn-sm remove-option">';
            $allOptionsHtml .= '<i class="fa fa-times"></i>';
            $allOptionsHtml .= '</button>';
            $allOptionsHtml .= '</div>';
        }

        $allOptionsHtml .= '</div>'; // End options-container

        // Message container
        $allOptionsHtml .= '<div id="options-message" class="text-danger small mt-1" style="display: none;"></div>';

        // Add button
        $allOptionsHtml .= '<div class="mt-2">';
        $allOptionsHtml .= '<button type="button" class="btn btn-secondary btn-sm" id="add-option">';
        $allOptionsHtml .= '<i class="fa fa-plus"></i> ' . get_string('addoptions', 'customfield_checkbox_multi');
        $allOptionsHtml .= '</button>';
        $allOptionsHtml .= '</div>';

        $allOptionsHtml .= '</div>'; // End customfield-options-wrapper

        // Add help Label.
        $mform->addElement('static', 'options_label', get_string('menuoptions', 'customfield_checkbox_multi'). ' ' . "(required)", $allOptionsHtml);
        $mform->addHelpButton('options_label', 'menuoptions_help', 'customfield_checkbox_multi');
//        $mform->addRule('options_label', get_string('errornotenoughoptions', 'customfield_checkbox_multi'), 'required', null, 'server');

        // Add JavaScript
        $mform->addElement('html', $this->get_options_javascript());
    }

    /**
     * Override to properly handle form data after submission
     */
    public function config_form_before_set_data(\stdClass $data) {
        global $_POST;

        parent::config_form_before_set_data($data);

        // If there's submitted data (validation failed), preserve it
        if (!empty($_POST) && isset($_POST['configdata']['options'])) {
            $data->configdata['options'] = $_POST['configdata']['options'];
        }
    }

    /**
     * JavaScript for managing options with inline default checkbox
     */
    private function get_options_javascript() {
        // Get existing default values
        $existingDefaults = $this->get_configdata_property('defaultvalue');
        $defaultArray = [];
        if (!empty($existingDefaults)) {
            $defaultArray = preg_split("/\s*\n\s*/", trim($existingDefaults));
        }
        $defaultsJson = json_encode($defaultArray);

        return <<<EOD
<script type="text/javascript">
(function() {
    // Store existing default values
    var existingDefaults = {$defaultsJson};

    // Simple message display
    function showMessage(message) {
        var messageDiv = document.getElementById('options-message');
        if (messageDiv) {
            messageDiv.textContent = message;
            messageDiv.style.display = 'block';

            setTimeout(function() {
                messageDiv.style.display = 'none';
            }, 3000);
        }
    }

    function hideMessage() {
        var messageDiv = document.getElementById('options-message');
        if (messageDiv) {
            messageDiv.style.display = 'none';
        }
    }

    // Update default value hidden field based on checkbox selections
    function updateDefaultHiddenField() {
        var selectedDefaults = [];
        document.querySelectorAll('.option-item').forEach(function(item) {
            var input = item.querySelector('.option-input');
            var checkbox = item.querySelector('.default-checkbox');
            
            if (input && checkbox) {
                var value = input.value.trim();
                if (value !== '' && checkbox.checked) {
                    selectedDefaults.push(value);
                }
            }
        });

        var hiddenField = document.querySelector('input[name="configdata[defaultvalue]"]');
        if (hiddenField) {
            hiddenField.value = selectedDefaults.join('\\n');
        }

        // Update existingDefaults to maintain state
        existingDefaults = selectedDefaults;
    }

    // Update the hidden field with all options
    function updateHiddenField() {
        var options = [];
        document.querySelectorAll('.option-input').forEach(function(input) {
            var value = input.value.trim();
            options.push(value);
        });

        while (options.length > 0 && options[options.length - 1] === '') {
            options.pop();
        }

        var hiddenField = document.querySelector('input[name="configdata[options]"]');
        if (hiddenField) {
            hiddenField.value = options.join('\\n');
        }

        // Also update default values when options change
        updateDefaultHiddenField();
    }

    // Handle checkbox changes
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('default-checkbox')) {
            updateDefaultHiddenField();
        }
    });

    // Update hidden field whenever an option changes
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('option-input')) {
            updateHiddenField();
            hideMessage();
            
            // If the option value changes and it was a default, uncheck the checkbox
            var optionItem = e.target.closest('.option-item');
            if (optionItem) {
                var checkbox = optionItem.querySelector('.default-checkbox');
                var value = e.target.value.trim();
                
                // If value is empty or changed, uncheck the default checkbox
                if (checkbox && value === '') {
                    checkbox.checked = false;
                    updateDefaultHiddenField();
                }
            }
        }
    });

    document.addEventListener('blur', function(e) {
        if (e.target.classList.contains('option-input')) {
            updateHiddenField();
        }
    }, true);

    var optionIndex = document.querySelectorAll('.option-input').length;

    // Add option functionality
    document.getElementById('add-option').addEventListener('click', function() {
        var container = document.getElementById('options-container');

        var newOption = document.createElement('div');
        newOption.className = 'option-item d-flex align-items-center mb-2';
        newOption.setAttribute('data-index', optionIndex);

        newOption.innerHTML =
            '<div class="form-check ml-2 mr-2" style="margin-bottom: 0;">' +
            '<input type="checkbox" class="form-check-input default-checkbox" ' +
            'id="default_' + optionIndex + '" data-index="' + optionIndex + '" ' +
            'title="Default value" />' +
            '<label class="form-check-label" for="default_' + optionIndex + '" style="display: none;"></label>' +
            '</div>' +
            '<input type="text" class="form-control option-input" ' +
            'data-index="' + optionIndex + '" value="" />' +
            '<button type="button" class="btn btn-danger btn-sm remove-option">' +
            '<i class="fa fa-times"></i>' +
            '</button>';

        container.appendChild(newOption);

        var newInput = newOption.querySelector('.option-input');
        newInput.addEventListener('input', function() {
            updateHiddenField();
            hideMessage();
        });
        newInput.addEventListener('blur', function() {
            updateHiddenField();
        });

        // Focus on the new input
        newInput.focus();

        var deleteBtn = newOption.querySelector('.remove-option');
        attachDeleteEvent(deleteBtn);

        optionIndex++;

        // Update hidden field immediately
        updateHiddenField();
    });

    // Delete functionality
    function attachDeleteEvent(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            var optionItem = this.closest('.option-item');
            var totalOptions = document.querySelectorAll('.option-input').length;

            if (totalOptions > 2) {
                optionItem.remove();

                // Re-index remaining options
                var items = document.querySelectorAll('.option-item');
                items.forEach(function(item, index) {
                    item.setAttribute('data-index', index);
                    var input = item.querySelector('.option-input');
                    if (input) {
                        input.setAttribute('data-index', index);
                    }
                    var checkbox = item.querySelector('.default-checkbox');
                    if (checkbox) {
                        checkbox.setAttribute('data-index', index);
                        checkbox.id = 'default_' + index;
                    }
                });

                updateHiddenField();
            } else {
                showMessage('You must keep at least 2 options');
            }
        });
    }

    // Attach delete events to existing buttons
    document.querySelectorAll('.remove-option').forEach(function(button) {
        attachDeleteEvent(button);
    });

    // Initialize on load
    window.addEventListener('load', function() {
        updateHiddenField();
    });

    // Also update immediately
    updateHiddenField();

    // Update before form submission
    var form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function() {
            updateHiddenField();
            updateDefaultHiddenField();
            return true;
        });
    }
})();
</script>

<style>
#customfield-options-wrapper {
    width: 100%;
}

#options-container {
    width: 100%;
}

.option-item {
    display: flex;
    align-items: center;
    width: 100%;
    margin-bottom: 0.5rem;
}

.option-item .option-input {
    flex: 1;
    width: 100%;
}

.option-item .default-checkbox {
    cursor: pointer;
    width: 18px;
    height: 18px;
}

.option-item .form-check {
    margin: 0;
    min-height: auto;
}

.option-item .remove-option {
    width: 38px;
    height: 38px;
    padding: 0;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-left: 0.5rem; 
}

#all-options-container {
    position: relative;
}

#all-options-container .option-item:first-child {
    margin-top: 0;
}

#add-option {
    font-size: 0.9rem;
}

.option-item:hover .option-input {
    border-color: #80bdff;
}

.default-checkbox[title]:hover::after {
    content: attr(title);
    position: absolute;
    z-index: 1000;
    background-color: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 4px 8px;
    font-size: 12px;
    border-radius: 4px;
    white-space: nowrap;
    margin-top: -30px;
    margin-left: -10px;
}

#options-message {
    font-size: 0.875rem;
    color: #dc3545;
}

#fitem_id_options_label .form-inline.felement {
    min-height: 38px;
    display: flex;
    align-items: center;
}

.ml-2 { margin-left: 0.5rem !important; }
.mr-2 { margin-right: 0.5rem !important; }
.mb-2 { margin-bottom: 0.5rem !important; }
.mt-2 { margin-top: 0.5rem !important; }
.mt-1 { margin-top: 0.25rem !important; }

@media (max-width: 768px) {
    #all-options-container {
        margin-top: 0 !important;
    }

    .form-group.row .col-md-3,
    .form-group.row .col-md-9 {
        max-width: 100%;
        flex: 0 0 100%;
    }
}
</style>
EOD;
    }

    /**
     * Get options array
     */
    public function get_options(): array {
        $options_data = $this->get_configdata_property('options');

        if (empty($options_data)) {
            return array();
        }

        $options = preg_split("/\s*\n\s*/", trim($options_data));
        $options = array_filter($options, function($option) {
            return trim($option) !== '';
        });

        return array_values($options);
    }

    /**
     * Static helper to get options
     */
    public static function get_options_array(\core_customfield\field_controller $field): array {
        return $field->get_options();
    }

    /**
     * Validate the configuration form
     */
    public function config_form_validation(array $data, $files = array()): array {
        $errors = [];

        $options = [];
        if (isset($data['configdata']['options'])) {
            $options_string = trim($data['configdata']['options']);
            if (!empty($options_string)) {
                $options = preg_split("/\s*\n\s*/", $options_string);
                $options = array_filter($options, function($option) {
                    return trim($option) !== '';
                });
            }
        }

        // Check minimum number of options
        if (count($options) < 2) {
            $errors['options_label'] = get_string('errornotenoughoptions', 'customfield_checkbox_multi');
        }

        // Check for duplicate options
        $uniqueOptions = array_unique(array_map('trim', $options));
        if (count($uniqueOptions) < count($options)) {
            $errors['options_label'] = 'Duplicate options are not allowed';
        }

        // Validate default values
        if (!empty($data['configdata']['defaultvalue'])) {
            $defaultvalue = trim($data['configdata']['defaultvalue']);

            $defaultValues = preg_split("/\s*\n\s*/", $defaultvalue);
            $defaultValues = array_filter($defaultValues, function($val) {
                return trim($val) !== '';
            });

            $cleanOptions = array_map('trim', $options);

            foreach ($defaultValues as $val) {
                $val = trim($val);
                if (!empty($val) && !in_array($val, $cleanOptions)) {
                    $errorMessage = get_string('errordefaultvaluenotinlist', 'customfield_checkbox_multi', $val);
                    $errors['options_label'] = $errorMessage;
                    break;
                }
            }
        }
        return $errors;
    }
}