<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
  /* Grillas admin (categorías, empresas, suscripciones, etc.) — alineado con login #dc2626 */
  .admin-grid-scope {
    --grid-accent: #dc2626;
    --grid-accent-dark: #b91c1c;
    --grid-accent-soft: rgba(220, 38, 38, 0.12);
    --grid-text: #1a1a1a;
    --grid-radius-lg: 16px;
    --grid-radius-md: 12px;
    --grid-radius-sm: 10px;
  }

  .admin-grid-scope .page-header {
    margin-bottom: 0.35rem;
  }
  .admin-grid-scope .page-header .page-title {
    margin: 0;
    font-size: 1.45rem;
    font-weight: 700;
    letter-spacing: -0.03em;
    color: var(--grid-text);
  }

  .admin-grid-scope .card {
    border-radius: var(--grid-radius-lg);
    border: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow:
      0 4px 24px rgba(0, 0, 0, 0.06),
      0 0 0 1px var(--grid-accent-soft);
    overflow: hidden;
    background: #fff;
  }
  .admin-grid-scope .card::before {
    content: "";
    display: block;
    height: 3px;
    background: linear-gradient(
      90deg,
      rgba(26, 26, 26, 0.28) 0%,
      rgba(26, 26, 26, 0.28) 8%,
      var(--grid-accent) 8%,
      var(--grid-accent) 92%,
      rgba(26, 26, 26, 0.28) 92%,
      rgba(26, 26, 26, 0.28) 100%
    );
  }
  .admin-grid-scope .card .card-body {
    padding: 1.35rem 1.45rem;
  }

  .admin-grid-scope .table-responsive {
    border-radius: var(--grid-radius-md);
  }
  .admin-grid-scope .table {
    margin-bottom: 0;
    border-color: rgba(0, 0, 0, 0.07);
  }
  .admin-grid-scope .table thead th {
    color: #262626 !important;
    white-space: normal !important;
    font-size: 0.72rem !important;
    font-weight: 700 !important;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    line-height: 1.35 !important;
    vertical-align: middle !important;
    background: linear-gradient(180deg, rgba(250, 250, 250, 0.98) 0%, rgba(245, 245, 247, 0.95) 100%);
    border-bottom: 2px solid rgba(220, 38, 38, 0.22) !important;
    border-top: none !important;
    opacity: 1 !important;
    visibility: visible !important;
    padding-top: 0.85rem;
    padding-bottom: 0.85rem;
  }
  .admin-grid-scope .table tbody td {
    color: var(--grid-text);
    font-size: 0.875rem;
    vertical-align: middle !important;
    border-color: rgba(0, 0, 0, 0.06) !important;
  }
  .admin-grid-scope .table tbody tr:hover td {
    background: rgba(220, 38, 38, 0.045);
  }

  .admin-grid-scope .form-group label {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #262626;
    margin-bottom: 0.45rem;
  }
  .admin-grid-scope .form-control {
    border-radius: var(--grid-radius-sm);
    border-color: rgba(0, 0, 0, 0.12);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
  }
  .admin-grid-scope .form-control:focus {
    border-color: rgba(220, 38, 38, 0.55) !important;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
    background: #fff !important;
  }

  /* CTA superior (Añadir …): una sola pieza clara, no mezclar con acciones de fila */
  .admin-grid-scope .admin-grid-page-cta {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    min-height: 2.5rem;
    padding: 0.5rem 1.15rem;
    font-size: 0.8125rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    text-decoration: none !important;
    color: #fff !important;
    -webkit-text-fill-color: #fff !important;
    background: linear-gradient(165deg, var(--grid-accent) 0%, var(--grid-accent-dark) 100%) !important;
    border: 1px solid rgba(0, 0, 0, 0.1) !important;
    border-radius: var(--grid-radius-sm);
    box-shadow:
      0 1px 0 rgba(255, 255, 255, 0.2) inset,
      0 4px 14px rgba(220, 38, 38, 0.28);
    transition: transform 0.15s ease, box-shadow 0.2s ease, filter 0.15s ease;
  }
  .admin-grid-scope .admin-grid-page-cta:hover {
    color: #fff !important;
    filter: brightness(1.05);
    box-shadow:
      0 1px 0 rgba(255, 255, 255, 0.22) inset,
      0 6px 20px rgba(220, 38, 38, 0.35);
  }
  .admin-grid-scope .admin-grid-page-cta:active {
    transform: translateY(1px);
  }
  .admin-grid-scope .admin-grid-page-cta .mdi {
    font-size: 1.125rem;
    line-height: 1;
    opacity: 0.95;
  }

  /* Grupo de acciones en tabla: iconos alineados, mismo tamaño, ritmo visual estable */
  .admin-grid-scope .admin-grid-actions {
    display: inline-flex;
    flex-wrap: nowrap;
    align-items: center;
    justify-content: flex-start;
    gap: 0.4rem;
    vertical-align: middle;
  }

  .admin-grid-scope .admin-grid-actions .btn.admin-grid-btn {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    width: 2.375rem;
    height: 2.375rem;
    min-width: 2.375rem;
    min-height: 2.375rem;
    padding: 0 !important;
    margin: 0;
    border-radius: var(--grid-radius-sm);
    border: 1px solid rgba(0, 0, 0, 0.1) !important;
    background: #fff !important;
    color: #3f3f46 !important;
    -webkit-text-fill-color: currentColor !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    font-size: 0 !important;
    line-height: 0 !important;
    text-decoration: none !important;
    transition:
      background 0.18s ease,
      border-color 0.18s ease,
      color 0.18s ease,
      box-shadow 0.18s ease,
      transform 0.12s ease;
  }
  .admin-grid-scope .admin-grid-actions .btn.admin-grid-btn .mdi {
    font-size: 1.125rem;
    line-height: 1;
  }
  .admin-grid-scope .admin-grid-actions .btn.admin-grid-btn:focus {
    outline: none;
    box-shadow: 0 0 0 3px var(--grid-accent-soft);
  }
  .admin-grid-scope .admin-grid-actions .btn.admin-grid-btn:active {
    transform: scale(0.96);
  }

  .admin-grid-scope .admin-grid-actions .btn.admin-grid-btn--view {
    background: #fafafa !important;
    border-color: #e4e4e7 !important;
    color: #52525b !important;
  }
  .admin-grid-scope .admin-grid-actions .btn.admin-grid-btn--view:hover {
    background: #f4f4f5 !important;
    border-color: #d4d4d8 !important;
    color: #27272a !important;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
  }

  .admin-grid-scope .admin-grid-actions .btn.admin-grid-btn--edit {
    border-color: rgba(220, 38, 38, 0.35) !important;
    color: var(--grid-accent) !important;
    background: #fff !important;
  }
  .admin-grid-scope .admin-grid-actions .btn.admin-grid-btn--edit:hover {
    background: var(--grid-accent-soft) !important;
    border-color: var(--grid-accent) !important;
    color: var(--grid-accent-dark) !important;
  }

  .admin-grid-scope .admin-grid-actions .btn.admin-grid-btn--delete {
    border-color: rgba(220, 38, 38, 0.28) !important;
    color: var(--grid-accent) !important;
    background: #fff !important;
  }
  .admin-grid-scope .admin-grid-actions .btn.admin-grid-btn--delete:hover {
    background: rgba(254, 242, 242, 0.95) !important;
    border-color: rgba(220, 38, 38, 0.55) !important;
    color: var(--grid-accent-dark) !important;
    box-shadow: 0 2px 8px rgba(220, 38, 38, 0.12);
  }

  .admin-grid-scope .table thead th:last-child {
    text-align: center;
  }
  .admin-grid-scope .table tbody td .admin-grid-actions {
    padding: 0.125rem 0;
  }
  .admin-grid-scope .table tbody td:last-child {
    text-align: center;
    white-space: nowrap;
  }
  .admin-grid-scope .table tbody td:last-child .admin-grid-actions {
    justify-content: center;
    width: 100%;
  }

  .admin-grid-scope .form-group:last-of-type {
    margin-bottom: 0;
  }

  .admin-grid-scope .admin-grid-scope-badge-enable {
    background: #16a34a !important;
    color: #fff !important;
    font-weight: 600;
    font-size: 0.72rem;
    padding: 0.35em 0.65em;
  }
  .admin-grid-scope .admin-grid-scope-badge-disable {
    background: #71717a !important;
    color: #fff !important;
    font-weight: 600;
    font-size: 0.72rem;
    padding: 0.35em 0.65em;
  }

  /*
   * DataTables + Bootstrap dom: las .row usan márgenes negativos; con overflow:hidden del .card
   * la fila de búsqueda/paginación queda recortada y parece que DataTables “no carga”.
   */
  .admin-grid-scope .card .dataTables_wrapper.dt-bootstrap4 > .row {
    margin-left: 0;
    margin-right: 0;
  }
</style>
