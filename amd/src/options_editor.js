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

const SELECTORS = {
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
    form: 'form',
};

const strings = {
    defaultoptiontitle: '',
    defaultoptionlabel: '',
    errornotenoughoptions: '',
    debugenabled: false,
};

const messageTimeouts = {};
let handlersbound = false;

/**
 * Write debugging information into the browser console.
 *
 * @param {HTMLElement|null} wrapper
 * @param {string} message
 * @param {*} details
 */
const log = (wrapper, message, details) => {
    let debugenabled = !!strings.debugenabled;

    if (wrapper && wrapper.dataset && typeof wrapper.dataset.debugenabled !== 'undefined') {
        debugenabled = wrapper.dataset.debugenabled === '1';
    }

    if (!debugenabled || !window.console || !window.console.log) {
        return;
    }

    if (typeof details === 'undefined') {
        window.console.log('[customfield_checkbox_multi]', message);
        return;
    }

    window.console.log('[customfield_checkbox_multi]', message, details);
};

/**
 * Merge string configuration.
 *
 * @param {Object} config
 */
const applyConfig = (config) => {
    if (!config || typeof config !== 'object') {
        return;
    }

    Object.keys(config).forEach((key) => {
        strings[key] = config[key];
    });
};

/**
 * Pull configuration values from wrapper dataset.
 *
 * @param {HTMLElement} wrapper
 */
const applyWrapperConfig = (wrapper) => {
    if (!wrapper || !wrapper.dataset) {
        return;
    }

    if (wrapper.dataset.defaultoptiontitle) {
        strings.defaultoptiontitle = wrapper.dataset.defaultoptiontitle;
    }
    if (wrapper.dataset.defaultoptionlabel) {
        strings.defaultoptionlabel = wrapper.dataset.defaultoptionlabel;
    }
    if (wrapper.dataset.errornotenoughoptions) {
        strings.errornotenoughoptions = wrapper.dataset.errornotenoughoptions;
    }
    if (typeof wrapper.dataset.debugenabled !== 'undefined') {
        strings.debugenabled = wrapper.dataset.debugenabled === '1';
    }
};

/**
 * Prepare wrapper-level state for a control event.
 *
 * @param {HTMLElement} control
 * @returns {HTMLElement|null}
 */
const prepareWrapper = (control) => {
    const wrapper = control && control.closest ? control.closest(SELECTORS.wrapper) : null;
    if (!wrapper) {
        log(null, 'No wrapper found for control', {
            controltag: control && control.tagName ? control.tagName : '',
        });
        return null;
    }

    applyWrapperConfig(wrapper);
    bindHandlers();
    return wrapper;
};

/**
 * Build a new option row element.
 *
 * @param {Number} index
 * @param {HTMLElement} wrapper
 * @returns {HTMLElement}
 */
const createOptionRow = (index, wrapper) => {
    const row = document.createElement('div');
    row.className = 'option-item customfield-checkbox-multi-option-item';
    row.setAttribute('data-index', index);

    const checkContainer = document.createElement('div');
    checkContainer.className = 'form-check customfield-checkbox-multi-check';

    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.className = 'form-check-input default-checkbox';
    checkbox.id = 'default_' + index;
    checkbox.setAttribute('data-index', index);
    checkbox.title = strings.defaultoptiontitle;

    const label = document.createElement('label');
    label.className = 'form-check-label accesshide';
    label.setAttribute('for', 'default_' + index);
    label.textContent = strings.defaultoptionlabel;

    checkContainer.appendChild(checkbox);
    checkContainer.appendChild(label);

    const optionInput = document.createElement('input');
    optionInput.type = 'text';
    optionInput.className = 'form-control option-input customfield-checkbox-multi-option-input';
    optionInput.setAttribute('data-index', index);
    optionInput.setAttribute('name', 'configdata_options_ui[]');
    optionInput.value = '';

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'btn btn-danger btn-sm remove-option customfield-checkbox-multi-remove-option';

    const removeIcon = document.createElement('i');
    removeIcon.className = 'fa fa-times';
    removeButton.appendChild(removeIcon);

    row.appendChild(checkContainer);
    row.appendChild(optionInput);
    row.appendChild(removeButton);

    log(wrapper, 'Created option row', {index: index});
    return row;
};

/**
 * Get a stable key for wrapper-specific timers.
 *
 * @param {HTMLElement} wrapper
 * @returns {String}
 */
