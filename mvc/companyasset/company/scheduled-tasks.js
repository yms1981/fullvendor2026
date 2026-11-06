(function (window, $) {
    'use strict';

    if (!$) {
        return;
    }

    function normalizeMessage(message, fallback) {
        if (message == null || message === '') {
            return fallback || '';
        }
        if (typeof message === 'string') {
            return message;
        }
        if (typeof message === 'object') {
            if (typeof message.message === 'string' && message.message) {
                return message.message;
            }
            if (message.result && typeof message.result.message === 'string') {
                return message.result.message;
            }
            if (message.data && typeof message.data.last_message === 'string') {
                return message.data.last_message;
            }
        }
        return fallback || '';
    }

    function notify(message, icon) {
        message = normalizeMessage(message, '');
        if (!message) {
            return;
        }
        if (typeof swal === 'function') {
            swal({
                title: icon === 'success' ? message : '',
                text: icon === 'success' ? '' : message,
                icon: icon || 'info',
                timer: icon === 'success' ? 1600 : undefined,
            });
            return;
        }
        window.alert(message);
    }

    function saveRow($row, $table) {
        var type = $row.data('task-type');
        var url = ($table.data('update-base') || '') + encodeURIComponent(type);
        var payload = {
            enabled: $row.find('.fv-task-enabled').is(':checked'),
            frequency: $row.find('.fv-task-frequency').val(),
        };

        return $.ajax({
            url: url,
            method: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify(payload),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
    }

    function formatWhen(raw) {
        var value = String(raw || '').trim();
        if (!value) {
            return '';
        }
        var d = new Date(value);
        if (isNaN(d.getTime())) {
            return value;
        }
        var pad = function (n) {
            return n < 10 ? '0' + n : String(n);
        };
        return (
            d.getFullYear() +
            '-' +
            pad(d.getMonth() + 1) +
            '-' +
            pad(d.getDate()) +
            ' ' +
            pad(d.getHours()) +
            ':' +
            pad(d.getMinutes()) +
            ':' +
            pad(d.getSeconds())
        );
    }

    function shortMessage(message, maxLen) {
        var text = String(message || '').trim();
        var max = maxLen || 160;
        if (!text) {
            return { short: '', full: '' };
        }
        var match = text.match(/ERP HTTP\s+\d+:\s*(\{[\s\S]*\})\s*$/);
        if (match) {
            try {
                var parsed = JSON.parse(match[1]);
                if (parsed && typeof parsed.message === 'string' && parsed.message.trim()) {
                    text = parsed.message.trim();
                }
            } catch (e) {
                // keep original
            }
        }
        if (text.length <= max) {
            return { short: text, full: text };
        }
        return { short: text.slice(0, max).replace(/\s+\S*$/, '') + '…', full: text };
    }

    function statusLabel(status) {
        var texts = window.fvScheduledTaskUiTexts || {};
        if (status === 'success') {
            return texts.status_success || status;
        }
        if (status === 'error') {
            return texts.status_error || status;
        }
        return status;
    }

    function applyTaskData($row, data) {
        if (!data) {
            return;
        }
        if (data.last_run_at) {
            $row.find('.fv-task-last-run').text(formatWhen(data.last_run_at));
        }
        if (data.last_status) {
            var status = String(data.last_status);
            var badgeClass = status === 'success' ? 'success' : status === 'error' ? 'danger' : 'secondary';
            $row.find('.fv-task-last-status').html(
                '<span class="badge badge-' +
                    badgeClass +
                    ' fv-scheduled-status">' +
                    escapeHtml(statusLabel(status)) +
                    '</span>'
            );
        }
        if (typeof data.last_message === 'string') {
            var parts = shortMessage(data.last_message, 100);
            var $msg = $row.find('td:first .fv-scheduled-task-msg');
            if ($msg.length) {
                $msg.attr('title', parts.full).text(parts.short);
            } else if (parts.short) {
                $row.find('td:first').append(
                    $('<div class="small text-muted fv-scheduled-task-msg"></div>')
                        .attr('title', parts.full)
                        .text(parts.short)
                );
            }
        }
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function typeLabel(type) {
        var labels = window.fvScheduledTaskTypeLabels || {};
        return labels[type] || type;
    }

    function triggerLabel(trigger) {
        var texts = window.fvScheduledTaskUiTexts || {};
        if (trigger === 'cron') {
            return texts.trigger_cron || 'cron';
        }
        return texts.trigger_manual || 'manual';
    }

    function renderLogs(logs) {
        var $tbody = $('#fv-scheduled-logs-table tbody');
        if (!$tbody.length) {
            return;
        }
        var texts = window.fvScheduledTaskUiTexts || {};
        if (!logs || !logs.length) {
            $tbody.html(
                '<tr class="fv-logs-empty"><td colspan="6" class="text-muted">' +
                    escapeHtml(texts.logs_empty || '') +
                    '</td></tr>'
            );
            return;
        }

        var html = '';
        logs.forEach(function (log) {
            var status = String(log.status || '');
            var badge = status === 'success' ? 'success' : 'danger';
            var when = formatWhen(log.finished_at || log.created_at || '');
            var duration = log.duration_ms != null ? String(log.duration_ms) + ' ms' : '';
            var trigger = String(log.trigger || '');
            var triggerClass = trigger === 'cron' ? 'cron' : 'manual';
            var msg = shortMessage(log.message || '', 160);
            var rowClass = status === 'error' ? 'fv-log-row-error' : 'fv-log-row-success';
            html +=
                '<tr class="' +
                rowClass +
                '">' +
                '<td class="text-nowrap">' +
                escapeHtml(when) +
                '</td>' +
                '<td>' +
                escapeHtml(typeLabel(log.task_type)) +
                '</td>' +
                '<td><span class="fv-scheduled-trigger fv-scheduled-trigger--' +
                triggerClass +
                '">' +
                escapeHtml(triggerLabel(trigger)) +
                '</span></td>' +
                '<td><span class="badge badge-' +
                badge +
                ' fv-scheduled-status">' +
                escapeHtml(statusLabel(status)) +
                '</span></td>' +
                '<td class="text-nowrap">' +
                escapeHtml(duration) +
                '</td>' +
                '<td class="fv-scheduled-log-msg" title="' +
                escapeHtml(msg.full) +
                '">' +
                escapeHtml(msg.short) +
                '</td>' +
                '</tr>';
        });
        $tbody.html(html);
    }

    function refreshLogs($table) {
        var url = $table.data('logs-url');
        if (!url) {
            return $.Deferred().resolve().promise();
        }
        var params = {
            per_page: 50,
            page: 1,
        };
        var taskType = String($('#fv-logs-filter-type').val() || '');
        var status = String($('#fv-logs-filter-status').val() || '');
        if (taskType) {
            params.task_type = taskType;
        }
        if (status) {
            params.status = status;
        }

        return $.ajax({
            url: url,
            method: 'GET',
            data: params,
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        }).done(function (resp) {
            renderLogs((resp && resp.data) || []);
        });
    }

    function queueStatusLabel(status) {
        var texts = window.fvScheduledTaskUiTexts || {};
        var map = {
            pending: texts.queue_status_pending,
            processing: texts.queue_status_processing,
            downloaded: texts.queue_status_downloaded,
            failed: texts.queue_status_failed,
            skipped: texts.queue_status_skipped,
        };
        return map[status] || status;
    }

    function queueBadgeClass(status) {
        if (status === 'downloaded') {
            return 'success';
        }
        if (status === 'failed') {
            return 'danger';
        }
        if (status === 'processing') {
            return 'info';
        }
        if (status === 'skipped') {
            return 'secondary';
        }
        return 'warning';
    }

    function renderImageQueue(data) {
        var $panel = $('#fv-image-queue-progress');
        if (!$panel.length || !data) {
            return;
        }
        var texts = window.fvScheduledTaskUiTexts || {};
        $panel.find('.fv-iq-total').text(data.total || 0);
        $panel.find('.fv-iq-pending').text(data.pending || 0);
        $panel.find('.fv-iq-downloaded').text(data.downloaded || 0);
        $panel.find('.fv-iq-failed').text(data.failed || 0);
        $panel.find('.fv-iq-skipped').text(data.skipped || 0);
        var percent = Number(data.percent || 0);
        $('#fv-image-queue-bar-fill').css('width', percent + '%');
        $('#fv-image-queue-percent').text(
            (Math.round(percent * 10) / 10).toString().replace(/\.0$/, '') + '%'
        );
        if (data.active) {
            $panel.attr('data-active', '1');
            $('#fv-image-queue-active').removeClass('d-none');
        } else {
            $panel.attr('data-active', '0');
            $('#fv-image-queue-active').addClass('d-none');
        }

        var $tbody = $('#fv-image-queue-table tbody');
        var recent = data.recent || [];
        if (!recent.length) {
            $tbody.html(
                '<tr class="fv-iq-empty"><td colspan="5" class="text-muted">' +
                    escapeHtml(texts.queue_empty || '') +
                    '</td></tr>'
            );
            return;
        }
        var html = '';
        recent.forEach(function (row) {
            var st = String(row.status || '');
            var when = formatWhen(row.finished_at || row.updated_at || row.queued_at || '');
            var detail = String(row.last_error || row.image_url || '');
            if (detail.length > 120) {
                detail = detail.slice(0, 120) + '…';
            }
            html +=
                '<tr>' +
                '<td class="text-nowrap">' +
                escapeHtml(row.sku || '') +
                '</td>' +
                '<td><span class="badge badge-' +
                queueBadgeClass(st) +
                ' fv-scheduled-status">' +
                escapeHtml(queueStatusLabel(st)) +
                '</span></td>' +
                '<td>' +
                escapeHtml(row.attempts != null ? row.attempts : 0) +
                '</td>' +
                '<td class="text-nowrap">' +
                escapeHtml(when) +
                '</td>' +
                '<td class="fv-scheduled-log-msg" title="' +
                escapeHtml(row.last_error || row.image_url || '') +
                '">' +
                escapeHtml(detail) +
                '</td>' +
                '</tr>';
        });
        $tbody.html(html);
    }

    function refreshImageQueue() {
        var $panel = $('#fv-image-queue-progress');
        var url = $panel.data('queue-url');
        if (!url) {
            return $.Deferred().resolve().promise();
        }
        return $.ajax({
            url: url,
            method: 'GET',
            data: { recent: 40 },
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        }).done(function (resp) {
            renderImageQueue((resp && resp.data) || {});
        });
    }

    $(function () {
        var $table = $('#fv-scheduled-tasks-table');
        if (!$table.length) {
            return;
        }

        var erpEnabled = String($table.data('erp-enabled')) === '1';
        var queueTimer = null;

        function startQueuePolling() {
            if (queueTimer) {
                return;
            }
            queueTimer = window.setInterval(function () {
                refreshImageQueue().done(function () {
                    if (String($('#fv-image-queue-progress').attr('data-active')) !== '1' && queueTimer) {
                        window.clearInterval(queueTimer);
                        queueTimer = null;
                    }
                });
            }, 5000);
        }

        if (String($('#fv-image-queue-progress').attr('data-active')) === '1') {
            startQueuePolling();
        }

        $table.on('change', '.fv-task-enabled, .fv-task-frequency', function () {
            if (!erpEnabled) {
                notify($table.data('msg-erp-disabled'), 'warning');
                return;
            }
            var $row = $(this).closest('tr');
            saveRow($row, $table)
                .done(function (resp) {
                    applyTaskData($row, resp && resp.data);
                    notify($table.data('msg-saved'), 'success');
                })
                .fail(function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || $table.data('msg-error');
                    notify(msg, 'error');
                });
        });

        $table.on('click', '.fv-task-run', function () {
            if (!erpEnabled) {
                notify($table.data('msg-erp-disabled'), 'warning');
                return;
            }
            var $btn = $(this);
            var $row = $btn.closest('tr');
            var type = $row.data('task-type');
            var url = ($table.data('run-base') || '') + encodeURIComponent(type);

            $btn.prop('disabled', true);
            notify($table.data('msg-running'), 'info');

            $.ajax({
                url: url,
                method: 'POST',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .done(function (resp) {
                    applyTaskData($row, resp && resp.data);
                    var msg = normalizeMessage(
                        (resp && resp.result && resp.result.message) || (resp && resp.message),
                        $table.data('msg-saved')
                    );
                    notify(msg, resp && resp.result && resp.result.status === 'success' ? 'success' : 'error');
                    refreshLogs($table);
                    refreshImageQueue().done(function () {
                        if (String($('#fv-image-queue-progress').attr('data-active')) === '1') {
                            startQueuePolling();
                        }
                    });
                })
                .fail(function (xhr) {
                    var body = xhr.responseJSON || {};
                    applyTaskData($row, body.data);
                    var msg = normalizeMessage(
                        body.message || (body.result && body.result.message) || (body.data && body.data.last_message),
                        $table.data('msg-error')
                    );
                    notify(msg, 'error');
                    refreshLogs($table);
                    refreshImageQueue();
                })
                .always(function () {
                    $btn.prop('disabled', false);
                });
        });

        $('#fv-logs-refresh, #fv-logs-filter-type, #fv-logs-filter-status').on('click change', function (e) {
            if (e.type === 'click' && this.id !== 'fv-logs-refresh') {
                return;
            }
            refreshLogs($table).fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || $table.data('msg-error');
                notify(msg, 'error');
            });
        });

        $('#fv-image-queue-refresh').on('click', function () {
            refreshImageQueue().fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || $table.data('msg-error');
                notify(msg, 'error');
            });
        });
    });
})(window, window.jQuery);
