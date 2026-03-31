<?php
/**
 * sidebar.php — Sovereign WMS
 * Reusable topbar + sidebar include.
 *
 * USAGE ON EVERY PAGE:
 * ─────────────────────────────────────────────────────────────
 * 1. Start your page with session/auth checks, then:
 *      <?php include('sidebar.php'); ?>
 *
 * 2. Put your page content inside:
 *      <div class="main">
 *        <!-- breadcrumb, page header, cards, tables… -->
 *      </div>
 *
 * 3. Mark the active nav item by setting $active_page before the include:
 *      <?php
 *        $active_page = 'asn';   // see IDs below
 *        include('sidebar.php');
 *      ?>
 *
 * ACTIVE PAGE IDs  →  set $active_page to one of:
 *   'dashboard'
 *   'asn' | 'gatepass_in' | 'receive' | 'location' | 'location_list'
 *   'transfer_note' | 'order_prep' | 'picking' | 'seg_list' | 'gatepass_out'
 *   'return_stock' | 'return_gatepass'
 *   'items' | 'adjustment' | 'pre_adjustment' | 'stock_check' | 'customer' | 'zone'
 *   'loc_stock' | 'inbound_rpt' | 'outbound_rpt' | 'batch_rpt' | 'all_stock'
 *   'discrepancy' | 'expiry' | 'expiry_90' | 'ledger'
 *   'rec_accuracy' | 'out_accuracy' | 'picking_time' | 'adj_rpt' | 'qms' | 'stock_count_rpt'
 *   'qc_daily' | 'qc_weekly'
 *   'count_start' | 'stock_count'
 * ─────────────────────────────────────────────────────────────
 */

$active_page = $active_page ?? '';

// Which nav groups should start open (based on active page)
$open_groups = [
    'inbound'     => in_array($active_page, ['asn','gatepass_in','receive','location','location_list']),
    'outbound'    => in_array($active_page, ['transfer_note','order_prep','picking','seg_list','gatepass_out']),
    'return'      => in_array($active_page, ['return_stock','return_gatepass']),
    'config'      => in_array($active_page, ['items','adjustment','pre_adjustment','stock_check','customer','zone']),
    'reports'     => in_array($active_page, ['loc_stock','inbound_rpt','outbound_rpt','batch_rpt','all_stock','discrepancy','expiry','expiry_90','ledger']),
    'performance' => in_array($active_page, ['rec_accuracy','out_accuracy','picking_time','adj_rpt','qms','stock_count_rpt']),
    'quality'     => in_array($active_page, ['qc_daily','qc_weekly']),
    'stock_maint' => in_array($active_page, ['count_start','stock_count']),
];

// Helper: returns 'open' class string if group should be open
function grp($key) { global $open_groups; return $open_groups[$key] ? 'open' : ''; }
// Helper: returns 'active' class string if page matches
function act($id) { global $active_page; return $active_page === $id ? 'active' : ''; }

