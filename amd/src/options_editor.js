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
 * @copyright  2018 Toni Barbera <toni@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTORS = {
    addOptionButton: '#add-option',
    optionsContainer: '#options-container',
    optionItem: '.option-item',
    optionInput: '.option-input',
    defaultCheckbox: '.default-checkbox',
    removeOptionButton: '.remove-option',
    optionsInput: 'input[name="configdata[options]"]',
    defaultInput: 'input[name="configdata[defaultvalue]"]',
    message: '#options-message',
    form: 'form',
};

let initialised = false;
let messageTimeout = null;

/**
 * Build a new option row element.
 *
 * @param {number} index
 * @param {Object} strings
 * @returns {HTMLElement}
 */
const createOptionRow = (index, strings) => {
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
    optionInput.value = '';

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'btn btn-danger btn-sm remove-option customfield-checkbox-multi-remove-option';
    removeButton.innerHTML = '<i class="fa fa-times"></i>';

    row.appendChild(checkContainer);
    row.appendChild(optionInput);
    row.appendChild(removeButton);

    return row;
};

/**
 * Hide helper message.
 */
const hideMessage = () => {
    const messageElement = document.querySelector(SELECTORS.message);
    if (!messageElement) {
        return;
    }

    messageElement.style.display = 'none';
    messageElement.textContent = '';
};

/**
 * Show helper message.
 *
 * @param {string} message
 */
const showMessage = (message) => {
    const messageElement = document.querySelector(SELECTORS.message);
    if (!messageElement) {
        return;
    }

    messageElement.textContent = message;
    messageElement.style.display = 'block';

    if (messageTimeout) {
        clearTimeout(messageTimeout);
    }

    messageTimeout = setTimeout(() => {
        hideMessage();
    }, 3000);
};

/**
 * Reindex option rows after add/remove.
 */
const reindexOptions = () => {
    document.querySelectorAll(SELECTORS.optionItem).forEach((item, index) => {
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
 * Update hidden default-value field from checked options.
 */
const updateDefaultHiddenField = () => {
    const selectedDefaults = [];

    document.querySelectorAll(SELECTORS.optionItem).forEach((item) => {
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

    const hiddenField = document.querySelector(SELECTORS.defaultInput);
    if (hiddenField) {
        hiddenField.value = selectedDefaults.join('\n');
    }
};

/**
 * Update hidden options field from visible option inputs.
 */
const updateOptionsHiddenField = () => {
    const options = [];

    document.querySelectorAll(SELECTORS.optionInput).forEach((input) => {
        options.push(input.value.trim());
    });

    while (options.length > 0 && options[options.length - 1] === '') {
        options.pop();
    }

    const hiddenField = document.querySelector(SELECTORS.optionsInput);
    if (hiddenField) {
        hiddenField.value = options.join('\n');
    }

    updateDefaultHiddenField();
};

/**
 * Init module.
 *
 * @param {Object} config
 */
export const init = (config = {}) => {
    if (initialised) {
        return;
    }

    const addButton = document.querySelector(SELECTORS.addOptionButton);
    const container = document.querySelector(SELECTORS.optionsContainer);
    if (!addButton || !container) {
        return;
    }

    const strings = {
        defaultoptiontitle: 'Default option',
        defaultoptionlabel: 'Default option',
        errornotenoughoptions: 'You must provide at least two options.',
        ...config,
    };

    document.addEventListener('change', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        if (event.target.matches(SELECTORS.defaultCheckbox)) {
            updateDefaultHiddenField();
        }
    });

    document.addEventListener('input', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        if (!event.target.matches(SELECTORS.optionInput)) {
            return;
        }

        updateOptionsHiddenField();
        hideMessage();

        const optionItem = event.target.closest(SELECTORS.optionItem);
        if (!optionItem) {
            return;
        }

        const checkbox = optionItem.querySelector(SELECTORS.defaultCheckbox);
        if (!checkbox) {
            return;
        }

        if (event.target.value.trim() === '') {
            checkbox.checked = false;
            updateDefaultHiddenField();
        }
    });

    document.addEventListener('blur', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        if (event.target.matches(SELECTORS.optionInput)) {
            updateOptionsHiddenField();
        }
    }, true);

    container.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const removeButton = event.target.closest(SELECTORS.removeOptionButton);
        if (!removeButton) {
            return;
        }

        event.preventDefault();

        const totalOptions = document.querySelectorAll(SELECTORS.optionInput).length;
        if (totalOptions <= 2) {
            showMessage(strings.errornotenoughoptions);
            return;
        }

        const optionItem = removeButton.closest(SELECTORS.optionItem);
        if (optionItem) {
            optionItem.remove();
            reindexOptions();
            updateOptionsHiddenField();
        }
    });

    let optionIndex = document.querySelectorAll(SELECTORS.optionInput).length;

    addButton.addEventListener('click', () => {
        const newOption = createOptionRow(optionIndex, strings);
        container.appendChild(newOption);

        const newInput = newOption.querySelector(SELECTORS.optionInput);
        if (newInput) {
            newInput.focus();
        }

        optionIndex++;
        hideMessage();
        updateOptionsHiddenField();
    });

    const form = document.querySelector(SELECTORS.form);
    if (form) {
        form.addEventListener('submit', () => {
            updateOptionsHiddenField();
            updateDefaultHiddenField();
            return true;
        });
    }

    updateOptionsHiddenField();
    initialised = true;
};
