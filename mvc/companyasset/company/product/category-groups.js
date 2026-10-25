(function ($) {
    'use strict';

    function selectedCategoryIds() {
        var $sel = $('#modal_product_list_taxonomy').hasClass('show')
            ? $('#fv_list_taxonomy_category')
            : $('#category');
        if (!$sel.length) {
            $sel = $('#fv_list_taxonomy_category').length ? $('#fv_list_taxonomy_category') : $('#category');
        }
        if (!$sel.length) {
            return [];
        }
        var vals = $sel.val();
        if (!vals) {
            return [];
        }
        if (!$.isArray(vals)) {
            vals = [vals];
        }
        return vals.map(function (v) { return parseInt(v, 10); }).filter(function (v) { return v > 0; });
    }

    function currentSelection() {
        var groups = [];
        var subgroups = [];
        $('#fv_cat_groups_hidden_inputs input[name="category_group_ids[]"]').each(function () {
            var id = parseInt($(this).val(), 10);
            if (id > 0) {
                groups.push(id);
            }
        });
        $('#fv_cat_groups_hidden_inputs input[name="category_subgroup_ids[]"]').each(function () {
            var id = parseInt($(this).val(), 10);
            if (id > 0) {
                subgroups.push(id);
            }
        });
        return { groups: groups, subgroups: subgroups };
    }

    function escapeHtml(text) {
        return $('<div>').text(text == null ? '' : String(text)).html();
    }

    function collectDisplayLabels() {
        var labels = [];
        var coveredGroups = {};

        $('#fv_cat_groups_hidden_inputs input[name="category_subgroup_ids[]"]').each(function () {
            var parentId = parseInt($(this).attr('data-parent-group-id'), 10) || 0;
            if (parentId > 0) {
                coveredGroups[parentId] = true;
            }
            var label = $.trim($(this).attr('data-label') || '');
            if (label) {
                labels.push(label);
            }
        });

        $('#fv_cat_groups_hidden_inputs input[name="category_group_ids[]"]').each(function () {
            var id = parseInt($(this).val(), 10) || 0;
            if (id > 0 && coveredGroups[id]) {
                return;
            }
            var label = $.trim($(this).attr('data-label') || '');
            if (label) {
                labels.push(label);
            }
        });

        return labels;
    }

    function updateSummary() {
        var $summary = $('#fv_cat_groups_summary');
        if (!$summary.length) {
            return;
        }
        var none = $summary.data('none') || '';
        var labels = collectDisplayLabels();
        var sel = currentSelection();
        var hasSelection = sel.groups.length + sel.subgroups.length > 0;

        if (!hasSelection) {
            $summary
                .removeClass('fv-cat-groups-summary')
                .addClass('text-muted')
                .html(escapeHtml(none));
            return;
        }

        if (!labels.length) {
            return;
        }

        var html = labels.map(function (label) {
            return '<span class="fv-cat-groups-summary__chip">' + escapeHtml(label) + '</span>';
        }).join('');

        $summary
            .removeClass('text-muted')
            .addClass('fv-cat-groups-summary')
            .html(html);
    }

    function writeHiddenInputs(groupItems, subgroupItems) {
        var $box = $('#fv_cat_groups_hidden_inputs');
        if (!$box.length) {
            return;
        }
        $box.empty();
        (groupItems || []).forEach(function (item) {
            $box.append(
                $('<input>', { type: 'hidden', name: 'category_group_ids[]', value: item.id })
                    .attr('data-label', item.label || '')
            );
        });
        (subgroupItems || []).forEach(function (item) {
            $box.append(
                $('<input>', { type: 'hidden', name: 'category_subgroup_ids[]', value: item.id })
                    .attr('data-label', item.label || '')
                    .attr('data-parent-group-id', item.parentGroupId || '')
            );
        });
        updateSummary();
    }

    var pendingChecks = { groups: [], subgroups: [] };

    function modalText(key) {
        return $('#modal_product_category_groups').attr(key) || '';
    }

    function createRow(kind, ownerId) {
        var isGroup = kind === 'group';
        var $row = $('<div class="fv-cat-groups-create"></div>');
        if (!isGroup) {
            $row.addClass('fv-cat-groups-create--sub');
        }
        var $input = $('<input type="text" autocomplete="off">')
            .attr('placeholder', modalText(isGroup ? 'data-ph-group' : 'data-ph-subgroup'))
            .attr('aria-label', modalText(isGroup ? 'data-label-add-group' : 'data-label-add-subgroup'))
            .attr('data-fv-no-icon', '1');
        var $btn = $('<button type="button" class="btn btn-sm btn-outline-danger"></button>')
            .text(modalText(isGroup ? 'data-label-add-group' : 'data-label-add-subgroup'))
            .attr(isGroup ? 'data-create-group' : 'data-create-subgroup', ownerId);
        $row.append($input).append($btn);
        return $row;
    }

    function renderTree(tree, selection) {
        var $root = $('#fv_cat_groups_tree').empty();
        if (!tree || !tree.length) {
            $('#fv_cat_groups_tree_empty').removeClass('d-none');
            return;
        }
        $('#fv_cat_groups_tree_empty').addClass('d-none');

        tree.forEach(function (cat) {
            var catId = parseInt(cat.cat_id, 10);
            var $cat = $('<div class="fv-cat-groups-tree__cat"></div>');
            $cat.append(
                $('<div class="fv-cat-groups-tree__cat-head"></div>').text(cat.category_name || ('#' + cat.cat_id))
            );

            var groups = cat.groups || [];
            if (!groups.length) {
                var emptyMsg = $('#modal_product_category_groups').data('empty-groups') || '';
                $cat.append($('<div class="fv-cat-groups-tree__empty-groups"></div>').text(emptyMsg));
            }

            groups.forEach(function (group) {
                var gid = parseInt(group.group_id, 10);
                var gName = group.group_name || ('#' + gid);
                var $g = $('<div class="fv-cat-groups-tree__group"></div>');
                var $gLabel = $('<label></label>');
                var $gCheck = $('<input type="checkbox" class="fv-cg-check-group">')
                    .attr('data-group-id', gid)
                    .attr('data-group-name', gName)
                    .prop('checked', selection.groups.indexOf(gid) !== -1);
                $gLabel.append($gCheck).append($('<span></span>').text(gName));
                $g.append($gLabel);

                var subs = group.subgroups || [];
                var $subs = $('<div class="fv-cat-groups-tree__subs"></div>');
                subs.forEach(function (sub) {
                    var sid = parseInt(sub.subgroup_id, 10);
                    var sName = sub.subgroup_name || ('#' + sid);
                    var $s = $('<div class="fv-cat-groups-tree__sub"></div>');
                    var $sLabel = $('<label></label>');
                    var $sCheck = $('<input type="checkbox" class="fv-cg-check-subgroup">')
                        .attr('data-subgroup-id', sid)
                        .attr('data-subgroup-name', sName)
                        .attr('data-parent-group-id', gid)
                        .attr('data-parent-group-name', gName)
                        .prop('checked', selection.subgroups.indexOf(sid) !== -1);
                    $sLabel.append($sCheck).append($('<span></span>').text(sName));
                    $s.append($sLabel);
                    $subs.append($s);
                });
                $subs.append(createRow('subgroup', gid));
                $g.append($subs);
                $cat.append($g);
            });

            $cat.append(createRow('group', catId));
            $root.append($cat);
        });
    }

    function liveSelection() {
        var sel = currentSelection();
        $('.fv-cg-check-group:checked').each(function () {
            var id = parseInt($(this).attr('data-group-id'), 10);
            if (id > 0 && sel.groups.indexOf(id) === -1) {
                sel.groups.push(id);
            }
        });
        $('.fv-cg-check-subgroup:checked').each(function () {
            var id = parseInt($(this).attr('data-subgroup-id'), 10);
            if (id > 0 && sel.subgroups.indexOf(id) === -1) {
                sel.subgroups.push(id);
            }
        });
        return sel;
    }

    function createdEntityId(resp, key) {
        var sources = [];
        if (resp && resp.data && typeof resp.data === 'object') {
            sources.push(resp.data);
        }
        if (resp && typeof resp === 'object') {
            sources.push(resp);
        }
        for (var i = 0; i < sources.length; i++) {
            var src = sources[i];
            var id = parseInt(src[key], 10);
            if (id > 0) {
                return id;
            }
            if (src.data && typeof src.data === 'object') {
                id = parseInt(src.data[key], 10);
                if (id > 0) {
                    return id;
                }
            }
        }
        return 0;
    }

    function notifySaveError(xhr) {
        var msg = modalText('data-msg-save-error');
        try {
            var json = xhr.responseJSON || JSON.parse(xhr.responseText || '{}');
            if (json && typeof json.message === 'string' && json.message) {
                msg = json.message;
            }
        } catch (e) {
            msg = msg || '';
        }
        if (typeof toastr !== 'undefined' && msg) {
            toastr.error(msg);
        } else if (msg) {
            window.alert(msg);
        }
    }

    function postCreate(url, payload, onDone) {
        return $.ajax({
            url: url,
            method: 'POST',
            data: payload,
            dataType: 'json'
        }).done(function (resp) {
            if (resp && resp.message && typeof toastr !== 'undefined') {
                toastr.success(resp.message);
            }
            if (typeof onDone === 'function') {
                onDone(resp);
            }
            loadTree();
        }).fail(notifySaveError);
    }

    function loadTree() {
        var ids = selectedCategoryIds();
        var $modal = $('#modal_product_category_groups');
        var url = $modal.data('tree-url');
        var $loading = $('#fv_cat_groups_tree_loading');
        var $tree = $('#fv_cat_groups_tree');
        var $empty = $('#fv_cat_groups_tree_empty');
        var sel = liveSelection();

        $tree.empty();
        if (!ids.length) {
            $loading.addClass('d-none');
            $empty.removeClass('d-none');
            return;
        }

        $empty.addClass('d-none');
        $loading.removeClass('d-none');

        $.ajax({
            url: url,
            method: 'GET',
            data: { category_ids: ids },
            dataType: 'json'
        }).done(function (resp) {
            var tree = (resp && resp.data) ? resp.data : [];
            pendingChecks.groups.forEach(function (id) {
                if (sel.groups.indexOf(id) === -1) {
                    sel.groups.push(id);
                }
            });
            pendingChecks.subgroups.forEach(function (id) {
                if (sel.subgroups.indexOf(id) === -1) {
                    sel.subgroups.push(id);
                }
            });
            pendingChecks = { groups: [], subgroups: [] };
            renderTree(tree, sel);
        }).fail(function () {
            $empty.removeClass('d-none');
        }).always(function () {
            $loading.addClass('d-none');
        });
    }

    $(document).on('show.bs.modal', '#modal_product_category_groups', function () {
        loadTree();
    });

    $(document).on('keydown', '.fv-cat-groups-create input', function (e) {
        if (e.key !== 'Enter') {
            return;
        }
        e.preventDefault();
        $(this).closest('.fv-cat-groups-create').find('button').trigger('click');
    });

    $(document).on('click', '[data-create-group]', function () {
        var $btn = $(this);
        var $input = $btn.closest('.fv-cat-groups-create').find('input');
        var name = $.trim($input.val());
        var categoryId = parseInt($btn.attr('data-create-group'), 10);
        if (!name) {
            var required = modalText('data-msg-name-required');
            if (typeof toastr !== 'undefined' && required) {
                toastr.warning(required);
            }
            $input.focus();
            return;
        }
        $btn.prop('disabled', true);
        postCreate(modalText('data-save-group-url'), {
            category_id: categoryId,
            group_name: name
        }, function (resp) {
            var id = createdEntityId(resp, 'group_id');
            if (id > 0) {
                pendingChecks.groups.push(id);
            }
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    $(document).on('click', '[data-create-subgroup]', function () {
        var $btn = $(this);
        var $input = $btn.closest('.fv-cat-groups-create').find('input');
        var name = $.trim($input.val());
        var groupId = parseInt($btn.attr('data-create-subgroup'), 10);
        if (!name) {
            var required = modalText('data-msg-name-required');
            if (typeof toastr !== 'undefined' && required) {
                toastr.warning(required);
            }
            $input.focus();
            return;
        }
        $btn.prop('disabled', true);
        postCreate(modalText('data-save-subgroup-url'), {
            group_id: groupId,
            subgroup_name: name
        }, function (resp) {
            var id = createdEntityId(resp, 'subgroup_id');
            if (id > 0) {
                pendingChecks.subgroups.push(id);
            }
            if (groupId > 0 && pendingChecks.groups.indexOf(groupId) === -1) {
                pendingChecks.groups.push(groupId);
            }
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    $(document).on('change', '.fv-cg-check-subgroup', function () {
        if (!this.checked) {
            return;
        }
        var parentId = parseInt($(this).attr('data-parent-group-id'), 10);
        if (parentId > 0) {
            $('.fv-cg-check-group[data-group-id="' + parentId + '"]').prop('checked', true);
        }
    });

    $(document).on('click', '#fv_cat_groups_apply', function () {
        var groupMap = {};
        var subgroupItems = [];
        var coveredParents = {};

        $('.fv-cg-check-subgroup:checked').each(function () {
            var id = parseInt($(this).attr('data-subgroup-id'), 10);
            if (!(id > 0)) {
                return;
            }
            var parentId = parseInt($(this).attr('data-parent-group-id'), 10) || 0;
            var parentName = $.trim($(this).attr('data-parent-group-name') || '');
            var subName = $.trim($(this).attr('data-subgroup-name') || '');
            var label = parentName && subName
                ? (parentName + ' / ' + subName)
                : (subName || parentName || ('#' + id));
            if (parentId > 0) {
                coveredParents[parentId] = true;
            }
            subgroupItems.push({
                id: id,
                label: label,
                parentGroupId: parentId,
            });
        });

        $('.fv-cg-check-group:checked').each(function () {
            var id = parseInt($(this).attr('data-group-id'), 10);
            if (!(id > 0)) {
                return;
            }
            groupMap[id] = {
                id: id,
                label: $.trim($(this).attr('data-group-name') || '') || ('#' + id),
            };
        });

        Object.keys(coveredParents).forEach(function (parentIdStr) {
            var parentId = parseInt(parentIdStr, 10);
            if (!(parentId > 0) || groupMap[parentId]) {
                return;
            }
            var $parent = $('.fv-cg-check-group[data-group-id="' + parentId + '"]');
            groupMap[parentId] = {
                id: parentId,
                label: $.trim($parent.attr('data-group-name') || '') || ('#' + parentId),
            };
        });

        var groupItems = Object.keys(groupMap).map(function (k) {
            return groupMap[k];
        });

        writeHiddenInputs(groupItems, subgroupItems);
        $('#modal_product_category_groups').modal('hide');
    });

    // Expuesto para el modal del listado (list-taxonomy.js).
    window.fvWriteCategoryGroupHiddenInputs = writeHiddenInputs;
    window.fvUpdateCategoryGroupsSummary = updateSummary;

    $(function () {
        updateSummary();
    });
})(jQuery);