$branch = $_SESSION['branch'] ?? 'Main Warehouse';
$name   = $_SESSION['name']   ?? 'User';
$initials = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $name), 0, 2))));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sovereign WMS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

  <style>
    /* ── Reset ── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    /* ── Brand tokens ── */
    :root {
      --navy:          #1a2238;
      --navy-mid:      #1e2a42;
      --navy-light:    #253350;
      --orange:        #d95f2b;
      --orange-light:  #f4722e;
      --orange-muted:  #fdf1eb;
      --orange-border: #f6c9b0;
      --bg:            #f6f5f3;
      --bg2:           #eeede9;
      --white:         #ffffff;
      --border:        #e2e0db;
      --border2:       #d0cec8;
      --text1:         #1a1a18;
      --text2:         #5c5b57;
      --text3:         #9e9c96;
      --green:         #1a6b3a;
      --green-bg:      #eef7f2;
      --green-border:  #b6dfc8;
      --red:           #b91c1c;
      --red-bg:        #fef2f2;
      --red-border:    #fecaca;
      --amber:         #92580a;
      --amber-bg:      #fffbeb;
      --amber-border:  #fcd88a;
      --mono:          'JetBrains Mono', monospace;
      --sidebar-w:     220px;
      --topbar-h:      52px;
    }

    /* ── Base ── */
    html, body {
      height: 100%;
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      color: var(--text1);
      font-size: 13px;
    }

    /* ════════════════════════════════
       TOPBAR
    ════════════════════════════════ */
    .swms-topbar {
      position: fixed;
      top: 0; left: 0; right: 0;
      height: var(--topbar-h);
      background: var(--navy);
      display: flex;
      align-items: center;
      padding: 0 18px;
      gap: 10px;
      z-index: 100;
      border-bottom: 2px solid var(--orange);
    }

    .swms-logo-mark { display: flex; align-items: center; width: 30px; height: 30px; flex-shrink: 0; }

    .swms-brand { display: flex; flex-direction: column; line-height: 1; }
    .swms-brand .b1 { font-size: 14px; font-weight: 600; color: #fff; letter-spacing: -.2px; }
    .swms-brand .b2 { font-size: 9px; font-weight: 400; color: #8a9ab8; letter-spacing: .12em; text-transform: uppercase; margin-top: 1px; }

    .swms-topbar-right { margin-left: auto; display: flex; align-items: center; gap: 14px; }

    .swms-branch-pill {
      background: var(--navy-light);
      border: 1px solid #304060;
      border-radius: 6px;
      padding: 4px 10px;
      display: flex;
      align-items: center;
      gap: 7px;
      font-size: 11px;
      color: #8a9ab8;
    }
    .swms-branch-pill strong { color: #fff; font-weight: 500; }

    .swms-avatar {
      width: 30px; height: 30px;
      border-radius: 50%;
      background: var(--orange);
      display: flex; align-items: center; justify-content: center;
      font-size: 11px; font-weight: 600; color: #fff;
      flex-shrink: 0;
      cursor: pointer;
    }

    /* ════════════════════════════════
       SIDEBAR
    ════════════════════════════════ */
    .swms-sidebar {
      position: fixed;
      top: var(--topbar-h);
      left: 0;
      bottom: 0;
      width: var(--sidebar-w);
      background: var(--navy);
      overflow-y: auto;
      padding: 6px 0 24px;
      border-right: 1px solid #253350;
      z-index: 90;
    }
    .swms-sidebar::-webkit-scrollbar { width: 3px; }
    .swms-sidebar::-webkit-scrollbar-thumb { background: #2e3d5a; border-radius: 3px; }

    /* Section label */
    .nav-sect {
      padding: 14px 14px 5px;
      font-size: 9.5px;
      font-weight: 600;
      color: #364d70;
      letter-spacing: .1em;
      text-transform: uppercase;
    }

    /* Top-level nav item (no children) */
    .nav-item {
      display: flex;
      align-items: center;
      gap: 9px;
      padding: 7px 14px;
      cursor: pointer;
      color: #7a8ba8;
      font-size: 12px;
      font-weight: 400;
      text-decoration: none;
      transition: background .12s, color .12s;
      position: relative;
    }
    .nav-item:hover { background: #1e2a42; color: #c8d3e8; }
    .nav-item.active { color: #fff; }
    .nav-item.active::after {
      content: '';
      position: absolute;
      right: 0; top: 6px; bottom: 6px;
      width: 2.5px;
      background: var(--orange);
      border-radius: 2px 0 0 2px;
    }
    .nav-item svg, .nav-group-hdr svg.ic { width: 14px; height: 14px; flex-shrink: 0; opacity: .55; }
    .nav-item:hover svg, .nav-item.active svg { opacity: 1; }

    /* Collapsible group */
    .nav-group-hdr {
      display: flex;
      align-items: center;
      gap: 9px;
      padding: 7px 14px;
      cursor: pointer;
      color: #7a8ba8;
      font-size: 12px;
      transition: background .12s, color .12s;
    }
    .nav-group-hdr:hover { background: #1e2a42; color: #c8d3e8; }
    .nav-group-hdr:hover svg.ic { opacity: 1; }

    .nav-group-hdr svg.ch {
      margin-left: auto;
      width: 11px; height: 11px;
      opacity: .4;
      transition: transform .18s;
      flex-shrink: 0;
    }
    .nav-group.open .nav-group-hdr { color: #c8d3e8; }
    .nav-group.open .nav-group-hdr svg.ic { opacity: 1; }
    .nav-group.open .nav-group-hdr svg.ch { transform: rotate(90deg); opacity: .7; }

    .nav-sub-list { display: none; padding: 2px 0; }
    .nav-group.open .nav-sub-list { display: block; }

    .nav-sub {
      display: block;
      padding: 5.5px 14px 5.5px 36px;
      cursor: pointer;
      color: #5c6e8a;
      font-size: 11.5px;
      text-decoration: none;
      transition: color .12s, background .12s;
    }
    .nav-sub:hover { color: #c8d3e8; background: #1c2640; }
    .nav-sub.active { color: var(--orange-light); font-weight: 500; }

    /* Divider */
    .nav-sep { height: 1px; background: #253350; margin: 8px 14px; }

    /* ════════════════════════════════
       MAIN CONTENT AREA
       (used by every page, not in this file)
    ════════════════════════════════ */
    .main {
      position: fixed;
      top: var(--topbar-h);
      left: var(--sidebar-w);
      right: 0;
      bottom: 0;
      overflow-y: auto;
      padding: 22px 26px 40px;
    }
    .main::-webkit-scrollbar { width: 4px; }
    .main::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 4px; }

    /* ════════════════════════════════
       SHARED PAGE COMPONENTS
       (available to all pages)
    ════════════════════════════════ */

    /* Breadcrumb */
    .crumb { font-size: 11px; color: var(--text3); display: flex; align-items: center; gap: 5px; margin-bottom: 10px; }
    .crumb a { color: var(--text2); text-decoration: none; }
    .crumb a:hover { color: var(--text1); }

    /* Page header */
    .ph { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 18px; }
    .ph-left .title { font-size: 18px; font-weight: 600; color: var(--text1); letter-spacing: -.4px; }
    .ph-left .sub { font-size: 12px; color: var(--text2); margin-top: 3px; }
    .ph-right { display: flex; gap: 8px; align-items: center; }

    /* Buttons */
    .btn {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 15px;
      border-radius: 7px;
      font-size: 12px; font-weight: 500;
      cursor: pointer;
      transition: all .13s;
      border: none;
      font-family: 'Inter', sans-serif;
      white-space: nowrap;
    }
    .btn svg { width: 13px; height: 13px; flex-shrink: 0; }
    .btn-navy   { background: var(--navy);   color: #fff; }
    .btn-navy:hover { background: var(--navy-mid); }
    .btn-orange { background: var(--orange); color: #fff; }
    .btn-orange:hover { background: var(--orange-light); }
    .btn-ghost  { background: var(--white);  color: var(--text2); border: 1px solid var(--border2); }
    .btn-ghost:hover { background: var(--bg2); color: var(--text1); }

    /* Cards */
    .card { background: var(--white); border: 1px solid var(--border); border-radius: 10px; overflow: hidden; }
    .card-hdr { padding: 12px 16px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .card-hdr-title { font-size: 13px; font-weight: 600; color: var(--text1); }
    .card-hdr-note  { font-size: 11px; color: var(--text3); }
    .card-body   { padding: 16px; }
    .card-footer { padding: 12px 16px; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }

    /* Table */
    .tbl-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th {
      font-size: 10.5px; font-weight: 600; color: var(--text3);
      text-transform: uppercase; letter-spacing: .05em;
      padding: 8px 12px; border-bottom: 1px solid var(--border);
      text-align: left; white-space: nowrap;
    }
    td { padding: 9px 12px; border-bottom: 1px solid #f2f0ec; font-size: 12px; color: var(--text1); vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: #fbfaf8; }
    .mono { font-family: var(--mono); font-size: 11px; color: var(--text2); }

    /* Badges */
    .badge { display: inline-flex; align-items: center; padding: 2px 9px; border-radius: 20px; font-size: 11px; font-weight: 500; }
    .badge-amber  { background: var(--amber-bg);  color: var(--amber);  border: 1px solid var(--amber-border); }
    .badge-green  { background: var(--green-bg);  color: var(--green);  border: 1px solid var(--green-border); }
    .badge-red    { background: var(--red-bg);    color: var(--red);    border: 1px solid var(--red-border); }
    .badge-orange { background: var(--orange-muted); color: var(--orange); border: 1px solid var(--orange-border); }

    /* Danger button (inline tables) */
    .del-btn {
      background: var(--red-bg); color: var(--red);
      border: 1px solid var(--red-border);
      padding: 3px 10px; border-radius: 5px;
      font-size: 11px; font-weight: 500;
      cursor: pointer; font-family: 'Inter', sans-serif;
    }

    /* Import strip */
    .import-strip { display: flex; align-items: center; gap: 12px; padding: 11px 16px; background: var(--orange-muted); border: 1px solid var(--orange-border); border-radius: 9px; margin-bottom: 18px; }
    .import-strip-icon { width: 32px; height: 32px; border-radius: 7px; background: var(--orange); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .import-strip-icon svg { width: 15px; height: 15px; }
    .import-strip-text strong { display: block; font-size: 12px; font-weight: 600; color: var(--orange); margin-bottom: 1px; }
    .import-strip-text span { font-size: 11px; color: #9a5535; }
    .import-strip-actions { margin-left: auto; display: flex; gap: 8px; align-items: center; }

    /* Form helpers */
    .field-label { font-size: 10.5px; font-weight: 600; color: var(--text3); text-transform: uppercase; letter-spacing: .06em; white-space: nowrap; }
    .field-sel { padding: 7px 10px; border: 1px solid var(--border2); border-radius: 7px; font-size: 12px; font-family: 'Inter', sans-serif; background: var(--white); color: var(--text1); outline: none; }
    .field-sel:focus { border-color: #9aafcf; }
    .ff { display: flex; flex-direction: column; gap: 5px; }
    .ff label { font-size: 10.5px; font-weight: 600; color: var(--text3); text-transform: uppercase; letter-spacing: .06em; }
    .ff input, .ff select, .ff textarea {
      padding: 7px 10px; border: 1px solid var(--border2); border-radius: 7px;
      font-size: 12px; font-family: 'Inter', sans-serif;
      color: var(--text1); background: var(--white); outline: none; width: 100%;
    }
    .ff input:focus, .ff select:focus, .ff textarea:focus { border-color: #9aafcf; }

    /* Footer note */
    .footer-note { font-size: 11px; color: var(--text3); }
    .footer-note strong { color: var(--text2); }
  </style>
</head>
<body>

<!-- ══════════════════════════════════
     TOPBAR
══════════════════════════════════ -->
<div class="swms-topbar">
  <svg class="swms-logo-mark" viewBox="0 0 30 36" fill="none">
    <rect x="5" y="1" width="16" height="16" rx="1.5" transform="rotate(45 5 1)" stroke="#d95f2b" stroke-width="2.4" fill="none"/>
    <rect x="9" y="13" width="16" height="16" rx="1.5" transform="rotate(45 9 13)" stroke="#ffffff" stroke-width="2.4" fill="none"/>
  </svg>
  <div class="swms-brand">
    <span class="b1">Sovereign</span>
    <span class="b2">Warehousing &amp; Distribution</span>
  </div>
  <div class="swms-topbar-right">
    <div class="swms-branch-pill">
      <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
        <rect x="1" y="4" width="10" height="7" rx="1.5" stroke="currentColor" stroke-width="1.2"/>
        <path d="M4 4V3a2 2 0 1 1 4 0v1" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
      </svg>
      <span>Branch:</span>
      <strong><?php echo htmlspecialchars($branch); ?></strong>
    </div>
    <div class="swms-avatar" title="<?php echo htmlspecialchars($name); ?>">
      <?php echo htmlspecialchars($initials); ?>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════
     SIDEBAR
══════════════════════════════════ -->
<aside class="swms-sidebar">

  <div class="nav-sect">Main</div>
  <a href="new_dash.php" class="nav-item <?= act('dashboard') ?>">
    <svg viewBox="0 0 14 14" fill="none"><rect x="1" y="1" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="8" y="1" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="1" y="8" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="8" y="8" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/></svg>
    Dashboard
  </a>

  <div class="nav-sect">Operations</div>

  <!-- Inbound -->
  <div class="nav-group <?= grp('inbound') ?>">
    <div class="nav-group-hdr">
      <svg class="ic" viewBox="0 0 14 14" fill="none"><path d="M1 7h12M7 3l4 4-4 4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Inbound
      <svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <div class="nav-sub-list">
      <a href="inward_transaction.php" class="nav-sub <?= act('asn') ?>">A.S.N</a>
      <a href="gatepass.php"           class="nav-sub <?= act('gatepass_in') ?>">Gate Pass</a>
      <a href="final_barcode.php"      class="nav-sub <?= act('receive') ?>">Receive</a>
      <a href="final_location.php"     class="nav-sub <?= act('location') ?>">Location</a>
      <a href="index_stkveh.php"       class="nav-sub <?= act('location_list') ?>">Location List</a>
    </div>
  </div>

  <!-- Outbound -->
  <div class="nav-group <?= grp('outbound') ?>">
    <div class="nav-group-hdr">
      <svg class="ic" viewBox="0 0 14 14" fill="none"><path d="M13 7H1M7 11l-4-4 4-4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Outbound
      <svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <div class="nav-sub-list">
      <a href="outward_transaction.php" class="nav-sub <?= act('transfer_note') ?>">Transfer Note</a>
      <a href="final_out2.php"          class="nav-sub <?= act('order_prep') ?>">Order Preparation</a>
      <a href="picking_summery.php"     class="nav-sub <?= act('picking') ?>">Picking Summary</a>
      <a href="seg_list.php"            class="nav-sub <?= act('seg_list') ?>">Segregation List</a>
      <a href="gatepass_out.php"        class="nav-sub <?= act('gatepass_out') ?>">Gate Pass</a>
    </div>
  </div>

  <!-- Return -->
  <div class="nav-group <?= grp('return') ?>">
    <div class="nav-group-hdr">
      <svg class="ic" viewBox="0 0 14 14" fill="none"><path d="M2 5h8a3 3 0 0 1 0 6H6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 3L2 5l2 2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Return
      <svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <div class="nav-sub-list">
      <a href="final_barcode_return.php"  class="nav-sub <?= act('return_stock') ?>">Return Stock</a>
      <a href="gatepass_newreturn.php"    class="nav-sub <?= act('return_gatepass') ?>">Return Gate Pass</a>
    </div>
  </div>

  <div class="nav-sep"></div>
  <div class="nav-sect">Warehouse</div>

  <!-- Configuration -->
  <div class="nav-group <?= grp('config') ?>">
    <div class="nav-group-hdr">
      <svg class="ic" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="2" stroke="currentColor" stroke-width="1.2"/><path d="M7 1v1.5M7 11.5V13M1 7h1.5M11.5 7H13M2.93 2.93l1.06 1.06M10.01 10.01l1.06 1.06M2.93 11.07l1.06-1.06M10.01 3.99l1.06-1.06" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
      Configuration
      <svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <div class="nav-sub-list">
      <a href="product.php"        class="nav-sub <?= act('items') ?>">Items</a>
      <a href="dealer.php"         class="nav-sub <?= act('adjustment') ?>">Adjustment</a>
      <a href="dealer_pre.php"     class="nav-sub <?= act('pre_adjustment') ?>">Pre-Adjustment</a>
      <a href="stockcount_int.php" class="nav-sub <?= act('stock_check') ?>">Stock Check</a>
      <a href="supplier.php"       class="nav-sub <?= act('customer') ?>">Customer</a>
      <a href="zone.php"           class="nav-sub <?= act('zone') ?>">Zone</a>
    </div>
  </div>

  <!-- Reports -->
  <div class="nav-group <?= grp('reports') ?>">
    <div class="nav-group-hdr">
      <svg class="ic" viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 5h5M4.5 7.5h5M4.5 10h3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
      Reports
      <svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <div class="nav-sub-list">
      <a href="index_stock.php"       class="nav-sub <?= act('loc_stock') ?>">Location Wise Stock</a>
      <a href="inbound_report.php"    class="nav-sub <?= act('inbound_rpt') ?>">Inbound Report</a>
      <a href="outbound_report.php"   class="nav-sub <?= act('outbound_rpt') ?>">Outbound Report</a>
      <a href="index_stockbatch.php"  class="nav-sub <?= act('batch_rpt') ?>">Batch Wise Report</a>
      <a href="index_stockall.php"    class="nav-sub <?= act('all_stock') ?>">All Stock Report</a>
      <a href="discrepancy.php"       class="nav-sub <?= act('discrepancy') ?>">Discrepancy Report</a>
      <a href="expire.php"            class="nav-sub <?= act('expiry') ?>">Expiry Report</a>
      <a href="expire_intimation.php" class="nav-sub <?= act('expiry_90') ?>">90 Days Expiry</a>
      <a href="index_ledger.php"      class="nav-sub <?= act('ledger') ?>">Customer Ledger</a>
    </div>
  </div>

  <!-- Performance -->
  <div class="nav-group <?= grp('performance') ?>">
    <div class="nav-group-hdr">
      <svg class="ic" viewBox="0 0 14 14" fill="none"><path d="M1 11L4.5 7l3 2.5L11 4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Performance
      <svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <div class="nav-sub-list">
      <a href="rec_time.php"           class="nav-sub <?= act('rec_accuracy') ?>">Receive Accuracy</a>
      <a href="out_timeold.php"        class="nav-sub <?= act('out_accuracy') ?>">Outbound Accuracy</a>
      <a href="picking_time.php"       class="nav-sub <?= act('picking_time') ?>">Picking Time</a>
      <a href="adj_rpt.php"            class="nav-sub <?= act('adj_rpt') ?>">Adjustment Report</a>
      <a href="quality_checkrpt.php"   class="nav-sub <?= act('qms') ?>">QMS</a>
      <a href="stockcount_report.php"  class="nav-sub <?= act('stock_count_rpt') ?>">Stock Count Report</a>
    </div>
  </div>

  <div class="nav-sep"></div>
  <div class="nav-sect">Quality</div>

  <!-- Quality Check -->
  <div class="nav-group <?= grp('quality') ?>">
    <div class="nav-group-hdr">
      <svg class="ic" viewBox="0 0 14 14" fill="none"><path d="M2 7l3.5 3.5L12 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Quality Check
      <svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <div class="nav-sub-list">
      <a href="qualitycheck.php"      class="nav-sub <?= act('qc_daily') ?>">Daily QMS</a>
      <a href="qualitycheck_week.php" class="nav-sub <?= act('qc_weekly') ?>">Weekly QMS</a>
    </div>
  </div>

  <!-- Stock Maintenance -->
  <div class="nav-group <?= grp('stock_maint') ?>">
    <div class="nav-group-hdr">
      <svg class="ic" viewBox="0 0 14 14" fill="none"><rect x="2" y="4" width="10" height="9" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M5 4V3a2 2 0 0 1 4 0v1" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
      Stock Maintenance
      <svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <div class="nav-sub-list">
      <a href="stockcount_int.php" class="nav-sub <?= act('count_start') ?>">Count Start</a>
      <a href="stock_count.php"    class="nav-sub <?= act('stock_count') ?>">Stock Count</a>
    </div>
  </div>

  <div class="nav-sep"></div>
  <a href="logout.php" class="nav-item" style="color:#5c6e8a; margin-top:4px">
    <svg viewBox="0 0 14 14" fill="none"><path d="M9 7H1M5 4l-3 3 3 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 2h2.5A1.5 1.5 0 0 1 13 3.5v7A1.5 1.5 0 0 1 11.5 12H9" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
    Logout
  </a>

</aside>

<!-- ══════════════════════════════════
     Accordion JS (collapsible groups)
══════════════════════════════════ -->
<script>
document.querySelectorAll('.nav-group-hdr').forEach(hdr => {
  hdr.addEventListener('click', () => {
    hdr.parentElement.classList.toggle('open');
  });
});
</script>

<!-- PAGE CONTENT STARTS BELOW — wrap in <div class="main"> … </div> -->
