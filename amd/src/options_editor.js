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
 * Options editor for multiselect custom field configuration.
 *
 * @module     customfield_checkbox_multi/options_editor
 * @copyright  2026 Boxuan Liu <boxuan.liu@tu-dresden.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    var SELECTORS = {
        wrapper: '.customfield-checkbox-multi-wrapper',
        optionsContainer: '.customfield-checkbox-multi-options-container',
        optionItem: '.customfield-checkbox-multi-option-item',
        optionInput: '.customfield-checkbox-multi-option-input',
        defaultCheckbox: '.default-checkbox',
        removeOptionButton: '.customfield-checkbox-multi-remove-option',
        addOptionButton: '.customfield-checkbox-multi-add-option',
        optionsInput: 'input[name="configdata[options]"]',
        defaultInput: 'input[name="configdata[defaultvalue]"]',
        message: '.customfield-checkbox-multi-message',
        form: 'form'
    };

    var strings = {
        defaultoptiontitle: '',
        defaultoptionlabel: '',
        errornotenoughoptions: ''
    };
    var messageTimeouts = {};
    var handlersbound = false;

    /**
     * Iterate over node list safely.
     *
     * @param {NodeList} list
     * @param {Function} callback
     */
    function forEachNode(list, callback) {
        var i;
        for (i = 0; i < list.length; i++) {
            callback(list[i], i);
        }
    }

    /**
     * Build a new option row element.
     *
     * @param {number} index
     * @returns {HTMLElement}
     */
    function createOptionRow(index) {
        var row = document.createElement('div');
        row.className = 'customfield-checkbox-multi-option-item';
        row.setAttribute('data-index', index);

        var checkContainer = document.createElement('div');
        checkContainer.className = 'form-check customfield-checkbox-multi-check';

        var checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'form-check-input default-checkbox';
        checkbox.id = 'default_' + index;
        checkbox.setAttribute('data-index', index);
        checkbox.title = strings.defaultoptiontitle;

        var label = document.createElement('label');
        label.className = 'form-check-label accesshide';
        label.setAttribute('for', 'default_' + index);
        label.textContent = strings.defaultoptionlabel;

        checkContainer.appendChild(checkbox);
        checkContainer.appendChild(label);

        var optionInput = document.createElement('input');
        optionInput.type = 'text';
        optionInput.className = 'form-control customfield-checkbox-multi-option-input';
        optionInput.setAttribute('data-index', index);
        optionInput.setAttribute('name', 'configdata_options_ui[]');
        optionInput.value = '';

        var removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'btn btn-danger btn-sm customfield-checkbox-multi-remove-option';
        removeButton.innerHTML = '<i class="fa fa-times"></i>';

        row.appendChild(checkContainer);
        row.appendChild(optionInput);
        row.appendChild(removeButton);

        return row;
    }

    /**
     * Get a stable key for wrapper-specific timers.
     *
     * @param {HTMLElement} wrapper
     * @returns {string}
     */
    function getWrapperKey(wrapper) {
        if (!wrapper) {
            return '';
        }

        if (!wrapper.getAttribute('data-editorid')) {
            wrapper.setAttribute('data-editorid', String(Date.now()) + String(Math.random()));
        }

        return wrapper.getAttribute('data-editorid');
    }

    /**
     * Hide helper message.
     *
     * @param {HTMLElement} wrapper
     */
    function hideMessage(wrapper) {
        var messageElement = wrapper ? wrapper.querySelector(SELECTORS.message) : null;
        if (!messageElement) {
            return;
        }

        messageElement.style.display = 'none';
        messageElement.textContent = '';
    }

    /**
     * Show helper message.
     *
     * @param {string} message
     */
    function showMessage(wrapper, message) {
        var messageElement = wrapper ? wrapper.querySelector(SELECTORS.message) : null;
        var wrapperKey = getWrapperKey(wrapper);
        if (!messageElement) {
            return;
        }

        messageElement.textContent = message;
        messageElement.style.display = 'block';

        if (wrapperKey && messageTimeouts[wrapperKey]) {
            clearTimeout(messageTimeouts[wrapperKey]);
        }

        messageTimeouts[wrapperKey] = setTimeout(function() {
            hideMessage(wrapper);
        }, 3000);
    }

    /**
     * Reindex option rows after add/remove.
     *
     * @param {HTMLElement} wrapper
     */
    function reindexOptions(wrapper) {
        var items = wrapper.querySelectorAll(SELECTORS.optionItem);

        forEachNode(items, function(item, index) {
            var input;
            var checkbox;
            var label;

            item.setAttribute('data-index', index);

            input = item.querySelector(SELECTORS.optionInput);
            if (input) {
                input.setAttribute('data-index', index);
            }

            checkbox = item.querySelector(SELECTORS.defaultCheckbox);
            if (checkbox) {
                checkbox.setAttribute('data-index', index);
                checkbox.id = 'default_' + index;
            }

            label = item.querySelector('.form-check-label');
            if (label) {
                label.setAttribute('for', 'default_' + index);
            }
        });
    }

    /**
     * Collect clean option values from wrapper.
     *
     * @param {HTMLElement} wrapper
     * @returns {Array}
     */
    function getOptionValues(wrapper) {
        var values = [];
        var options = wrapper.querySelectorAll(SELECTORS.optionInput);

        forEachNode(options, function(optionInput) {
            var value = optionInput.value.trim();
            if (value !== '') {
                values.push(value);
            }
        });

        return values;
    }

    /**
     * Update hidden options field.
     *
     * @param {HTMLElement} wrapper
     */
    function updateOptionsHiddenField(wrapper) {
        var form = wrapper.closest(SELECTORS.form);
        var hiddenField = form ? form.querySelector(SELECTORS.optionsInput) : null;

        if (hiddenField) {
            hiddenField.value = getOptionValues(wrapper).join('\n');
        }
    }

    /**
     * Update hidden default-value field from checked options.
     *
     * @param {HTMLElement} wrapper
     */
    function updateDefaultHiddenField(wrapper) {
        var selectedDefaults = [];
        var items = wrapper.querySelectorAll(SELECTORS.optionItem);

        forEachNode(items, function(item) {
            var input = item.querySelector(SELECTORS.optionInput);
            var checkbox = item.querySelector(SELECTORS.defaultCheckbox);
            var value;

            if (!input || !checkbox) {
                return;
            }

            value = input.value.trim();
            if (value !== '' && checkbox.checked) {
                selectedDefaults.push(value);
            }
        });

        var form = wrapper.closest(SELECTORS.form);
        var hiddenField = form ? form.querySelector(SELECTORS.defaultInput) : null;
        if (hiddenField) {
            hiddenField.value = selectedDefaults.join('\n');
        }
    }

    /**
     * Sync state after option changes.
     */
    function syncOptionState(wrapper) {
        updateOptionsHiddenField(wrapper);
        updateDefaultHiddenField(wrapper);
    }

    /**
     * Handle add-option click.
     *
     * @param {HTMLElement} addButton
     */
    function handleAddOption(addButton) {
        var wrapper = addButton.closest(SELECTORS.wrapper);
        var container = wrapper ? wrapper.querySelector(SELECTORS.optionsContainer) : null;
        var optionIndex;
        var newOption;
        var newInput;

        if (!wrapper || !container) {
            return;
        }

        optionIndex = wrapper.querySelectorAll(SELECTORS.optionItem).length;
        newOption = createOptionRow(optionIndex);
        container.appendChild(newOption);

        newInput = newOption.querySelector(SELECTORS.optionInput);
        if (newInput) {
            newInput.focus();
        }

        hideMessage(wrapper);
        syncOptionState(wrapper);
    }

    /**
     * Handle remove-option click.
     *
     * @param {HTMLElement} removeButton
     */
    function handleRemoveOption(removeButton) {
        var wrapper = removeButton.closest(SELECTORS.wrapper);
        var optionItem = removeButton.closest(SELECTORS.optionItem);
        var totalOptions;

        if (!wrapper || !optionItem) {
            return;
        }

        totalOptions = wrapper.querySelectorAll(SELECTORS.optionInput).length;
        if (totalOptions <= 2) {
            showMessage(wrapper, strings.errornotenoughoptions);
            return;
        }

        optionItem.remove();
        reindexOptions(wrapper);
        syncOptionState(wrapper);
    }

    /**
     * Register delegated handlers once.
     */
    function bindHandlers() {
        if (handlersbound) {
            return;
        }

        document.addEventListener('click', function(event) {
            var target = event.target;
            var addButton;
            var removeButton;

            if (!target || !target.closest) {
                return;
            }

            addButton = target.closest(SELECTORS.addOptionButton);
            if (addButton) {
                event.preventDefault();
                handleAddOption(addButton);
                return;
            }

            removeButton = target.closest(SELECTORS.removeOptionButton);
            if (removeButton) {
                event.preventDefault();
                handleRemoveOption(removeButton);
            }
        });

        document.addEventListener('change', function(event) {
            var target = event.target;
            var wrapper;

            if (!target || !target.matches || !target.matches(SELECTORS.defaultCheckbox)) {
                return;
            }

            wrapper = target.closest(SELECTORS.wrapper);
            if (wrapper) {
                syncOptionState(wrapper);
            }
        });

        document.addEventListener('input', function(event) {
            var target = event.target;
            var wrapper;
            var optionItem;
            var checkbox;

            if (!target || !target.matches || !target.matches(SELECTORS.optionInput)) {
                return;
            }

            wrapper = target.closest(SELECTORS.wrapper);
            if (!wrapper) {
                return;
            }

            optionItem = target.closest(SELECTORS.optionItem);
            if (optionItem && target.value.trim() === '') {
                checkbox = optionItem.querySelector(SELECTORS.defaultCheckbox);
                if (checkbox) {
                    checkbox.checked = false;
                }
            }

            hideMessage(wrapper);
            syncOptionState(wrapper);
        });

        document.addEventListener('blur', function(event) {
            var target = event.target;
            var wrapper;

            if (!target || !target.matches || !target.matches(SELECTORS.optionInput)) {
                return;
            }

            wrapper = target.closest(SELECTORS.wrapper);
            if (wrapper) {
                syncOptionState(wrapper);
            }
        }, true);

        document.addEventListener('submit', function(event) {
            var form = event.target;
            var wrappers;

            if (!form || !form.matches || !form.matches(SELECTORS.form)) {
                return;
            }

            wrappers = form.querySelectorAll(SELECTORS.wrapper);
            forEachNode(wrappers, function(wrapper) {
                syncOptionState(wrapper);
            });
        }, true);

        handlersbound = true;
    }

    /**
     * Sync all editors on page.
     */
    function syncAllEditors() {
        var wrappers = document.querySelectorAll(SELECTORS.wrapper);
        forEachNode(wrappers, function(wrapper) {
            syncOptionState(wrapper);
        });
    }

    /**
     * Init module.
     *
     * @param {Object} config
     */
    function init(config) {
        if (config && typeof config === 'object') {
            Object.keys(config).forEach(function(key) {
                strings[key] = config[key];
            });
        }

        bindHandlers();
        syncAllEditors();
        window.setTimeout(syncAllEditors, 0);
    }

    return {
        init: init
    };
});
