/**
 * Boundary Autocomplete Module
 * 
 * Provides autocomplete functionality for boundary/location selection with inline badge display.
 * Features:
 * - Autocomplete dropdown on 3+ character input
 * - Inline badge display with color coding
 * - Continued typing after badge selection
 * - Hidden boundaries[] array for form submission
 * 
 * Usage: BoundaryAutocomplete.init(inputSelector, formSelector)
 * Example: BoundaryAutocomplete.init('#kulcsszo', '#kereses')
 */

const BoundaryAutocomplete = (function() {
    'use strict';

    // State management
    const state = {
        selectedBoundaries: [], // { id, name, type, color }
        apiUrl: '/ajax/autocompleteboundaries',
        minChars: 3,
        debounceTimeout: null,
        boundaryCache: {} // Cache for boundary data by ID
    };

    // CSS styles
    const styles = `
        .boundary-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .boundary-input-wrapper .form-control {
            padding-right: 8px;
            min-height: 38px;
        }

        .boundary-badges-container {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 4px;
            padding: 2px 6px;
            align-items: center;
            min-height: 24px;
        }

        .boundary-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            color: #fff;
            white-space: nowrap;
            user-select: none;
        }

        .boundary-badge-remove {
            cursor: pointer;
            font-weight: bold;
            font-size: 1.1em;
            margin-left: 2px;
            line-height: 1;
        }

        .boundary-badge-remove:hover {
            opacity: 0.7;
        }

        .boundary-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #ddd;
            border-top: none;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: none;
        }

        .boundary-dropdown.visible {
            display: block;
        }

        .boundary-dropdown-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .boundary-dropdown-item:hover {
            background-color: #f5f5f5;
        }

        .boundary-dropdown-badge {
            padding: 4px 8px;
            border-radius: 4px;
            color: #fff;
            font-size: 0.85em;
            font-weight: 500;
            min-width: 80px;
            text-align: center;
        }

        .boundary-dropdown-text {
            flex: 1;
        }

        .boundary-hidden-fields-container {
            display: none;
        }
    `;

    /**
     * Initialize boundary autocomplete for an input field
     * @param {string} inputSelector - CSS selector for input field
     * @param {string} formSelector - CSS selector for parent form
     * @param {Array} initialBoundaries - Optional array of pre-populated boundaries [{id, name, type, color}, ...]
     */
    function init(inputSelector, formSelector, initialBoundaries) {
        const inputField = document.querySelector(inputSelector);
        const form = document.querySelector(formSelector);

        if (!inputField) {
            console.warn('BoundaryAutocomplete: Input field not found:', inputSelector);
            return false;
        }

        // Inject CSS
        injectStyles();

        // Wrap input field and create supporting elements
        wrapInputField(inputField, form);

        // Attach event listeners
        attachEventListeners(inputField);

        // Load initial boundaries from URL parameters if not provided
        if (!initialBoundaries || initialBoundaries.length === 0) {
            loadBoundariesFromUrl(inputField);
        } else {
            // Load provided boundaries
            initialBoundaries.forEach(boundary => {
                selectBoundary(inputField, boundary);
            });
        }

        return true;
    }

    /**
     * Load boundaries from window variable provided by PHP backend
     * PHP must inject: window.boundaryDataFromUrl = [...]
     */
    function loadBoundariesFromUrl(inputField) {
        // Only load data if PHP backend provided it
        if (window.boundaryDataFromUrl && Array.isArray(window.boundaryDataFromUrl) && window.boundaryDataFromUrl.length > 0) {
            window.boundaryDataFromUrl.forEach(boundary => {
                if (boundary.id && boundary.name && boundary.type && boundary.color) {
                    selectBoundary(inputField, boundary);
                }
            });
        }
    }
    /**
     * Inject CSS styles into page
     */
    function injectStyles() {
        if (document.getElementById('boundary-autocomplete-styles')) {
            return; // Already injected
        }

        const styleEl = document.createElement('style');
        styleEl.id = 'boundary-autocomplete-styles';
        styleEl.textContent = styles;
        document.head.appendChild(styleEl);
    }

    /**
     * Wrap input field with container and create supporting elements
     */
    function wrapInputField(inputField, form) {
        // Create wrapper
        const wrapper = document.createElement('div');
        wrapper.className = 'boundary-input-wrapper';

        // Get parent and insert wrapper
        const parent = inputField.parentNode;
        parent.insertBefore(wrapper, inputField);

        // Move input to wrapper
        wrapper.appendChild(inputField);

        // Add badges container after input (inline display)
        const badgesContainer = document.createElement('div');
        badgesContainer.className = 'boundary-badges-container';
        badgesContainer.id = 'boundary-badges-' + (inputField.id || 'default');
        wrapper.appendChild(badgesContainer);

        // Add dropdown
        const dropdown = document.createElement('div');
        dropdown.className = 'boundary-dropdown';
        dropdown.id = 'boundary-dropdown-' + (inputField.id || 'default');
        wrapper.appendChild(dropdown);

        // Add hidden container for boundaries[] fields
        const hiddenContainer = document.createElement('div');
        hiddenContainer.className = 'boundary-hidden-fields-container';
        hiddenContainer.id = 'boundary-hidden-' + (inputField.id || 'default');
        if (form) {
            form.appendChild(hiddenContainer);
        } else {
            wrapper.appendChild(hiddenContainer);
        }

        // Store references on input element
        inputField.boundaryAutocomplete = {
            wrapper: wrapper,
            badgesContainer: badgesContainer,
            dropdown: dropdown,
            hiddenContainer: hiddenContainer
        };
    }

    /**
     * Attach event listeners to input field
     */
    function attachEventListeners(inputField) {
        const acData = inputField.boundaryAutocomplete;

        // Input event for autocomplete
        inputField.addEventListener('input', function(e) {
            handleInput(inputField);
        });

        // Click outside to close dropdown
        document.addEventListener('click', function(e) {
            if (!acData.wrapper.contains(e.target)) {
                closeDropdown(inputField);
            }
        });

        // Form submission handler
        const form = inputField.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                handleFormSubmit(inputField, form);
            });
        }
    }

    /**
     * Handle input event - trigger autocomplete
     */
    function handleInput(inputField) {
        const text = inputField.value.trim();

        // Clear previous debounce
        clearTimeout(state.debounceTimeout);

        if (text.length < state.minChars) {
            closeDropdown(inputField);
            return;
        }

        // Debounce API call
        state.debounceTimeout = setTimeout(function() {
            fetchAutocomplete(inputField, text);
        }, 300);
    }

    /**
     * Fetch autocomplete results from API
     */
    function fetchAutocomplete(inputField, text) {
        // Build URL with text parameter
        let url = state.apiUrl + '?text=' + encodeURIComponent(text);
        
        // Add excluded_ids parameter - already selected boundaries should not appear again
        if (state.selectedBoundaries.length > 0) {
            const excludedIds = state.selectedBoundaries.map(b => b.id).join(',');
            url += '&excluded_ids=' + encodeURIComponent(excludedIds);
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                // Cache results by ID for future reference
                if (data.results) {
                    data.results.forEach(result => {
                        state.boundaryCache[result.id] = result;
                    });
                }
                renderDropdown(inputField, data.results || []);
            })
            .catch(error => {
                console.error('BoundaryAutocomplete: API error', error);
                closeDropdown(inputField);
            });
    }

    /**
     * Render dropdown with results
     */
    function renderDropdown(inputField, results) {
        const acData = inputField.boundaryAutocomplete;
        const dropdown = acData.dropdown;

        dropdown.innerHTML = '';

        if (results.length === 0) {
            closeDropdown(inputField);
            return;
        }

        results.forEach(result => {
            const item = document.createElement('div');
            item.className = 'boundary-dropdown-item';

            // Badge with color
            const badge = document.createElement('div');
            badge.className = 'boundary-dropdown-badge';
            badge.style.backgroundColor = result.color || '#999';
            badge.textContent = result.type || '';

            // Text
            const text = document.createElement('div');
            text.className = 'boundary-dropdown-text';
            text.textContent = result.name || '';

            item.appendChild(badge);
            item.appendChild(text);

            // Click handler
            item.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                selectBoundary(inputField, result);
                closeDropdown(inputField);
                inputField.focus();
            });

            dropdown.appendChild(item);
        });

        // Show dropdown
        dropdown.classList.add('visible');
    }

    /**
     * Select a boundary and display as badge
     */
    function selectBoundary(inputField, boundaryData) {
        // Check if already selected
        const alreadySelected = state.selectedBoundaries.some(b => b.id === boundaryData.id);
        if (alreadySelected) {
            return;
        }

        // Add to state
        state.selectedBoundaries.push({
            id: boundaryData.id,
            name: boundaryData.name,
            type: boundaryData.type,
            color: boundaryData.color
        });

        // Add badge to UI
        addBadgeToUI(inputField, boundaryData);

        // Add hidden field
        addHiddenField(inputField, boundaryData.id);

        // Clear input for continued typing
        inputField.value = '';
        inputField.focus();
    }

    /**
     * Add badge to UI
     */
    function addBadgeToUI(inputField, boundaryData) {
        const acData = inputField.boundaryAutocomplete;
        const badgesContainer = acData.badgesContainer;

        const badge = document.createElement('div');
        badge.className = 'boundary-badge';
        badge.style.backgroundColor = boundaryData.color || '#999';
        badge.dataset.boundaryId = boundaryData.id;

        const text = document.createElement('span');
        text.textContent = boundaryData.type + ': ' + boundaryData.name;

        const removeBtn = document.createElement('span');
        removeBtn.className = 'boundary-badge-remove';
        removeBtn.textContent = '×';
        removeBtn.setAttribute('aria-label', 'Remove boundary');
        removeBtn.setAttribute('role', 'button');
        removeBtn.setAttribute('tabindex', '0');

        removeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            removeBoundary(inputField, boundaryData.id);
        });

        removeBtn.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                removeBoundary(inputField, boundaryData.id);
            }
        });

        badge.appendChild(text);
        badge.appendChild(removeBtn);
        badgesContainer.appendChild(badge);
    }

    /**
     * Remove badge from UI and state
     */
    function removeBoundary(inputField, boundaryId) {
        const acData = inputField.boundaryAutocomplete;
        const badgesContainer = acData.badgesContainer;
        const hiddenContainer = acData.hiddenContainer;

        // Remove from state
        state.selectedBoundaries = state.selectedBoundaries.filter(b => b.id !== boundaryId);

        // Remove badge from UI
        const badge = badgesContainer.querySelector('[data-boundary-id="' + boundaryId + '"]');
        if (badge) {
            badge.remove();
        }

        // Remove hidden field
        const hiddenField = hiddenContainer.querySelector('[value="' + boundaryId + '"]');
        if (hiddenField) {
            hiddenField.remove();
        }

        inputField.focus();
    }

    /**
     * Add hidden input field for boundary ID
     */
    function addHiddenField(inputField, boundaryId) {
        const acData = inputField.boundaryAutocomplete;
        const hiddenContainer = acData.hiddenContainer;

        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'boundaries[]';
        hidden.value = boundaryId;

        hiddenContainer.appendChild(hidden);
    }

    /**
     * Close dropdown
     */
    function closeDropdown(inputField) {
        const acData = inputField.boundaryAutocomplete;
        if (acData && acData.dropdown) {
            acData.dropdown.classList.remove('visible');
            acData.dropdown.innerHTML = '';
        }
    }

    /**
     * Handle form submission - ensure text-only content in input
     */
    function handleFormSubmit(inputField, form) {
        // Input field already contains only text (badges are in separate UI)
        // Just ensure no extra processing needed
        // The hidden boundaries[] fields are already in place
    }

    /**
     * Public API
     */
    return {
        init: init,
        closeDropdown: closeDropdown,
        getSelectedBoundaries: function() {
            return state.selectedBoundaries;
        },
        clearSelectedBoundaries: function() {
            state.selectedBoundaries = [];
        }
    };

})();
