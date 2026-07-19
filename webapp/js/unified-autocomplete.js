/**
 * Unified Autocomplete Module
 *
 * Replaces both the jQuery UI keyword autocomplete and BoundaryAutocomplete.
 * Provides a single dropdown that merges:
 *   - Geographic boundary suggestions (multi-select, colored badges)
 *   - Church suggestions (multi-select, church-icon badges)
 *
 * Usage:
 *   UnifiedAutocomplete.init('#keyword', '#kereses', {
 *     initialBoundaries: window.boundaryDataFromUrl || [],
 *     initialChurches:   window.churchDataFromUrl   || []
 *   });
 *
 * Form fields added on selection:
 *   - Boundaries: <input type="hidden" name="boundaries[]" value="...">
 *   - Churches:   <input type="hidden" name="church_ids[]" value="...">
 */

const UnifiedAutocomplete = (function () {
    'use strict';

    // ── State ────────────────────────────────────────────────────────────────
    const state = {
        selectedBoundaries: [],  // { id, name, type, color, osm }
        selectedChurches:   [],  // { id, name, city }
        apiUrl:      '/ajax/AutocompleteCombined',
        minChars:    3,
        debounce:    null,
        boundaryCache: {}
    };

    // ── CSS ──────────────────────────────────────────────────────────────────
    const CHURCH_BADGE_COLOR = '#5a6e8c';

    const styles = `
        .unified-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .unified-input-wrapper .form-control {
            padding-right: 8px;
            min-height: 38px;
        }

        .unified-badges-container {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 4px;
            padding: 2px 6px;
            align-items: center;
            min-height: 24px;
        }

        .unified-badge {
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

        .unified-badge-remove {
            cursor: pointer;
            font-weight: bold;
            font-size: 1.1em;
            margin-left: 2px;
            line-height: 1;
        }

        .unified-badge-remove:hover {
            opacity: 0.7;
        }

        .unified-church-icon {
            font-style: normal;
            margin-right: 2px;
        }

        .unified-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #ddd;
            border-top: none;
            max-height: 320px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: none;
        }

        .unified-dropdown.visible {
            display: block;
        }

        .unified-dropdown-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .unified-dropdown-item:hover,
        .unified-dropdown-item:focus {
            background-color: #f5f5f5;
            outline: none;
        }

        .unified-dropdown-badge {
            padding: 3px 7px;
            border-radius: 4px;
            color: #fff;
            font-size: 0.82em;
            font-weight: 500;
            min-width: 72px;
            text-align: center;
            flex-shrink: 0;
        }

        .unified-dropdown-church-icon {
            font-style: normal;
            font-size: 1.15em;
            flex-shrink: 0;
            color: ${CHURCH_BADGE_COLOR};
        }

        .unified-dropdown-text {
            flex: 1;
        }

        .unified-dropdown-text strong {
            display: block;
        }

        .unified-dropdown-text small {
            color: #666;
        }

        .unified-boundary-osm-icon {
            cursor: pointer;
            color: #007bff;
            font-size: 0.9em;
            transition: color 0.2s;
            text-decoration: none;
        }

        .unified-boundary-osm-icon:hover {
            color: #0056b3;
        }

        .unified-hidden-fields-container {
            display: none;
        }
    `;

    // ── Public API ───────────────────────────────────────────────────────────

    /**
     * Initialise the unified autocomplete on an input field.
     *
     * @param {string} inputSelector  CSS selector for the text input
     * @param {string} formSelector   CSS selector for the enclosing form
     * @param {object} options
     * @param {Array}  options.initialBoundaries  Pre-selected boundaries [{id,name,type,color,...}]
     * @param {Array}  options.initialChurches    Pre-selected churches   [{id,name,city}]
     */
    function init(inputSelector, formSelector, options) {
        options = options || {};
        const inputField = document.querySelector(inputSelector);
        const form       = document.querySelector(formSelector);

        if (!inputField) {
            console.warn('UnifiedAutocomplete: input not found:', inputSelector);
            return false;
        }

        injectStyles();
        wrapInputField(inputField, form);
        attachEventListeners(inputField);

        // Restore pre-selected items (e.g. back-navigation from search results)
        (options.initialBoundaries || []).forEach(function (b) {
            if (b.id && b.name && b.type) selectBoundary(inputField, b);
        });
        (options.initialChurches || []).forEach(function (c) {
            if (c.id && c.name) selectChurch(inputField, c);
        });

        return true;
    }

    // ── Internals ────────────────────────────────────────────────────────────

    function injectStyles() {
        if (document.getElementById('unified-autocomplete-styles')) return;
        const el = document.createElement('style');
        el.id = 'unified-autocomplete-styles';
        el.textContent = styles;
        document.head.appendChild(el);
    }

    function wrapInputField(inputField, form) {
        const wrapper = document.createElement('div');
        wrapper.className = 'unified-input-wrapper';

        const parent = inputField.parentNode;
        parent.insertBefore(wrapper, inputField);
        wrapper.appendChild(inputField);

        const badgesContainer = document.createElement('div');
        badgesContainer.className = 'unified-badges-container';
        badgesContainer.id = 'unified-badges-' + (inputField.id || 'default');
        wrapper.appendChild(badgesContainer);

        const dropdown = document.createElement('div');
        dropdown.className = 'unified-dropdown';
        dropdown.id = 'unified-dropdown-' + (inputField.id || 'default');
        wrapper.appendChild(dropdown);

        // Hidden fields container lives inside the form, not inside the badge wrapper
        const hiddenContainer = document.createElement('div');
        hiddenContainer.className = 'unified-hidden-fields-container';
        hiddenContainer.id = 'unified-hidden-' + (inputField.id || 'default');
        if (form) {
            form.appendChild(hiddenContainer);
        } else {
            wrapper.appendChild(hiddenContainer);
        }

        inputField._ua = {
            wrapper:         wrapper,
            badgesContainer: badgesContainer,
            dropdown:        dropdown,
            hiddenContainer: hiddenContainer,
            activeIndex:     -1
        };
    }

    function attachEventListeners(inputField) {
        const ua = inputField._ua;

        inputField.addEventListener('input', function () {
            handleInput(inputField);
        });

        inputField.addEventListener('keydown', function (e) {
            handleKeydown(inputField, e);
        });

        // Close on outside click
        document.addEventListener('click', function (e) {
            if (!ua.wrapper.contains(e.target)) {
                closeDropdown(inputField);
            }
        });
    }

    function handleInput(inputField) {
        const text = inputField.value.trim();
        clearTimeout(state.debounce);

        if (text.length < state.minChars) {
            closeDropdown(inputField);
            return;
        }

        state.debounce = setTimeout(function () {
            fetchSuggestions(inputField, text);
        }, 300);
    }

    function handleKeydown(inputField, e) {
        const ua = inputField._ua;
        const items = ua.dropdown.querySelectorAll('.unified-dropdown-item');

        if (!ua.dropdown.classList.contains('visible') || items.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            ua.activeIndex = Math.min(ua.activeIndex + 1, items.length - 1);
            items[ua.activeIndex].focus();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            ua.activeIndex = Math.max(ua.activeIndex - 1, -1);
            if (ua.activeIndex >= 0) {
                items[ua.activeIndex].focus();
            } else {
                inputField.focus();
            }
        } else if (e.key === 'Escape') {
            closeDropdown(inputField);
        }
    }

    function fetchSuggestions(inputField, text) {
        // Build excluded id lists
        const excludedBoundaryIds = state.selectedBoundaries.map(function (b) { return b.id; }).join(',');
        const excludedChurchIds   = state.selectedChurches.map(function (c) { return c.id; }).join(',');

        let url = state.apiUrl + '?text=' + encodeURIComponent(text);
        if (excludedBoundaryIds) url += '&excluded_ids=' + encodeURIComponent(excludedBoundaryIds);
        if (excludedChurchIds)   url += '&excluded_church_ids=' + encodeURIComponent(excludedChurchIds);

        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                renderDropdown(inputField, data.results || []);
            })
            .catch(function (err) {
                console.error('UnifiedAutocomplete: fetch error', err);
                closeDropdown(inputField);
            });
    }

    function renderDropdown(inputField, results) {
        const ua = inputField._ua;
        ua.dropdown.innerHTML = '';
        ua.activeIndex = -1;

        if (results.length === 0) {
            closeDropdown(inputField);
            return;
        }

        results.forEach(function (result) {
            const item = document.createElement('div');
            item.className = 'unified-dropdown-item';
            item.setAttribute('tabindex', '0');
            item.setAttribute('role', 'option');

            if (result.kind === 'church') {
                // Church item: slate-blue "misézőhely" badge + "name, city" + church detail link on the right
                const badge = document.createElement('div');
                badge.className = 'unified-dropdown-badge unified-dropdown-church-badge';
                badge.style.backgroundColor = CHURCH_BADGE_COLOR;
                badge.textContent = 'misézőhely';

                const textEl = document.createElement('div');
                textEl.className = 'unified-dropdown-text';
                textEl.textContent = result.name + (result.city ? ', ' + result.city : '');

                item.appendChild(badge);
                item.appendChild(textEl);

                // Right-aligned link to the church detail page
                const churchLink = document.createElement('a');
                churchLink.href = '/templom/' + encodeURIComponent(result.id);
                churchLink.className = 'unified-boundary-osm-icon unified-church-detail-link';
                churchLink.setAttribute('title', 'Templomoldal megnyitása');
                churchLink.setAttribute('target', '_blank');
                churchLink.setAttribute('rel', 'noopener noreferrer');
                const linkIcon = document.createElement('i');
                linkIcon.className = 'fas fa-church';
                churchLink.appendChild(linkIcon);
                item.appendChild(churchLink);

                item.addEventListener('click', function (e) {
                    // Don't trigger selection on detail link click
                    if (e.target.closest('.unified-church-detail-link')) return;
                    e.preventDefault();
                    e.stopPropagation();
                    selectChurch(inputField, result);
                    closeDropdown(inputField);
                    inputField.focus();
                });
                item.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        if (e.target.closest('.unified-church-detail-link')) return;
                        e.preventDefault();
                        selectChurch(inputField, result);
                        closeDropdown(inputField);
                        inputField.focus();
                    }
                });

            } else {
                // Boundary item: colored type badge + name + optional OSM link
                const badge = document.createElement('div');
                badge.className = 'unified-dropdown-badge';
                badge.style.backgroundColor = result.color || '#999';
                badge.textContent = result.type || '';

                const textEl = document.createElement('div');
                textEl.className = 'unified-dropdown-text';
                textEl.textContent = result.name || '';

                item.appendChild(badge);
                item.appendChild(textEl);

                if (result.osm && result.osm.type && result.osm.id) {
                    const osmLink = document.createElement('a');
                    osmLink.href = '/collection/' + encodeURIComponent(result.osm.type) + ':' + encodeURIComponent(result.osm.id);
                    osmLink.className = 'unified-boundary-osm-icon';
                    osmLink.title = 'Megjelenítés a térképen';
                    osmLink.target = '_blank';
                    osmLink.rel = 'noopener noreferrer';
                    const mapIcon = document.createElement('i');
                    mapIcon.className = 'fas fa-map-marked-alt';
                    osmLink.appendChild(mapIcon);
                    item.appendChild(osmLink);
                }

                item.addEventListener('click', function (e) {
                    // Don't trigger on OSM link click
                    if (e.target.closest('.unified-boundary-osm-icon')) return;
                    e.preventDefault();
                    e.stopPropagation();
                    selectBoundary(inputField, result);
                    closeDropdown(inputField);
                    inputField.focus();
                });
                item.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        if (e.target.closest('.unified-boundary-osm-icon')) return;
                        e.preventDefault();
                        selectBoundary(inputField, result);
                        closeDropdown(inputField);
                        inputField.focus();
                    }
                });
            }

            ua.dropdown.appendChild(item);
        });

        ua.dropdown.classList.add('visible');
    }

    // ── Selection ────────────────────────────────────────────────────────────

    function selectBoundary(inputField, data) {
        if (state.selectedBoundaries.some(function (b) { return b.id === data.id; })) return;

        state.selectedBoundaries.push({
            id:    data.id,
            name:  data.name,
            type:  data.type,
            color: data.color,
            osm:   data.osm || null
        });

        addBoundaryBadge(inputField, data);
        addHiddenField(inputField, 'boundaries[]', data.id);

        inputField.value = '';
        inputField.focus();
    }

    function selectChurch(inputField, data) {
        if (state.selectedChurches.some(function (c) { return c.id === data.id; })) return;

        state.selectedChurches.push({ id: data.id, name: data.name, city: data.city || '' });

        addChurchBadge(inputField, data);
        addHiddenField(inputField, 'church_ids[]', data.id);

        inputField.value = '';
        inputField.focus();
    }

    // ── Badge rendering ──────────────────────────────────────────────────────

    function addBoundaryBadge(inputField, data) {
        const ua = inputField._ua;

        const badge = document.createElement('div');
        badge.className = 'unified-badge';
        badge.style.backgroundColor = data.color || '#999';
        badge.dataset.boundaryId = data.id;

        const text = document.createElement('span');
        text.textContent = data.type + ': ' + data.name;
        badge.appendChild(text);

        if (data.osm && data.osm.type && data.osm.id) {
            const osmLink = document.createElement('a');
            osmLink.href = '/collection/' + encodeURIComponent(data.osm.type) + ':' + encodeURIComponent(data.osm.id);
            osmLink.style.color = '#fff';
            osmLink.style.marginLeft = '4px';
            osmLink.title = 'Megjelenítés a térképen';
            osmLink.target = '_blank';
            osmLink.rel = 'noopener noreferrer';
            const icon = document.createElement('i');
            icon.className = 'fas fa-map-marked-alt';
            osmLink.appendChild(icon);
            badge.appendChild(osmLink);
        }

        badge.appendChild(makeRemoveBtn(function () {
            removeBoundary(inputField, data.id);
        }));

        ua.badgesContainer.appendChild(badge);
    }

    function addChurchBadge(inputField, data) {
        const ua = inputField._ua;

        const badge = document.createElement('div');
        badge.className = 'unified-badge unified-church-badge';
        badge.style.backgroundColor = CHURCH_BADGE_COLOR;
        badge.dataset.churchId = data.id;

        const icon = document.createElement('span');
        icon.className = 'unified-church-icon';
        icon.textContent = '⛪';
        icon.setAttribute('aria-hidden', 'true');
        badge.appendChild(icon);

        const text = document.createElement('span');
        text.textContent = data.name + (data.city ? ' (' + data.city + ')' : '');
        badge.appendChild(text);

        badge.appendChild(makeRemoveBtn(function () {
            removeChurch(inputField, data.id);
        }));

        ua.badgesContainer.appendChild(badge);
    }

    function makeRemoveBtn(onClick) {
        const btn = document.createElement('span');
        btn.className = 'unified-badge-remove';
        btn.textContent = '×';
        btn.setAttribute('aria-label', 'Eltávolítás');
        btn.setAttribute('role', 'button');
        btn.setAttribute('tabindex', '0');
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            onClick();
        });
        btn.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                onClick();
            }
        });
        return btn;
    }

    // ── Removal ──────────────────────────────────────────────────────────────

    function removeBoundary(inputField, boundaryId) {
        state.selectedBoundaries = state.selectedBoundaries.filter(function (b) { return b.id !== boundaryId; });

        const ua = inputField._ua;
        const badgeEl = ua.badgesContainer.querySelector('[data-boundary-id="' + boundaryId + '"]');
        if (badgeEl) badgeEl.remove();

        const hiddenEl = ua.hiddenContainer.querySelector('input[name="boundaries[]"][value="' + boundaryId + '"]');
        if (hiddenEl) hiddenEl.remove();

        inputField.focus();
    }

    function removeChurch(inputField, churchId) {
        state.selectedChurches = state.selectedChurches.filter(function (c) { return c.id !== churchId; });

        const ua = inputField._ua;
        const badgeEl = ua.badgesContainer.querySelector('[data-church-id="' + churchId + '"]');
        if (badgeEl) badgeEl.remove();

        const hiddenEl = ua.hiddenContainer.querySelector('input[name="church_ids[]"][value="' + churchId + '"]');
        if (hiddenEl) hiddenEl.remove();

        inputField.focus();
    }

    // ── Hidden fields ────────────────────────────────────────────────────────

    function addHiddenField(inputField, name, value) {
        const ua = inputField._ua;
        const hidden = document.createElement('input');
        hidden.type  = 'hidden';
        hidden.name  = name;
        hidden.value = value;
        ua.hiddenContainer.appendChild(hidden);
    }

    // ── Dropdown close ───────────────────────────────────────────────────────

    function closeDropdown(inputField) {
        const ua = inputField._ua;
        if (ua && ua.dropdown) {
            ua.dropdown.classList.remove('visible');
            ua.dropdown.innerHTML = '';
            ua.activeIndex = -1;
        }
    }

    // ── Public exports ───────────────────────────────────────────────────────

    return {
        init: init,
        closeDropdown: closeDropdown,
        getSelectedBoundaries: function () { return state.selectedBoundaries; },
        getSelectedChurches:   function () { return state.selectedChurches; },
        clearAll: function () {
            state.selectedBoundaries = [];
            state.selectedChurches   = [];
        }
    };

})();
