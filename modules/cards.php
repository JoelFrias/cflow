<?php
// modules/cards.php - Módulo para gestión de tarjetas de crédito y débito

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Tarjetas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<style>
/* =============================================
   VARIABLES & BASE
   ============================================= */
:root {
    --card-radius: 16px;
    --soft-border: 1px solid #e8e8e8;
    --muted: #6c757d;
    --danger-light: #fff1f0;
    --danger-mid: #e24b4a;
    --success-light: #f0faf4;
    --success-mid: #1D9E75;
    --warn-light: #fffbeb;
    --warn-mid: #BA7517;
    --info-light: #eef4fd;
    --info-mid: #185FA5;

    /* History modal tokens (mirrors transactions module) */
    --h-inc:      #1D9E75;
    --h-exp:      #D85A30;
    --h-inc-bg:   #eaf3de;
    --h-exp-bg:   #faece7;
    --h-inc-text: #3B6D11;
    --h-exp-text: #993C1D;
    --h-border:   #e8e8e8;
    --h-radius:   10px;
}

body { background: #f5f5f5; }

/* =============================================
   PAGE HEADER
   ============================================= */
.page-header {
    background: #fff;
    border-bottom: var(--soft-border);
    padding: 14px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.page-header h5 { margin: 0; font-size: 16px; font-weight: 600; }

/* =============================================
   SUMMARY STRIP
   ============================================= */
.summary-strip {
    background: #fff;
    border-bottom: var(--soft-border);
    padding: 10px 16px;
    display: flex;
    gap: 20px;
    overflow-x: auto;
    scrollbar-width: none;
    white-space: nowrap;
}
.summary-strip::-webkit-scrollbar { display: none; }
.summary-item { display: flex; flex-direction: column; }
.summary-item .s-label { font-size: 10px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; }
.summary-item .s-value { font-size: 14px; font-weight: 600; color: #212529; }
.summary-item .s-value.is-danger { color: var(--danger-mid); }
.summary-divider { width: 1px; background: #e8e8e8; flex-shrink: 0; align-self: stretch; }

/* =============================================
   SECTION LABEL
   ============================================= */
.section-label {
    font-size: 11px;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 14px 16px 6px;
}

/* =============================================
   SHARED CARD INTERNALS
   ============================================= */
.card-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}
.card-type-badge {
    font-size: 10px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 20px;
    display: inline-block;
    letter-spacing: 0.03em;
}
.badge-credit { background: var(--danger-light); color: var(--danger-mid); }
.badge-debit  { background: var(--info-light);   color: var(--info-mid); }

/* Edit button */
.card-edit-btn {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 1px solid #e0e0e0;
    background: #f5f5f5;
    color: #9ca3af;
    font-size: 11px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.18s;
    flex-shrink: 0;
}
.card-edit-btn:hover {
    background: var(--info-light);
    border-color: var(--info-mid);
    color: var(--info-mid);
}

.card-name { font-size: 15px; font-weight: 600; margin-bottom: 12px; color: #212529; }

.balances-row { display: flex; gap: 10px; margin-bottom: 12px; }
.balance-box {
    flex: 1;
    background: #f8f8f8;
    border-radius: 10px;
    padding: 9px 11px;
}
.bal-label { font-size: 10px; color: var(--muted); margin-bottom: 2px; text-transform: uppercase; letter-spacing: 0.04em; }
.bal-amount { font-size: 15px; font-weight: 700; }
.bal-zero   { color: #aaa; }
.bal-debt   { color: var(--danger-mid); }

.mini-progress {
    height: 3px;
    background: #e8e8e8;
    border-radius: 2px;
    overflow: hidden;
    margin-top: 5px;
}
.mini-bar { height: 100%; border-radius: 2px; }
.bar-ok     { background: var(--success-mid); }
.bar-warn   { background: var(--warn-mid); }
.bar-danger { background: var(--danger-mid); }

.card-limit-hint { font-size: 10px; color: var(--muted); margin-top: 2px; }

.card-actions-row { display: flex; gap: 7px; margin-top: 2px; }
.card-action-btn {
    flex: 1;
    font-size: 12px;
    font-weight: 500;
    padding: 7px 4px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    background: #fff;
    color: #212529;
    cursor: pointer;
    text-align: center;
    transition: background 0.15s;
    white-space: nowrap;
}
.card-action-btn:hover { background: #f5f5f5; }
.card-action-btn.btn-expense { background: var(--info-light); border-color: transparent; color: var(--info-mid); }
.card-action-btn.btn-pay    { background: var(--success-light); border-color: transparent; color: var(--success-mid); }

.card-limit-alert {
    font-size: 11px;
    padding: 5px 9px;
    border-radius: 7px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 5px;
}
.alert-over { background: var(--danger-light); color: var(--danger-mid); }
.alert-near { background: var(--warn-light);   color: var(--warn-mid); }

/* =============================================
   MOBILE: SLIDER (max 767px)
   ============================================= */
@media (max-width: 767px) {
    .cards-desktop { display: none !important; }

    .cards-slider-outer { padding: 0 0 0 16px; }
    .cards-slider-wrap {
        display: flex;
        gap: 12px;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        padding-bottom: 4px;
        padding-right: 16px;
    }
    .cards-slider-wrap::-webkit-scrollbar { display: none; }

    .card-slide {
        flex: 0 0 calc(100vw - 48px);
        max-width: 340px;
        scroll-snap-align: start;
        background: #fff;
        border: var(--soft-border);
        border-radius: var(--card-radius);
        padding: 16px;
        transition: border-color 0.2s;
    }
    .card-slide.is-active { border-color: #185FA5; border-width: 1.5px; }

    .dot-indicators {
        display: flex;
        justify-content: center;
        gap: 6px;
        padding: 10px 0 4px;
    }
    .dot {
        width: 6px; height: 6px;
        border-radius: 50%;
        background: #d0d0d0;
        transition: background 0.2s, transform 0.2s;
    }
    .dot.active { background: #185FA5; transform: scale(1.35); }

    .card-detail-panel {
        margin: 12px 16px 24px;
        background: #fff;
        border: var(--soft-border);
        border-radius: var(--card-radius);
        overflow: hidden;
    }
    .detail-tabs { display: flex; border-bottom: var(--soft-border); }
    .detail-tab {
        flex: 1;
        padding: 10px 0;
        font-size: 13px;
        text-align: center;
        color: var(--muted);
        cursor: pointer;
        border-bottom: 2px solid transparent;
        background: none;
        border-top: none;
        border-left: none;
        border-right: none;
    }
    .detail-tab.active { color: #212529; font-weight: 600; border-bottom-color: #185FA5; }
    .detail-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 14px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 13px;
    }
    .detail-row:last-child { border-bottom: none; }
    .detail-row .dl { color: var(--muted); }
    .detail-row .dr { font-weight: 500; }
    .tx-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 14px;
        border-bottom: 1px solid #f0f0f0;
    }
    .tx-row:last-child { border-bottom: none; }
    .tx-meta { font-size: 13px; font-weight: 500; }
    .tx-date { font-size: 11px; color: var(--muted); margin-top: 1px; }
    .tx-amount { font-size: 13px; font-weight: 600; text-align: right; }
    .tx-badge { font-size: 10px; padding: 1px 7px; border-radius: 20px; }
    .tx-gasto { background: var(--danger-light); color: var(--danger-mid); }
    .tx-pago  { background: var(--success-light); color: var(--success-mid); }
}

/* =============================================
   DESKTOP: GRID (min 768px)
   ============================================= */
@media (min-width: 768px) {
    .cards-slider-outer { display: none !important; }
    .dot-indicators     { display: none !important; }
    .card-detail-panel  { display: none !important; }
    .section-label      { display: none; }

    .cards-desktop {
        display: grid !important;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 16px;
    }
    .card-desktop-item {
        background: #fff;
        border: var(--soft-border);
        border-radius: var(--card-radius);
        padding: 18px;
    }
}

/* =============================================
   SKELETON / EMPTY
   ============================================= */
.skeleton-card {
    background: #fff;
    border: var(--soft-border);
    border-radius: var(--card-radius);
    padding: 16px;
    height: 200px;
    animation: pulse 1.4s infinite;
}
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }

.empty-state { text-align: center; padding: 48px 16px; color: var(--muted); }
.empty-state i { font-size: 2.5rem; margin-bottom: 12px; opacity: 0.3; display: block; }
.empty-state p { font-size: 14px; margin-bottom: 16px; }

/* =============================================
   HISTORY MODAL — REDESIGN (mirrors transactions module)
   ============================================= */

/* Filter toggle header */
.hist-filter-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 20px;
    background: #fafafa;
    border-bottom: 1px solid var(--h-border);
    cursor: pointer;
    user-select: none;
    transition: background 0.15s;
}
.hist-filter-toggle:hover { background: #f3f4f6; }
.hist-filter-toggle-left {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #6c757d;
}
.hist-filter-chevron { transition: transform 0.2s; color: #9ca3af; }
.hist-filter-chevron.open { transform: rotate(180deg); }
.hist-filters-active-badge {
    background: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
    font-size: 10px;
    padding: 1px 7px;
    border-radius: 20px;
}

/* Filter panel */
.hist-filter-panel {
    padding: 14px 20px;
    background: #fff;
    border-bottom: 1px solid var(--h-border);
}
.hist-filter-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 10px;
    margin-bottom: 10px;
}
@media (max-width: 576px) {
    .hist-filter-grid { grid-template-columns: 1fr 1fr; }
}
.hist-filter-label {
    display: block;
    font-size: 10px;
    color: #9ca3af;
    margin-bottom: 3px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.hist-filter-input {
    width: 100%;
    font-size: 13px;
    padding: 7px 10px;
    border: 1px solid var(--h-border);
    border-radius: 8px;
    background: #fafafa;
    color: #374151;
    outline: none;
    transition: border-color 0.15s;
}
.hist-filter-input:focus { border-color: #a5b4fc; background: #fff; }
.hist-btn-apply {
    padding: 7px 18px;
    background: #374151;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    cursor: pointer;
    transition: background 0.15s;
}
.hist-btn-apply:hover { background: #1f2937; }
.hist-btn-clear {
    padding: 7px 14px;
    background: #f3f4f6;
    color: #6b7280;
    border: 1px solid var(--h-border);
    border-radius: 8px;
    font-size: 13px;
    cursor: pointer;
    transition: background 0.15s;
}
.hist-btn-clear:hover { background: #e5e7eb; }

/* Controls bar (records info) */
.hist-controls-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 20px;
    background: #fff;
    border-bottom: 1px solid var(--h-border);
}
.hist-records-info { font-size: 12px; color: #9ca3af; }

/* Loading / empty */
.hist-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 40px 20px;
    color: #9ca3af;
    font-size: 14px;
}
.hist-empty {
    text-align: center;
    padding: 50px 20px;
    color: #9ca3af;
    font-size: 14px;
}
.hist-empty-icon { font-size: 30px; margin-bottom: 8px; opacity: 0.4; display: block; }

/* ── Mobile list (inside history modal) ── */
.hist-mobile-list {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 8px 12px;
}
.hist-item {
    background: #fff;
    border: 1px solid var(--h-border);
    border-radius: var(--h-radius);
    overflow: hidden;
}
.hist-item-inner {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    min-height: 64px;
}
.hist-dot-icon {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.hdot-exp { background: var(--h-exp-bg); color: var(--h-exp-text); }
.hdot-pay { background: var(--success-light); color: var(--success-mid); }
.hist-main { flex: 1; min-width: 0; }
.hist-desc {
    font-size: 13px;
    font-weight: 500;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.hist-cat  { font-size: 11px; color: #9ca3af; margin-top: 1px; }
.hist-date { font-size: 11px; color: #b0b7c3; }
.hist-amount-col { text-align: right; flex-shrink: 0; }
.hist-amt-main { font-size: 14px; font-weight: 600; }
.hamt-exp { color: var(--h-exp); }
.hamt-pay { color: var(--success-mid); }
.hist-amt-sub { font-size: 10px; color: #9ca3af; margin-top: 1px; }
.hist-type-badge {
    font-size: 10px;
    padding: 2px 8px;
    border-radius: 20px;
    display: inline-block;
}
.hbadge-exp { background: var(--h-exp-bg);   color: var(--h-exp-text); }
.hbadge-pay { background: var(--success-light); color: var(--success-mid); }

/* ── Desktop table (inside history modal) ── */
.hist-desktop-wrap { display: none; }
.hist-desktop-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(0,0,0,.04), 0 4px 16px rgba(0,0,0,.05);
    margin: 12px 16px;
}
.hist-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.hist-table thead tr {
    background: #f9fafb;
    border-bottom: 1px solid #e9ecef;
}
.hist-table thead th {
    padding: 11px 16px;
    text-align: left;
    font-size: 10.5px;
    font-weight: 600;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    white-space: nowrap;
}
.hist-table thead th:first-child { padding-left: 20px; }
.hist-table thead th:last-child  { padding-right: 20px; text-align: center; }
.hist-table tbody tr {
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.1s;
}
.hist-table tbody tr:last-child { border-bottom: none; }
.hist-table tbody tr:hover { background: #fafbfc; }
.hist-table td {
    padding: 12px 16px;
    color: #374151;
    vertical-align: middle;
}
.hist-table td:first-child { padding-left: 20px; }
.hist-table td:last-child  { padding-right: 20px; text-align: center; }
.hist-tbl-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 500;
}
.hist-tbl-amt { font-weight: 600; white-space: nowrap; }
.hist-tbl-desc { color: #6b7280; font-size: 12px; max-width: 200px; word-break: break-word; }
.hist-tbl-muted { color: #9ca3af; font-size: 12px; }
.btn-del-hist {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: 1px solid #fecaca;
    background: #fef2f2;
    color: #dc2626;
    font-size: 12px;
    cursor: pointer;
    transition: background 0.15s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.btn-del-hist:hover { background: #fee2e2; }
.btn-locked-hist {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    color: #d1d5db;
    font-size: 12px;
    cursor: not-allowed;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* ── Responsive for history modal ── */
@media (min-width: 768px) {
    .hist-mobile-list { display: none !important; }
    .hist-desktop-wrap { display: block !important; }
}
@media (max-width: 767px) {
    .hist-desktop-wrap { display: none !important; }
    .hist-mobile-list  { display: flex !important; }
}

/* Pagination (history modal) */
.hist-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    flex-wrap: wrap;
    padding: 12px 16px;
}
.hpag-btn {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: 1px solid var(--h-border);
    background: #fff;
    color: #6b7280;
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.12s;
    text-decoration: none;
}
.hpag-btn:hover { background: #f3f4f6; color: #1f2937; }
.hpag-btn.active { background: #374151; color: #fff; border-color: #374151; }
.hpag-btn.disabled { opacity: 0.4; pointer-events: none; cursor: default; }
</style>
</head>
<body>

<!-- ============================================ -->
<!-- PAGE HEADER                                 -->
<!-- ============================================ -->
<div class="page-header">
    <h5><i class="fas fa-credit-card me-2 text-primary"></i>Mis Tarjetas</h5>
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createCardModal">
        <i class="fas fa-plus"></i> Nueva
    </button>
</div>

<!-- ============================================ -->
<!-- SUMMARY STRIP                               -->
<!-- ============================================ -->
<div class="summary-strip">
    <div class="summary-item">
        <span class="s-label">Tarjetas</span>
        <span class="s-value" id="sum-count">—</span>
    </div>
    <div class="summary-divider"></div>
    <div class="summary-item">
        <span class="s-label">Deuda DOP</span>
        <span class="s-value is-danger" id="sum-dop">—</span>
    </div>
    <div class="summary-divider"></div>
    <div class="summary-item">
        <span class="s-label">Deuda USD</span>
        <span class="s-value is-danger" id="sum-usd">—</span>
    </div>
    <div class="summary-divider"></div>
    <div class="summary-item">
        <span class="s-label">Crédito / Débito</span>
        <span class="s-value" id="sum-types">—</span>
    </div>
</div>

<!-- Alertas globales -->
<div id="limit-alerts" class="px-3 pt-2"></div>

<!-- ============================================ -->
<!-- MOBILE: SLIDER                              -->
<!-- ============================================ -->
<div class="section-label">Selecciona una tarjeta</div>

<div class="cards-slider-outer" id="mobile-slider-outer">
    <div class="cards-slider-wrap" id="cards-slider">
        <div class="skeleton-card" style="flex:0 0 calc(100vw - 48px);max-width:340px;"></div>
        <div class="skeleton-card" style="flex:0 0 calc(100vw - 48px);max-width:340px;"></div>
    </div>
</div>

<div class="dot-indicators" id="dot-indicators"></div>

<!-- Panel detalle / historial (solo móvil) -->
<div class="card-detail-panel" id="card-detail-panel" style="display:none;">
    <div class="detail-tabs">
        <button class="detail-tab active" onclick="switchTab(event,'tab-limits')">Límites</button>
        <button class="detail-tab" onclick="switchTab(event,'tab-history')">Últimos movimientos</button>
    </div>
    <div id="tab-limits">
        <div class="detail-row"><span class="dl">Selecciona una tarjeta</span></div>
    </div>
    <div id="tab-history" style="display:none;">
        <div class="detail-row"><span class="dl">Selecciona una tarjeta</span></div>
    </div>
</div>

<!-- ============================================ -->
<!-- DESKTOP: GRID                               -->
<!-- ============================================ -->
<div class="container-fluid py-3 d-none d-md-block">
    <div class="cards-desktop" id="cards-desktop">
        <div class="skeleton-card"></div>
        <div class="skeleton-card"></div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL: CREAR TARJETA                        -->
<!-- ============================================ -->
<div class="modal fade" id="createCardModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Nueva Tarjeta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nombre de la tarjeta</label>
                    <input type="text" id="card-name" class="form-control" placeholder="Ej: Visa Platinum" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipo</label>
                    <select id="card-type" class="form-control" required>
                        <option value="debit_card">Tarjeta de Débito</option>
                        <option value="credit_card">Tarjeta de Crédito</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Límite (USD)</label>
                        <input type="number" step="0.01" id="card-limit-usd" class="form-control" placeholder="Opcional">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Límite (DOP)</label>
                        <input type="number" step="0.01" id="card-limit-dop" class="form-control" placeholder="Opcional">
                    </div>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <small>La tarjeta puede tener gastos en USD y DOP, con balances separados por moneda.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-create-card">
                    <span id="btn-create-card-text">Crear Tarjeta</span>
                    <span id="btn-create-card-spinner" class="spinner-border spinner-border-sm d-none"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL: EDITAR TARJETA  ← NEW              -->
<!-- ============================================ -->
<div class="modal fade" id="editCardModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-pencil-alt me-2"></i>Editar Tarjeta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-card-id">
                <div class="mb-3">
                    <label class="form-label">Nombre de la tarjeta</label>
                    <input type="text" id="edit-card-name" class="form-control" placeholder="Ej: Visa Platinum" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipo</label>
                    <select id="edit-card-type" class="form-control" required>
                        <option value="debit_card">Tarjeta de Débito</option>
                        <option value="credit_card">Tarjeta de Crédito</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Límite (USD)</label>
                        <input type="number" step="0.01" id="edit-card-limit-usd" class="form-control" placeholder="Opcional (dejar vacío para sin límite)">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Límite (DOP)</label>
                        <input type="number" step="0.01" id="edit-card-limit-dop" class="form-control" placeholder="Opcional (dejar vacío para sin límite)">
                    </div>
                </div>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i>
                    <small>Los balances existentes no se modifican al editar la tarjeta.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-save-edit-card">
                    <span id="btn-edit-card-text">Guardar cambios</span>
                    <span id="btn-edit-card-spinner" class="spinner-border spinner-border-sm d-none"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL: REGISTRAR GASTO                      -->
<!-- ============================================ -->
<div class="modal fade" id="expenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-shopping-cart"></i> Registrar Gasto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="expense-card-id">
                <div class="mb-3">
                    <label class="form-label">Moneda del gasto</label>
                    <select id="expense-currency" class="form-control" required>
                        <option value="DOP">DOP - Peso Dominicano</option>
                        <option value="USD">USD - Dólar Americano</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Monto</label>
                    <input type="number" step="0.01" id="expense-amount" class="form-control" placeholder="0.00" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Categoría</label>
                    <select id="expense-category" class="form-control">
                        <option value="">Seleccionar categoría</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Fecha</label>
                    <input type="date" id="expense-date" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea id="expense-description" class="form-control" rows="2" placeholder="Opcional"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn-add-expense">
                    <span id="btn-expense-text">Registrar Gasto</span>
                    <span id="btn-expense-spinner" class="spinner-border spinner-border-sm d-none"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL: PAGAR TARJETA                        -->
<!-- ============================================ -->
<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-money-bill-wave"></i> Pagar Tarjeta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="pay-card-id">
                <div class="alert alert-info">
                    <small><i class="fas fa-info-circle"></i> Las cuentas de origen están en DOP. Para pagar USD, especifica cuánto DOP deseas debitar.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Cuenta de origen (DOP)</label>
                    <select id="pay-account" class="form-control" required>
                        <option value="">Seleccionar cuenta</option>
                    </select>
                    <small id="no-dop-accounts-msg" class="text-danger d-none">
                        No tienes cuentas en DOP. <a href="?module=accounts">Crea una cuenta primero</a>.
                    </small>
                </div>
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="enable-dop-payment">
                            <label class="form-check-label fw-bold" for="enable-dop-payment">Pagar en DOP</label>
                        </div>
                    </div>
                    <div class="card-body d-none" id="dop-payment-section">
                        <label>Monto a pagar en DOP</label>
                        <div class="input-group">
                            <span class="input-group-text">RD$</span>
                            <input type="number" step="0.01" id="pay-dop" class="form-control" placeholder="0.00" value="0">
                        </div>
                    </div>
                </div>
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="enable-usd-payment">
                            <label class="form-check-label fw-bold" for="enable-usd-payment">Pagar en USD</label>
                        </div>
                    </div>
                    <div class="card-body d-none" id="usd-payment-section">
                        <div class="mb-2">
                            <label>Monto a pagar en USD</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" id="pay-usd" class="form-control" placeholder="0.00" value="0">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label>Monto en DOP a debitar</label>
                            <div class="input-group">
                                <span class="input-group-text">RD$</span>
                                <input type="number" step="0.01" id="dop-for-usd" class="form-control" placeholder="0.00" value="0">
                            </div>
                            <small class="text-muted" id="usd-rate-hint">Tasa actual: 1 USD = RD$ ...</small>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Fecha de pago</label>
                    <input type="date" id="pay-date" class="form-control" required>
                </div>
                <div class="alert alert-secondary" id="total-preview">
                    <strong>Total a descontar:</strong> RD$ 0.00
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn-pay-card">
                    <span id="btn-pay-text">Confirmar Pago</span>
                    <span id="btn-pay-spinner" class="spinner-border spinner-border-sm d-none"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL: HISTORIAL COMPLETO  ← REDESIGNED    -->
<!-- ============================================ -->
<div class="modal fade" id="transactionsHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border:none;border-radius:14px;overflow:hidden;">
            <div class="modal-header" style="background:#374151;border:none;border-radius:0;padding:14px 20px;">
                <h5 class="modal-title" style="font-size:15px;font-weight:500;color:#fff;">
                    <i class="fas fa-history me-2" style="opacity:.8"></i>
                    Historial — <span id="history-card-name" style="font-weight:700;"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Body sin padding propio (cada sección maneja el suyo) -->
            <div class="modal-body p-0" style="background:#f6f7f9;">
                <input type="hidden" id="history-card-id">

                <!-- Filter toggle -->
                <div class="hist-filter-toggle" id="histFilterToggleBtn">
                    <div class="hist-filter-toggle-left">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 4h12M4 8h8M6 12h4"/></svg>
                        <span>Filtros</span>
                        <span class="hist-filters-active-badge d-none" id="hist-filters-badge">activos</span>
                    </div>
                    <svg class="hist-filter-chevron" id="histFilterChevron" width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6l4 4 4-4"/></svg>
                </div>

                <!-- Filter panel (collapsible) -->
                <div class="hist-filter-panel" id="histFiltersPanel" style="display:none;">
                    <div class="hist-filter-grid">
                        <div>
                            <label class="hist-filter-label">Fecha desde</label>
                            <input type="date" id="filter-date-from" class="hist-filter-input">
                        </div>
                        <div>
                            <label class="hist-filter-label">Fecha hasta</label>
                            <input type="date" id="filter-date-to" class="hist-filter-input">
                        </div>
                        <div>
                            <label class="hist-filter-label">Tipo</label>
                            <select id="filter-type" class="hist-filter-input">
                                <option value="">Todos</option>
                                <option value="expense">Gastos</option>
                                <option value="payment">Pagos</option>
                            </select>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;margin-top:2px;">
                        <button class="hist-btn-apply" id="btn-apply-filters">
                            <i class="fas fa-filter me-1"></i> Aplicar
                        </button>
                        <button class="hist-btn-clear" id="btn-clear-filters">Limpiar</button>
                    </div>
                </div>

                <!-- Records info bar -->
                <div class="hist-controls-bar">
                    <span class="hist-records-info" id="hist-records-info"></span>
                </div>

                <!-- Content area: mobile list + desktop table rendered here -->
                <div id="hist-content-area">
                    <div class="hist-loading">
                        <div class="spinner-border spinner-border-sm text-secondary"></div>
                        <span>Cargando transacciones…</span>
                    </div>
                </div>

                <!-- Pagination -->
                <div id="history-pagination" class="hist-pagination"></div>
            </div>

            <div class="modal-footer" style="padding:12px 20px;border-top:1px solid #e8e8e8;background:#fff;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- SCRIPTS                                     -->
<!-- ============================================ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const CARDS_AJAX_URL = 'ajax/cards.php';

// Estado global
let _usdRate       = 62.50;
let _categories    = [];
let _dopAccounts   = [];
let _allCards      = [];
let _activeCardIdx = 0;

// Historial
let _historyTransactions = [];
let _historyPage         = 1;
const HISTORY_PAGE_SIZE  = 5;

/* ============================================
   HELPERS
   ============================================ */
function showError(message, fullMessage = null) {
    console.error('[Cards]', fullMessage || message);
    Swal.fire({ toast:true, position:'top-start', icon:'error', title:message,
        showConfirmButton:false, timer:4500, timerProgressBar:true });
}
function showSuccess(message) {
    Swal.fire({ toast:true, position:'top-start', icon:'success', title:message,
        showConfirmButton:false, timer:3000, timerProgressBar:true });
}
function fmt(n) {
    return parseFloat(n).toLocaleString('es-DO', { minimumFractionDigits:2 });
}
function escapeHtml(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
function setLoading(btnId, textId, spinnerId, loading) {
    const btn = document.getElementById(btnId);
    const textEl = document.getElementById(textId);
    const spinnerEl = document.getElementById(spinnerId);
    if (!btn || !textEl || !spinnerEl) return;
    btn.disabled = loading;
    if (loading) { textEl.dataset.original = textEl.textContent; textEl.textContent = 'Procesando...'; }
    else { textEl.textContent = textEl.dataset.original || textEl.textContent; }
    spinnerEl.classList.toggle('d-none', !loading);
}
function localDateStr() {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}`;
}
function ajaxPost(body) {
    return fetch(CARDS_AJAX_URL, {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams(body).toString(),
    }).then(r => r.json());
}

/* ============================================
   HTML INTERNO COMPARTIDO (slider + grid)
   ============================================ */
function buildCardInnerHTML(card) {
    const pctUsd = card.credit_limit_usd ? Math.min(100,(card.balance_usd/card.credit_limit_usd)*100) : 0;
    const pctDop = card.credit_limit_dop ? Math.min(100,(card.balance_dop/card.credit_limit_dop)*100) : 0;
    const classUsd = pctUsd>90?'bar-danger':pctUsd>70?'bar-warn':'bar-ok';
    const classDop = pctDop>90?'bar-danger':pctDop>70?'bar-warn':'bar-ok';
    const typeBadge = card.type==='credit_card'?'badge-credit':'badge-debit';
    const typeLabel = card.type==='credit_card'?'Crédito':'Débito';

    let alertHtml = '';
    if (card.credit_limit_usd && pctUsd>=100)
        alertHtml += `<div class="card-limit-alert alert-over"><i class="fas fa-exclamation-circle"></i> Límite USD excedido</div>`;
    else if (card.credit_limit_usd && pctUsd>=80)
        alertHtml += `<div class="card-limit-alert alert-near"><i class="fas fa-exclamation-triangle"></i> Cerca del límite USD (${Math.round(pctUsd)}%)</div>`;
    if (card.credit_limit_dop && pctDop>=100)
        alertHtml += `<div class="card-limit-alert alert-over"><i class="fas fa-exclamation-circle"></i> Límite DOP excedido</div>`;
    else if (card.credit_limit_dop && pctDop>=80)
        alertHtml += `<div class="card-limit-alert alert-near"><i class="fas fa-exclamation-triangle"></i> Cerca del límite DOP (${Math.round(pctDop)}%)</div>`;

    const usdExtra = card.credit_limit_usd
        ? `<div class="mini-progress"><div class="mini-bar ${classUsd}" style="width:${pctUsd}%"></div></div>
           <div class="card-limit-hint">Lím: $${fmt(card.credit_limit_usd)} &middot; Disp: $${fmt(card.credit_limit_usd-card.balance_usd)}</div>`
        : '';
    const dopExtra = card.credit_limit_dop
        ? `<div class="mini-progress"><div class="mini-bar ${classDop}" style="width:${pctDop}%"></div></div>
           <div class="card-limit-hint">Lím: RD$${fmt(card.credit_limit_dop)} &middot; Disp: RD$${fmt(card.credit_limit_dop-card.balance_dop)}</div>`
        : '';

    const canDelete = parseFloat(card.balance_usd)===0 && parseFloat(card.balance_dop)===0;
    const deleteBtn = canDelete
        ? `<button class="card-action-btn" style="flex:0 0 36px;" title="Eliminar tarjeta"
               onclick="confirmDelete(${card.id},'${escapeHtml(card.name)}')">
               <i class="fas fa-trash" style="font-size:11px;color:#e24b4a;"></i>
           </button>`
        : '';

    return `
        ${alertHtml}
        <!-- Card header row: badge + edit button -->
        <div class="card-header-row">
            <span class="card-type-badge ${typeBadge}">${typeLabel}</span>
            <button class="card-edit-btn" onclick="openEditModal(${card.id})" title="Editar tarjeta">
                <i class="fas fa-pencil-alt"></i>
            </button>
        </div>
        <div class="card-name">${escapeHtml(card.name)}</div>
        <div class="balances-row">
            <div class="balance-box">
                <div class="bal-label">USD</div>
                <div class="bal-amount ${parseFloat(card.balance_usd)>0?'bal-debt':'bal-zero'}">
                    $${fmt(card.balance_usd)}
                </div>
                ${usdExtra}
            </div>
            <div class="balance-box">
                <div class="bal-label">DOP</div>
                <div class="bal-amount ${parseFloat(card.balance_dop)>0?'bal-debt':'bal-zero'}">
                    RD$${fmt(card.balance_dop)}
                </div>
                ${dopExtra}
            </div>
        </div>
        <div class="card-actions-row">
            <button class="card-action-btn btn-expense" onclick="openExpenseModal(${card.id})">
                <i class="fas fa-plus" style="font-size:10px;"></i> Gasto
            </button>
            <button class="card-action-btn btn-pay" onclick="openPayModal(${card.id})">
                <i class="fas fa-money-bill-wave" style="font-size:10px;"></i> Pagar
            </button>
            <button class="card-action-btn" onclick="openHistoryModal(${card.id},'${escapeHtml(card.name)}')">
                <i class="fas fa-history" style="font-size:10px;"></i> Historial
            </button>
            ${deleteBtn}
        </div>
    `;
}

/* ============================================
   SUMMARY STRIP
   ============================================ */
function renderSummaryStrip(data) {
    const credito = data.cards.filter(c=>c.type==='credit_card').length;
    const debito  = data.cards.filter(c=>c.type==='debit_card').length;
    document.getElementById('sum-count').textContent = data.cards.length;
    document.getElementById('sum-dop').textContent   = 'RD$ ' + fmt(data.total_balance_dop);
    document.getElementById('sum-usd').textContent   = '$ '   + fmt(data.total_balance_usd);
    document.getElementById('sum-types').textContent = `${credito} / ${debito}`;
}

/* ============================================
   ALERTAS GLOBALES
   ============================================ */
function renderAlerts(data) {
    let html = '';
    if (data.cards_over_limit && data.cards_over_limit.length > 0) {
        const items = data.cards_over_limit.map(a=>`<li>${escapeHtml(a.name)} — ${a.percent}% (${a.currency})</li>`).join('');
        html += `<div class="alert alert-danger py-2"><i class="fas fa-exclamation-circle"></i> <strong>Límite excedido:</strong><ul class="mb-0 mt-1">${items}</ul></div>`;
    }
    if (data.cards_near_limit && data.cards_near_limit.length > 0) {
        const items = data.cards_near_limit.map(a=>`<li>${escapeHtml(a.name)} — ${a.percent}% (${a.currency})</li>`).join('');
        html += `<div class="alert alert-warning py-2"><i class="fas fa-exclamation-triangle"></i> <strong>Cerca del límite:</strong><ul class="mb-0 mt-1">${items}</ul></div>`;
    }
    document.getElementById('limit-alerts').innerHTML = html;
}

/* ============================================
   MOBILE SLIDER
   ============================================ */
function renderMobileSlider(cards) {
    const slider = document.getElementById('cards-slider');
    const dotsEl = document.getElementById('dot-indicators');
    const panel  = document.getElementById('card-detail-panel');

    if (cards.length === 0) {
        slider.innerHTML = `
            <div style="flex:0 0 calc(100vw - 48px);max-width:340px;">
                <div class="empty-state">
                    <i class="fas fa-credit-card"></i>
                    <p>No tienes tarjetas registradas</p>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createCardModal">
                        Crear primera tarjeta
                    </button>
                </div>
            </div>`;
        dotsEl.innerHTML = '';
        panel.style.display = 'none';
        return;
    }

    slider.innerHTML = cards.map((card,idx) =>
        `<div class="card-slide ${idx===0?'is-active':''}" data-idx="${idx}">
            ${buildCardInnerHTML(card)}
        </div>`
    ).join('');

    dotsEl.innerHTML = cards.map((_,i) =>
        `<div class="dot ${i===0?'active':''}"></div>`
    ).join('');

    panel.style.display = '';
    _activeCardIdx = 0;
    renderMobileDetailPanel(cards[0]);

    slider.addEventListener('scroll', () => {
        const slideW = slider.firstElementChild ? slider.firstElementChild.offsetWidth + 12 : 1;
        const idx    = Math.round(slider.scrollLeft / slideW);
        if (idx !== _activeCardIdx && cards[idx]) {
            _activeCardIdx = idx;
            document.querySelectorAll('.card-slide').forEach((c,i) => c.classList.toggle('is-active', i===idx));
            document.querySelectorAll('#dot-indicators .dot').forEach((d,i) => d.classList.toggle('active', i===idx));
            renderMobileDetailPanel(cards[idx]);
        }
    }, { passive: true });
}

/* ============================================
   DESKTOP GRID
   ============================================ */
function renderDesktopGrid(cards) {
    const grid = document.getElementById('cards-desktop');
    if (cards.length === 0) {
        grid.innerHTML = `
            <div style="grid-column:1/-1;">
                <div class="empty-state">
                    <i class="fas fa-credit-card"></i>
                    <p>No tienes tarjetas registradas</p>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createCardModal">
                        Crear primera tarjeta
                    </button>
                </div>
            </div>`;
        return;
    }
    grid.innerHTML = cards.map(card =>
        `<div class="card-desktop-item" id="card-item-${card.id}">
            ${buildCardInnerHTML(card)}
        </div>`
    ).join('');
}

/* ============================================
   PANEL DETALLE MÓVIL
   ============================================ */
function renderMobileDetailPanel(card) {
    const pctUsd = card.credit_limit_usd ? Math.min(100,(card.balance_usd/card.credit_limit_usd)*100) : null;
    const pctDop = card.credit_limit_dop ? Math.min(100,(card.balance_dop/card.credit_limit_dop)*100) : null;

    let limHtml = '';
    if (pctUsd !== null) {
        limHtml += `
            <div class="detail-row"><span class="dl">Límite USD</span><span class="dr">$${fmt(card.credit_limit_usd)}</span></div>
            <div class="detail-row"><span class="dl">Utilizado USD</span>
                <span class="dr ${pctUsd>90?'text-danger':pctUsd>70?'text-warning':'text-success'}">${Math.round(pctUsd)}%</span></div>
            <div class="detail-row"><span class="dl">Disponible USD</span><span class="dr">$${fmt(card.credit_limit_usd-card.balance_usd)}</span></div>`;
    }
    if (pctDop !== null) {
        limHtml += `
            <div class="detail-row"><span class="dl">Límite DOP</span><span class="dr">RD$${fmt(card.credit_limit_dop)}</span></div>
            <div class="detail-row"><span class="dl">Utilizado DOP</span>
                <span class="dr ${pctDop>90?'text-danger':pctDop>70?'text-warning':'text-success'}">${Math.round(pctDop)}%</span></div>
            <div class="detail-row"><span class="dl">Disponible DOP</span><span class="dr">RD$${fmt(card.credit_limit_dop-card.balance_dop)}</span></div>`;
    }
    if (!limHtml) limHtml = `<div class="detail-row"><span class="dl">Sin límites definidos</span></div>`;
    document.getElementById('tab-limits').innerHTML = limHtml;

    document.getElementById('tab-history').innerHTML =
        `<div class="detail-row"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Cargando...</div>`;

    ajaxPost({ action:'get_card_transactions', card_id:card.id })
        .then(data => {
            const tabHist = document.getElementById('tab-history');
            if (!data.success || !data.transactions || data.transactions.length === 0) {
                tabHist.innerHTML = `<div class="detail-row"><span class="dl">Sin movimientos recientes</span></div>`;
                return;
            }
            const recent = data.transactions.slice(0, 5);
            tabHist.innerHTML = recent.map(t => {
                const signo = t.row_type==='expense'?'+':'-';
                const cls   = t.row_type==='expense'?'text-danger':'text-success';
                const badge = t.row_type==='expense'
                    ? '<span class="tx-badge tx-gasto">Gasto</span>'
                    : '<span class="tx-badge tx-pago">Pago</span>';
                const fecha = new Date(t.date+'T00:00:00').toLocaleDateString('es-DO',{day:'numeric',month:'short'});
                const desc  = escapeHtml(t.description||(t.row_type==='payment'?'Pago de tarjeta':'Gasto'));
                return `<div class="tx-row">
                    <div>
                        <div class="tx-meta">${desc}</div>
                        <div class="tx-date">${fecha}</div>
                    </div>
                    <div>
                        <div class="tx-amount ${cls}">${signo} ${t.currency_symbol||''}${fmt(t.amount)}</div>
                        ${badge}
                    </div>
                </div>`;
            }).join('') + `
                <div class="detail-row" style="justify-content:center;">
                    <span style="font-size:12px;color:#185FA5;cursor:pointer;"
                          onclick="openHistoryModal(${card.id},'${escapeHtml(card.name)}')">
                        Ver historial completo →
                    </span>
                </div>`;
        })
        .catch(() => {
            document.getElementById('tab-history').innerHTML =
                `<div class="detail-row"><span class="dl text-danger">Error al cargar</span></div>`;
        });
}

function switchTab(event, panelId) {
    document.querySelectorAll('.detail-tab').forEach(t => t.classList.remove('active'));
    event.target.classList.add('active');
    ['tab-limits','tab-history'].forEach(id => {
        document.getElementById(id).style.display = id===panelId ? 'block' : 'none';
    });
}

/* ============================================
   CARGAR DATOS
   ============================================ */
function loadCards() {
    ajaxPost({ action:'get_cards' })
        .then(data => {
            if (!data.success) { showError(data.message, data.full_message); return; }
            _usdRate     = data.usd_rate;
            _categories  = data.categories;
            _dopAccounts = data.dop_accounts;
            _allCards    = data.cards;
            _activeCardIdx = 0;

            renderSummaryStrip(data);
            renderAlerts(data);
            renderMobileSlider(data.cards);
            renderDesktopGrid(data.cards);
        })
        .catch(err => showError('No se pudieron cargar las tarjetas.', err.message));
}

/* ============================================
   CREAR TARJETA
   ============================================ */
document.getElementById('btn-create-card').addEventListener('click', function () {
    const name     = document.getElementById('card-name').value.trim();
    const type     = document.getElementById('card-type').value;
    const limitUsd = document.getElementById('card-limit-usd').value;
    const limitDop = document.getElementById('card-limit-dop').value;
    if (!name) { showError('El nombre de la tarjeta es obligatorio.'); return; }

    setLoading('btn-create-card','btn-create-card-text','btn-create-card-spinner',true);
    ajaxPost({ action:'create_card', name, type, credit_limit_usd:limitUsd, credit_limit_dop:limitDop })
        .then(data => {
            if (!data.success) { showError(data.message, data.full_message); return; }
            showSuccess(data.message);
            bootstrap.Modal.getInstance(document.getElementById('createCardModal')).hide();
            document.getElementById('card-name').value = '';
            document.getElementById('card-limit-usd').value = '';
            document.getElementById('card-limit-dop').value = '';
            loadCards();
        })
        .catch(err => showError('Error de red al crear la tarjeta.', err.message))
        .finally(() => setLoading('btn-create-card','btn-create-card-text','btn-create-card-spinner',false));
});

/* ============================================
   EDITAR TARJETA  ← NEW
   ============================================ */
function openEditModal(cardId) {
    const card = _allCards.find(c => c.id == cardId);
    if (!card) { showError('No se encontró la tarjeta.'); return; }

    document.getElementById('edit-card-id').value         = card.id;
    document.getElementById('edit-card-name').value       = card.name;
    document.getElementById('edit-card-type').value       = card.type;
    document.getElementById('edit-card-limit-usd').value  = card.credit_limit_usd || '';
    document.getElementById('edit-card-limit-dop').value  = card.credit_limit_dop || '';

    new bootstrap.Modal(document.getElementById('editCardModal')).show();
}

document.getElementById('btn-save-edit-card').addEventListener('click', function () {
    const cardId   = document.getElementById('edit-card-id').value;
    const name     = document.getElementById('edit-card-name').value.trim();
    const type     = document.getElementById('edit-card-type').value;
    const limitUsd = document.getElementById('edit-card-limit-usd').value;
    const limitDop = document.getElementById('edit-card-limit-dop').value;

    if (!name) { showError('El nombre de la tarjeta es obligatorio.'); return; }

    setLoading('btn-save-edit-card','btn-edit-card-text','btn-edit-card-spinner',true);
    ajaxPost({ action:'edit_card', card_id:cardId, name, type, credit_limit_usd:limitUsd, credit_limit_dop:limitDop })
        .then(data => {
            if (!data.success) { showError(data.message, data.full_message); return; }
            showSuccess(data.message);
            bootstrap.Modal.getInstance(document.getElementById('editCardModal')).hide();
            loadCards();
        })
        .catch(err => showError('Error de red al guardar los cambios.', err.message))
        .finally(() => setLoading('btn-save-edit-card','btn-edit-card-text','btn-edit-card-spinner',false));
});

/* ============================================
   MODAL GASTO
   ============================================ */
function openExpenseModal(cardId) {
    document.getElementById('expense-card-id').value     = cardId;
    document.getElementById('expense-amount').value      = '';
    document.getElementById('expense-description').value = '';
    document.getElementById('expense-date').value        = localDateStr();  // ← auto fecha
    const sel = document.getElementById('expense-category');
    sel.innerHTML = '<option value="">Seleccionar categoría</option>' +
        _categories.map(c=>`<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
    new bootstrap.Modal(document.getElementById('expenseModal')).show();
}

document.getElementById('btn-add-expense').addEventListener('click', function () {
    const cardId      = document.getElementById('expense-card-id').value;
    const currency    = document.getElementById('expense-currency').value;
    const amount      = document.getElementById('expense-amount').value;
    const categoryId  = document.getElementById('expense-category').value;
    const date        = document.getElementById('expense-date').value;
    const description = document.getElementById('expense-description').value.trim();
    if (!amount || parseFloat(amount) <= 0) { showError('El monto debe ser mayor a 0.'); return; }

    setLoading('btn-add-expense','btn-expense-text','btn-expense-spinner',true);
    ajaxPost({ action:'add_card_expense', card_id:cardId, currency, amount, category_id:categoryId, date, description })
        .then(data => {
            if (!data.success) { showError(data.message, data.full_message); return; }
            showSuccess(data.message);
            bootstrap.Modal.getInstance(document.getElementById('expenseModal')).hide();
            loadCards();
        })
        .catch(err => showError('Error de red al registrar el gasto.', err.message))
        .finally(() => setLoading('btn-add-expense','btn-expense-text','btn-expense-spinner',false));
});

/* ============================================
   MODAL PAGO
   ============================================ */
function openPayModal(cardId) {
    document.getElementById('pay-card-id').value = cardId;
    document.getElementById('pay-date').value     = localDateStr();  // ← auto fecha
    document.getElementById('pay-dop').value      = '0';
    document.getElementById('pay-usd').value      = '0';
    document.getElementById('dop-for-usd').value  = '0';
    document.getElementById('enable-dop-payment').checked = false;
    document.getElementById('enable-usd-payment').checked = false;
    document.getElementById('dop-payment-section').classList.add('d-none');
    document.getElementById('usd-payment-section').classList.add('d-none');
    document.getElementById('usd-rate-hint').textContent = `Tasa actual: 1 USD = RD$ ${fmt(_usdRate)}`;
    updateTotalPreview();

    const sel   = document.getElementById('pay-account');
    const noMsg = document.getElementById('no-dop-accounts-msg');
    sel.innerHTML = '<option value="">Seleccionar cuenta</option>';
    if (_dopAccounts.length === 0) {
        noMsg.classList.remove('d-none');
    } else {
        noMsg.classList.add('d-none');
        _dopAccounts.forEach(acc => {
            sel.innerHTML += `<option value="${acc.id}">${escapeHtml(acc.name)} - Saldo: RD$ ${fmt(acc.balance)}</option>`;
        });
    }
    new bootstrap.Modal(document.getElementById('payModal')).show();
}

function updateTotalPreview() {
    const dop       = parseFloat(document.getElementById('pay-dop').value) || 0;
    const dopForUsd = parseFloat(document.getElementById('dop-for-usd').value) || 0;
    document.getElementById('total-preview').innerHTML =
        `<strong>Total a descontar:</strong> RD$ ${fmt(dop + dopForUsd)}`;
}

document.getElementById('enable-dop-payment').addEventListener('change', function () {
    document.getElementById('dop-payment-section').classList.toggle('d-none', !this.checked);
    if (!this.checked) { document.getElementById('pay-dop').value='0'; updateTotalPreview(); }
});
document.getElementById('enable-usd-payment').addEventListener('change', function () {
    document.getElementById('usd-payment-section').classList.toggle('d-none', !this.checked);
    if (!this.checked) {
        document.getElementById('pay-usd').value='0';
        document.getElementById('dop-for-usd').value='0';
        updateTotalPreview();
    }
});
['pay-dop','dop-for-usd'].forEach(id =>
    document.getElementById(id).addEventListener('input', updateTotalPreview)
);

document.getElementById('btn-pay-card').addEventListener('click', function () {
    const cardId      = document.getElementById('pay-card-id').value;
    const accountId   = document.getElementById('pay-account').value;
    const payUsd      = document.getElementById('pay-usd').value || '0';
    const payDop      = document.getElementById('pay-dop').value || '0';
    const dopForUsd   = document.getElementById('dop-for-usd').value || '0';
    const paymentDate = document.getElementById('pay-date').value;
    if (!accountId) { showError('Debes seleccionar una cuenta de origen.'); return; }
    if (parseFloat(payUsd)<=0 && parseFloat(payDop)<=0) { showError('Debes ingresar al menos un monto.'); return; }

    setLoading('btn-pay-card','btn-pay-text','btn-pay-spinner',true);
    ajaxPost({ action:'pay_card', card_id:cardId, account_id:accountId,
        pay_usd:payUsd, pay_dop:payDop, dop_amount_for_usd:dopForUsd, payment_date:paymentDate })
        .then(data => {
            if (!data.success) { showError(data.message, data.full_message); return; }
            bootstrap.Modal.getInstance(document.getElementById('payModal')).hide();
            Swal.fire({ title:'¡Pago exitoso!', html:data.summary.join('<br>'), icon:'success' });
            loadCards();
        })
        .catch(err => showError('Error de red al procesar el pago.', err.message))
        .finally(() => setLoading('btn-pay-card','btn-pay-text','btn-pay-spinner',false));
});

/* ============================================
   ELIMINAR TARJETA
   ============================================ */
function confirmDelete(cardId, cardName) {
    Swal.fire({
        title:'¿Eliminar tarjeta?',
        text:`¿Eliminar "${cardName}"? Solo es posible si no tiene deuda.`,
        icon:'warning', showCancelButton:true,
        confirmButtonColor:'#d33', cancelButtonText:'Cancelar', confirmButtonText:'Sí, eliminar',
    }).then(result => {
        if (!result.isConfirmed) return;
        ajaxPost({ action:'delete_card', card_id:cardId })
            .then(data => {
                if (!data.success) { showError(data.message, data.full_message); return; }
                showSuccess(data.message);
                loadCards();
            })
            .catch(err => showError('Error de red al eliminar.', err.message));
    });
}

/* ============================================
   HISTORIAL COMPLETO — REDESIGNED
   ============================================ */

/* --- Filter toggle --- */
document.getElementById('histFilterToggleBtn').addEventListener('click', function () {
    const panel   = document.getElementById('histFiltersPanel');
    const chevron = document.getElementById('histFilterChevron');
    const open    = panel.style.display !== 'none';
    panel.style.display = open ? 'none' : 'block';
    chevron.classList.toggle('open', !open);
});

/* --- Apply / clear filters --- */
document.getElementById('btn-apply-filters').addEventListener('click', function () {
    const cardId = document.getElementById('history-card-id').value;
    if (cardId) {
        updateHistFilterBadge();
        loadCardTransactions(cardId);
    }
});
document.getElementById('btn-clear-filters').addEventListener('click', function () {
    document.getElementById('filter-date-from').value = '';
    document.getElementById('filter-date-to').value   = '';
    document.getElementById('filter-type').value       = '';
    document.getElementById('hist-filters-badge').classList.add('d-none');
    const cardId = document.getElementById('history-card-id').value;
    if (cardId) loadCardTransactions(cardId);
});

function updateHistFilterBadge() {
    const hasFilters =
        document.getElementById('filter-date-from').value ||
        document.getElementById('filter-date-to').value   ||
        document.getElementById('filter-type').value;
    document.getElementById('hist-filters-badge').classList.toggle('d-none', !hasFilters);
}

/* --- Open modal --- */
function openHistoryModal(cardId, cardName) {
    document.getElementById('history-card-id').value         = cardId;
    document.getElementById('history-card-name').textContent = cardName;
    document.getElementById('filter-date-from').value = '';
    document.getElementById('filter-date-to').value   = '';
    document.getElementById('filter-type').value       = '';
    document.getElementById('hist-filters-badge').classList.add('d-none');
    document.getElementById('history-pagination').innerHTML = '';
    // Collapse filters on open
    document.getElementById('histFiltersPanel').style.display = 'none';
    document.getElementById('histFilterChevron').classList.remove('open');
    _historyTransactions = [];
    _historyPage = 1;
    new bootstrap.Modal(document.getElementById('transactionsHistoryModal')).show();
    loadCardTransactions(cardId);
}

/* --- Load transactions from server --- */
function loadCardTransactions(cardId, resetPage = true) {
    if (resetPage) _historyPage = 1;
    const area    = document.getElementById('hist-content-area');
    const pagDiv  = document.getElementById('history-pagination');
    const recInfo = document.getElementById('hist-records-info');
    area.innerHTML = `<div class="hist-loading">
        <div class="spinner-border spinner-border-sm text-secondary"></div>
        <span>Cargando transacciones…</span>
    </div>`;
    pagDiv.innerHTML  = '';
    recInfo.textContent = '';

    ajaxPost({
        action:'get_card_transactions', card_id:cardId,
        date_from: document.getElementById('filter-date-from').value,
        date_to:   document.getElementById('filter-date-to').value,
        type:      document.getElementById('filter-type').value,
    }).then(data => {
        if (!data.success) {
            showError(data.message, data.full_message);
            area.innerHTML = `<div class="hist-empty"><span class="hist-empty-icon">⚠️</span>Error al cargar.</div>`;
            return;
        }
        _historyTransactions = data.transactions || [];
        renderHistoryPage();
    }).catch(err => {
        showError('Error de conexión', err.message);
        area.innerHTML = `<div class="hist-empty"><span class="hist-empty-icon">⚠️</span>Error de red.</div>`;
    });
}

/* --- SVG icons for history list --- */
function histIcon(type) {
    if (type === 'expense') {
        return `<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12l7-7 7 7"/></svg>`;
    }
    return `<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="M5 12l7 7 7-7"/></svg>`;
}

/* --- Delete button for history --- */
function histDeleteBtn(t, mode) {
    const today   = new Date(); today.setHours(0,0,0,0);
    const txDate  = new Date(t.date+'T00:00:00');
    const diffDays = Math.round((today - txDate) / 86400000);
    const canDel   = diffDays >= -1 && diffDays <= 3;
    const cardId   = document.getElementById('history-card-id').value;

    if (!canDel) {
        const lockSvg = `<svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M11 7V5a3 3 0 0 0-6 0v2H3v8h10V7h-2zm-4-2a1 1 0 0 1 2 0v2H7V5z"/></svg>`;
        return mode === 'desktop'
            ? `<button class="btn-locked-hist" title="Solo eliminable los primeros 3 días" disabled>${lockSvg}</button>`
            : `<button class="btn-locked-hist" title="Solo eliminable los primeros 3 días" disabled style="width:28px;height:28px;font-size:11px;">${lockSvg}</button>`;
    }
    const trashSvg = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>`;
    return mode === 'desktop'
        ? `<button class="btn-del-hist" onclick="confirmDeleteTransaction(${t.id},${cardId})" title="Eliminar y revertir">${trashSvg}</button>`
        : `<button class="btn-del-hist" onclick="confirmDeleteTransaction(${t.id},${cardId})" title="Eliminar y revertir" style="width:28px;height:28px;">${trashSvg}</button>`;
}

/* --- Render mobile list for history --- */
function renderHistMobile(transactions) {
    if (!transactions.length) return '';
    return `<div class="hist-mobile-list">` +
        transactions.map(t => {
            const isExp  = t.row_type === 'expense';
            const dotCls = isExp ? 'hdot-exp' : 'hdot-pay';
            const amtCls = isExp ? 'hamt-exp' : 'hamt-pay';
            const signo  = isExp ? '+' : '-';
            const badgeCls = isExp ? 'hbadge-exp' : 'hbadge-pay';
            const badgeLbl = isExp ? 'Gasto' : 'Pago';
            const fecha    = new Date(t.date+'T00:00:00').toLocaleDateString('es-DO',{day:'2-digit',month:'2-digit',year:'numeric'});
            const desc     = escapeHtml(t.description || (isExp ? 'Gasto' : 'Pago de tarjeta'));
            const cat      = t.category_name ? escapeHtml(t.category_name) : (isExp ? 'Sin categoría' : 'Pago');
            const sym      = escapeHtml(t.currency_symbol || '');
            return `
            <div class="hist-item">
                <div class="hist-item-inner">
                    <div class="hist-dot-icon ${dotCls}">${histIcon(t.row_type)}</div>
                    <div class="hist-main">
                        <div class="hist-desc">${desc}</div>
                        <div class="hist-cat">${cat} &middot; <span class="hist-date">${fecha}</span></div>
                    </div>
                    <div class="hist-amount-col">
                        <div class="hist-amt-main ${amtCls}">${signo} ${sym}${fmt(t.amount)}</div>
                        <div style="text-align:right;margin-top:3px;">
                            <span class="hist-type-badge ${badgeCls}">${badgeLbl}</span>
                        </div>
                    </div>
                    <div style="margin-left:8px;flex-shrink:0;">${histDeleteBtn(t, 'mobile')}</div>
                </div>
            </div>`;
        }).join('') + `</div>`;
}

/* --- Render desktop table for history --- */
function renderHistDesktop(transactions) {
    if (!transactions.length) return '';
    const rows = transactions.map(t => {
        const isExp    = t.row_type === 'expense';
        const badgeCls = isExp ? 'hbadge-exp' : 'hbadge-pay';
        const badgeLbl = isExp ? 'Gasto' : 'Pago';
        const amtCls   = isExp ? 'hamt-exp' : 'hamt-pay';
        const signo    = isExp ? '+' : '-';
        const fecha    = new Date(t.date+'T00:00:00').toLocaleDateString('es-DO',{day:'2-digit',month:'short',year:'numeric'});
        const desc     = escapeHtml(t.description || (isExp ? '—' : 'Pago de tarjeta'));
        const cat      = t.category_name ? escapeHtml(t.category_name) : '—';
        const sym      = escapeHtml(t.currency_symbol || '');
        const balAfter = parseFloat(t.balance_after||0).toLocaleString('es-DO',{minimumFractionDigits:2});
        return `<tr>
            <td style="white-space:nowrap;color:#9ca3af;font-size:12px;">${fecha}</td>
            <td class="hist-tbl-desc">${desc}</td>
            <td style="font-size:12px;color:#374151;">${cat}</td>
            <td><span class="hist-tbl-badge ${badgeCls}">${badgeLbl}</span></td>
            <td class="hist-tbl-amt ${amtCls}">${signo} ${sym} ${fmt(t.amount)}</td>
            <td class="hist-tbl-muted" style="white-space:nowrap;">${sym} ${balAfter}</td>
            <td>${histDeleteBtn(t, 'desktop')}</td>
        </tr>`;
    }).join('');

    return `<div class="hist-desktop-wrap">
        <div class="hist-desktop-card">
            <div style="overflow-x:auto;">
                <table class="hist-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Descripción</th>
                            <th>Categoría</th>
                            <th>Tipo</th>
                            <th>Monto</th>
                            <th>Balance</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        </div>
    </div>`;
}

/* --- Main render page function --- */
function renderHistoryPage() {
    const area    = document.getElementById('hist-content-area');
    const pagDiv  = document.getElementById('history-pagination');
    const recInfo = document.getElementById('hist-records-info');

    if (_historyTransactions.length === 0) {
        area.innerHTML = `<div class="hist-empty">
            <span class="hist-empty-icon">📭</span>
            No hay transacciones que coincidan con los filtros.
        </div>`;
        pagDiv.innerHTML    = '';
        recInfo.textContent = 'Sin resultados';
        return;
    }

    const totalPages = Math.ceil(_historyTransactions.length / HISTORY_PAGE_SIZE);
    const start      = (_historyPage - 1) * HISTORY_PAGE_SIZE;
    const end        = Math.min(start + HISTORY_PAGE_SIZE, _historyTransactions.length);
    const pageTx     = _historyTransactions.slice(start, end);

    // Render both views
    area.innerHTML = renderHistMobile(pageTx) + renderHistDesktop(pageTx);

    // Records info
    recInfo.textContent = `Mostrando ${start+1}–${end} de ${_historyTransactions.length} transacción(es)`;

    // Pagination
    if (totalPages <= 1) {
        pagDiv.innerHTML = '';
        return;
    }

    const prev = _historyPage - 1;
    const next = _historyPage + 1;
    let pHtml  = '';

    pHtml += `<button class="hpag-btn ${_historyPage<=1?'disabled':''}" onclick="changeHistoryPage(1)">«</button>`;
    pHtml += `<button class="hpag-btn ${_historyPage<=1?'disabled':''}" onclick="changeHistoryPage(${prev})">‹</button>`;

    const st = Math.max(1, _historyPage - 2);
    const en = Math.min(totalPages, _historyPage + 2);
    if (st > 1) pHtml += `<button class="hpag-btn disabled">…</button>`;
    for (let i = st; i <= en; i++) {
        pHtml += `<button class="hpag-btn ${i===_historyPage?'active':''}" onclick="changeHistoryPage(${i})">${i}</button>`;
    }
    if (en < totalPages) pHtml += `<button class="hpag-btn disabled">…</button>`;
    pHtml += `<button class="hpag-btn ${_historyPage>=totalPages?'disabled':''}" onclick="changeHistoryPage(${next})">›</button>`;
    pHtml += `<button class="hpag-btn ${_historyPage>=totalPages?'disabled':''}" onclick="changeHistoryPage(${totalPages})">»</button>`;

    pagDiv.innerHTML = pHtml;
}

function changeHistoryPage(page) {
    const totalPages = Math.ceil(_historyTransactions.length / HISTORY_PAGE_SIZE);
    if (page < 1 || page > totalPages) return;
    _historyPage = page;
    renderHistoryPage();
    document.getElementById('hist-content-area').scrollIntoView({ behavior:'smooth', block:'nearest' });
}

function confirmDeleteTransaction(txId, cardId) {
    Swal.fire({
        title:'¿Eliminar transacción?',
        html:`Esta acción <strong>revertirá los balances</strong> de la tarjeta y cuenta de origen.<br><br>¿Continuar?`,
        icon:'warning', showCancelButton:true,
        confirmButtonColor:'#d33', cancelButtonText:'Cancelar', confirmButtonText:'Sí, eliminar y revertir',
    }).then(result => {
        if (!result.isConfirmed) return;
        ajaxPost({ action:'delete_card_transaction', transaction_id:txId, card_id:cardId })
            .then(data => {
                if (!data.success) { showError(data.message, data.full_message); return; }
                showSuccess(data.message);
                _historyTransactions = _historyTransactions.filter(t => t.id != txId);
                const totalPages = Math.ceil(_historyTransactions.length / HISTORY_PAGE_SIZE);
                if (_historyPage > totalPages && totalPages > 0) _historyPage = totalPages;
                renderHistoryPage();
                loadCards();
            })
            .catch(err => showError('Error de red al eliminar.', err.message));
    });
}

/* ============================================
   INICIO
   ============================================ */
loadCards();
</script>
</body>
</html>