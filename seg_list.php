<?php
session_start();
if (empty($_SESSION['id']))     { header('Location:../index.php'); exit; }
if (empty($_SESSION['branch'])) { header('Location:../index.php'); exit; }
$branch = $_SESSION['branch'];
$id     = $_SESSION['id'];
$name   = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';

include('conn/dbcon.php');
include('DBController.php');

$orno     = $_POST['rec']      ?? '';
$pick_dat = $_POST['pick_dat'] ?? '';
$dt2      = $pick_dat ? date('Y/m/d', strtotime($pick_dat)) : '';
$dt3      = $pick_dat ? date('d M Y', strtotime($pick_dat)) : '';

$productResult = [];
if ($orno && $pick_dat) {
  $db_handle     = new DBController();
  $productResult = $db_handle->runQuery(
    "SELECT * FROM stockout
     INNER JOIN product ON product.prod_desc = stockout.product_id
     WHERE gatepass_id='0'
       AND stockout.stockout_deliveryno='$dt2'
       AND stockout_truckno='$orno'
       AND stockout.branch_id='$branch'
     ORDER BY city ASC"
  );
}

// Excel export
if (isset($_POST['export']) && !empty($productResult)) {
  header("Content-Type: application/vnd.ms-excel");
  header("Content-Disposition: attachment; filename=\"SegList_{$orno}_{$dt2}.xls\"");
  $isPrintHeader = false;
  foreach ($productResult as $row) {
    if (!$isPrintHeader) { echo implode("\t", array_keys($row)) . "\n"; $isPrintHeader = true; }
    echo implode("\t", array_values($row)) . "\n";
  }
  exit;
}

