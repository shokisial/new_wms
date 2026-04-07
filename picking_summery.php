<?php
session_start();
if (empty($_SESSION['id']))     { header('Location:../index.php'); exit; }
if (empty($_SESSION['branch'])) { header('Location:../index.php'); exit; }
$branch = $_SESSION['branch'];
$id     = $_SESSION['id'];
$name   = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';

include('conn/dbcon.php');

// Fetch picking summary grouped by picking_no + date
$pickings = array();
$qp = mysqli_query($con,
  "SELECT picking_no, dat, COUNT(*) as item_count
   FROM location_temp
   WHERE picked_user='0' AND branch_id='$branch'
   GROUP BY picking_no, dat
   ORDER BY picking_no DESC"
) or die(mysqli_error($con));
while ($r = mysqli_fetch_array($qp)) { $pickings[] = $r; }

$total_pickings = count($pickings);
$total_items    = array_sum(array_column($pickings, 'item_count'));

// Helper: relative date
function relativeDate($datestr) {
  if (!$datestr) return '—';
  $ts   = strtotime($datestr);
  if (!$ts) return $datestr;
  $diff = floor((time() - $ts) / 86400);
  if ($diff == 0)  return 'Today';
  if ($diff == 1)  return '1 day ago';
  return $diff . ' days ago';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sovereign WMS — Picking Summary</title>
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
    .nav-item.active { color: #fff; }
    .nav-item.active::after { content: ''; position: absolute; right: 0; top: 6px; bottom: 6px; width: 2.5px; background: var(--orange); border-radius: 2px 0 0 2px; }
    .nav-item svg { width: 14px; height: 14px; flex-shrink: 0; opacity: .55; }
    .nav-item:hover svg, .nav-item.active svg { opacity: 1; }
    .nav-grp-hdr { display: flex; align-items: center; gap: 9px; padding: 7px 14px; cursor: pointer; color: #7a8ba8; font-size: 12px; transition: background .12s, color .12s; }
    .nav-grp-hdr:hover { background: #1e2a42; color: #c8d3e8; }
    .nav-grp-hdr svg.ic { width: 14px; height: 14px; flex-shrink: 0; opacity: .55; }
    .nav-grp-hdr:hover svg.ic, .nav-grp.open .nav-grp-hdr svg.ic { opacity: 1; }
    .nav-grp-hdr svg.ch { margin-left: auto; width: 11px; height: 11px; opacity: .4; transition: transform .18s; }
    .nav-grp.open .nav-grp-hdr { color: #c8d3e8; }
    .nav-grp.open .nav-grp-hdr svg.ch { transform: rotate(90deg); opacity: .7; }
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
    .ph { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 20px; }
    .ph-left .title { font-size: 18px; font-weight: 600; color: var(--text1); letter-spacing: -.4px; }
    .ph-left .sub { font-size: 12px; color: var(--text2); margin-top: 3px; }

    /* Stats */
    .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
    .stat-card { background: var(--white); border: 1px solid var(--border); border-radius: 10px; padding: 14px 16px; display: flex; align-items: center; gap: 12px; }
    .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .stat-icon svg { width: 18px; height: 18px; }
    .si-navy   { background: var(--navy);      color: #fff; }
    .si-orange { background: var(--orange-muted); color: var(--orange); }
    .si-green  { background: var(--green-bg);  color: var(--green); }
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
    .search-wrap input { padding: 7px 10px 7px 30px; border: 1px solid var(--border2); border-radius: 7px; font-size: 12px; font-family: 'Inter', sans-serif; color: var(--text1); background: var(--bg); outline: none; width: 210px; transition: border .15s; }
    .search-wrap input:focus { border-color: #9aafcf; background: var(--white); }

    /* Table */
    .tbl-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th { font-size: 10px; font-weight: 600; color: var(--text3); text-transform: uppercase; letter-spacing: .06em; padding: 9px 14px; border-bottom: 1px solid var(--border); text-align: left; white-space: nowrap; background: #fafaf8; }
    td { padding: 0; border-bottom: 1px solid #f2f0ec; vertical-align: middle; }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover td { background: #faf9f7; }
    tbody tr:hover .row-action { opacity: 1; }

    /* Picking No cell */
    .pick-cell { display: flex; align-items: center; gap: 10px; padding: 13px 14px; }
    .pick-icon { width: 32px; height: 32px; border-radius: 7px; background: var(--bg2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all .13s; }
    .pick-icon svg { width: 14px; height: 14px; color: var(--text2); }
    tbody tr:hover .pick-icon { background: var(--orange-muted); border-color: var(--orange-border); }
    tbody tr:hover .pick-icon svg { color: var(--orange); }
    .pick-num { font-family: 'JetBrains Mono', monospace; font-size: 12px; font-weight: 600; color: var(--text1); }
    .pick-label { font-size: 10.5px; color: var(--text3); margin-top: 1px; }

    /* Date cell */
    .date-cell { padding: 13px 14px; }
    .date-val { font-size: 12px; font-weight: 500; color: var(--text1); }
    .date-rel { font-size: 10.5px; color: var(--text3); margin-top: 2px; }

    /* Items cell */
    .items-cell { padding: 13px 14px; }
    .items-main { font-size: 14px; font-weight: 700; color: var(--text1); line-height: 1; }
    .items-sub { font-size: 10.5px; color: var(--text3); margin-top: 3px; }

    /* Badge */
    .badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
    .badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; }
    .b-amber { background: var(--amber-bg); color: var(--amber); border: 1px solid var(--amber-bd); }
    .b-amber::before { background: var(--amber); }

    /* Action */
    .row-action { opacity: 0; padding: 13px 14px; transition: opacity .13s; }
    .act-btn { display: inline-flex; align-items: center; gap: 5px; padding: 6px 13px; border-radius: 6px; font-size: 11.5px; font-weight: 500; cursor: pointer; border: none; font-family: 'Inter', sans-serif; transition: all .13s; white-space: nowrap; background: var(--navy); color: #fff; text-decoration: none; }
    .act-btn:hover { background: var(--navy-mid); }
    .act-btn svg { width: 12px; height: 12px; }

    /* Footer */
    .tbl-footer { padding: 10px 14px; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; background: #fafaf8; }
    .tbl-footer-note { font-size: 11px; color: var(--text3); }
    .tbl-footer-note strong { color: var(--text2); }

    /* Empty */
    .empty-state { padding: 52px 20px; text-align: center; }
    .empty-icon { width: 52px; height: 52px; border-radius: 14px; background: var(--bg2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; }
    .empty-icon svg { width: 22px; height: 22px; color: var(--text3); }
    .empty-title { font-size: 14px; font-weight: 600; color: var(--text1); margin-bottom: 5px; }
    .empty-sub { font-size: 12px; color: var(--text2); }
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
    <div class="crumb">
      <a href="#">Outbound</a>
      <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Picking Summary
    </div>

    <div class="ph">
      <div class="ph-left">
        <div class="title">Picking Summary</div>
        <div class="sub">Click a picking number to view and process its details — Unit: <?php echo htmlspecialchars($branch); ?></div>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon si-navy">
          <svg viewBox="0 0 18 18" fill="none"><rect x="2" y="4" width="14" height="11" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 4V3a3 3 0 0 1 6 0v1" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Open Pick Lists</div>
          <div class="stat-value"><?php echo $total_pickings; ?></div>
          <div class="stat-sub">Awaiting processing</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-orange">
          <svg viewBox="0 0 18 18" fill="none"><rect x="3" y="2" width="12" height="14" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 6h6M6 9h6M6 12h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Total Line Items</div>
          <div class="stat-value"><?php echo number_format($total_items); ?></div>
          <div class="stat-sub">Across <?php echo $total_pickings; ?> pick lists</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-green">
          <svg viewBox="0 0 18 18" fill="none"><path d="M9 3a6 6 0 1 0 0 12A6 6 0 0 0 9 3z" stroke="currentColor" stroke-width="1.3"/><path d="M6.5 9l2 2 3-3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Status</div>
          <div class="stat-value" style="font-size:15px">Pending</div>
          <div class="stat-sub">Not yet picked</div>
        </div>
      </div>
    </div>

    <!-- Table Card -->
    <div class="card">
      <div class="card-hdr">
        <div class="card-hdr-title">Pending pick lists</div>
        <div class="toolbar">
          <button class="filter-btn active" onclick="setFilter('all',this)">All</button>
          <button class="filter-btn" onclick="setFilter('today',this)">Today</button>
          <div class="search-wrap">
            <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
            <input type="text" id="si" placeholder="Search picking no or date…" oninput="filterRows(this.value)">
          </div>
        </div>
      </div>

      <div class="tbl-wrap">
        <table id="pt">
          <thead>
            <tr>
              <th>Picking No.</th>
              <th>Date</th>
              <th>Items</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($pickings)): ?>
            <tr><td colspan="5" style="border:none">
              <div class="empty-state">
                <div class="empty-icon">
                  <svg viewBox="0 0 22 22" fill="none"><rect x="3" y="5" width="16" height="13" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M7 5V4a4 4 0 0 1 8 0v1" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                </div>
                <div class="empty-title">No pending pick lists</div>
                <div class="empty-sub">All picking orders have been processed for this branch.</div>
              </div>
            </td></tr>
            <?php else: ?>
              <?php foreach ($pickings as $row):
                $rel      = relativeDate($row['dat']);
                $date_fmt = $row['dat'] ? date('d M Y', strtotime($row['dat'])) : $row['dat'];
              ?>
              <tr>
                <!-- Picking No -->
                <td>
                  <div class="pick-cell">
                    <div class="pick-icon">
                      <svg viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 5h5M4.5 7h5M4.5 9h3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
                    </div>
                    <div>
                      <div class="pick-num"><?php echo htmlspecialchars($row['picking_no']); ?></div>
                      <div class="pick-label">Pick List</div>
                    </div>
                  </div>
                </td>

                <!-- Date -->
                <td>
                  <div class="date-cell">
                    <div class="date-val"><?php echo htmlspecialchars($date_fmt ?: '—'); ?></div>
                    <div class="date-rel"><?php echo $rel; ?></div>
                  </div>
                </td>

                <!-- Items -->
                <td>
                  <div class="items-cell">
                    <div class="items-main"><?php echo number_format($row['item_count']); ?></div>
                    <div class="items-sub">Line items</div>
                  </div>
                </td>

                <!-- Status -->
                <td style="padding:13px 14px">
                  <span class="badge b-amber">Pending</span>
                </td>

                <!-- Action -->
                <td class="row-action">
                  <form method="POST" action="summeryno2.php" style="display:inline">
                    <input type="hidden" name="dt"  value="<?php echo htmlspecialchars($row['dat']); ?>">
                    <button type="submit" name="sub" value="<?php echo htmlspecialchars($row['picking_no']); ?>" class="act-btn">
                      <svg viewBox="0 0 12 12" fill="none"><path d="M2 6h8M6 2l4 4-4 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                      Open
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="tbl-footer">
        <div class="tbl-footer-note"><strong><?php echo $total_pickings; ?> pick list<?php echo $total_pickings != 1 ? 's' : ''; ?></strong> · <?php echo number_format($total_items); ?> total line items</div>
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
  document.querySelectorAll('#pt tbody tr').forEach(function(r) {
    r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none';
  });
}

function setFilter(type, btn) {
  document.querySelectorAll('.filter-btn').forEach(function(b) { b.classList.remove('active'); });
  btn.classList.add('active');
  var today = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
  document.querySelectorAll('#pt tbody tr').forEach(function(r) {
    if (type === 'all') { r.style.display = ''; }
    else if (type === 'today') { r.style.display = r.textContent.includes('Today') ? '' : 'none'; }
  });
}
</script>
</body>
</html>