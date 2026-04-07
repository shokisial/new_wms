<?php
session_start();
if (empty($_SESSION['id']))     { header('Location:../index.php'); exit; }
if (empty($_SESSION['branch'])) { header('Location:../index.php'); exit; }
$branch = $_SESSION['branch'];
$id     = $_SESSION['id'];
$name   = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';

include('conn/dbcon.php');

// ── Branch name ───────────────────────────────────────────────────────────────
$branchRow = mysqli_fetch_array(mysqli_query($con, "SELECT branch_name FROM branch WHERE branch_id='$branch'"));
$branch_name = $branchRow ? $branchRow['branch_name'] : $branch;

// ── Filter inputs ─────────────────────────────────────────────────────────────
$start    = $_POST['start']   ?? '';
$end      = $_POST['end']     ?? '';
$batchno  = trim($_POST['batchno'] ?? '');
$gpno     = trim($_POST['gpno']    ?? '');
$code     = trim($_POST['code']    ?? '');
$mode     = $_POST['mode']    ?? ''; // 'date' | 'search'

$dt_start = ''; $dt_end = ''; $dt_start_fmt = ''; $dt_end_fmt = '';
if ($mode === 'date' && $start && $end) {
  $dt_start     = date('Y/m/d', strtotime($start));
  $dt_end       = date('Y/m/d', strtotime($end));
  $dt_start_fmt = date('d M Y', strtotime($start));
  $dt_end_fmt   = date('d M Y', strtotime($end));
}

// ── Query ─────────────────────────────────────────────────────────────────────
$productResult = [];
$searched = false;

if ($mode === 'date' && $dt_start && $dt_end) {
  $searched = true;
  $q = mysqli_query($con,
    "SELECT stockin.*, product.prod_name, product.prod_desc AS p_code
     FROM stockin
     INNER JOIN product ON product.prod_desc = stockin.prod_id
     WHERE stockin.date >= '$dt_start' AND stockin.date <= '$dt_end'
       AND stockin.qty > 0
       AND stockin.branch_id = '$branch'
     ORDER BY stockin.date ASC"
  ) or die(mysqli_error($con));
  while ($row = mysqli_fetch_assoc($q)) $productResult[] = $row;
}

if ($mode === 'search' && ($batchno || $gpno || $code)) {
  $searched = true;
  $clauses = [];
  if ($batchno) $clauses[] = "stockin.batch = '$batchno'";
  if ($gpno)    $clauses[] = "stockin.gatepass_id = '$gpno'";
  if ($code)    $clauses[] = "stockin.prod_id = '$code'";
  $where = implode(' OR ', $clauses);
  $q = mysqli_query($con,
    "SELECT stockin.*, product.prod_name, product.prod_desc AS p_code
     FROM stockin
     INNER JOIN product ON product.prod_desc = stockin.prod_id
     WHERE ($where) AND stockin.branch_id = '$branch'
     ORDER BY stockin.date ASC"
  ) or die(mysqli_error($con));
  while ($row = mysqli_fetch_assoc($q)) $productResult[] = $row;
}

// ── Totals ────────────────────────────────────────────────────────────────────
$total_rows = count($productResult);
$grand_qty  = array_sum(array_column($productResult, 'qty'));

