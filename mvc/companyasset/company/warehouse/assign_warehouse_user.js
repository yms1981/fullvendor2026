(function ($) {
    'use strict';

    function syncSelectedUserId($row) {
        var userId = parseInt($row.attr('data-user-id') || 0, 10) || 0;
        $row.closest('tbody').find('.fv-warehouse-user-picker__row').removeClass('is-selected');
        $row.addClass('is-selected');
        $row.find('input[type="radio"]').prop('checked', true);
        $('#user_id').val(userId > 0 ? String(userId) : '');
    }

    function filterWarehouseUserPicker(term) {
        var normalized = String(term || '').toLowerCase().trim();
        var visibleCount = 0;

        $('#warehouseUserPicker .fv-warehouse-user-picker__row').each(function () {
            var $row = $(this);
            var haystack = String($row.attr('data-search') || '').toLowerCase();
            var visible = normalized === '' || haystack.indexOf(normalized) !== -1;
            $row.toggle(visible);
            if (visible) {
                visibleCount += 1;
            }
        });

        $('#warehouse_user_picker_empty_filter').toggleClass('d-none', visibleCount > 0);
    }

    function resetWarehouseUserPicker() {
        $('#warehouse_user_picker_filter').val('');
        $('#user_id').val('');
        $('#warehouseUserPicker .fv-warehouse-user-picker__row').removeClass('is-selected');
        $('#warehouseUserPicker input[type="radio"]').prop('checked', false);
        filterWarehouseUserPicker('');
    }

    $(function () {
        if (!$('#warehouseUserPicker').length) {
            return;
        }

        $('#warehouse_user_picker_filter').on('input', function () {
            filterWarehouseUserPicker($(this).val());
        });

        $(document).on('click', '.fv-warehouse-user-picker__row', function (e) {
            if ($(e.target).is('input[type="radio"]')) {
                syncSelectedUserId($(this).closest('.fv-warehouse-user-picker__row'));
                return;
            }
            e.preventDefault();
            syncSelectedUserId($(this));
        });

        $(document).on('keydown', '.fv-warehouse-user-picker__row', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                syncSelectedUserId($(this));
            }
        });

        $('#addUserModal').on('hidden.bs.modal', function () {
            resetWarehouseUserPicker();
        });

        $('#addUserForm').on('submit', function (e) {
            if (!$('#warehouseUserPicker').length) {
                return;
            }

            var userId = parseInt(String($('#user_id').val() || ''), 10) || 0;
            if (userId > 0) {
                return;
            }

            e.preventDefault();
            e.stopImmediatePropagation();

            var msg = String($(this).attr('data-msg-select-user-required') || '').trim();
            if (typeof swal === 'function') {
                swal({ title: msg, icon: 'warning' });
            }
        });
    });
}(jQuery));
