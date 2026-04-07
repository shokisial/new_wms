<?php
session_start();
if (empty($_SESSION['id']))     { header('Location:../index.php'); exit; }
if (empty($_SESSION['branch'])) { header('Location:../index.php'); exit; }
$branch = $_SESSION['branch'];
$id     = $_SESSION['id'];
$name   = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';

include('conn/dbcon.php');

// Fetch pending delivery notes (not yet assigned a gate pass)
$orders = [];
$grand  = 0;
$query  = mysqli_query($con,
  "SELECT *, SUM(stockout_qty - hold_qty - hold_qty) as dnqty
   FROM stockout
   WHERE branch_id='$branch' AND gatepass_id='0'
   GROUP BY stockout_orderno"
) or die(mysqli_error($con));
while ($row = mysqli_fetch_array($query)) {
  $grand += $row['dnqty'];
  $orders[] = $row;
}
$order_count = count($orders);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sovereign WMS — New Outward Gate Pass</title>
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
    .nav-item { display:flex; align-items:center; gap:9px; padding:7px 14px; color:#7a8ba8; font-size:12px; text-decoration:none; position:relative; transition:background .12s, color .12s; }
    .nav-item:hover { background:#1e2a42; color:#c8d3e8; }
    .nav-item svg { width:14px; height:14px; flex-shrink:0; opacity:.55; }
    .nav-item:hover svg { opacity:1; }
    .nav-grp-hdr { display:flex; align-items:center; gap:9px; padding:7px 14px; cursor:pointer; color:#7a8ba8; font-size:12px; transition:background .12s, color .12s; }
    .nav-grp-hdr:hover { background:#1e2a42; color:#c8d3e8; }
    .nav-grp-hdr svg.ic { width:14px; height:14px; flex-shrink:0; opacity:.55; }
    .nav-grp-hdr svg.ch { margin-left:auto; width:11px; height:11px; opacity:.4; transition:transform .18s; }
    .nav-grp.open .nav-grp-hdr { color:#c8d3e8; }
    .nav-grp.open .nav-grp-hdr svg.ch { transform:rotate(90deg); opacity:.7; }
    .nav-grp.open .nav-grp-hdr svg.ic { opacity:1; }
    .nav-sub-list { display:none; padding:2px 0; }
    .nav-grp.open .nav-sub-list { display:block; }
    .nav-sub { display:block; padding:5.5px 14px 5.5px 36px; color:#5c6e8a; font-size:11.5px; text-decoration:none; transition:color .12s, background .12s; }
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
    .ph-right { display:flex; gap:8px; align-items:center; }

    /* ── Two-panel layout ── */
    .panels { display:grid; grid-template-columns:1fr 1.4fr; gap:18px; align-items:start; }

    /* ── Card ── */
    .card { background:var(--white); border:1px solid var(--border); border-radius:10px; overflow:hidden; }
    .card-hdr { padding:13px 16px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; gap:10px; }
    .card-hdr-left { display:flex; align-items:center; gap:9px; }
    .card-hdr-icon { width:30px; height:30px; border-radius:7px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .chi-blue   { background:var(--blue-bg);     color:var(--blue); }
    .chi-orange { background:var(--orange-muted); color:var(--orange); }
    .card-hdr-icon svg { width:14px; height:14px; }
    .card-hdr-title { font-size:13px; font-weight:600; color:var(--text1); }
    .card-hdr-sub   { font-size:10.5px; color:var(--text3); margin-top:1px; }
    .card-body { padding:0; }

    /* ── DN selection table ── */
    .dn-search { padding:10px 14px; border-bottom:1px solid var(--border); position:relative; }
    .dn-search svg { position:absolute; left:22px; top:50%; transform:translateY(-50%); width:13px; height:13px; color:var(--text3); pointer-events:none; }
    .dn-search input { width:100%; padding:7px 10px 7px 30px; border:1px solid var(--border2); border-radius:7px; font-size:12px; font-family:'Inter',sans-serif; color:var(--text1); background:var(--bg); outline:none; transition:border .15s; }
    .dn-search input:focus { border-color:#9aafcf; background:var(--white); }

    .dn-list { max-height:420px; overflow-y:auto; }
    .dn-list::-webkit-scrollbar { width:3px; }
    .dn-list::-webkit-scrollbar-thumb { background:var(--border2); border-radius:3px; }

    .dn-item { display:flex; align-items:center; gap:12px; padding:11px 14px; border-bottom:1px solid #f2f0ec; cursor:pointer; transition:background .1s; }
    .dn-item:last-child { border-bottom:none; }
    .dn-item:hover { background:#faf9f7; }
    .dn-item.selected { background:var(--orange-muted); }

    /* custom checkbox */
    .dn-check { width:16px; height:16px; border:1.5px solid var(--border2); border-radius:4px; flex-shrink:0; display:flex; align-items:center; justify-content:center; transition:all .12s; background:var(--white); }
    .dn-item.selected .dn-check { background:var(--orange); border-color:var(--orange); }
    .dn-check svg { width:9px; height:9px; color:#fff; opacity:0; }
    .dn-item.selected .dn-check svg { opacity:1; }
    .dn-check input[type=checkbox] { display:none; }

    .dn-info { flex:1; min-width:0; }
    .dn-no   { font-family:'JetBrains Mono',monospace; font-size:12px; font-weight:600; color:var(--text1); }
    .dn-meta { font-size:10.5px; color:var(--text3); margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .dn-qty  { font-size:12px; font-weight:700; color:var(--text1); text-align:right; white-space:nowrap; }
    .dn-city { font-size:10px; color:var(--blue); background:var(--blue-bg); border:1px solid var(--blue-bd); border-radius:4px; padding:1px 6px; white-space:nowrap; }

    .dn-footer { padding:10px 14px; border-top:1px solid var(--border); background:#fafaf8; display:flex; align-items:center; justify-content:space-between; }
    .dn-footer-note { font-size:11px; color:var(--text3); }
    .dn-footer-note strong { color:var(--text2); }
    .sel-count { font-size:11px; font-weight:600; color:var(--orange); }

    /* ── Form card ── */
    .form-body { padding:18px 20px; display:flex; flex-direction:column; gap:16px; }

    .form-section-title { font-size:10px; font-weight:700; color:var(--text3); text-transform:uppercase; letter-spacing:.1em; padding-bottom:8px; border-bottom:1px solid var(--border); }

    .form-grid { display:grid; gap:12px; }
    .form-grid-2 { grid-template-columns:1fr 1fr; }
    .form-grid-3 { grid-template-columns:1fr 1fr 1fr; }

    .form-group { display:flex; flex-direction:column; gap:5px; }
    .form-group.full { grid-column:1/-1; }
    .form-label { font-size:10.5px; font-weight:600; color:var(--text3); text-transform:uppercase; letter-spacing:.06em; }
    .form-label span { color:var(--red); margin-left:2px; }
    .form-input, .form-select, .form-textarea {
      padding:8px 12px; border:1px solid var(--border2); border-radius:7px;
      font-size:12px; font-family:'Inter',sans-serif; color:var(--text1);
      background:var(--bg); outline:none; transition:border .15s, background .15s; width:100%;
    }
    .form-input:focus, .form-select:focus, .form-textarea:focus { border-color:#9aafcf; background:var(--white); }
    .form-input::placeholder { color:var(--text3); }
    .form-select {
      appearance:none;
      background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%239e9c96' stroke-width='1.3' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
      background-repeat:no-repeat; background-position:right 10px center; padding-right:28px;
    }
    .form-textarea { resize:vertical; min-height:56px; }

    /* Form footer */
    .form-ftr { padding:14px 20px; border-top:1px solid var(--border); background:#fafaf8; display:flex; justify-content:flex-end; gap:8px; }
    .btn-save { display:inline-flex; align-items:center; gap:6px; padding:8px 20px; border-radius:7px; font-size:12.5px; font-weight:600; cursor:pointer; border:none; font-family:'Inter',sans-serif; transition:all .13s; background:var(--navy); color:#fff; }
    .btn-save:hover { background:var(--navy-mid); }
    .btn-save svg { width:13px; height:13px; }
    .btn-cancel { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:7px; font-size:12px; font-weight:500; cursor:pointer; border:1px solid var(--border2); font-family:'Inter',sans-serif; transition:all .13s; background:var(--white); color:var(--text2); text-decoration:none; }
    .btn-cancel:hover { background:var(--bg2); }

    /* Empty state */
    .empty-state { padding:36px 20px; text-align:center; }
    .empty-icon { width:44px; height:44px; border-radius:12px; background:var(--bg2); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; margin:0 auto 12px; }
    .empty-icon svg { width:20px; height:20px; color:var(--text3); }
    .empty-title { font-size:13px; font-weight:600; color:var(--text1); margin-bottom:4px; }
    .empty-sub   { font-size:11.5px; color:var(--text2); }

    /* Select-all bar */
    .sel-bar { padding:8px 14px; border-bottom:1px solid var(--border); background:var(--bg); display:flex; align-items:center; justify-content:space-between; }
    .sel-all-btn { font-size:11px; font-weight:500; color:var(--blue); cursor:pointer; border:none; background:none; font-family:'Inter',sans-serif; padding:0; }
    .sel-all-btn:hover { text-decoration:underline; }
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
        <a href="seg_list.php"            class="nav-sub">Segregation List</a>
        <a href="gatepass_out.php"        class="nav-sub active">Gate Pass</a>
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
      <a href="gatepass_out.php">Gate Pass</a>
      <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
      New Gate Pass
    </div>

    <!-- Page Header -->
    <div class="ph">
      <div class="ph-left">
        <div class="title">New Outward Gate Pass</div>
        <div class="sub">Select delivery notes and fill vehicle details to create a gate pass</div>
      </div>
      <div class="ph-right">
        <a href="gatepass_out.php" class="btn-cancel">
          <svg viewBox="0 0 13 13" fill="none"><path d="M8 2L3 6.5 8 11" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Back
        </a>
      </div>
    </div>

    <!-- Two-panel form -->
    <form method="POST" action="gatepass_out_add.php" name="optionlist" enctype="multipart/form-data">
    <div class="panels">

      <!-- LEFT: D.N. Selection -->
      <div class="card">
        <div class="card-hdr">
          <div class="card-hdr-left">
            <div class="card-hdr-icon chi-blue">
              <svg viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 4h5M4.5 6.5h5M4.5 9h3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
            </div>
            <div>
              <div class="card-hdr-title">Pending Delivery Notes</div>
              <div class="card-hdr-sub"><?php echo $order_count; ?> orders awaiting dispatch</div>
            </div>
          </div>
        </div>

        <?php if (empty($orders)): ?>
        <div class="empty-state">
          <div class="empty-icon"><svg viewBox="0 0 22 22" fill="none"><rect x="3" y="3" width="16" height="16" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M7 11h8M7 7h8M7 15h5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg></div>
          <div class="empty-title">No pending orders</div>
          <div class="empty-sub">All delivery notes have been assigned to a gate pass.</div>
        </div>
        <?php else: ?>
        <div class="sel-bar">
          <span class="dn-footer-note">Tick to include in this gate pass</span>
          <button type="button" class="sel-all-btn" onclick="toggleAll()">Select all</button>
        </div>
        <div class="dn-search">
          <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
          <input type="text" placeholder="Search D.N., distributor, city…" oninput="filterDN(this.value)">
        </div>
        <div class="dn-list" id="dn-list">
          <?php foreach ($orders as $row): ?>
          <label class="dn-item" id="dni-<?php echo htmlspecialchars($row['stockout_orderno']); ?>">
            <div class="dn-check">
              <input type="checkbox" name="grn_no[]" value="<?php echo htmlspecialchars($row['stockout_orderno']); ?>">
              <svg viewBox="0 0 9 9" fill="none"><path d="M1.5 4.5l2.5 2.5 3.5-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="dn-info">
              <div class="dn-no"><?php echo htmlspecialchars($row['stockout_orderno']); ?></div>
              <div class="dn-meta">
                <?php echo htmlspecialchars($row['stockout_deliveryno'] . ($row['stockout_truckno'] ? ' · ' . $row['stockout_truckno'] : '')); ?>
                &nbsp;·&nbsp; <?php echo htmlspecialchars($row['dealer_code'] ?: '—'); ?>
              </div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px">
              <span class="dn-qty"><?php echo number_format($row['dnqty']); ?></span>
              <?php if ($row['city']): ?>
              <span class="dn-city"><?php echo htmlspecialchars($row['city']); ?></span>
              <?php endif; ?>
            </div>
          </label>
          <?php endforeach; ?>
        </div>
        <div class="dn-footer">
          <span class="dn-footer-note">Total: <strong><?php echo number_format($grand); ?></strong> units</span>
          <span class="sel-count" id="sel-count">0 selected</span>
        </div>
        <?php endif; ?>
      </div>

      <!-- RIGHT: Vehicle / Driver Details -->
      <div class="card">
        <div class="card-hdr">
          <div class="card-hdr-left">
            <div class="card-hdr-icon chi-orange">
              <svg viewBox="0 0 14 14" fill="none"><rect x="1" y="5" width="12" height="7" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M3 5V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/><circle cx="4" cy="12" r="1" fill="currentColor"/><circle cx="10" cy="12" r="1" fill="currentColor"/></svg>
            </div>
            <div>
              <div class="card-hdr-title">Vehicle &amp; Gate Pass Details</div>
              <div class="card-hdr-sub">All fields marked * are required</div>
            </div>
          </div>
        </div>

        <div class="form-body">

          <!-- Gate Pass / Transporter -->
          <div>
            <div class="form-section-title">Gate Pass Info</div>
            <div class="form-grid form-grid-2" style="margin-top:12px">
              <div class="form-group">
                <label class="form-label">Gate Pass No.<span>*</span></label>
                <input type="number" name="gpsno" class="form-input" placeholder="e.g. 1050" required>
              </div>
              <div class="form-group">
                <label class="form-label">Transporter Name<span>*</span></label>
                <input type="text" name="trns_name" class="form-input" placeholder="Transporter name" required>
              </div>
              <div class="form-group">
                <label class="form-label">Seal No.<span>*</span></label>
                <input type="text" name="seal_no" class="form-input" placeholder="Seal number" required>
              </div>
              <div class="form-group">
                <label class="form-label">Bilty No.<span>*</span></label>
                <input type="text" name="bilty" class="form-input" placeholder="Bilty number" required>
              </div>
            </div>
          </div>

          <!-- Vehicle -->
          <div>
            <div class="form-section-title">Vehicle Details</div>
            <div class="form-grid form-grid-3" style="margin-top:12px">
              <div class="form-group">
                <label class="form-label">Vehicle No.<span>*</span></label>
                <input type="text" name="vehicle_no" class="form-input" placeholder="e.g. LEA-1234" required>
              </div>
              <div class="form-group">
                <label class="form-label">Vehicle Type<span>*</span></label>
                <select name="vehicle_type" class="form-select" required>
                  <option value="">Select</option>
                  <?php foreach (['45FT','40FT','20FT','18FT','16FT','14FT','Pickup','Other'] as $vt): ?>
                  <option value="<?php echo $vt; ?>"><?php echo $vt; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Dock No.</label>
                <select name="dock" class="form-select">
                  <option value="">Select</option>
                  <?php for ($d = 1; $d <= 10; $d++): ?>
                  <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                  <?php endfor; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Vehicle Temp.<span>*</span></label>
                <input type="text" name="veh_temp" class="form-input" placeholder="°C" required>
              </div>
              <div class="form-group">
                <label class="form-label">Product Temp.<span>*</span></label>
                <input type="text" name="item_temp" class="form-input" placeholder="°C" required>
              </div>
              <div class="form-group">
                <label class="form-label">Other Detail</label>
                <input type="text" name="veh_other" class="form-input" placeholder="Optional">
              </div>
            </div>
          </div>

          <!-- Driver -->
          <div>
            <div class="form-section-title">Driver Details</div>
            <div class="form-grid form-grid-2" style="margin-top:12px">
              <div class="form-group">
                <label class="form-label">Driver Name<span>*</span></label>
                <input type="text" name="driver" class="form-input" placeholder="Full name" required>
              </div>
              <div class="form-group">
                <label class="form-label">Driver CNIC<span>*</span></label>
                <input type="text" name="cnic" class="form-input" placeholder="xxxxx-xxxxxxx-x" required>
              </div>
              <div class="form-group">
                <label class="form-label">Driver Mobile<span>*</span></label>
                <input type="text" name="mobile" class="form-input" placeholder="03xx-xxxxxxx" required>
              </div>
              <div class="form-group">
                <label class="form-label">Remarks</label>
                <input type="text" name="remarks" class="form-input" placeholder="Optional">
              </div>
            </div>
          </div>

          <!-- Timing -->
          <div>
            <div class="form-section-title">Timing</div>
            <div class="form-grid form-grid-2" style="margin-top:12px">
              <div class="form-group">
                <label class="form-label">In Time<span>*</span></label>
                <input type="datetime-local" name="indate" class="form-input" required>
              </div>
              <div class="form-group">
                <label class="form-label">Out Time<span>*</span></label>
                <input type="datetime-local" name="outdate" class="form-input" required>
              </div>
            </div>
          </div>

        </div><!-- /.form-body -->

        <div class="form-ftr">
          <a href="gatepass_out.php" class="btn-cancel">Cancel</a>
          <button type="submit" name="optionlist" class="btn-save">
            <svg viewBox="0 0 13 13" fill="none"><path d="M2 7l3.5 3.5L11 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Create Gate Pass
          </button>
        </div>
      </div>

    </div><!-- /.panels -->
    </form>

  </div><!-- /.main -->
</div><!-- /.layout -->

<script>
// Sidebar toggles
document.querySelectorAll('.nav-grp-hdr').forEach(function(h) {
  h.addEventListener('click', function() { h.parentElement.classList.toggle('open'); });
});

// Checkbox visual toggle
document.querySelectorAll('.dn-item').forEach(function(item) {
  item.addEventListener('click', function(e) {
    // avoid double-toggle if clicking the actual checkbox
    var cb = item.querySelector('input[type=checkbox]');
    if (e.target !== cb) cb.checked = !cb.checked;
    item.classList.toggle('selected', cb.checked);
    updateSelCount();
  });
});

function updateSelCount() {
  var n = document.querySelectorAll('.dn-item.selected').length;
  var el = document.getElementById('sel-count');
  if (el) el.textContent = n + ' selected';
}

var allSelected = false;
function toggleAll() {
  allSelected = !allSelected;
  document.querySelectorAll('.dn-item').forEach(function(item) {
    var cb = item.querySelector('input[type=checkbox]');
    cb.checked = allSelected;
    item.classList.toggle('selected', allSelected);
  });
  updateSelCount();
  document.querySelector('.sel-all-btn').textContent = allSelected ? 'Deselect all' : 'Select all';
}

function filterDN(v) {
  v = v.toLowerCase();
  document.querySelectorAll('.dn-item').forEach(function(item) {
    item.style.display = item.textContent.toLowerCase().includes(v) ? '' : 'none';
  });
}
</script>
</body>
</html>