// ── Excel export ──────────────────────────────────────────────────────────────
if (isset($_POST['export']) && !empty($productResult)) {
  header("Content-Type: application/vnd.ms-excel");
  header("Content-Disposition: attachment; filename=\"InboundReport_{$dt_start}_{$dt_end}.xls\"");
  echo "Sr#\tASN#\tGate Pass#\tBatch\tItem Code\tArticle\tQty\tReceive Date\tLocation\n";
  $sno = 1;
  foreach ($productResult as $r) {
    echo implode("\t", [
      $sno++,
      $r['rec_dnno'],
      $r['gatepass_id'],
      $r['batch'],
      $r['prod_id'],
      $r['prod_name'],
      $r['qty'],
      date('d-m-Y', strtotime($r['date'])),
      $r['location'],
    ]) . "\n";
  }
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sovereign WMS — Inbound Report</title>
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
    html, body { height:100%; font-family:'Inter',sans-serif; background:var(--bg); color:var(--text1); font-size:13px; }

    /* ── Topbar ── */
    .topbar { position:fixed; top:0; left:0; right:0; height:var(--topbar-h); background:var(--navy); display:flex; align-items:center; padding:0 18px; gap:10px; z-index:100; border-bottom:2px solid var(--orange); }
    .logo-mark { display:flex; align-items:center; width:30px; height:30px; flex-shrink:0; }
    .brand .b1 { font-size:14px; font-weight:600; color:#fff; letter-spacing:-.2px; }
    .brand .b2 { font-size:9px; color:#8a9ab8; letter-spacing:.12em; text-transform:uppercase; margin-top:1px; }
    .topbar-right { margin-left:auto; display:flex; align-items:center; gap:14px; }
    .branch-pill { background:var(--navy-light); border:1px solid #304060; border-radius:6px; padding:4px 10px; display:flex; align-items:center; gap:7px; font-size:11px; color:#8a9ab8; }
    .branch-pill strong { color:#fff; font-weight:500; }
    .avatar { width:30px; height:30px; border-radius:50%; background:var(--orange); display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:600; color:#fff; flex-shrink:0; }

    .layout { display:flex; padding-top:var(--topbar-h); min-height:100vh; }

    /* ── Sidebar ── */
    .sidebar { position:fixed; top:var(--topbar-h); left:0; bottom:0; width:var(--sidebar-w); background:var(--navy); overflow-y:auto; padding:6px 0 24px; border-right:1px solid #253350; z-index:90; }
    .sidebar::-webkit-scrollbar { width:3px; }
    .sidebar::-webkit-scrollbar-thumb { background:#2e3d5a; border-radius:3px; }
    .nav-sect { padding:14px 14px 5px; font-size:9.5px; font-weight:600; color:#364d70; letter-spacing:.1em; text-transform:uppercase; }
    .nav-item { display:flex; align-items:center; gap:9px; padding:7px 14px; color:#7a8ba8; font-size:12px; text-decoration:none; transition:background .12s,color .12s; }
    .nav-item:hover { background:#1e2a42; color:#c8d3e8; }
    .nav-item svg { width:14px; height:14px; flex-shrink:0; opacity:.55; }
    .nav-item:hover svg { opacity:1; }
    .nav-grp-hdr { display:flex; align-items:center; gap:9px; padding:7px 14px; cursor:pointer; color:#7a8ba8; font-size:12px; transition:background .12s,color .12s; }
    .nav-grp-hdr:hover { background:#1e2a42; color:#c8d3e8; }
    .nav-grp-hdr svg.ic { width:14px; height:14px; flex-shrink:0; opacity:.55; }
    .nav-grp-hdr svg.ch { margin-left:auto; width:11px; height:11px; opacity:.4; transition:transform .18s; }
    .nav-grp.open .nav-grp-hdr { color:#c8d3e8; }
    .nav-grp.open .nav-grp-hdr svg.ch { transform:rotate(90deg); opacity:.7; }
    .nav-grp.open .nav-grp-hdr svg.ic { opacity:1; }
    .nav-sub-list { display:none; padding:2px 0; }
    .nav-grp.open .nav-sub-list { display:block; }
    .nav-sub { display:block; padding:5.5px 14px 5.5px 36px; color:#5c6e8a; font-size:11.5px; text-decoration:none; transition:color .12s,background .12s; }
    .nav-sub:hover { color:#c8d3e8; background:#1c2640; }
    .nav-sub.active { color:var(--orange-lt); font-weight:500; }
    .nav-sep { height:1px; background:#253350; margin:8px 14px; }

    /* ── Main ── */
    .main { margin-left:var(--sidebar-w); flex:1; padding:22px 26px 40px; }
    .crumb { font-size:11px; color:var(--text3); display:flex; align-items:center; gap:5px; margin-bottom:10px; }
    .crumb a { color:var(--text2); text-decoration:none; }
    .ph { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:20px; gap:12px; flex-wrap:wrap; }
    .ph-left .title { font-size:18px; font-weight:600; color:var(--text1); letter-spacing:-.4px; }
    .ph-left .sub   { font-size:12px; color:var(--text2); margin-top:3px; }
    .ph-right { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }

    /* ── Filter Panels ── */
    .filter-panels { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:20px; }
    @media(max-width:860px){ .filter-panels { grid-template-columns:1fr; } }
    .filter-card { background:var(--white); border:1px solid var(--border); border-radius:10px; padding:16px 20px; }
    .filter-card-title { font-size:11px; font-weight:600; color:var(--text2); text-transform:uppercase; letter-spacing:.07em; margin-bottom:12px; display:flex; align-items:center; gap:6px; }
    .filter-card-title svg { width:13px; height:13px; color:var(--text3); }
    .fc-row { display:flex; align-items:flex-end; gap:10px; flex-wrap:wrap; }
    .fc-group { display:flex; flex-direction:column; gap:5px; flex:1; min-width:100px; }
    .fc-label { font-size:10.5px; font-weight:600; color:var(--text3); text-transform:uppercase; letter-spacing:.06em; }
    .fc-input { height:34px; padding:0 11px; border:1px solid var(--border2); border-radius:7px; font-size:12px; font-family:'Inter',sans-serif; color:var(--text1); background:var(--bg); outline:none; transition:border .15s; width:100%; }
    .fc-input:focus { border-color:#9aafcf; background:var(--white); }
    .fc-btn { height:34px; padding:0 16px; border-radius:7px; font-size:12px; font-weight:500; cursor:pointer; border:none; font-family:'Inter',sans-serif; transition:all .13s; display:inline-flex; align-items:center; gap:6px; background:var(--navy); color:#fff; white-space:nowrap; flex-shrink:0; }
    .fc-btn:hover { background:var(--navy-mid); }
    .fc-btn svg { width:13px; height:13px; }
    .fc-btn-outline { background:var(--white); color:var(--text2); border:1px solid var(--border2); }
    .fc-btn-outline:hover { background:var(--bg2); color:var(--text1); }

    /* ── Stats ── */
    .stats-row { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
    @media(max-width:900px){ .stats-row { grid-template-columns:repeat(2,1fr); } }
    .stat-card { background:var(--white); border:1px solid var(--border); border-radius:10px; padding:14px 16px; display:flex; align-items:center; gap:12px; }
    .stat-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .stat-icon svg { width:18px; height:18px; }
    .si-navy   { background:var(--navy);         color:#fff; }
    .si-orange { background:var(--orange-muted); color:var(--orange); }
    .si-green  { background:var(--green-bg);     color:var(--green); }
    .si-blue   { background:var(--blue-bg);      color:var(--blue); }
    .stat-label { font-size:10.5px; color:var(--text3); margin-bottom:2px; }
    .stat-value { font-size:20px; font-weight:700; color:var(--text1); letter-spacing:-.5px; line-height:1; }
    .stat-sub { font-size:10px; color:var(--text3); margin-top:2px; }

    /* ── Card / Table ── */
    .card { background:var(--white); border:1px solid var(--border); border-radius:10px; overflow:hidden; }
    .card-hdr { padding:12px 16px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
    .card-hdr-title { font-size:13px; font-weight:600; color:var(--text1); }
    .card-hdr-meta  { font-size:11px; color:var(--text3); margin-top:2px; }
    .toolbar { display:flex; align-items:center; gap:8px; }
    .search-wrap { position:relative; }
    .search-wrap svg { position:absolute; left:10px; top:50%; transform:translateY(-50%); width:13px; height:13px; color:var(--text3); pointer-events:none; }
    .search-wrap input { padding:7px 10px 7px 30px; border:1px solid var(--border2); border-radius:7px; font-size:12px; font-family:'Inter',sans-serif; color:var(--text1); background:var(--bg); outline:none; width:210px; transition:border .15s; }
    .search-wrap input:focus { border-color:#9aafcf; background:var(--white); }

    .tbl-wrap { overflow-x:auto; }
    table { width:100%; border-collapse:collapse; }
    th { font-size:10px; font-weight:600; color:var(--text3); text-transform:uppercase; letter-spacing:.06em; padding:9px 14px; border-bottom:1px solid var(--border); text-align:left; white-space:nowrap; background:#fafaf8; cursor:pointer; user-select:none; }
    th:hover { color:var(--text2); }
    th .sort-ic { display:inline-block; margin-left:4px; opacity:.35; font-style:normal; }
    td { padding:0; border-bottom:1px solid #f2f0ec; vertical-align:middle; }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:#faf9f7; }
    .cell { padding:10px 14px; font-size:12px; color:var(--text1); }

    .sku-cell { display:flex; align-items:center; gap:10px; padding:10px 14px; }
    .sku-icon { width:32px; height:32px; border-radius:7px; background:var(--bg2); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:all .13s; }
    .sku-icon svg { width:14px; height:14px; color:var(--text2); }
    tbody tr:hover .sku-icon { background:var(--orange-muted); border-color:var(--orange-border); }
    tbody tr:hover .sku-icon svg { color:var(--orange); }
    .sku-name { font-size:12px; font-weight:500; color:var(--text1); }
    .sku-code { font-family:'JetBrains Mono',monospace; font-size:10.5px; color:var(--text3); margin-top:1px; }

    .mono { font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:500; }
    .qty-val { font-size:14px; font-weight:700; color:var(--text1); }
    .totals-row td { background:#fafaf8 !important; font-weight:600; }

    .gp-link { display:inline-flex; align-items:center; gap:5px; font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:600; color:var(--blue); background:var(--blue-bg); border:1px solid var(--blue-bd); border-radius:5px; padding:2px 9px; text-decoration:none; transition:all .13s; cursor:pointer; }
    .gp-link:hover { background:#dde8fb; }
    .gp-link svg { width:10px; height:10px; }

    .loc-tag { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:500; color:var(--green); background:var(--green-bg); border:1px solid var(--green-bd); border-radius:5px; padding:2px 9px; }
    .loc-tag svg { width:10px; height:10px; }

    .date-cell { font-size:11.5px; color:var(--text2); white-space:nowrap; }

    .tbl-footer { padding:10px 14px; border-top:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; background:#fafaf8; }
    .tbl-footer-note { font-size:11px; color:var(--text3); }
    .tbl-footer-note strong { color:var(--text2); }

    /* Buttons */
    .btn-primary-wms { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; border-radius:7px; font-size:12px; font-weight:500; cursor:pointer; border:none; font-family:'Inter',sans-serif; transition:all .13s; background:var(--navy); color:#fff; text-decoration:none; }
    .btn-primary-wms:hover { background:var(--navy-mid); }
    .btn-outline-wms { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; border-radius:7px; font-size:12px; font-weight:500; cursor:pointer; border:1px solid var(--border2); font-family:'Inter',sans-serif; transition:all .13s; background:var(--white); color:var(--text2); text-decoration:none; }
    .btn-outline-wms:hover { background:var(--bg2); color:var(--text1); }
    .btn-green-wms { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; border-radius:7px; font-size:12px; font-weight:500; cursor:pointer; font-family:'Inter',sans-serif; transition:all .13s; background:var(--green-bg); color:var(--green); border:1px solid var(--green-bd); }
    .btn-green-wms:hover { background:#d6f0e2; }
    .btn-print-wms { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; border-radius:7px; font-size:12px; font-weight:500; cursor:pointer; font-family:'Inter',sans-serif; transition:all .13s; background:var(--amber-bg); color:var(--amber); border:1px solid var(--amber-bd); }
    .btn-print-wms:hover { background:#fdefc0; }
    .btn-primary-wms svg, .btn-outline-wms svg, .btn-green-wms svg, .btn-print-wms svg { width:13px; height:13px; }

    /* Empty state */
    .empty-state { padding:52px 20px; text-align:center; }
    .empty-icon { width:52px; height:52px; border-radius:14px; background:var(--bg2); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; margin:0 auto 14px; }
    .empty-icon svg { width:22px; height:22px; color:var(--text3); }
    .empty-title { font-size:14px; font-weight:600; color:var(--text1); margin-bottom:5px; }
    .empty-sub   { font-size:12px; color:var(--text2); }

    /* Active filter badge */
    .filter-badge { display:inline-flex; align-items:center; gap:5px; background:var(--blue-bg); border:1px solid var(--blue-bd); border-radius:20px; padding:3px 10px; font-size:11px; font-weight:500; color:var(--blue); }

    /* ── Print ── */
    @media print {
      .topbar, .sidebar, .crumb, .ph-right, .filter-panels, .toolbar, .tbl-footer, .no-print { display:none !important; }
      .main { margin-left:0; padding:10px; }
      .layout { display:block; }
      .stats-row { display:none; }
      .print-header { display:block !important; }
    }
    .print-header { display:none; text-align:center; margin-bottom:16px; }
    .print-header h2 { font-size:14px; font-weight:700; }
    .print-header p  { font-size:12px; color:var(--text2); margin-top:3px; }
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
    <div class="b1">Sovereign</div>
    <div class="b2">Warehousing &amp; Distribution</div>
  </div>
  <div class="topbar-right">
    <div class="branch-pill">
      <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><rect x="1" y="4" width="10" height="7" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4 4V3a2 2 0 1 1 4 0v1" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
      Branch: <strong><?php echo htmlspecialchars($branch_name); ?></strong>
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
    <div class="nav-grp">
      <div class="nav-grp-hdr">
        <svg class="ic" viewBox="0 0 14 14" fill="none"><path d="M13 7H1M7 11l-4-4 4-4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Outbound
        <svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="nav-sub-list">
        <a href="seg_list.php"     class="nav-sub">Segregation List</a>
        <a href="picking_list.php" class="nav-sub">Picking List</a>
        <a href="gatepass_new.php" class="nav-sub">Outbound Gate Pass</a>
      </div>
    </div>
    <div class="nav-grp">
      <div class="nav-grp-hdr">
        <svg class="ic" viewBox="0 0 14 14" fill="none"><path d="M2 7h10M6 3l-4 4 4 4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
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
    <div class="nav-grp open">
      <div class="nav-grp-hdr">
        <svg class="ic" viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 5h5M4.5 7.5h5M4.5 10h3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        Reports
        <svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="nav-sub-list">
        <a href="inbound_report.php"  class="nav-sub active">Inbound Report</a>
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
      <a href="#">Reports</a>
      <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Inbound Report
    </div>

    <!-- Page Header -->
    <div class="ph">
      <div class="ph-left">
        <div class="title">Inbound Report</div>
        <div class="sub">
          <?php if ($searched && !empty($productResult)): ?>
            <?php if ($mode === 'date'): ?>
              <?php echo htmlspecialchars($branch_name); ?> &nbsp;·&nbsp; <?php echo $dt_start_fmt; ?> → <?php echo $dt_end_fmt; ?>
            <?php else: ?>
              <?php echo htmlspecialchars($branch_name); ?> &nbsp;·&nbsp; Custom search
            <?php endif; ?>
          <?php else: ?>
            Use the filters below to query inbound stock records
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
          <input type="hidden" name="mode"    value="<?php echo htmlspecialchars($mode); ?>">
          <input type="hidden" name="start"   value="<?php echo htmlspecialchars($start); ?>">
          <input type="hidden" name="end"     value="<?php echo htmlspecialchars($end); ?>">
          <input type="hidden" name="batchno" value="<?php echo htmlspecialchars($batchno); ?>">
          <input type="hidden" name="gpno"    value="<?php echo htmlspecialchars($gpno); ?>">
          <input type="hidden" name="code"    value="<?php echo htmlspecialchars($code); ?>">
          <button type="submit" name="export" class="btn-green-wms">
            <svg viewBox="0 0 13 13" fill="none"><path d="M6.5 2v7M3.5 6l3 3 3-3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 11h9" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
            Export Excel
          </button>
        </form>
        <form action="inventory_inboundrpt.php" method="POST" target="_blank" style="display:inline">
          <input type="hidden" name="stdt" value="<?php echo htmlspecialchars($dt_start); ?>">
          <input type="hidden" name="endt" value="<?php echo htmlspecialchars($dt_end); ?>">
          <button type="submit" class="btn-print-wms">
            <svg viewBox="0 0 13 13" fill="none"><rect x="2" y="1" width="9" height="11" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 4h4M4.5 6.5h4M4.5 9h2.5" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
            Print List
          </button>
        </form>
      </div>
      <?php endif; ?>
    </div>

    <!-- Filter Panels -->
    <div class="filter-panels no-print">

      <!-- Panel 1: Date Range -->
      <div class="filter-card">
        <div class="filter-card-title">
          <svg viewBox="0 0 13 13" fill="none"><rect x="1" y="2" width="11" height="10" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4 1v2M9 1v2M1 5h11" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
          Filter by Date Range
        </div>
        <form method="POST" action="">
          <input type="hidden" name="mode" value="date">
          <div class="fc-row">
            <div class="fc-group">
              <span class="fc-label">From</span>
              <input type="date" name="start" class="fc-input" value="<?php echo htmlspecialchars($start); ?>" required>
            </div>
            <div class="fc-group">
              <span class="fc-label">To</span>
              <input type="date" name="end" class="fc-input" value="<?php echo htmlspecialchars($end); ?>" required>
            </div>
            <button type="submit" class="fc-btn">
              <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
              Search
            </button>
          </div>
        </form>
      </div>

      <!-- Panel 2: Custom Search -->
      <div class="filter-card">
        <div class="filter-card-title">
          <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
          Search by Reference
        </div>
        <form method="POST" action="">
          <input type="hidden" name="mode" value="search">
          <div class="fc-row">
            <div class="fc-group">
              <span class="fc-label">Batch No.</span>
              <input type="text" name="batchno" class="fc-input" placeholder="e.g. B-0012" value="<?php echo htmlspecialchars($batchno); ?>">
            </div>
            <div class="fc-group">
              <span class="fc-label">Gate Pass #</span>
              <input type="text" name="gpno" class="fc-input" placeholder="e.g. GP-100" value="<?php echo htmlspecialchars($gpno); ?>">
            </div>
            <div class="fc-group">
              <span class="fc-label">Item Code</span>
              <input type="text" name="code" class="fc-input" placeholder="e.g. RM1R10" value="<?php echo htmlspecialchars($code); ?>">
            </div>
            <button type="submit" class="fc-btn">
              <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
              Search
            </button>
          </div>
        </form>
      </div>

    </div><!-- /.filter-panels -->

    <?php if ($searched && !empty($productResult)): ?>

    <!-- Active filter indicator -->
    <?php if ($mode === 'search'): ?>
    <div style="margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap">
      <?php if ($batchno): ?><span class="filter-badge"><svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M2 5h6" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg> Batch: <?php echo htmlspecialchars($batchno); ?></span><?php endif; ?>
      <?php if ($gpno):    ?><span class="filter-badge"><svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M2 5h6" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg> Gate Pass: <?php echo htmlspecialchars($gpno); ?></span><?php endif; ?>
      <?php if ($code):    ?><span class="filter-badge"><svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M2 5h6" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg> Item Code: <?php echo htmlspecialchars($code); ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon si-navy">
          <svg viewBox="0 0 18 18" fill="none"><rect x="3" y="2" width="12" height="14" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 6h6M6 9h6M6 12h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Total Lines</div>
          <div class="stat-value"><?php echo number_format($total_rows); ?></div>
          <div class="stat-sub">Inbound records</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-orange">
          <svg viewBox="0 0 18 18" fill="none"><rect x="2" y="4" width="14" height="11" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 4V3a3 3 0 0 1 6 0v1" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Total Qty Received</div>
          <div class="stat-value"><?php echo number_format($grand_qty); ?></div>
          <div class="stat-sub">Units inbound</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-green">
          <svg viewBox="0 0 18 18" fill="none"><path d="M4 9h10M9 4l-5 5 5 5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Unique ASNs</div>
          <div class="stat-value"><?php echo count(array_unique(array_column($productResult, 'rec_dnno'))); ?></div>
          <div class="stat-sub">Delivery notes</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-blue">
          <svg viewBox="0 0 18 18" fill="none"><path d="M3 9h12M9 3v12" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Unique Gate Passes</div>
          <div class="stat-value"><?php echo count(array_unique(array_column($productResult, 'gatepass_id'))); ?></div>
          <div class="stat-sub">Distinct GPs</div>
        </div>
      </div>
    </div>

    <?php endif; ?>

    <!-- Print-only header -->
    <div class="print-header">
      <h2>Sovereign Warehouse — <?php echo htmlspecialchars($branch_name); ?></h2>
      <p>Inbound Report <?php if ($dt_start_fmt && $dt_end_fmt) echo "· $dt_start_fmt → $dt_end_fmt"; ?></p>
    </div>

    <!-- Table Card -->
    <div class="card">
      <div class="card-hdr">
        <div>
          <div class="card-hdr-title">Inbound Records</div>
          <?php if (!empty($productResult)): ?>
          <div class="card-hdr-meta">
            <?php if ($mode === 'date'): ?>
              <?php echo $dt_start_fmt; ?> → <?php echo $dt_end_fmt; ?>
            <?php else: ?>
              Custom search results
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
        <?php if (!empty($productResult)): ?>
        <div class="toolbar no-print">
          <div class="search-wrap">
            <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
            <input type="text" id="si" placeholder="Filter article, batch, GP…" oninput="filterRows(this.value)">
          </div>
        </div>
        <?php endif; ?>
      </div>

      <div class="tbl-wrap">
        <table id="st">
          <thead>
            <tr>
              <th>#</th>
              <th>ASN #</th>
              <th>Gate Pass #</th>
              <th>Batch</th>
              <th>Article</th>
              <th>Qty Received</th>
              <th>Receive Date</th>
              <th>Location</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($productResult)): ?>
            <tr><td colspan="8" style="border:none">
              <div class="empty-state">
                <div class="empty-icon">
                  <svg viewBox="0 0 22 22" fill="none"><rect x="3" y="5" width="16" height="13" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M7 5V4a4 4 0 0 1 8 0v1" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                </div>
                <div class="empty-title">No records found</div>
                <div class="empty-sub"><?php echo $searched ? 'No inbound records match your filters.' : 'Select a date range or enter a reference above to load inbound data.'; ?></div>
              </div>
            </td></tr>
            <?php else: ?>
            <?php $sno = 1; foreach ($productResult as $row): ?>
            <tr>
              <!-- # -->
              <td><div class="cell" style="color:var(--text3)"><?php echo $sno; ?></div></td>

              <!-- ASN -->
              <td><div class="cell mono"><?php echo htmlspecialchars($row['rec_dnno'] ?: '—'); ?></div></td>

              <!-- Gate Pass -->
              <td>
                <div class="cell">
                  <?php if ($row['gatepass_id']): ?>
                  <form action="recieptgrn.php" method="POST" target="_blank" style="display:inline" name="sub">
                    <input type="hidden" name="g_no" value="<?php echo htmlspecialchars($row['gatepass_id']); ?>">
                    <button type="submit" class="gp-link" name="sub">
                      <svg viewBox="0 0 10 10" fill="none"><path d="M5 1v8M1 5h8" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
                      <?php echo htmlspecialchars($row['gatepass_id']); ?>
                    </button> 
                  </form>
                  <?php else: ?>—<?php endif; ?>
                </div>
              </td>

              <!-- Batch -->
              <td><div class="cell mono"><?php echo htmlspecialchars($row['batch'] ?: '—'); ?></div></td>

              <!-- Article -->
              <td>
                <div class="sku-cell">
                  <div class="sku-icon">
                    <svg viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 5h5M4.5 7h5M4.5 9h3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
                  </div>
                  <div>
                    <div class="sku-name"><?php echo htmlspecialchars($row['prod_name']); ?></div>
                    <div class="sku-code"><?php echo htmlspecialchars($row['prod_id']); ?></div>
                  </div>
                </div>
              </td>

              <!-- Qty -->
              <td><div class="cell"><span class="qty-val"><?php echo number_format($row['qty']); ?></span></div></td>

              <!-- Date -->
              <td><div class="cell date-cell"><?php echo date('d M Y', strtotime($row['date'])); ?></div></td>

              <!-- Location -->
              <td>
                <div class="cell">
                  <?php if ($row['location']): ?>
                  <span class="loc-tag">
                    <svg viewBox="0 0 10 10" fill="none"><circle cx="5" cy="4" r="1.8" stroke="currentColor" stroke-width="1.1"/><path d="M2 9c0-1.657 1.343-3 3-3s3 1.343 3 3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
                    <?php echo htmlspecialchars($row['location']); ?>
                  </span>
                  <?php else: ?>—<?php endif; ?>
                </div>
              </td>
            </tr>
            <?php $sno++; endforeach; ?>

            <!-- Totals -->
            <tr class="totals-row">
              <td colspan="5"><div class="cell" style="color:var(--text2)">Total</div></td>
              <td><div class="cell"><span class="qty-val"><?php echo number_format($grand_qty); ?></span></div></td>
              <td colspan="2"></td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if (!empty($productResult)): ?>
      <div class="tbl-footer">
        <div class="tbl-footer-note"><strong><?php echo $total_rows; ?> line<?php echo $total_rows != 1 ? 's' : ''; ?></strong> · <?php echo number_format($grand_qty); ?> total units received</div>
        <div class="tbl-footer-note">Generated: <?php echo date('d M Y, H:i'); ?></div>
      </div>
      <?php endif; ?>
    </div>

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