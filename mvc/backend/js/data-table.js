(function($) {
  'use strict';

  function wireDataTableControls($table, searchPlaceholder) {
    var $wrap = $table.closest('.dataTables_wrapper');
    if (!$wrap.length) {
      return;
    }
    var search_input = $wrap.find('div[id$=_filter] input');
    search_input.removeClass('form-control-sm');
    if (searchPlaceholder) {
      search_input.attr('placeholder', searchPlaceholder);
    }
    var length_sel = $wrap.find('div[id$=_length] select');
    length_sel.removeClass('form-control-sm');
  }

  $(function() {
    if (!$.fn.DataTable) {
      return;
    }

    $('#order-listing').each(function() {
      var el = this;
      if ($.fn.DataTable.isDataTable(el)) {
        return;
      }
      var $el = $(el);
      $el.DataTable({
        aLengthMenu: [
          [5, 10, 15, -1],
          [5, 10, 15, 'All']
        ],
        iDisplayLength: 10,
        language: {
          search: ''
        }
      });
      wireDataTableControls($el, 'Search');
    });

    $('#cms-data-table').each(function() {
      var el = this;
      if ($.fn.DataTable.isDataTable(el)) {
        return;
      }
      var L = window.__cmsDataTableLang || {};
      var $el = $(el);
      $el.DataTable({
        aLengthMenu: [
          [5, 10, 15, -1],
          [5, 10, 15, L.all || 'All']
        ],
        iDisplayLength: 10,
        language: {
          search: '',
          lengthMenu: L.lengthMenu || 'Show _MENU_ entries',
          zeroRecords: L.zeroRecords || 'No matching records found.',
          info: L.info || 'Showing _START_ to _END_ of _TOTAL_ entries',
          infoEmpty: L.infoEmpty || 'Showing 0 to 0 of 0 entries',
          infoFiltered: L.infoFiltered || '(filtered from _MAX_ total entries)',
          paginate: {
            previous: L.previous || 'Previous',
            next: L.next || 'Next'
          }
        }
      });
      wireDataTableControls($el, L.searchPlaceholder || 'Search');
    });
  });
})(jQuery);
