<?php
/**
 * Bloquea visualmente las acciones (edit/delete) y el toggle enable/disable
 * cuando el listado de unidades se abre desde el módulo de productos.
 * Solo se incluye cuando Lists detecta el flag readonlyFromProducts.
 */
?>
<style>
  .units-readonly-locked .status-switch,
  .units-readonly-locked button[data-target="#modal_edit_unit"],
  .units-readonly-locked .go-delete,
  .units-readonly-locked .btn-go-delete {
    pointer-events: none !important;
    cursor: not-allowed !important;
    opacity: 1 !important;
  }

  .units-readonly-locked .custom-control-input:disabled ~ .custom-control-label::before {
    opacity: 1;
  }

  .units-readonly-locked .custom-control-label {
    cursor: not-allowed !important;
  }
</style>
<script>
  (function () {
    function lockUnitsListReadonly() {
      var tables = document.querySelectorAll('table');
      if (!tables.length) {
        return;
      }
      tables.forEach(function (table) {
        var hasUnitSwitch = table.querySelector('.status-switch') !== null;
        var hasUnitEdit = table.querySelector('button[data-target="#modal_edit_unit"]') !== null;
        var hasUnitDelete = table.querySelector('.go-delete, .btn-go-delete') !== null;
        if (!hasUnitSwitch && !hasUnitEdit && !hasUnitDelete) {
          return;
        }
        table.classList.add('units-readonly-locked');
        table.querySelectorAll('.status-switch').forEach(function (el) {
          el.disabled = true;
          el.setAttribute('aria-disabled', 'true');
          el.addEventListener('click', function (ev) { ev.preventDefault(); ev.stopImmediatePropagation(); }, true);
          el.addEventListener('change', function (ev) { ev.preventDefault(); ev.stopImmediatePropagation(); }, true);
        });
        table.querySelectorAll('button[data-target="#modal_edit_unit"], .go-delete, .btn-go-delete').forEach(function (el) {
          el.disabled = true;
          el.setAttribute('aria-disabled', 'true');
          el.removeAttribute('data-toggle');
          el.removeAttribute('data-target');
          el.addEventListener('click', function (ev) { ev.preventDefault(); ev.stopImmediatePropagation(); }, true);
        });
      });
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', lockUnitsListReadonly);
    } else {
      lockUnitsListReadonly();
    }
  })();
</script>