const getWrapperKey = (wrapper) => {
    if (!wrapper) {
        return '';
    }

    if (!wrapper.getAttribute('data-editorid')) {
        wrapper.setAttribute('data-editorid', String(Date.now()) + String(Math.random()));
    }

    return wrapper.getAttribute('data-editorid');
};

/**
 * Hide helper message.
 *
 * @param {HTMLElement} wrapper
 */
const hideMessage = (wrapper) => {
    const messageElement = wrapper ? wrapper.querySelector(SELECTORS.message) : null;
    if (!messageElement) {
        return;
    }

    messageElement.style.display = 'none';
    messageElement.textContent = '';
};

/**
 * Show helper message.
 *
 * @param {HTMLElement} wrapper
 * @param {String} message
 */
const showMessage = (wrapper, message) => {
    const messageElement = wrapper ? wrapper.querySelector(SELECTORS.message) : null;
    const wrapperKey = getWrapperKey(wrapper);
    if (!messageElement) {
        return;
    }

    messageElement.textContent = message;
    messageElement.style.display = 'block';
    log(wrapper, 'Show message', {message: message});

    if (wrapperKey && messageTimeouts[wrapperKey]) {
        clearTimeout(messageTimeouts[wrapperKey]);
    }

    messageTimeouts[wrapperKey] = setTimeout(() => {
        hideMessage(wrapper);
    }, 3000);
};

/**
 * Reindex option rows after add/remove.
 *
 * @param {HTMLElement} wrapper
 */
const reindexOptions = (wrapper) => {
    wrapper.querySelectorAll(SELECTORS.optionItem).forEach((item, index) => {
        item.setAttribute('data-index', index);

        const input = item.querySelector(SELECTORS.optionInput);
        if (input) {
            input.setAttribute('data-index', index);
        }

        const checkbox = item.querySelector(SELECTORS.defaultCheckbox);
        if (checkbox) {
            checkbox.setAttribute('data-index', index);
            checkbox.id = 'default_' + index;
        }

        const label = item.querySelector('.form-check-label');
        if (label) {
            label.setAttribute('for', 'default_' + index);
        }
    });
};

/**
 * Collect clean option values from wrapper.
 *
 * @param {HTMLElement} wrapper
 * @returns {Array}
 */
const getOptionValues = (wrapper) => {
    const values = [];

    wrapper.querySelectorAll(SELECTORS.optionInput).forEach((optionInput) => {
        const value = optionInput.value.trim();
        if (value !== '') {
            values.push(value);
        }
    });

    return values;
};

/**
 * Collect checked default option values from wrapper.
 *
 * @param {HTMLElement} wrapper
 * @returns {Array}
 */
const getDefaultValues = (wrapper) => {
    const selectedDefaults = [];

    wrapper.querySelectorAll(SELECTORS.optionItem).forEach((item) => {
        const input = item.querySelector(SELECTORS.optionInput);
        const checkbox = item.querySelector(SELECTORS.defaultCheckbox);

        if (!input || !checkbox) {
            return;
        }

        const value = input.value.trim();
        if (value !== '' && checkbox.checked) {
            selectedDefaults.push(value);
        }
    });

    return selectedDefaults;
};

/**
 * Update hidden options field.
 *
 * @param {HTMLElement} wrapper
 * @returns {Array}
 */
const updateOptionsHiddenField = (wrapper) => {
    const form = wrapper.closest(SELECTORS.form);
    const hiddenField = form ? form.querySelector(SELECTORS.optionsInput) : null;
    const values = getOptionValues(wrapper);

    if (hiddenField) {
        hiddenField.value = values.join('\n');
    }

    return values;
};

/**
 * Update hidden default-value field from checked options.
 *
 * @param {HTMLElement} wrapper
 * @returns {Array}
 */
const updateDefaultHiddenField = (wrapper) => {
    const form = wrapper.closest(SELECTORS.form);
    const hiddenField = form ? form.querySelector(SELECTORS.defaultInput) : null;
    const values = getDefaultValues(wrapper);

    if (hiddenField) {
        hiddenField.value = values.join('\n');
    }

    return values;
};

/**
 * Sync state after option changes.
 *
 * @param {HTMLElement} wrapper
 */
const syncOptionState = (wrapper) => {
    const options = updateOptionsHiddenField(wrapper);
    const defaults = updateDefaultHiddenField(wrapper);

    log(wrapper, 'Synced option state', {
        optioncount: options.length,
        defaultcount: defaults.length,
        options: options,
        defaults: defaults,
    });
};

/**
 * Sync a control-driven change.
 *
 * @param {HTMLElement} control
 */
