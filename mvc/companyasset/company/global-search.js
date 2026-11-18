(function () {
    function initGlobalSearch() {
        if (window.__fvGlobalSearchInitialized) {
            return;
        }

        const input = document.getElementById('fv-global-search-input');
        const dropdown = document.getElementById('fv-global-search-results');
        if (!input || !dropdown) {
            return;
        }

        window.__fvGlobalSearchInitialized = true;

        const lang = window.fvGlobalSearchLang || {};
        let debounceTimer = null;
        let lastQuery = '';
        let activeController = null;
        let activeIndex = -1;

        function t(key, fallback) {
            return lang[key] || fallback || '';
        }

        function baseUrl() {
            if (input.dataset.searchUrl) {
                return String(input.dataset.searchUrl).replace(/\/$/, '');
            }
            if (window.options && window.options.url) {
                return String(window.options.url).replace(/\/$/, '');
            }
            return '';
        }

        function hideDropdown() {
            dropdown.classList.add('is-hidden');
            dropdown.innerHTML = '';
            activeIndex = -1;
        }

        function showDropdown(html) {
            dropdown.innerHTML = html;
            dropdown.classList.remove('is-hidden');
            activeIndex = -1;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text == null ? '' : String(text);
            return div.innerHTML;
        }

        function thumbHtml(item) {
            const image = String(item.image || '').trim();
            const icon = escapeHtml(item.icon || 'mdi-magnify');
            if (image) {
                return '<span class="fv-global-search-item__thumb">' +
                    '<img src="' + escapeHtml(image) + '" alt="" loading="lazy" ' +
                    'onerror="this.remove();this.parentNode.classList.add(\'is-fallback\');">' +
                    '<i class="mdi ' + icon + '" aria-hidden="true"></i>' +
                    '</span>';
            }
            return '<span class="fv-global-search-item__thumb is-fallback">' +
                '<i class="mdi ' + icon + '" aria-hidden="true"></i></span>';
        }

        function metaHtml(item) {
            const meta = Array.isArray(item.meta) ? item.meta : [];
            if (!meta.length) {
                if (item.subtitle) {
                    return '<span class="fv-global-search-item__sub">' + escapeHtml(item.subtitle) + '</span>';
                }
                return '';
            }
            let html = '<span class="fv-global-search-item__meta">';
            meta.forEach(function (line) {
                if (!line) {
                    return;
                }
                html += '<span class="fv-global-search-item__meta-line">' + escapeHtml(String(line)) + '</span>';
            });
            html += '</span>';
            return html;
        }

        function renderResults(results) {
            if (!results || results.length === 0) {
                showDropdown('<div class="fv-global-search-empty">' + escapeHtml(t('no_results', 'No results')) + '</div>');
                return;
            }

            let html = '';
            results.forEach(function (item, index) {
                const typeLabel = item.type_label || item.section || item.type || '';
                html += '<button type="button" class="fv-global-search-item" role="option"';
                html += ' data-index="' + index + '"';
                html += ' data-type="' + escapeHtml(item.type || '') + '"';
                html += ' data-id="' + escapeHtml(item.id || '') + '"';
                html += ' data-url="' + escapeHtml(item.url || '#') + '">';
                html += thumbHtml(item);
                html += '<span class="fv-global-search-item__text">';
                html += '<strong class="fv-global-search-item__title">' + escapeHtml(item.title || '') + '</strong>';
                html += metaHtml(item);
                html += '</span>';
                if (typeLabel) {
                    html += '<span class="fv-global-search-item__badge">' + escapeHtml(typeLabel) + '</span>';
                }
                html += '</button>';
            });

            showDropdown(html);
        }

        function search(query) {
            const root = baseUrl();
            if (!root) {
                showDropdown('<div class="fv-global-search-empty">' + escapeHtml(t('modal_error', 'Error')) + '</div>');
                return;
            }

            if (activeController) {
                activeController.abort();
            }
            activeController = typeof AbortController !== 'undefined' ? new AbortController() : null;

            const url = root + (root.indexOf('?') >= 0 ? '&' : '?') + 'q=' + encodeURIComponent(query);
            showDropdown('<div class="fv-global-search-empty">' + escapeHtml(t('searching', 'Searching...')) + '</div>');

            const fetchOpts = {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            };
            if (activeController) {
                fetchOpts.signal = activeController.signal;
            }

            fetch(url, fetchOpts)
                .then(function (res) {
                    if (!res.ok) {
                        throw new Error('http_' + res.status);
                    }
                    return res.json();
                })
                .then(function (data) {
                    if (lastQuery !== query) {
                        return;
                    }
                    let results = [];
                    if (data && Array.isArray(data.results)) {
                        results = data.results;
                    } else if (data && data.data && Array.isArray(data.data.results)) {
                        results = data.data.results;
                    } else if (data && Array.isArray(data.data)) {
                        results = data.data;
                    }
                    renderResults(results);
                })
                .catch(function (err) {
                    if (err && err.name === 'AbortError') {
                        return;
                    }
                    if (lastQuery === query) {
                        showDropdown('<div class="fv-global-search-empty">' + escapeHtml(t('no_results', 'No results')) + '</div>');
                    }
                });
        }

        function goToItem(btn) {
            if (!btn) {
                return;
            }
            const url = btn.getAttribute('data-url') || '';
            hideDropdown();
            input.blur();
            if (url && url !== '#') {
                window.location.href = url;
            }
        }

        function highlightActive() {
            const items = dropdown.querySelectorAll('.fv-global-search-item');
            items.forEach(function (el, idx) {
                el.classList.toggle('is-active', idx === activeIndex);
            });
            if (activeIndex >= 0 && items[activeIndex]) {
                items[activeIndex].scrollIntoView({ block: 'nearest' });
            }
        }

        dropdown.addEventListener('click', function (e) {
            const btn = e.target.closest('.fv-global-search-item');
            if (!btn) {
                return;
            }
            e.preventDefault();
            goToItem(btn);
        });

        input.addEventListener('input', function () {
            const query = input.value.trim();
            lastQuery = query;
            clearTimeout(debounceTimer);

            if (query.length < 1) {
                hideDropdown();
                return;
            }

            debounceTimer = setTimeout(function () {
                search(query);
            }, 180);
        });

        input.addEventListener('focus', function () {
            const query = input.value.trim();
            if (query.length >= 1) {
                if (dropdown.innerHTML === '') {
                    search(query);
                } else {
                    dropdown.classList.remove('is-hidden');
                }
            }
        });

        document.addEventListener('click', function (e) {
            const wrap = input.closest('.fv-company-nav__search');
            if (wrap && wrap.contains(e.target)) {
                return;
            }
            if (!dropdown.contains(e.target) && e.target !== input) {
                hideDropdown();
            }
        });

        input.addEventListener('keydown', function (e) {
            const items = dropdown.querySelectorAll('.fv-global-search-item');
            if (e.key === 'Escape') {
                hideDropdown();
                input.blur();
                return;
            }
            if (e.key === 'ArrowDown') {
                if (!items.length) {
                    return;
                }
                e.preventDefault();
                activeIndex = activeIndex < items.length - 1 ? activeIndex + 1 : 0;
                highlightActive();
                return;
            }
            if (e.key === 'ArrowUp') {
                if (!items.length) {
                    return;
                }
                e.preventDefault();
                activeIndex = activeIndex > 0 ? activeIndex - 1 : items.length - 1;
                highlightActive();
                return;
            }
            if (e.key === 'Enter') {
                e.preventDefault();
                if (activeIndex >= 0 && items[activeIndex]) {
                    goToItem(items[activeIndex]);
                    return;
                }
                if (items[0]) {
                    goToItem(items[0]);
                }
            }
        });

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
                e.preventDefault();
                input.focus();
                input.select();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGlobalSearch);
    } else {
        initGlobalSearch();
    }
})();
