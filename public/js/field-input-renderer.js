/**
 * Centralized Field Input Renderer
 * 
 * This module provides consistent field input rendering across all parts of the application
 * that use field definitions from FieldDefinitions.php
 */

window.FieldInputRenderer = {
    /**
     * Generate input HTML for a field based on its definition
     * 
     * @param {Object} fieldDefinition - The field definition from FieldDefinitions.php
     * @param {string} fieldName - The name of the field
     * @param {*} defaultValue - The default value for the field
     * @param {Object} options - Additional options for rendering
     * @returns {string} HTML string for the input element
     */
    generateInput: function(fieldDefinition, fieldName, defaultValue = '', options = {}) {
        const inputType = fieldDefinition.input_type || 'text';
        const dataType = fieldDefinition.data_type || 'string';
        const fieldOptions = fieldDefinition.options || {};
        const required = fieldDefinition.required || false;
        const namePrefix = options.namePrefix || '';
        const cssClass = options.cssClass || 'form-control input-sm';
        const containerClass = options.containerClass || '';
        
        const requiredAttr = required ? 'required' : '';
        const nameAttr = `name="${namePrefix}${fieldName}"`;
        const idAttr = options.id ? `id="${options.id}"` : '';
        const placeholder = options.placeholder || '';
        
        let inputHtml = '';
        
        switch (inputType) {
            case 'financial_entity_select':
                inputHtml = this.generateFinancialEntitySelect(fieldName, defaultValue, fieldOptions, requiredAttr, nameAttr, idAttr, cssClass);
                break;
                
            case 'currency_select':
                inputHtml = this.generateCurrencySelect(fieldName, defaultValue, fieldOptions, requiredAttr, nameAttr, idAttr, cssClass);
                break;
                
            case 'select':
                inputHtml = this.generateSelectInput(fieldName, defaultValue, fieldOptions, requiredAttr, nameAttr, idAttr, cssClass);
                break;
                
            case 'checkbox':
            case 'boolean':
                inputHtml = this.generateCheckboxInput(fieldName, defaultValue, requiredAttr, nameAttr, idAttr);
                break;
                
            case 'date':
                inputHtml = this.generateDateInput(fieldName, defaultValue, requiredAttr, nameAttr, idAttr, cssClass);
                break;
                
            case 'datetime':
            case 'timestamp':
                inputHtml = this.generateDateTimeInput(fieldName, defaultValue, requiredAttr, nameAttr, idAttr, cssClass);
                break;
                
            case 'number':
            case 'integer':
            case 'int':
                inputHtml = this.generateNumberInput(fieldName, defaultValue, dataType, requiredAttr, nameAttr, idAttr, cssClass);
                break;
                
            case 'decimal':
            case 'float':
            case 'double':
                inputHtml = this.generateDecimalInput(fieldName, defaultValue, requiredAttr, nameAttr, idAttr, cssClass);
                break;
                
            case 'email':
                inputHtml = this.generateEmailInput(fieldName, defaultValue, requiredAttr, nameAttr, idAttr, cssClass, placeholder);
                break;
                
            case 'url':
                inputHtml = this.generateUrlInput(fieldName, defaultValue, requiredAttr, nameAttr, idAttr, cssClass, placeholder);
                break;
                
            case 'tel':
            case 'phone':
                inputHtml = this.generateTelInput(fieldName, defaultValue, requiredAttr, nameAttr, idAttr, cssClass, placeholder);
                break;
                
            case 'textarea':
                inputHtml = this.generateTextareaInput(fieldName, defaultValue, requiredAttr, nameAttr, idAttr, cssClass, placeholder);
                break;
                
            case 'beneficiaries':
                inputHtml = this.generateBeneficiariesInput(fieldName, defaultValue, requiredAttr, nameAttr, idAttr);
                break;
                
            case 'text':
            default:
                inputHtml = this.generateTextInput(fieldName, defaultValue, requiredAttr, nameAttr, idAttr, cssClass, placeholder);
                break;
        }
        
        // Wrap in container if specified
        if (containerClass) {
            inputHtml = `<div class="${containerClass}">${inputHtml}</div>`;
        }
        
        return inputHtml;
    },

    /**
     * Generate a financial entity select dropdown
     */
    generateFinancialEntitySelect: function(fieldName, defaultValue, fieldOptions, requiredAttr, nameAttr, idAttr, cssClass) {
        const excludeTypes = fieldOptions.exclude_entity_types || [];
        const excludeTypesJson = JSON.stringify(excludeTypes);
        
        let html = `<select class="${cssClass} financial-entity-select" ${nameAttr} ${idAttr} ${requiredAttr} data-exclude-types='${excludeTypesJson}'>`;
        html += `<option value="">Nothing selected</option>`;
        
        if (defaultValue) {
            html += `<option value="${this.escapeHtml(defaultValue)}" selected>${this.escapeHtml(defaultValue)}</option>`;
        }
        
        html += '</select>';
        return html;
    },

    /**
     * Generate a currency select dropdown
     */
    generateCurrencySelect: function(fieldName, defaultValue, fieldOptions, requiredAttr, nameAttr, idAttr, cssClass) {
        let html = `<select class="${cssClass} currency-select" ${nameAttr} ${idAttr} ${requiredAttr}>`;
        html += `<option value="">Nothing selected</option>`;
        
        if (defaultValue) {
            html += `<option value="${this.escapeHtml(defaultValue)}" selected>${this.escapeHtml(defaultValue)}</option>`;
        }
        
        html += '</select>';
        return html;
    },

    /**
     * Generate a select input for predefined options
     */
    generateSelectInput: function(fieldName, defaultValue, fieldOptions, requiredAttr, nameAttr, idAttr, cssClass) {
        let html = `<select class="${cssClass}" ${nameAttr} ${idAttr} ${requiredAttr}>`;
        html += `<option value="">Nothing selected</option>`;
        
        // Handle both simple arrays and key-value objects
        if (Array.isArray(fieldOptions)) {
            fieldOptions.forEach(option => {
                const value = typeof option === 'object' ? option.value : option;
                const label = typeof option === 'object' ? option.label : option;
                const selected = (defaultValue == value) ? 'selected' : '';
                html += `<option value="${this.escapeHtml(value)}" ${selected}>${this.escapeHtml(label)}</option>`;
            });
        } else {
            for (const value in fieldOptions) {
                if (fieldOptions.hasOwnProperty(value)) {
                    const selected = (defaultValue == value) ? 'selected' : '';
                    html += `<option value="${this.escapeHtml(value)}" ${selected}>${this.escapeHtml(fieldOptions[value])}</option>`;
                }
            }
        }
        
        html += '</select>';
        return html;
    },

    /**
     * Generate a checkbox input
     */
    generateCheckboxInput: function(fieldName, defaultValue, requiredAttr, nameAttr, idAttr) {
        const isChecked = (defaultValue === '1' || defaultValue === 'true' || defaultValue === true) ? 'checked' : '';
        return `<input type="checkbox" ${nameAttr} ${idAttr} ${isChecked} ${requiredAttr}>`;
    },

    /**
     * Generate a date input
     */
    generateDateInput: function(fieldName, defaultValue, requiredAttr, nameAttr, idAttr, cssClass) {
        return `<input type="date" class="${cssClass}" ${nameAttr} ${idAttr} value="${this.escapeHtml(defaultValue)}" ${requiredAttr}>`;
    },

    /**
     * Generate a datetime input
     */
    generateDateTimeInput: function(fieldName, defaultValue, requiredAttr, nameAttr, idAttr, cssClass) {
        return `<input type="datetime-local" class="${cssClass}" ${nameAttr} ${idAttr} value="${this.escapeHtml(defaultValue)}" ${requiredAttr}>`;
    },

    /**
     * Generate a number input
     */
    generateNumberInput: function(fieldName, defaultValue, dataType, requiredAttr, nameAttr, idAttr, cssClass) {
        const step = (dataType === 'integer' || dataType === 'int') ? 'step="1"' : '';
        return `<input type="number" class="${cssClass}" ${nameAttr} ${idAttr} value="${this.escapeHtml(defaultValue)}" ${step} ${requiredAttr}>`;
    },

    /**
     * Generate a decimal input
     */
    generateDecimalInput: function(fieldName, defaultValue, requiredAttr, nameAttr, idAttr, cssClass) {
        return `<input type="number" class="${cssClass}" ${nameAttr} ${idAttr} value="${this.escapeHtml(defaultValue)}" step="0.01" ${requiredAttr}>`;
    },

    /**
     * Generate an email input
     */
    generateEmailInput: function(fieldName, defaultValue, requiredAttr, nameAttr, idAttr, cssClass, placeholder) {
        const placeholderAttr = placeholder ? `placeholder="${this.escapeHtml(placeholder)}"` : '';
        return `<input type="email" class="${cssClass}" ${nameAttr} ${idAttr} value="${this.escapeHtml(defaultValue)}" ${placeholderAttr} ${requiredAttr}>`;
    },

    /**
     * Generate a URL input
     */
    generateUrlInput: function(fieldName, defaultValue, requiredAttr, nameAttr, idAttr, cssClass, placeholder) {
        const placeholderAttr = placeholder ? `placeholder="${this.escapeHtml(placeholder)}"` : '';
        return `<input type="url" class="${cssClass}" ${nameAttr} ${idAttr} value="${this.escapeHtml(defaultValue)}" ${placeholderAttr} ${requiredAttr}>`;
    },

    /**
     * Generate a tel input
     */
    generateTelInput: function(fieldName, defaultValue, requiredAttr, nameAttr, idAttr, cssClass, placeholder) {
        const placeholderAttr = placeholder ? `placeholder="${this.escapeHtml(placeholder)}"` : '';
        return `<input type="tel" class="${cssClass}" ${nameAttr} ${idAttr} value="${this.escapeHtml(defaultValue)}" ${placeholderAttr} ${requiredAttr}>`;
    },

    /**
     * Generate a textarea input
     */
    generateTextareaInput: function(fieldName, defaultValue, requiredAttr, nameAttr, idAttr, cssClass, placeholder) {
        const placeholderAttr = placeholder ? `placeholder="${this.escapeHtml(placeholder)}"` : '';
        return `<textarea class="${cssClass}" ${nameAttr} ${idAttr} ${placeholderAttr} ${requiredAttr}>${this.escapeHtml(defaultValue)}</textarea>`;
    },

    /**
     * Generate a text input
     */
    generateTextInput: function(fieldName, defaultValue, requiredAttr, nameAttr, idAttr, cssClass, placeholder) {
        const placeholderAttr = placeholder ? `placeholder="${this.escapeHtml(placeholder)}"` : '';
        return `<input type="text" class="${cssClass}" ${nameAttr} ${idAttr} value="${this.escapeHtml(defaultValue)}" ${placeholderAttr} ${requiredAttr}>`;
    },

    /**
     * Generate a beneficiaries input (special case for trust beneficiaries)
     */
    generateBeneficiariesInput: function(fieldName, defaultValue, requiredAttr, nameAttr, idAttr) {
        // This is a complex input that would need special handling
        // For now, return a simple textarea for JSON input
        return `<textarea class="form-control" ${nameAttr} ${idAttr} ${requiredAttr} placeholder="JSON format">${this.escapeHtml(defaultValue)}</textarea>`;
    },

    /**
     * Load financial entities for dropdowns
     */
    loadFinancialEntitiesForDropdowns: function(container = document) {
        const financialEntitySelects = container.querySelectorAll('.financial-entity-select');
        
        financialEntitySelects.forEach(select => {
            const excludeTypes = JSON.parse(select.getAttribute('data-exclude-types') || '[]');
            this.loadFinancialEntitiesForSelect(select, excludeTypes);
        });
    },

    /**
     * Load currencies for dropdowns
     */
    loadCurrenciesForDropdowns: function(container = document) {
        const currencySelects = container.querySelectorAll('.currency-select');
        
        currencySelects.forEach(select => {
            this.loadCurrenciesForSelect(select);
        });
    },

    /**
     * Load financial entities for a specific select
     */
    loadFinancialEntitiesForSelect: function(selectElement, excludeTypes) {
        const excludeTypesParam = excludeTypes.length > 0 ? '?exclude_types=' + excludeTypes.join(',') : '';
        
        fetch('/financial-entities/beneficiary-entities' + excludeTypesParam)
            .then(response => response.json())
            .then(entities => {
                // Clear existing options except the first one
                const firstOption = selectElement.querySelector('option[value=""]');
                selectElement.innerHTML = '';
                if (firstOption) {
                    selectElement.appendChild(firstOption);
                }
                
                // Add entity options
                entities.forEach(entity => {
                    const option = document.createElement('option');
                    option.value = entity.name;
                    option.textContent = entity.display_name || entity.name;
                    selectElement.appendChild(option);
                });
                
                // Update placeholder styling after loading options
                if (typeof updateDropdownPlaceholderStyling === 'function') {
                    updateDropdownPlaceholderStyling();
                }
            })
            .catch(error => {
                console.error('Error loading financial entities:', error);
            });
    },

    /**
     * Load currencies for a specific select
     */
    loadCurrenciesForSelect: function(selectElement) {
        fetch('/currencies')
            .then(response => response.json())
            .then(currencies => {
                // Clear existing options except the first one
                const firstOption = selectElement.querySelector('option[value=""]');
                selectElement.innerHTML = '';
                if (firstOption) {
                    selectElement.appendChild(firstOption);
                }
                
                // Add currency options
                currencies.forEach(currency => {
                    const option = document.createElement('option');
                    option.value = currency.id;
                    option.textContent = `${currency.code} - ${currency.name}`;
                    selectElement.appendChild(option);
                });
                
                // Update placeholder styling after loading options
                if (typeof updateDropdownPlaceholderStyling === 'function') {
                    updateDropdownPlaceholderStyling();
                }
            })
            .catch(error => {
                console.error('Error loading currencies:', error);
            });
    },

    /**
     * Get translation from window.translations or fallback
     */
    getTranslation: function(key) {
        if (window.translations && window.translations[key]) {
            return window.translations[key];
        }
        // Fallback to key if translation not found
        return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    },

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml: function(text) {
        if (text === null || text === undefined) {
            return '';
        }
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};
