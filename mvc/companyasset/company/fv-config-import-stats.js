(function (window, $) {
    'use strict';

    var PAGE_SIZE = 100;
    var preview = window.fvConfigImportPreview || {};
    var state = {
        filter: 'total',
        page: 1,
        filtered: []
    };

    function formatCount(value) {
        var n = parseInt(value, 10) || 0;
        var sep = preview.groupSep || ',';
        return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, sep);
    }

    function replaceTokens(template, data) {
        if (!template) {
            return '';
        }
        return String(template).replace(/\{(\w+)\}/g, function (_, key) {
            if (!data || data[key] === undefined || data[key] === null) {
                return '';
            }
            return String(data[key]);
        });
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function filterRows(filter) {
        var rows = Array.isArray(preview.rows) ? preview.rows : [];

        if (filter === 'valid') {
            return rows.filter(function (row) {
                return row.valid && !row.duplicate && !row.skip;
            });
        }
        if (filter === 'errors') {
            return rows.filter(function (row) {
                return !row.valid && !row.skip;
            });
        }
        if (filter === 'duplicates') {
            return rows.filter(function (row) {
                return row.duplicate;
            });
        }

        return rows.slice();
    }

    function rowClass(row) {
        if (row.duplicate) {
            return 'fv-config-import__row--duplicate';
        }
        if (row.skip) {
            return 'fv-config-import__row--skip';
        }
        if (!row.valid) {
            return 'fv-config-import__row--error';
        }
        return 'fv-config-import__row--ok';
    }

    function statusHtml(row) {
        if (row.duplicate) {
            var dupHtml = '<span class="fv-config-import__badge fv-config-import__badge--warn">' + escapeHtml(preview.statusDuplicate) + '</span>';
            if (row.errors && row.errors.length) {
                dupHtml += '<small class="d-block fv-config-import__error-detail mt-1">' + escapeHtml(row.errors.join(' · ')) + '</small>';
            }
            return dupHtml;
        }
        if (row.skip) {
            var skipHtml = '<span class="fv-config-import__badge fv-config-import__badge--skip">' + escapeHtml(preview.statusSkipped || preview.statusError) + '</span>';
            if (row.errors && row.errors.length) {
                skipHtml += '<small class="d-block fv-config-import__error-detail mt-1">' + escapeHtml(row.errors.join(' · ')) + '</small>';
            }
            return skipHtml;
        }
        if (row.valid) {
            return '<span class="fv-config-import__badge fv-config-import__badge--ok">' + escapeHtml(preview.statusOk) + '</span>';
        }
        var html = '<span class="fv-config-import__badge fv-config-import__badge--error">' + escapeHtml(preview.statusError) + '</span>';
        if (row.errors && row.errors.length) {
            html += '<small class="d-block fv-config-import__error-detail mt-1">' + escapeHtml(row.errors.join(' · ')) + '</small>';
        }
        return html;
    }

    function renderTableHead() {
        var $head = $('#fv-config-import-stats-modal-head');
        if (!$head.length) {
            return;
        }

        var html = '<th class="fv-config-import__col-line">' + escapeHtml(preview.colLine || '#') + '</th>';
        (preview.columns || []).forEach(function (label) {
            html += '<th>' + escapeHtml(label) + '</th>';
        });
        html += '<th class="fv-config-import__col-status">' + escapeHtml(preview.colStatus || '') + '</th>';
        $head.html(html);
    }

    function renderTableBody() {
        var $body = $('#fv-config-import-stats-modal-body');
        var $empty = $('.fv-config-import-stats-modal__empty');
        var $tableWrap = $('.fv-config-import-stats-modal__table-wrap');
        if (!$body.length) {
            return;
        }

        var total = state.filtered.length;
        var totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
        if (state.page > totalPages) {
            state.page = totalPages;
        }
        if (state.page < 1) {
            state.page = 1;
        }

        var start = (state.page - 1) * PAGE_SIZE;
        var pageRows = state.filtered.slice(start, start + PAGE_SIZE);

        if (total === 0) {
            $body.empty();
            $tableWrap.addClass('d-none');
            $empty.removeClass('d-none').text(preview.empty || '');
            renderPager(0, 0);
            return;
        }

        $tableWrap.removeClass('d-none');
        $empty.addClass('d-none');

        var html = '';
        pageRows.forEach(function (row) {
            html += '<tr class="' + rowClass(row) + '">';
            html += '<td class="fv-config-import__col-line">' + formatCount(row.line) + '</td>';
            (row.cells || []).forEach(function (cell, cellIndex) {
                var isErrorCell = (row.error_indexes || []).indexOf(cellIndex) !== -1;
                html += '<td class="' + (isErrorCell ? 'fv-config-import__cell--error' : '') + '">' + escapeHtml(cell) + '</td>';
            });
            html += '<td class="fv-config-import__status-cell">' + statusHtml(row) + '</td>';
            html += '</tr>';
        });
        $body.html(html);
        renderPager(totalPages, total);
    }

    function renderPager(totalPages, totalRows) {
        var $pager = $('.fv-config-import-stats-modal__pager');
        var $summary = $('.fv-config-import-stats-modal__summary');
        if (!$pager.length) {
            return;
        }

        $summary.text(replaceTokens(preview.summary || '', {
            count: formatCount(totalRows),
            page: formatCount(state.page),
            pages: formatCount(totalPages)
        }));

        if (totalPages <= 1) {
            $pager.empty();
            return;
        }

        var prevDisabled = state.page <= 1 ? ' disabled' : '';
        var nextDisabled = state.page >= totalPages ? ' disabled' : '';
        $pager.html(
            '<button type="button" class="btn btn-outline-secondary btn-sm fv-config-import-stats-modal__prev"' + prevDisabled + '>' + escapeHtml(preview.prev || '') + '</button>' +
            '<button type="button" class="btn btn-light btn-sm" disabled>' + escapeHtml(replaceTokens(preview.pageLabel || '', {
                current: formatCount(state.page),
                total: formatCount(totalPages)
            })) + '</button>' +
            '<button type="button" class="btn btn-outline-secondary btn-sm fv-config-import-stats-modal__next"' + nextDisabled + '>' + escapeHtml(preview.next || '') + '</button>'
        );
    }

    function openModal(filter) {
        var titles = preview.titles || {};
        state.filter = filter || 'total';
        state.page = 1;
        state.filtered = filterRows(state.filter);

        $('#fv-config-import-stats-modal-title').text(titles[state.filter] || titles.total || '');
        renderTableHead();
        renderTableBody();
        $('#fv-config-import-stats-modal').modal('show');
    }

    function init() {
        if (!preview.rows || !preview.rows.length || typeof $ !== 'function') {
            return;
        }

        renderTableHead();

        $(document).on('click', '.fv-config-import__stat-link', function () {
            openModal($(this).data('filter'));
        });

        $(document).on('click', '.fv-config-import-stats-modal__prev', function () {
            if (state.page > 1) {
                state.page--;
                renderTableBody();
            }
        });

        $(document).on('click', '.fv-config-import-stats-modal__next', function () {
            var totalPages = Math.max(1, Math.ceil(state.filtered.length / PAGE_SIZE));
            if (state.page < totalPages) {
                state.page++;
                renderTableBody();
            }
        });
    }

    $(init);
}(window, window.jQuery));