export const syncFromControl = (control) => {
    const wrapper = prepareWrapper(control);

    if (!wrapper) {
        return;
    }

    const optionItem = control.closest ? control.closest(SELECTORS.optionItem) : null;
    if (optionItem && control.matches && control.matches(SELECTORS.optionInput) && control.value.trim() === '') {
        const checkbox = optionItem.querySelector(SELECTORS.defaultCheckbox);
        if (checkbox) {
            checkbox.checked = false;
        }
    }

    hideMessage(wrapper);
    syncOptionState(wrapper);
    log(wrapper, 'syncFromControl', {
        tag: control.tagName,
        name: control.name || '',
        value: typeof control.value !== 'undefined' ? control.value : '',
    });
};

/**
 * Handle add-option click.
 *
 * @param {HTMLElement} addButton
 */
export const handleAddButton = (addButton) => {
    const wrapper = prepareWrapper(addButton);
    const container = wrapper ? wrapper.querySelector(SELECTORS.optionsContainer) : null;

    if (!wrapper || !container) {
        return;
    }

    const optionIndex = wrapper.querySelectorAll(SELECTORS.optionItem).length;
    const newOption = createOptionRow(optionIndex, wrapper);
    container.appendChild(newOption);
    reindexOptions(wrapper);

    const newInput = newOption.querySelector(SELECTORS.optionInput);
    if (newInput) {
        newInput.focus();
    }

    hideMessage(wrapper);
    syncOptionState(wrapper);
    log(wrapper, 'Added option row', {newindex: optionIndex});
};

/**
 * Handle remove-option click.
 *
 * @param {HTMLElement} removeButton
 */
export const handleRemoveButton = (removeButton) => {
    const wrapper = prepareWrapper(removeButton);
    const optionItem = removeButton.closest ? removeButton.closest(SELECTORS.optionItem) : null;

    if (!wrapper || !optionItem) {
        return;
    }

    const totalOptions = wrapper.querySelectorAll(SELECTORS.optionInput).length;
    if (totalOptions <= 2) {
        showMessage(wrapper, strings.errornotenoughoptions);
        return;
    }

    optionItem.remove();
    reindexOptions(wrapper);
    syncOptionState(wrapper);
    log(wrapper, 'Removed option row', {remaining: wrapper.querySelectorAll(SELECTORS.optionInput).length});
};

/**
 * Register delegated handlers once.
 */
const bindHandlers = () => {
    if (handlersbound) {
        return;
    }

    document.addEventListener('click', (event) => {
        const target = event.target;

        if (!target || !target.closest) {
            return;
        }

        const addButton = target.closest(SELECTORS.addOptionButton);
        if (addButton) {
            event.preventDefault();
            handleAddButton(addButton);
            return;
        }

        const removeButton = target.closest(SELECTORS.removeOptionButton);
        if (removeButton) {
            event.preventDefault();
            handleRemoveButton(removeButton);
        }
    });

    document.addEventListener('change', (event) => {
        const target = event.target;

        if (!target || !target.matches) {
            return;
        }

        if (target.matches(SELECTORS.defaultCheckbox) || target.matches(SELECTORS.optionInput)) {
            syncFromControl(target);
        }
    });

    document.addEventListener('input', (event) => {
        const target = event.target;

        if (!target || !target.matches) {
            return;
        }

        if (target.matches(SELECTORS.optionInput)) {
            syncFromControl(target);
        }
    });

    document.addEventListener('blur', (event) => {
        const target = event.target;

        if (!target || !target.matches) {
            return;
        }

        if (target.matches(SELECTORS.optionInput)) {
            syncFromControl(target);
        }
    }, true);

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!form || !form.matches || !form.matches(SELECTORS.form)) {
            return;
        }

        form.querySelectorAll(SELECTORS.wrapper).forEach((wrapper) => {
            applyWrapperConfig(wrapper);
            syncOptionState(wrapper);
        });
    }, true);

    handlersbound = true;
    log(null, 'Bound delegated handlers');
};

/**
 * Sync all editors on page.
 */
const syncAllEditors = () => {
    const wrappers = document.querySelectorAll(SELECTORS.wrapper);
    wrappers.forEach((wrapper) => {
        applyWrapperConfig(wrapper);
        syncOptionState(wrapper);
    });
    log(null, 'syncAllEditors completed', {wrappercount: wrappers.length});
};

/**
 * Init module.
 *
 * @param {Object} config
 */
export const init = (config) => {
    applyConfig(config);
    bindHandlers();
    log(null, 'options_editor init', config || {});
    syncAllEditors();
    window.setTimeout(syncAllEditors, 0);
};
