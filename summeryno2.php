<?php
session_start();
if (empty($_SESSION['id']))     { header('Location:../index.php'); exit; }
if (empty($_SESSION['branch'])) { header('Location:../index.php'); exit; }
$branch = $_SESSION['branch'];
$uid    = $_SESSION['id'];
$name   = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';

include('conn/dbcon.php');

$pick_no = $_POST['sub'] ?? '';
$dt2     = $_POST['dt']  ?? '';

// Handle "Mark as Picked" action
if (isset($_POST['sub12'])) {
  $sub1  = $_POST['sub1'];
  $dt10  = $_POST['dt'];
  $loc   = $_POST['loc'];
  $dt3   = date("Y/m/d H:i:s");
  mysqli_query($con,
    "UPDATE `location_temp`
     SET `picked_user`='$uid', picked_tim='$dt3'
     WHERE location='$loc' AND picked_user='0'"
  ) or die(mysqli_error($con));
  // Reload page with same pick list
  echo "<form id='rf' method='POST' action=''>";
  echo "<input type='hidden' name='dt'  value='" . htmlspecialchars($dt10) . "'>";
  echo "<input type='hidden' name='sub' value='" . htmlspecialchars($sub1) . "'>";
  echo "</form><script>document.getElementById('rf').submit();</script>";
  exit;
}

// Fetch rows grouped by cond
$rows = [];
$sno = 1;
$first12 = 0; $whole2 = 0; $loose2 = 0;

$sql1 = mysqli_query($con,
  "SELECT * FROM location_temp
   WHERE picking_no='$pick_no' AND dat='$dt2' AND branch_id='$branch'
   GROUP BY cond ORDER BY location ASC"
) or die(mysqli_error($con));

while ($row1 = mysqli_fetch_array($sql1)) {
  $cond = $row1['cond'];

  // Aggregate qty and pick info for this cond
  $qt_total = 0; $prd = ''; $bth = ''; $lc = ''; $p_user = '0'; $tm = '';
  $sql2 = mysqli_query($con,
    "SELECT * FROM location_temp
     WHERE picking_no='$pick_no' AND dat='$dt2' AND cond='$cond' AND branch_id='$branch'
     ORDER BY location ASC"
  ) or die(mysqli_error($con));
  while ($row2 = mysqli_fetch_array($sql2)) {
    $prd      = $row2['prod_id'];
    $bth      = $row2['batch_id'];
    $lc       = $row2['location'];
    $qt_total += $row2['st_qty'];
    $p_user   = $row2['picked_user'];
    $tm       = $row2['picked_tim'];
  }
  $new_date = $tm ? date('H:i:s', strtotime($tm)) : '';

  // Product details
  $prod_name = ''; $vol1 = 1;
  $sql13 = mysqli_query($con, "SELECT * FROM product WHERE prod_desc='$prd'") or die(mysqli_error($con));
  while ($row13 = mysqli_fetch_array($sql13)) { $prod_name = $row13['prod_name']; $vol1 = $row13['volume'] ?: 1; }

  // Barcode
  $ds = '';
  $sql0 = mysqli_query($con, "SELECT * FROM product_barcode WHERE prod_desc='$prd'") or die(mysqli_error($con));
  while ($row0 = mysqli_fetch_array($sql0)) { $ds = $row0['barcode']; }

  // Balance
  $rmn = 0;
  $sql15 = mysqli_query($con,
    "SELECT * FROM location_control WHERE prod_id='$prd' AND stock_location='$lc' AND out_blc > '0' AND supplier_id='55'"
  ) or die(mysqli_error($con));
  while ($row15 = mysqli_fetch_array($sql15)) { $rmn = $row15['out_blc']; }

  // Carton / loose calculation
  $whole1 = round((int)($qt_total / $vol1));
  $loose1 = round(($qt_total / $vol1 - (int)($qt_total / $vol1)) * $vol1);

  $first12 += $qt_total;
  $whole2  += $whole1;
  $loose2  += $loose1;

  $rows[] = compact('sno','prd','ds','prod_name','bth','lc','qt_total','whole1','loose1','p_user','new_date','rmn');
  $sno++;
}

