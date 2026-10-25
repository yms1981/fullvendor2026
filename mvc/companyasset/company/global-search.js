(function () {
    function initGlobalSearch() {
        if (window.__fvGlobalSearchInitialized) {
            return;
        }

        const input = document.getElementById('fv-global-search-input');
        const dropdown = document.getElementById('fv-global-search-results');
        const modal = document.getElementById('fv-global-search-modal');
        if (!input || !dropdown) {
            return;
        }

        window.__fvGlobalSearchInitialized = true;

        const lang = window.fvGlobalSearchLang || {};
        let debounceTimer = null;
        let lastQuery = '';
        let activeController = null;

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

    function detailUrl() {
        if (input.dataset.detailUrl) {
            return String(input.dataset.detailUrl).replace(/\/$/, '');
        }
        return baseUrl() + '/search/detail';
    }

    function hideDropdown() {
        dropdown.classList.add('is-hidden');
        dropdown.innerHTML = '';
    }

    function showDropdown(html) {
        dropdown.innerHTML = html;
        dropdown.classList.remove('is-hidden');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function renderResults(results) {
        if (!results || results.length === 0) {
            showDropdown('<div class="fv-global-search-empty">' + escapeHtml(t('no_results', 'No results')) + '</div>');
            return;
        }

        const grouped = {};
        results.forEach(function (item) {
            const section = item.section || '';
            if (!grouped[section]) {
                grouped[section] = [];
            }
            grouped[section].push(item);
        });

        let html = '';
        Object.keys(grouped).forEach(function (section) {
            html += '<div class="fv-global-search-section">';
            html += '<div class="fv-global-search-section__title">' + escapeHtml(section) + '</div>';
            grouped[section].forEach(function (item) {
                html += '<button type="button" class="fv-global-search-item"';
                html += ' data-type="' + escapeHtml(item.type || '') + '"';
                html += ' data-id="' + escapeHtml(item.id || '') + '"';
                html += ' data-url="' + escapeHtml(item.url || '#') + '">';
                html += '<span class="fv-global-search-item__icon"><i class="mdi ' + escapeHtml(item.icon || 'mdi-magnify') + '"></i></span>';
                html += '<span class="fv-global-search-item__text"><strong>' + escapeHtml(item.title || '') + '</strong>';
                if (item.subtitle) {
                    html += '<br><small class="text-muted">' + escapeHtml(item.subtitle) + '</small>';
                }
                html += '</span></button>';
            });
            html += '</div>';
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
                const results = (data && data.results) ? data.results : [];
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

    function renderModalFields(fields) {
        const body = document.getElementById('fv-global-search-modal-body');
        if (!body) {
            return;
        }
        if (!fields || !fields.length) {
            body.innerHTML = '<p class="text-muted mb-0">' + escapeHtml(t('no_results', 'No results')) + '</p>';
            return;
        }
        let html = '<dl class="fv-global-search-detail">';
        fields.forEach(function (field) {
            html += '<dt>' + escapeHtml(field.label || '') + '</dt>';
            html += '<dd>' + escapeHtml(field.value || '') + '</dd>';
        });
        html += '</dl>';
        body.innerHTML = html;
    }

    function openDetailModal(type, id, fallbackUrl) {
        if (!modal || !type || !id) {
            if (fallbackUrl) {
                window.location.href = fallbackUrl;
            }
            return;
        }

        const titleEl = document.getElementById('fv-global-search-modal-title');
        const body = document.getElementById('fv-global-search-modal-body');
        const openBtn = document.getElementById('fv-global-search-modal-open');
        if (titleEl) {
            titleEl.textContent = t('modal_loading', 'Loading...');
        }
        if (body) {
            body.innerHTML = '<div class="fv-global-search-empty py-3">' + escapeHtml(t('modal_loading', 'Loading...')) + '</div>';
        }
        if (openBtn) {
            openBtn.href = fallbackUrl || '#';
            openBtn.classList.toggle('d-none', !fallbackUrl);
        }

        if (typeof window.jQuery !== 'undefined' && window.jQuery(modal).modal) {
            window.jQuery(modal).modal('show');
        } else {
            modal.classList.add('show');
            modal.style.display = 'block';
        }

        const url = detailUrl() + '?type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id);
        fetch(url, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('http_' + res.status);
                }
                return res.json();
            })
            .then(function (data) {
                const title = data.title || '';
                const fields = data.fields || [];
                const recordUrl = data.url || fallbackUrl || '#';
                if (titleEl) {
                    titleEl.textContent = title || t('modal_title', 'Detail');
                }
                renderModalFields(fields);
                if (openBtn) {
                    openBtn.href = recordUrl;
                    openBtn.classList.remove('d-none');
                }
            })
            .catch(function () {
                if (titleEl) {
                    titleEl.textContent = t('modal_error', 'Error');
                }
                if (body) {
                    body.innerHTML = '<p class="text-danger mb-0">' + escapeHtml(t('modal_error', 'Error')) + '</p>';
                }
            });
    }

    dropdown.addEventListener('click', function (e) {
        const btn = e.target.closest('.fv-global-search-item');
        if (!btn) {
            return;
        }
        e.preventDefault();
        const type = btn.getAttribute('data-type') || '';
        const id = btn.getAttribute('data-id') || '';
        const url = btn.getAttribute('data-url') || '';
        hideDropdown();
        input.blur();
        openDetailModal(type, id, url);
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
        }, 220);
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
        if (!dropdown.contains(e.target) && e.target !== input) {
            hideDropdown();
        }
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            hideDropdown();
            input.blur();
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