$total_rows = count($productResult ?? []);
$qt12 = 0;
foreach ($productResult as $r) { $qt12 += $r['stockout_dnqty']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sovereign WMS — Segregation List</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --navy:#1a2238; --navy-mid:#1e2a42; --navy-light:#253350;
      --orange:#d95f2b; --orange-lt:#f4722e; --orange-muted:#fdf1eb; --orange-border:#f6c9b0;
      --bg:#f6f5f3; --bg2:#eeede9; --white:#ffffff;
      --border:#e2e0db; --border2:#d0cec8;
      --text1:#1a1a18; --text2:#5c5b57; --text3:#9e9c96;
      --green:#1a6b3a; --green-bg:#eef7f2; --green-bd:#b6dfc8;
      --red:#b91c1c; --red-bg:#fef2f2; --red-bd:#fecaca;
      --amber:#92580a; --amber-bg:#fffbeb; --amber-bd:#fcd88a;
      --blue:#1e4fa0; --blue-bg:#eff4ff; --blue-bd:#bdd0f8;
      --sidebar-w:220px; --topbar-h:52px;
    }
    html, body { height: 100%; font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text1); font-size: 13px; }

    /* ── Topbar ── */
    .topbar { position: fixed; top: 0; left: 0; right: 0; height: var(--topbar-h); background: var(--navy); display: flex; align-items: center; padding: 0 18px; gap: 10px; z-index: 100; border-bottom: 2px solid var(--orange); }
    .logo-mark { display: flex; align-items: center; width: 30px; height: 30px; flex-shrink: 0; }
    .brand .b1 { font-size: 14px; font-weight: 600; color: #fff; letter-spacing: -.2px; }
    .brand .b2 { font-size: 9px; color: #8a9ab8; letter-spacing: .12em; text-transform: uppercase; margin-top: 1px; }
    .topbar-right { margin-left: auto; display: flex; align-items: center; gap: 14px; }
    .branch-pill { background: var(--navy-light); border: 1px solid #304060; border-radius: 6px; padding: 4px 10px; display: flex; align-items: center; gap: 7px; font-size: 11px; color: #8a9ab8; }
    .branch-pill strong { color: #fff; font-weight: 500; }
    .avatar { width: 30px; height: 30px; border-radius: 50%; background: var(--orange); display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 600; color: #fff; flex-shrink: 0; }

    .layout { display: flex; padding-top: var(--topbar-h); min-height: 100vh; }

    /* ── Sidebar ── */
    .sidebar { position: fixed; top: var(--topbar-h); left: 0; bottom: 0; width: var(--sidebar-w); background: var(--navy); overflow-y: auto; padding: 6px 0 24px; border-right: 1px solid #253350; z-index: 90; }
    .sidebar::-webkit-scrollbar { width: 3px; }
    .sidebar::-webkit-scrollbar-thumb { background: #2e3d5a; border-radius: 3px; }
    .nav-sect { padding: 14px 14px 5px; font-size: 9.5px; font-weight: 600; color: #364d70; letter-spacing: .1em; text-transform: uppercase; }
    .nav-item { display: flex; align-items: center; gap: 9px; padding: 7px 14px; color: #7a8ba8; font-size: 12px; text-decoration: none; position: relative; transition: background .12s, color .12s; }
    .nav-item:hover { background: #1e2a42; color: #c8d3e8; }
    .nav-item svg { width: 14px; height: 14px; flex-shrink: 0; opacity: .55; }
    .nav-item:hover svg { opacity: 1; }
    .nav-grp-hdr { display: flex; align-items: center; gap: 9px; padding: 7px 14px; cursor: pointer; color: #7a8ba8; font-size: 12px; transition: background .12s, color .12s; }
    .nav-grp-hdr:hover { background: #1e2a42; color: #c8d3e8; }
    .nav-grp-hdr svg.ic { width: 14px; height: 14px; flex-shrink: 0; opacity: .55; }
    .nav-grp-hdr svg.ch { margin-left: auto; width: 11px; height: 11px; opacity: .4; transition: transform .18s; }
    .nav-grp.open .nav-grp-hdr { color: #c8d3e8; }
    .nav-grp.open .nav-grp-hdr svg.ch { transform: rotate(90deg); opacity: .7; }
    .nav-grp.open .nav-grp-hdr svg.ic { opacity: 1; }
    .nav-sub-list { display: none; padding: 2px 0; }
    .nav-grp.open .nav-sub-list { display: block; }
    .nav-sub { display: block; padding: 5.5px 14px 5.5px 36px; color: #5c6e8a; font-size: 11.5px; text-decoration: none; transition: color .12s, background .12s; }
    .nav-sub:hover { color: #c8d3e8; background: #1c2640; }
    .nav-sub.active { color: var(--orange-lt); font-weight: 500; }
    .nav-sep { height: 1px; background: #253350; margin: 8px 14px; }

    /* ── Main ── */
    .main { margin-left: var(--sidebar-w); flex: 1; padding: 22px 26px 40px; }
    .crumb { font-size: 11px; color: var(--text3); display: flex; align-items: center; gap: 5px; margin-bottom: 10px; }
    .crumb a { color: var(--text2); text-decoration: none; }
    .ph { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 20px; gap: 12px; flex-wrap: wrap; }
    .ph-left .title { font-size: 18px; font-weight: 600; color: var(--text1); letter-spacing: -.4px; }
    .ph-left .sub   { font-size: 12px; color: var(--text2); margin-top: 3px; }
    .ph-right { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

    /* ── Filter Card ── */
    .filter-card { background: var(--white); border: 1px solid var(--border); border-radius: 10px; padding: 16px 20px; margin-bottom: 20px; display: flex; align-items: flex-end; gap: 14px; flex-wrap: wrap; }
    .fc-group { display: flex; flex-direction: column; gap: 5px; }
    .fc-label { font-size: 10.5px; font-weight: 600; color: var(--text3); text-transform: uppercase; letter-spacing: .06em; }
    .fc-input, .fc-select { height: 36px; padding: 0 12px; border: 1px solid var(--border2); border-radius: 7px; font-size: 12px; font-family: 'Inter', sans-serif; color: var(--text1); background: var(--bg); outline: none; transition: border .15s; }
    .fc-input:focus, .fc-select:focus { border-color: #9aafcf; background: var(--white); }
    .fc-select { padding-right: 28px; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%239e9c96' stroke-width='1.3' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; min-width: 180px; }
    .fc-btn { height: 36px; padding: 0 18px; border-radius: 7px; font-size: 12px; font-weight: 500; cursor: pointer; border: none; font-family: 'Inter', sans-serif; transition: all .13s; display: inline-flex; align-items: center; gap: 6px; background: var(--navy); color: #fff; white-space: nowrap; }
    .fc-btn:hover { background: var(--navy-mid); }
    .fc-btn svg { width: 13px; height: 13px; }

    /* ── Stats ── */
    .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
    .stat-card { background: var(--white); border: 1px solid var(--border); border-radius: 10px; padding: 14px 16px; display: flex; align-items: center; gap: 12px; }
    .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .stat-icon svg { width: 18px; height: 18px; }
    .si-navy   { background: var(--navy);         color: #fff; }
    .si-orange { background: var(--orange-muted); color: var(--orange); }
    .si-blue   { background: var(--blue-bg);      color: var(--blue); }
    .stat-label { font-size: 10.5px; color: var(--text3); margin-bottom: 2px; }
    .stat-value { font-size: 20px; font-weight: 700; color: var(--text1); letter-spacing: -.5px; line-height: 1; }
    .stat-sub { font-size: 10px; color: var(--text3); margin-top: 2px; }

    /* ── Card ── */
    .card { background: var(--white); border: 1px solid var(--border); border-radius: 10px; overflow: hidden; }
    .card-hdr { padding: 12px 16px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
    .card-hdr-title { font-size: 13px; font-weight: 600; color: var(--text1); }
    .card-hdr-meta  { font-size: 11px; color: var(--text3); }
    .toolbar { display: flex; align-items: center; gap: 8px; }
    .search-wrap { position: relative; }
    .search-wrap svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 13px; height: 13px; color: var(--text3); pointer-events: none; }
    .search-wrap input { padding: 7px 10px 7px 30px; border: 1px solid var(--border2); border-radius: 7px; font-size: 12px; font-family: 'Inter', sans-serif; color: var(--text1); background: var(--bg); outline: none; width: 200px; transition: border .15s; }
    .search-wrap input:focus { border-color: #9aafcf; background: var(--white); }

    /* ── Table ── */
    .tbl-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th { font-size: 10px; font-weight: 600; color: var(--text3); text-transform: uppercase; letter-spacing: .06em; padding: 9px 14px; border-bottom: 1px solid var(--border); text-align: left; white-space: nowrap; background: #fafaf8; }
    td { padding: 0; border-bottom: 1px solid #f2f0ec; vertical-align: middle; }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover td { background: #faf9f7; }
    .cell { padding: 11px 14px; font-size: 12px; color: var(--text1); }

    /* SKU cell */
    .sku-cell { display: flex; align-items: center; gap: 10px; padding: 11px 14px; }
    .sku-icon { width: 32px; height: 32px; border-radius: 7px; background: var(--bg2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all .13s; }
    .sku-icon svg { width: 14px; height: 14px; color: var(--text2); }
    tbody tr:hover .sku-icon { background: var(--orange-muted); border-color: var(--orange-border); }
    tbody tr:hover .sku-icon svg { color: var(--orange); }
    .sku-name { font-size: 12px; font-weight: 500; color: var(--text1); }
    .sku-code { font-family: 'JetBrains Mono', monospace; font-size: 10.5px; color: var(--text3); margin-top: 1px; }

    /* Misc cells */
    .mono { font-family: 'JetBrains Mono', monospace; font-size: 11.5px; font-weight: 500; }
    .city-tag { display: inline-flex; align-items: center; gap: 5px; background: var(--blue-bg); border: 1px solid var(--blue-bd); border-radius: 5px; padding: 2px 9px; font-size: 11px; font-weight: 500; color: var(--blue); }
    .dc-num { font-family: 'JetBrains Mono', monospace; font-size: 11.5px; font-weight: 600; color: var(--text1); }
    .dc-sub { font-size: 10.5px; color: var(--text3); margin-top: 1px; }
    .qty-val { font-size: 14px; font-weight: 700; color: var(--text1); }

    /* Totals row */
    .totals-row td { background: #fafaf8 !important; font-weight: 600; }

    /* Table footer */
    .tbl-footer { padding: 10px 14px; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; background: #fafaf8; }
    .tbl-footer-note { font-size: 11px; color: var(--text3); }
    .tbl-footer-note strong { color: var(--text2); }

    /* Signature strip */
    .sig-strip { margin-top: 20px; background: var(--white); border: 1px solid var(--border); border-radius: 10px; padding: 20px 24px; display: flex; gap: 40px; flex-wrap: wrap; }
    .sig-block { display: flex; flex-direction: column; gap: 28px; }
    .sig-label { font-size: 11px; font-weight: 600; color: var(--text2); text-transform: uppercase; letter-spacing: .06em; }
    .sig-line  { width: 220px; height: 1px; background: var(--border2); }

    /* Buttons */
    .btn-primary-wms { display: inline-flex; align-items: center; gap: 6px; padding: 7px 16px; border-radius: 7px; font-size: 12px; font-weight: 500; cursor: pointer; border: none; font-family: 'Inter', sans-serif; transition: all .13s; background: var(--navy); color: #fff; text-decoration: none; }
    .btn-primary-wms:hover { background: var(--navy-mid); }
    .btn-primary-wms svg { width: 13px; height: 13px; }
    .btn-outline-wms { display: inline-flex; align-items: center; gap: 6px; padding: 7px 16px; border-radius: 7px; font-size: 12px; font-weight: 500; cursor: pointer; border: 1px solid var(--border2); font-family: 'Inter', sans-serif; transition: all .13s; background: var(--white); color: var(--text2); text-decoration: none; }
    .btn-outline-wms:hover { background: var(--bg2); color: var(--text1); }
    .btn-outline-wms svg { width: 13px; height: 13px; }
    .btn-green-wms { display: inline-flex; align-items: center; gap: 6px; padding: 7px 16px; border-radius: 7px; font-size: 12px; font-weight: 500; cursor: pointer; border: none; font-family: 'Inter', sans-serif; transition: all .13s; background: var(--green-bg); color: var(--green); border: 1px solid var(--green-bd); }
    .btn-green-wms:hover { background: #d6f0e2; }
    .btn-green-wms svg { width: 13px; height: 13px; }

    /* Empty state */
    .empty-state { padding: 52px 20px; text-align: center; }
    .empty-icon { width: 52px; height: 52px; border-radius: 14px; background: var(--bg2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; }
    .empty-icon svg { width: 22px; height: 22px; color: var(--text3); }
    .empty-title { font-size: 14px; font-weight: 600; color: var(--text1); margin-bottom: 5px; }
    .empty-sub   { font-size: 12px; color: var(--text2); }

    /* ── Print ── */
    @media print {
      .topbar, .sidebar, .crumb, .ph-right, .filter-card, .toolbar, .tbl-footer, .no-print { display: none !important; }
      .main { margin-left: 0; padding: 10px; }
      .layout { display: block; }
      .stats-row { display: none; }
      .print-header { display: block !important; }
      .sig-strip { border: none; padding: 30px 0 0; }
    }
    .print-header { display: none; text-align: center; margin-bottom: 16px; }
    .print-header h2 { font-size: 14px; font-weight: 700; }
    .print-header p  { font-size: 12px; color: var(--text2); margin-top: 3px; }
  </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
  <svg class="logo-mark" viewBox="0 0 30 36" fill="none">
    <rect x="5" y="1" width="16" height="16" rx="1.5" transform="rotate(45 5 1)" stroke="#d95f2b" stroke-width="2.4" fill="none"/>
    <rect x="9" y="13" width="16" height="16" rx="1.5" transform="rotate(45 9 13)" stroke="#ffffff" stroke-width="2.4" fill="none"/>
  </svg>
  <div class="brand">
    <span class="b1">Sovereign</span>
    <span class="b2">Warehousing &amp; Distribution</span>
  </div>
  <div class="topbar-right">
    <div class="branch-pill">
      <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><rect x="1" y="4" width="10" height="7" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4 4V3a2 2 0 1 1 4 0v1" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
      Branch: <strong><?php echo htmlspecialchars($branch); ?></strong>
    </div>
    <div class="avatar"><?php
      $p = explode(' ', trim($name)); $ini = '';
      foreach (array_slice($p, 0, 2) as $x) $ini .= strtoupper($x[0]);
      echo htmlspecialchars($ini);
    ?></div>
  </div>
</div>

<div class="layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="nav-sect">Main</div>
    <a href="new_dash.php" class="nav-item">
      <svg viewBox="0 0 14 14" fill="none"><rect x="1" y="1" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="8" y="1" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="1" y="8" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="8" y="8" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/></svg>
      Dashboard
    </a>
    <div class="nav-sect">Operations</div>
    <div class="nav-grp">
      <div class="nav-grp-hdr">
        <svg class="ic" viewBox="0 0 14 14" fill="none"><path d="M1 7h12M7 3l4 4-4 4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Inbound
        <svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="nav-sub-list">
        <a href="inward_transaction.php" class="nav-sub">A.S.N</a>
        <a href="gatepass.php"           class="nav-sub">Gate Pass</a>
        <a href="final_barcode.php"      class="nav-sub">Receive</a>
        <a href="final_location.php"     class="nav-sub">Location</a>
        <a href="index_stkveh.php"       class="nav-sub">Location List</a>
      </div>
    </div>
    <div class="nav-grp open">
      <div class="nav-grp-hdr">
        <svg class="ic" viewBox="0 0 14 14" fill="none"><path d="M13 7H1M7 11l-4-4 4-4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Outbound
        <svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="nav-sub-list">
        <a href="outward_transaction.php" class="nav-sub">Transfer Note</a>
        <a href="final_out2.php"          class="nav-sub">Order Preparation</a>
        <a href="picking_summery.php"     class="nav-sub">Picking Summary</a>
        <a href="seg_list.php"            class="nav-sub active">Segregation List</a>
        <a href="gatepass_out.php"        class="nav-sub">Gate Pass</a>
      </div>
    </div>
    <div class="nav-grp">
      <div class="nav-grp-hdr">
        <svg class="ic" viewBox="0 0 14 14" fill="none"><path d="M2 5h8a3 3 0 0 1 0 6H6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 3L2 5l2 2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Return
        <svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="nav-sub-list">
        <a href="final_barcode_return.php" class="nav-sub">Return Stock</a>
        <a href="gatepass_newreturn.php"   class="nav-sub">Return Gate Pass</a>
      </div>
    </div>
    <div class="nav-sep"></div>
    <div class="nav-sect">Warehouse</div>
    <div class="nav-grp">
      <div class="nav-grp-hdr">
        <svg class="ic" viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 5h5M4.5 7.5h5M4.5 10h3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        Reports
        <svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="nav-sub-list">
        <a href="inbound_report.php"  class="nav-sub">Inbound Report</a>
        <a href="outbound_report.php" class="nav-sub">Outbound Report</a>
        <a href="expire.php"          class="nav-sub">Expiry Report</a>
        <a href="index_ledger.php"    class="nav-sub">Customer Ledger</a>
      </div>
    </div>
    <div class="nav-sep"></div>
    <a href="logout.php" class="nav-item" style="color:#5c6e8a;margin-top:4px">
      <svg viewBox="0 0 14 14" fill="none"><path d="M9 7H1M5 4l-3 3 3 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 2h2.5A1.5 1.5 0 0 1 13 3.5v7A1.5 1.5 0 0 1 11.5 12H9" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
      Logout
    </a>
  </aside>

  <!-- Main -->
  <div class="main">

    <!-- Breadcrumb -->
    <div class="crumb">
      <a href="#">Outbound</a>
      <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Segregation List
    </div>

    <!-- Page Header -->
    <div class="ph">
      <div class="ph-left">
        <div class="title">Segregation List</div>
        <div class="sub">
          <?php if ($orno && $dt3): ?>
            Vehicle <strong><?php echo htmlspecialchars($orno); ?></strong> &nbsp;·&nbsp; <?php echo htmlspecialchars($dt3); ?> &nbsp;·&nbsp; Unit: <?php echo htmlspecialchars($branch); ?>
          <?php else: ?>
            Select a vehicle and date to load the segregation list
          <?php endif; ?>
        </div>
      </div>
      <?php if (!empty($productResult)): ?>
      <div class="ph-right no-print">
        <button onclick="window.print()" class="btn-outline-wms">
          <svg viewBox="0 0 13 13" fill="none"><rect x="2" y="4" width="9" height="6" rx="1" stroke="currentColor" stroke-width="1.2"/><path d="M4 4V2.5h5V4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/><path d="M4 10v-2h5v2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
          Print
        </button>
        <form method="POST" action="" style="display:inline">
          <input type="hidden" name="rec"      value="<?php echo htmlspecialchars($orno); ?>">
          <input type="hidden" name="pick_dat" value="<?php echo htmlspecialchars($pick_dat); ?>">
          <button type="submit" name="export" class="btn-green-wms">
            <svg viewBox="0 0 13 13" fill="none"><path d="M6.5 2v7M3.5 6l3 3 3-3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 11h9" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
            Export Excel
          </button>
        </form>
      </div>
      <?php endif; ?>
    </div>

    <!-- Filter Card -->
    <div class="filter-card no-print">
      <form method="POST" action="" style="display:contents">
        <div class="fc-group">
          <span class="fc-label">Date</span>
          <input type="date" name="pick_dat" class="fc-input" value="<?php echo htmlspecialchars($pick_dat); ?>" required>
        </div>
        <div class="fc-group">
          <span class="fc-label">Vehicle / Route</span>
          <select name="rec" class="fc-select" required>
            <option value="">Select Vehicle</option>
            <?php
            $vehicles = ['1','2','3','4','5','6','7','8','TCS Over Night','TCS Over Land'];
            foreach ($vehicles as $v):
              $sel = ($v === $orno) ? 'selected' : '';
            ?>
            <option value="<?php echo htmlspecialchars($v); ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($v); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="fc-btn">
          <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
          Load List
        </button>
      </form>
    </div>

    <?php if ($orno && !empty($productResult)): ?>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon si-navy">
          <svg viewBox="0 0 18 18" fill="none"><rect x="3" y="2" width="12" height="14" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 6h6M6 9h6M6 12h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Total Orders</div>
          <div class="stat-value"><?php echo $total_rows; ?></div>
          <div class="stat-sub">Lines in this list</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-orange">
          <svg viewBox="0 0 18 18" fill="none"><rect x="2" y="4" width="14" height="11" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 4V3a3 3 0 0 1 6 0v1" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Total Quantity</div>
          <div class="stat-value"><?php echo number_format($qt12); ?></div>
          <div class="stat-sub">Units to segregate</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-blue">
          <svg viewBox="0 0 18 18" fill="none"><circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.3"/><path d="M6 9h6M9 6v6" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Vehicle</div>
          <div class="stat-value" style="font-size:15px"><?php echo htmlspecialchars($orno); ?></div>
          <div class="stat-sub"><?php echo htmlspecialchars($dt3); ?></div>
        </div>
      </div>
    </div>

    <?php endif; ?>

    <!-- Print-only header -->
    <div class="print-header">
      <h2>Sovereign Warehouse — Unit <?php echo htmlspecialchars($branch); ?></h2>
      <p>Picking List &nbsp;·&nbsp; Vehicle: <strong><?php echo htmlspecialchars($orno); ?></strong> &nbsp;·&nbsp; Date: <?php echo htmlspecialchars($dt3); ?></p>
    </div>

    <!-- Table Card -->
    <div class="card">
      <div class="card-hdr">
        <div>
          <div class="card-hdr-title">Segregation lines</div>
          <?php if ($orno): ?>
          <div class="card-hdr-meta">Vehicle <?php echo htmlspecialchars($orno); ?> &nbsp;·&nbsp; <?php echo htmlspecialchars($dt3); ?></div>
          <?php endif; ?>
        </div>
        <?php if (!empty($productResult)): ?>
        <div class="toolbar no-print">
          <div class="search-wrap">
            <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
            <input type="text" id="si" placeholder="Search SKU, D.C, city…" oninput="filterRows(this.value)">
          </div>
        </div>
        <?php endif; ?>
      </div>

      <div class="tbl-wrap">
        <table id="st">
          <thead>
            <tr>
              <th>#</th>
              <th>D.C No.</th>
              <th>SKU</th>
              <th>Batch No.</th>
              <th>Quantity</th>
              <th>Distributor</th>
              <th>City</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($productResult)): ?>
            <tr><td colspan="7" style="border:none">
              <div class="empty-state">
                <div class="empty-icon">
                  <svg viewBox="0 0 22 22" fill="none"><rect x="3" y="5" width="16" height="13" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M7 5V4a4 4 0 0 1 8 0v1" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                </div>
                <div class="empty-title">No records found</div>
                <div class="empty-sub"><?php echo ($orno) ? 'No stockout entries match this vehicle and date.' : 'Select a vehicle and date above to load the segregation list.'; ?></div>
              </div>
            </td></tr>
            <?php else: ?>
            <?php $sno = 1; foreach ($productResult as $key => $row): ?>
            <tr>
              <!-- # -->
              <td><div class="cell" style="color:var(--text3)"><?php echo $sno; ?></div></td>

              <!-- D.C No. -->
              <td>
                <div class="cell">
                  <div class="dc-num"><?php echo htmlspecialchars($row['stockout_orderno']); ?></div>
                </div>
              </td>

              <!-- SKU -->
              <td>
                <div class="sku-cell">
                  <div class="sku-icon">
                    <svg viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 5h5M4.5 7h5M4.5 9h3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
                  </div>
                  <div>
                    <div class="sku-name"><?php echo htmlspecialchars($row['prod_name']); ?></div>
                    <div class="sku-code"><?php echo htmlspecialchars($row['prod_desc']); ?></div>
                  </div>
                </div>
              </td>

              <!-- Batch -->
              <td><div class="cell mono"><?php echo htmlspecialchars($row['batch'] ?: '—'); ?></div></td>

              <!-- Quantity -->
              <td><div class="cell"><span class="qty-val"><?php echo number_format($row['stockout_dnqty']); ?></span></div></td>

              <!-- Distributor -->
              <td><div class="cell"><?php echo htmlspecialchars($row['dealer_code'] ?: '—'); ?></div></td>

              <!-- City -->
              <td><div class="cell"><span class="city-tag">
                <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><circle cx="4.5" cy="3.5" r="2" stroke="currentColor" stroke-width="1.1"/><path d="M1.5 8c0-1.657 1.343-3 3-3s3 1.343 3 3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
                <?php echo htmlspecialchars($row['city'] ?: '—'); ?>
              </span></div></td>
            </tr>
            <?php $sno++; endforeach; ?>

            <!-- Totals -->
            <tr class="totals-row">
              <td colspan="4"><div class="cell" style="color:var(--text2)">Total</div></td>
              <td><div class="cell"><span class="qty-val"><?php echo number_format($qt12); ?></span></div></td>
              <td colspan="2"></td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if (!empty($productResult)): ?>
      <div class="tbl-footer">
        <div class="tbl-footer-note"><strong><?php echo $total_rows; ?> line<?php echo $total_rows!=1?'s':''; ?></strong> · <?php echo number_format($qt12); ?> total units</div>
        <div class="tbl-footer-note">Generated: <?php echo date('d M Y, H:i'); ?></div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Signature Strip -->
    <?php if (!empty($productResult)): ?>
    <div class="sig-strip">
      <div class="sig-block">
        <div class="sig-label">Segregated by</div>
        <div class="sig-line"></div>
      </div>
      <div class="sig-block">
        <div class="sig-label">Verified by</div>
        <div class="sig-line"></div>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /.main -->
</div><!-- /.layout -->

<script>
document.querySelectorAll('.nav-grp-hdr').forEach(function(h) {
  h.addEventListener('click', function() { h.parentElement.classList.toggle('open'); });
});
function filterRows(v) {
  v = v.toLowerCase();
  document.querySelectorAll('#st tbody tr:not(.totals-row)').forEach(function(r) {
    r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none';
  });
}
</script>
</body>
</html>
