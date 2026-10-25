<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * JS del selector de moneda admin — debe cargarse al final (después de Bootstrap).
 */
?>
<script>
(function($) {
    'use strict';

    if (!$('#adminCurrencyPickerModal').length) {
        return;
    }

    var $pickerModal = $('#adminCurrencyPickerModal');
    var adminCurrencyFlagsBaseUrl = String($pickerModal.attr('data-flags-base') || '');
    var adminCountrySortnameMap = {};
    try {
        adminCountrySortnameMap = JSON.parse(String($pickerModal.attr('data-country-map') || '{}'));
    } catch (e) {
        adminCountrySortnameMap = {};
    }

    var adminCurrencyPendingRow = null;

    function adminCurrencyResolveSortname(country, groupId, code) {
        var c = String(country || '').trim();
        if (c && adminCountrySortnameMap[c]) {
            return adminCountrySortnameMap[c];
        }
        if (c.indexOf(',') !== -1) {
            var parts = c.split(',');
            for (var i = 0; i < parts.length; i++) {
                var p = String(parts[i] || '').trim();
                if (p && adminCountrySortnameMap[p]) {
                    return adminCountrySortnameMap[p];
                }
            }
        }
        if (String(groupId || '') === 'Cryptocurrencies') {
            return '';
        }
        if (code && String(code).length === 2) {
            return String(code).toLowerCase();
        }
        return '';
    }

    function adminCurrencyFlagHtml(sortname, code) {
        var sn = String(sortname || '').toLowerCase().replace(/[^a-z-]/g, '');
        if (sn.length < 2) {
            return '<span class="admin-currency-flag-placeholder">' + String(code || '?').substring(0, 3) + '</span>';
        }
        var src = adminCurrencyFlagsBaseUrl + sn + '.png';
        return '<img class="admin-currency-flag" src="' + src.replace(/"/g, '&quot;') + '" alt="" onerror="this.outerHTML=\'<span class=&quot;admin-currency-flag-placeholder&quot;>' + String(code || '?').substring(0, 3) + '</span>\'" />';
    }

    function adminCurrencyRowId($row) {
        return parseInt(String($row.attr('data-currency-id') || ''), 10) || 0;
    }

    function adminCurrencyBuildLabel($row) {
        var country = String($row.attr('data-country') || '').trim();
        var name = String($row.attr('data-name') || '').trim();
        var code = String($row.attr('data-code') || '').trim();
        var symbol = String($row.attr('data-symbol') || '').trim();
        return [country, name, code + (symbol ? ' ' + symbol : '')].filter(Boolean).join(' · ');
    }

    function adminCurrencyCloseModal() {
        var el = document.getElementById('adminCurrencyPickerModal');
        if (!el) {
            return;
        }
        if ($.fn && typeof $.fn.modal === 'function') {
            $pickerModal.modal('hide');
        }
        el.classList.remove('show');
        el.style.display = 'none';
        el.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
        document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
            backdrop.remove();
        });
    }

    function adminCurrencyRenderFlags() {
        $('#admin_currency_picker_list .admin-currency-flag-slot').each(function() {
            var $slot = $(this);
            if ($slot.data('rendered')) {
                return;
            }
            var sortname = adminCurrencyResolveSortname(
                $slot.attr('data-country'),
                $slot.attr('data-group'),
                $slot.attr('data-code')
            );
            $slot.html(adminCurrencyFlagHtml(sortname, $slot.attr('data-code')));
            $slot.data('rendered', 1);
        });
    }

    function adminCurrencyFilterList(query) {
        query = String(query || '').trim().toLowerCase();
        var visible = 0;
        $('#admin_currency_picker_list .admin-currency-modal-group').each(function() {
            var groupVisible = 0;
            $(this).find('.admin-currency-modal-row').each(function() {
                var blob = String($(this).attr('data-search') || '');
                var show = query === '' || blob.indexOf(query) !== -1;
                $(this).toggle(show);
                if (show) {
                    groupVisible++;
                    visible++;
                }
            });
            $(this).toggle(groupVisible > 0);
        });
        $('#admin_currency_picker_empty').toggleClass('d-none', visible > 0);
    }

    function adminCurrencyUpdateConfirmBtn() {
        var enabled = !!(adminCurrencyPendingRow && adminCurrencyPendingRow.length);
        $('#admin_currency_picker_confirm_btn').prop('disabled', !enabled);
    }

    function adminCurrencyMarkPending($row) {
        if (!$row || !$row.length) {
            return;
        }
        adminCurrencyPendingRow = $row;
        $('#admin_currency_picker_list .admin-currency-modal-row').removeClass('is-active');
        $row.addClass('is-active');
        adminCurrencyUpdateConfirmBtn();
    }

    function adminCurrencySyncPendingFromValue() {
        var id = parseInt($('#admin_company_currency_id').val(), 10);
        if (id > 0) {
            var $row = $('#admin_currency_picker_list .admin-currency-modal-row[data-currency-id="' + id + '"]');
            if ($row.length) {
                adminCurrencyMarkPending($row.first());
                return;
            }
        }
        adminCurrencyPendingRow = null;
        $('#admin_currency_picker_list .admin-currency-modal-row').removeClass('is-active');
        adminCurrencyUpdateConfirmBtn();
    }

    function adminCurrencyApplySelection($row) {
        var id = adminCurrencyRowId($row);
        if (!id) {
            return;
        }
        $('#admin_company_currency_id').val(String(id));
        $('#admin_company_currency_display_text').text(adminCurrencyBuildLabel($row));
        $('#admin_currency_picker_list .admin-currency-modal-row').removeClass('is-active');
        $row.addClass('is-active');
        adminCurrencyPendingRow = $row;
        adminCurrencyCloseModal();
    }

    function adminCurrencyResolvePendingRow() {
        if (adminCurrencyPendingRow && adminCurrencyPendingRow.length) {
            return adminCurrencyPendingRow;
        }
        var $active = $('#admin_currency_picker_list .admin-currency-modal-row.is-active:visible').first();
        if ($active.length) {
            return $active;
        }
        var id = parseInt($('#admin_company_currency_id').val(), 10);
        if (id > 0) {
            var $row = $('#admin_currency_picker_list .admin-currency-modal-row[data-currency-id="' + id + '"]');
            if ($row.length) {
                return $row.first();
            }
        }
        return null;
    }

    $pickerModal.on('shown.bs.modal', function() {
        adminCurrencyRenderFlags();
        adminCurrencySyncPendingFromValue();
        adminCurrencyUpdateConfirmBtn();
        $('#admin_currency_picker_search').val('');
        adminCurrencyFilterList('');
        $('#admin_currency_picker_search').trigger('focus');
    });

    $(document).on('input', '#admin_currency_picker_search', function() {
        adminCurrencyFilterList($(this).val());
    });

    $(document).on('click', '.admin-currency-modal-row', function() {
        adminCurrencyMarkPending($(this));
    });

    $(document).on('keypress', '.admin-currency-modal-row', function(e) {
        if (e.which !== 13 && e.which !== 32) {
            return;
        }
        e.preventDefault();
        adminCurrencyMarkPending($(this));
    });

    $(document).on('dblclick', '.admin-currency-modal-row', function() {
        adminCurrencyApplySelection($(this));
    });

    $(document).on('click', '#admin_currency_picker_confirm_btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (this.disabled) {
            return;
        }
        var $row = adminCurrencyResolvePendingRow();
        if ($row && $row.length) {
            adminCurrencyApplySelection($row);
        }
    });

    $(document).on('keypress', '#admin_company_currency_display', function(e) {
        if (e.which === 13 || e.which === 32) {
            e.preventDefault();
            $pickerModal.modal('show');
        }
    });

    adminCurrencyRenderFlags();
})(jQuery);
</script>