$total_rows   = count($rows);
$picked_count = count(array_filter($rows, fn($r) => $r['p_user'] !== '0'));
$progress_pct = $total_rows > 0 ? round($picked_count / $total_rows * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sovereign WMS — Pick List <?php echo htmlspecialchars($pick_no); ?></title>
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

    /* Topbar */
    .topbar { position: fixed; top: 0; left: 0; right: 0; height: var(--topbar-h); background: var(--navy); display: flex; align-items: center; padding: 0 18px; gap: 10px; z-index: 100; border-bottom: 2px solid var(--orange); }
    .logo-mark { display: flex; align-items: center; width: 30px; height: 30px; flex-shrink: 0; }
    .brand .b1 { font-size: 14px; font-weight: 600; color: #fff; letter-spacing: -.2px; }
    .brand .b2 { font-size: 9px; color: #8a9ab8; letter-spacing: .12em; text-transform: uppercase; margin-top: 1px; }
    .topbar-right { margin-left: auto; display: flex; align-items: center; gap: 14px; }
    .branch-pill { background: var(--navy-light); border: 1px solid #304060; border-radius: 6px; padding: 4px 10px; display: flex; align-items: center; gap: 7px; font-size: 11px; color: #8a9ab8; }
    .branch-pill strong { color: #fff; font-weight: 500; }
    .avatar { width: 30px; height: 30px; border-radius: 50%; background: var(--orange); display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 600; color: #fff; flex-shrink: 0; }

    .layout { display: flex; padding-top: var(--topbar-h); min-height: 100vh; }

    /* Sidebar */
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

    /* Main */
    .main { margin-left: var(--sidebar-w); flex: 1; padding: 22px 26px 40px; }
    .crumb { font-size: 11px; color: var(--text3); display: flex; align-items: center; gap: 5px; margin-bottom: 10px; }
    .crumb a { color: var(--text2); text-decoration: none; }

    /* Page header */
    .ph { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 20px; gap: 12px; flex-wrap: wrap; }
    .ph-left .title { font-size: 18px; font-weight: 600; color: var(--text1); letter-spacing: -.4px; }
    .ph-left .sub { font-size: 12px; color: var(--text2); margin-top: 3px; }
    .ph-right { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

    /* Progress banner */
    .progress-banner { background: var(--white); border: 1px solid var(--border); border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 16px; }
    .pb-label { font-size: 12px; color: var(--text2); white-space: nowrap; }
    .pb-bar-wrap { flex: 1; height: 8px; background: var(--bg2); border-radius: 4px; overflow: hidden; min-width: 100px; }
    .pb-bar-fill { height: 100%; border-radius: 4px; background: var(--orange); transition: width .5s ease; }
    .pb-pct { font-size: 13px; font-weight: 700; color: var(--text1); white-space: nowrap; min-width: 36px; text-align: right; }
    .pb-counts { font-size: 11px; color: var(--text3); white-space: nowrap; }

    /* Stats */
    .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px; }
    .stat-card { background: var(--white); border: 1px solid var(--border); border-radius: 10px; padding: 14px 16px; display: flex; align-items: center; gap: 12px; }
    .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .stat-icon svg { width: 18px; height: 18px; }
    .si-navy   { background: var(--navy);        color: #fff; }
    .si-orange { background: var(--orange-muted); color: var(--orange); }
    .si-green  { background: var(--green-bg);    color: var(--green); }
    .si-blue   { background: var(--blue-bg);     color: var(--blue); }
    .stat-label { font-size: 10.5px; color: var(--text3); margin-bottom: 2px; }
    .stat-value { font-size: 20px; font-weight: 700; color: var(--text1); letter-spacing: -.5px; line-height: 1; }
    .stat-sub { font-size: 10px; color: var(--text3); margin-top: 2px; }

    /* Card */
    .card { background: var(--white); border: 1px solid var(--border); border-radius: 10px; overflow: hidden; }
    .card-hdr { padding: 12px 16px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
    .card-hdr-title { font-size: 13px; font-weight: 600; color: var(--text1); }
    .toolbar { display: flex; align-items: center; gap: 8px; }
    .filter-btn { padding: 6px 12px; border: 1px solid var(--border2); border-radius: 7px; font-size: 11.5px; font-family: 'Inter', sans-serif; color: var(--text2); background: var(--white); cursor: pointer; display: flex; align-items: center; gap: 5px; transition: all .12s; }
    .filter-btn:hover { background: var(--bg2); color: var(--text1); }
    .filter-btn.active { background: var(--navy); color: #fff; border-color: var(--navy); }
    .search-wrap { position: relative; }
    .search-wrap svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 13px; height: 13px; color: var(--text3); pointer-events: none; }
    .search-wrap input { padding: 7px 10px 7px 30px; border: 1px solid var(--border2); border-radius: 7px; font-size: 12px; font-family: 'Inter', sans-serif; color: var(--text1); background: var(--bg); outline: none; width: 200px; transition: border .15s; }
    .search-wrap input:focus { border-color: #9aafcf; background: var(--white); }

    /* Table */
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

    /* Mono cells */
    .mono { font-family: 'JetBrains Mono', monospace; font-size: 11.5px; font-weight: 500; }
    .loc-tag { display: inline-block; background: var(--bg2); border: 1px solid var(--border); border-radius: 5px; padding: 2px 8px; font-family: 'JetBrains Mono', monospace; font-size: 11px; font-weight: 600; color: var(--text1); }

    /* Qty cell */
    .qty-cell { padding: 11px 14px; }
    .qty-main { font-size: 14px; font-weight: 700; color: var(--text1); line-height: 1; }
    .qty-detail { display: flex; gap: 8px; margin-top: 4px; flex-wrap: wrap; }
    .qty-tag { font-size: 10px; color: var(--text3); }
    .qty-tag strong { color: var(--text2); }

    /* Badges */
    .badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
    .badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; }
    .b-amber { background: var(--amber-bg); color: var(--amber); border: 1px solid var(--amber-bd); }
    .b-amber::before { background: var(--amber); }
    .b-green { background: var(--green-bg); color: var(--green); border: 1px solid var(--green-bd); }
    .b-green::before { background: var(--green); }

    /* Action cell */
    .action-cell { padding: 11px 14px; }
    .pick-btn { display: inline-flex; align-items: center; gap: 6px; padding: 6px 13px; border-radius: 6px; font-size: 11.5px; font-weight: 500; cursor: pointer; border: none; font-family: 'Inter', sans-serif; transition: all .13s; white-space: nowrap; background: var(--navy); color: #fff; }
    .pick-btn:hover:not(:disabled) { background: var(--navy-mid); }
    .pick-btn:disabled { background: var(--green-bg); color: var(--green); border: 1px solid var(--green-bd); cursor: default; }
    .pick-btn svg { width: 12px; height: 12px; }
    .picked-time { font-size: 10px; color: var(--text3); margin-top: 4px; font-family: 'JetBrains Mono', monospace; }

    /* Totals row */
    .totals-row td { background: #fafaf8 !important; font-weight: 600; }

    /* Table footer */
    .tbl-footer { padding: 10px 14px; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; background: #fafaf8; }
    .tbl-footer-note { font-size: 11px; color: var(--text3); }
    .tbl-footer-note strong { color: var(--text2); }

    /* Buttons */
    .btn-primary-wms { display: inline-flex; align-items: center; gap: 6px; padding: 7px 16px; border-radius: 7px; font-size: 12px; font-weight: 500; cursor: pointer; border: none; font-family: 'Inter', sans-serif; transition: all .13s; background: var(--navy); color: #fff; text-decoration: none; }
    .btn-primary-wms:hover { background: var(--navy-mid); }
    .btn-primary-wms svg { width: 13px; height: 13px; }
    .btn-outline-wms { display: inline-flex; align-items: center; gap: 6px; padding: 7px 16px; border-radius: 7px; font-size: 12px; font-weight: 500; cursor: pointer; border: 1px solid var(--border2); font-family: 'Inter', sans-serif; transition: all .13s; background: var(--white); color: var(--text2); }
    .btn-outline-wms:hover { background: var(--bg2); color: var(--text1); }
    .btn-outline-wms svg { width: 13px; height: 13px; }

    /* Empty */
    .empty-state { padding: 52px 20px; text-align: center; }
    .empty-icon { width: 52px; height: 52px; border-radius: 14px; background: var(--bg2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; }
    .empty-icon svg { width: 22px; height: 22px; color: var(--text3); }
    .empty-title { font-size: 14px; font-weight: 600; color: var(--text1); margin-bottom: 5px; }
    .empty-sub { font-size: 12px; color: var(--text2); }

    @media print {
      .topbar, .sidebar, .crumb, .ph-right, .toolbar, .action-cell, .tbl-footer { display: none !important; }
      .main { margin-left: 0; padding: 10px; }
      .layout { display: block; }
    }
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
        <a href="picking_summery.php"     class="nav-sub active">Picking Summary</a>
        <a href="seg_list.php"            class="nav-sub">Segregation List</a>
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

  <!-- Main Content -->
  <div class="main">

    <!-- Breadcrumb -->
    <div class="crumb">
      <a href="#">Outbound</a>
      <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <a href="picking_summery.php">Picking Summary</a>
      <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Pick List Detail
    </div>

    <!-- Page Header -->
    <div class="ph">
      <div class="ph-left">
        <div class="title">Pick List — <?php echo htmlspecialchars($pick_no); ?></div>
        <div class="sub">Dated: <?php echo htmlspecialchars($dt2); ?> &nbsp;·&nbsp; Unit: <?php echo htmlspecialchars($branch); ?></div>
      </div>
      <div class="ph-right">
        <a href="picking_summery.php" class="btn-outline-wms">
          <svg viewBox="0 0 13 13" fill="none"><path d="M8 2L3 6.5 8 11" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Back
        </a>
        <button onclick="window.print()" class="btn-outline-wms">
          <svg viewBox="0 0 13 13" fill="none"><rect x="2" y="4" width="9" height="6" rx="1" stroke="currentColor" stroke-width="1.2"/><path d="M4 4V2.5h5V4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/><path d="M4 10v-2h5v2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
          Print
        </button>
        <form method="POST" action="seg_list.php" style="display:inline">
          <input type="hidden" name="rec"      value="<?php echo htmlspecialchars($pick_no); ?>">
          <input type="hidden" name="pick_dat" value="<?php echo htmlspecialchars($dt2); ?>">
          <button type="submit" name="load_excel_data" class="btn-primary-wms">
            <svg viewBox="0 0 13 13" fill="none"><rect x="2" y="1" width="9" height="11" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 4.5h4M4.5 6.5h4M4.5 8.5h2.5" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
            Segr. List
          </button>
        </form>
      </div>
    </div>

    <!-- Progress Banner -->
    <div class="progress-banner">
      <div class="pb-label">Picking Progress</div>
      <div class="pb-bar-wrap">
        <div class="pb-bar-fill" style="width:<?php echo $progress_pct; ?>%"></div>
      </div>
      <div class="pb-pct"><?php echo $progress_pct; ?>%</div>
      <div class="pb-counts"><?php echo $picked_count; ?> / <?php echo $total_rows; ?> items picked</div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon si-navy">
          <svg viewBox="0 0 18 18" fill="none"><rect x="2" y="3" width="14" height="12" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M5 7h8M5 10h5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Total Lines</div>
          <div class="stat-value"><?php echo $total_rows; ?></div>
          <div class="stat-sub">SKUs in pick list</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-orange">
          <svg viewBox="0 0 18 18" fill="none"><rect x="3" y="2" width="12" height="14" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 6h6M6 9h6M6 12h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Total Units</div>
          <div class="stat-value"><?php echo number_format($first12); ?></div>
          <div class="stat-sub"><?php echo $whole2; ?> cartons · <?php echo $loose2; ?> loose</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-green">
          <svg viewBox="0 0 18 18" fill="none"><path d="M4 9a5 5 0 1 0 10 0A5 5 0 0 0 4 9z" stroke="currentColor" stroke-width="1.3"/><path d="M6.5 9l2 2 3-3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Picked</div>
          <div class="stat-value"><?php echo $picked_count; ?></div>
          <div class="stat-sub">Lines completed</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-blue">
          <svg viewBox="0 0 18 18" fill="none"><path d="M9 3a6 6 0 1 0 0 12A6 6 0 0 0 9 3z" stroke="currentColor" stroke-width="1.3"/><path d="M9 6v3l2 2" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Remaining</div>
          <div class="stat-value"><?php echo $total_rows - $picked_count; ?></div>
          <div class="stat-sub">Lines pending</div>
        </div>
      </div>
    </div>

    <!-- Table Card -->
    <div class="card">
      <div class="card-hdr">
        <div class="card-hdr-title">Pick list items</div>
        <div class="toolbar">
          <button class="filter-btn active" onclick="setFilter('all',this)">All</button>
          <button class="filter-btn" onclick="setFilter('pending',this)">Pending</button>
          <button class="filter-btn" onclick="setFilter('picked',this)">Picked</button>
          <div class="search-wrap">
            <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
            <input type="text" id="si" placeholder="Search SKU, location, batch…" oninput="filterRows(this.value)">
          </div>
        </div>
      </div>

      <div class="tbl-wrap">
        <table id="pt">
          <thead>
            <tr>
              <th>#</th>
              <th>SKU</th>
              <th>Barcode</th>
              <th>Batch</th>
              <th>Location</th>
              <th>Qty</th>
              <th>Cartons</th>
              <th>Loose</th>
              <th>Balance</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="11" style="border:none">
              <div class="empty-state">
                <div class="empty-icon"><svg viewBox="0 0 22 22" fill="none"><rect x="3" y="5" width="16" height="13" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M7 5V4a4 4 0 0 1 8 0v1" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg></div>
                <div class="empty-title">No items found</div>
                <div class="empty-sub">No picking lines for this order.</div>
              </div>
            </td></tr>
            <?php else: ?>
            <?php foreach ($rows as $r): ?>
            <tr data-status="<?php echo $r['p_user'] !== '0' ? 'picked' : 'pending'; ?>">

              <!-- # -->
              <td><div class="cell" style="color:var(--text3)"><?php echo $r['sno']; ?></div></td>

              <!-- SKU -->
              <td>
                <div class="sku-cell">
                  <div class="sku-icon">
                    <svg viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 5h5M4.5 7h5M4.5 9h3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
                  </div>
                  <div>
                    <div class="sku-name"><?php echo htmlspecialchars($r['prod_name'] ?: '—'); ?></div>
                    <div class="sku-code"><?php echo htmlspecialchars($r['prd']); ?></div>
                  </div>
                </div>
              </td>

              <!-- Barcode -->
              <td><div class="cell mono"><?php echo htmlspecialchars($r['ds'] ?: '—'); ?></div></td>

              <!-- Batch -->
              <td><div class="cell mono"><?php echo htmlspecialchars($r['bth'] ?: '—'); ?></div></td>

              <!-- Location -->
              <td><div class="cell"><span class="loc-tag"><?php echo htmlspecialchars($r['lc']); ?></span></div></td>

              <!-- Qty -->
              <td>
                <div class="qty-cell">
                  <div class="qty-main"><?php echo number_format($r['qt_total']); ?></div>
                </div>
              </td>

              <!-- Cartons -->
              <td><div class="cell" style="font-weight:600"><?php echo $r['whole1']; ?></div></td>

              <!-- Loose -->
              <td><div class="cell"><?php echo $r['loose1']; ?></div></td>

              <!-- Balance -->
              <td><div class="cell mono"><?php echo $r['rmn'] ?: '—'; ?></div></td>

              <!-- Status -->
              <td><div class="cell">
                <?php if ($r['p_user'] !== '0'): ?>
                  <span class="badge b-green">Picked</span>
                <?php else: ?>
                  <span class="badge b-amber">Pending</span>
                <?php endif; ?>
              </div></td>

              <!-- Action -->
              <td class="action-cell">
                <form method="POST" action="">
                  <input type="hidden" name="prdt" value="<?php echo htmlspecialchars($r['prd']); ?>">
                  <input type="hidden" name="bdj"  value="<?php echo htmlspecialchars($r['bth']); ?>">
                  <input type="hidden" name="loc"  value="<?php echo htmlspecialchars($r['lc']); ?>">
                  <input type="hidden" name="sub1" value="<?php echo htmlspecialchars($pick_no); ?>">
                  <input type="hidden" name="dt"   value="<?php echo htmlspecialchars($dt2); ?>">
                  <?php if ($r['p_user'] === '0'): ?>
                    <button type="submit" name="sub12" value="1" class="pick-btn">
                      <svg viewBox="0 0 12 12" fill="none"><path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                      Mark Picked
                    </button>
                  <?php else: ?>
                    <button type="button" disabled class="pick-btn">
                      <svg viewBox="0 0 12 12" fill="none"><path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                      <?php echo htmlspecialchars($r['new_date']); ?>
                    </button>
                  <?php endif; ?>
                </form>
              </td>

            </tr>
            <?php endforeach; ?>

            <!-- Totals row -->
            <tr class="totals-row">
              <td colspan="5"><div class="cell" style="color:var(--text2)">Totals</div></td>
              <td><div class="cell" style="font-size:14px;font-weight:700"><?php echo number_format($first12); ?></div></td>
              <td><div class="cell" style="font-weight:700"><?php echo $whole2; ?></div></td>
              <td><div class="cell" style="font-weight:700"><?php echo $loose2; ?></div></td>
              <td colspan="3"></td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="tbl-footer">
        <div class="tbl-footer-note"><strong><?php echo $total_rows; ?> line<?php echo $total_rows!=1?'s':''; ?></strong> · <?php echo $picked_count; ?> picked · <?php echo $total_rows-$picked_count; ?> remaining</div>
        <div class="tbl-footer-note">Last refreshed: <?php echo date('d M Y, H:i'); ?></div>
      </div>
    </div>

  </div><!-- /.main -->
</div><!-- /.layout -->

<script>
document.querySelectorAll('.nav-grp-hdr').forEach(function(h) {
  h.addEventListener('click', function() { h.parentElement.classList.toggle('open'); });
});

function filterRows(v) {
  v = v.toLowerCase();
  document.querySelectorAll('#pt tbody tr:not(.totals-row)').forEach(function(r) {
    r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none';
  });
}

function setFilter(type, btn) {
  document.querySelectorAll('.filter-btn').forEach(function(b) { b.classList.remove('active'); });
  btn.classList.add('active');
  document.querySelectorAll('#pt tbody tr:not(.totals-row)').forEach(function(r) {
    var status = r.getAttribute('data-status');
    if (type === 'all')     r.style.display = '';
    else r.style.display = (status === type) ? '' : 'none';
  });
}
</script>
</body>
</html>