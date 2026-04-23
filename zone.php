<?php
session_start();
if (empty($_SESSION['id']))     { header('Location:../index.php'); exit; }
if (empty($_SESSION['branch'])) { header('Location:../index.php'); exit; }
$branch = $_SESSION['branch'];
$id     = $_SESSION['id'];
$name   = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$user_group = $_SESSION['user_group']; 

include('conn/dbcon.php');

// ── Fetch zones ───────────────────────────────────────────────────────────────
$zones = [];
$q = mysqli_query($con, "SELECT * FROM zone WHERE branch_id = '$branch' ORDER BY zone_name ASC")
     or die(mysqli_error($con));
while ($r = mysqli_fetch_assoc($q)) $zones[] = $r;
$total = count($zones);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sovereign WMS — Zone List</title>
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
    .topbar { position:fixed; top:0; left:0; right:0; height:var(--topbar-h); background:var(--navy); display:flex; align-items:center; padding:0 18px; gap:10px; z-index:200; border-bottom:2px solid var(--orange); }
    .logo-mark { display:flex; align-items:center; width:30px; height:30px; flex-shrink:0; }
    .brand .b1 { font-size:14px; font-weight:600; color:#fff; letter-spacing:-.2px; }
    .brand .b2 { font-size:9px; color:#8a9ab8; letter-spacing:.12em; text-transform:uppercase; margin-top:1px; }
    .topbar-right { margin-left:auto; display:flex; align-items:center; gap:14px; }
    .branch-pill { background:var(--navy-light); border:1px solid #304060; border-radius:6px; padding:4px 10px; display:flex; align-items:center; gap:7px; font-size:11px; color:#8a9ab8; }
    .branch-pill strong { color:#fff; font-weight:500; }
    .avatar { width:30px; height:30px; border-radius:50%; background:var(--orange); display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:600; color:#fff; flex-shrink:0; }

     .layout { display:flex; padding-top:var(--topbar-h); min-height:100vh; width:100%; overflow-x:hidden; }
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
    .main { margin-left:var(--sidebar-w); flex:1; padding:22px 26px 40px; min-width:0; width:calc(100% - var(--sidebar-w)); }
    .crumb { font-size:11px; color:var(--text3); display:flex; align-items:center; gap:5px; margin-bottom:10px; }
    .crumb a { color:var(--text2); text-decoration:none; }
    .ph { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:20px; gap:12px; flex-wrap:wrap; }
    .ph-left .title { font-size:18px; font-weight:600; color:var(--text1); letter-spacing:-.4px; }
    .ph-left .sub   { font-size:12px; color:var(--text2); margin-top:3px; }
    .ph-right { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }

    /* ── Stat cards ── */
    .stats-row { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:20px; }
    .stat-card { background:var(--white); border:1px solid var(--border); border-radius:10px; padding:14px 16px; display:flex; align-items:center; gap:12px; }
    .stat-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .stat-icon svg { width:18px; height:18px; }
    .si-navy   { background:var(--navy);         color:#fff; }
    .si-orange { background:var(--orange-muted); color:var(--orange); }
    .si-blue   { background:var(--blue-bg);      color:var(--blue); }
    .stat-label { font-size:10.5px; color:var(--text3); margin-bottom:2px; }
    .stat-value { font-size:20px; font-weight:700; color:var(--text1); letter-spacing:-.5px; line-height:1; }
    .stat-sub { font-size:10px; color:var(--text3); margin-top:2px; }

    /* ── Buttons ── */
    .btn-primary-wms { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; border-radius:7px; font-size:12px; font-weight:500; cursor:pointer; border:none; font-family:'Inter',sans-serif; transition:all .13s; background:var(--navy); color:#fff; text-decoration:none; }
    .btn-primary-wms:hover { background:var(--navy-mid); }
    .btn-outline-wms { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; border-radius:7px; font-size:12px; font-weight:500; cursor:pointer; border:1px solid var(--border2); font-family:'Inter',sans-serif; transition:all .13s; background:var(--white); color:var(--text2); text-decoration:none; }
    .btn-outline-wms:hover { background:var(--bg2); color:var(--text1); }
    .btn-amber-wms { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; border-radius:7px; font-size:12px; font-weight:500; cursor:pointer; border:1px solid var(--amber-bd); font-family:'Inter',sans-serif; transition:all .13s; background:var(--amber-bg); color:var(--amber); }
    .btn-amber-wms:hover { background:#fdefc0; }
    .btn-primary-wms svg, .btn-outline-wms svg, .btn-amber-wms svg { width:13px; height:13px; }
    .btn-danger-wms { display:inline-flex; align-items:center; gap:6px; padding:7px 18px; border-radius:7px; font-size:12px; font-weight:500; cursor:pointer; border:none; font-family:'Inter',sans-serif; transition:all .13s; background:var(--red); color:#fff; }
    .btn-danger-wms:hover { background:#991b1b; }
    .btn-danger-wms svg { width:13px; height:13px; }

    /* ── Card / Table ── */
    .card { background:var(--white); border:1px solid var(--border); border-radius:10px; overflow:hidden; width:100%; }
    .card-hdr { padding:12px 16px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
    .card-hdr-title { font-size:13px; font-weight:600; color:var(--text1); }
    .toolbar { display:flex; align-items:center; gap:8px; }
    .search-wrap { position:relative; }
    .search-wrap svg { position:absolute; left:10px; top:50%; transform:translateY(-50%); width:13px; height:13px; color:var(--text3); pointer-events:none; }
    .search-wrap input { padding:7px 10px 7px 30px; border:1px solid var(--border2); border-radius:7px; font-size:12px; font-family:'Inter',sans-serif; color:var(--text1); background:var(--bg); outline:none; width:210px; transition:border .15s; }
    .search-wrap input:focus { border-color:#9aafcf; background:var(--white); }

    .tbl-wrap { overflow-x:auto; }
    table { width:100%; border-collapse:collapse; }
    th { font-size:10px; font-weight:600; color:var(--text3); text-transform:uppercase; letter-spacing:.06em; padding:9px 14px; border-bottom:1px solid var(--border); text-align:left; white-space:nowrap; background:#fafaf8; }
    td { padding:0; border-bottom:1px solid #f2f0ec; vertical-align:middle; }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:#faf9f7; }
    .cell { padding:11px 14px; font-size:12px; color:var(--text1); }

    /* Zone cell */
    .zone-cell { display:flex; align-items:center; gap:10px; padding:10px 14px; }
    .zone-avatar { width:34px; height:34px; border-radius:8px; background:var(--navy-light); border:1px solid #304060; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; color:#8a9ab8; flex-shrink:0; transition:all .13s; }
    tbody tr:hover .zone-avatar { background:var(--orange-muted); border-color:var(--orange-border); color:var(--orange); }
    .zone-name { font-size:12px; font-weight:500; color:var(--text1); }
    .zone-id   { font-family:'JetBrains Mono',monospace; font-size:10.5px; color:var(--text3); margin-top:1px; }

    /* Action buttons */
    .action-wrap { display:flex; align-items:center; gap:6px; padding:10px 14px; }
    .act-btn { width:28px; height:28px; border-radius:6px; border:1px solid var(--border2); background:var(--white); display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all .13s; }
    .act-btn svg { width:13px; height:13px; color:var(--text3); }
    .act-btn-edit:hover  { background:var(--blue-bg);  border-color:var(--blue-bd); }
    .act-btn-edit:hover svg { color:var(--blue); }
    .act-btn-del:hover   { background:var(--red-bg);   border-color:var(--red-bd); }
    .act-btn-del:hover svg { color:var(--red); }

    /* Table footer */
    .tbl-footer { padding:10px 14px; border-top:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; background:#fafaf8; }
    .tbl-footer-note { font-size:11px; color:var(--text3); }
    .tbl-footer-note strong { color:var(--text2); }

    /* Empty state */
    .empty-state { padding:52px 20px; text-align:center; }
    .empty-icon { width:52px; height:52px; border-radius:14px; background:var(--bg2); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; margin:0 auto 14px; }
    .empty-icon svg { width:22px; height:22px; color:var(--text3); }
    .empty-title { font-size:14px; font-weight:600; color:var(--text1); margin-bottom:5px; }
    .empty-sub   { font-size:12px; color:var(--text2); }

    /* ── Slide-over drawer ── */
    .overlay { position:fixed; inset:0; background:rgba(0,0,0,.35); z-index:300; opacity:0; pointer-events:none; transition:opacity .22s; }
    .overlay.open { opacity:1; pointer-events:all; }
    .drawer { position:fixed; top:0; right:0; bottom:0; width:400px; max-width:95vw; background:var(--white); z-index:310; transform:translateX(100%); transition:transform .24s cubic-bezier(.4,0,.2,1); display:flex; flex-direction:column; box-shadow:-8px 0 32px rgba(0,0,0,.12); }
    .drawer.open { transform:translateX(0); }
    .drawer-hdr { padding:18px 22px 16px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; flex-shrink:0; }
    .drawer-hdr-left { display:flex; flex-direction:column; gap:3px; }
    .drawer-title { font-size:15px; font-weight:600; color:var(--text1); letter-spacing:-.2px; }
    .drawer-sub   { font-size:11px; color:var(--text3); }
    .drawer-close { width:30px; height:30px; border-radius:7px; border:1px solid var(--border2); background:var(--white); display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all .13s; }
    .drawer-close:hover { background:var(--red-bg); border-color:var(--red-bd); }
    .drawer-close:hover svg { color:var(--red); }
    .drawer-close svg { width:14px; height:14px; color:var(--text3); }
    .drawer-body { flex:1; overflow-y:auto; padding:22px; display:flex; flex-direction:column; gap:14px; }
    .drawer-body::-webkit-scrollbar { width:3px; }
    .drawer-body::-webkit-scrollbar-thumb { background:var(--border2); border-radius:3px; }
    .drawer-footer { padding:16px 22px; border-top:1px solid var(--border); display:flex; gap:10px; justify-content:flex-end; flex-shrink:0; background:var(--bg); }

    /* Form fields inside drawer */
    .form-section { font-size:10px; font-weight:700; color:var(--text3); text-transform:uppercase; letter-spacing:.1em; padding-bottom:8px; border-bottom:1px solid var(--border); margin-bottom:2px; }
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .form-row.full { grid-template-columns:1fr; }
    .field { display:flex; flex-direction:column; gap:5px; }
    .field label { font-size:10.5px; font-weight:600; color:var(--text3); text-transform:uppercase; letter-spacing:.06em; }
    .field input { height:36px; padding:0 12px; border:1px solid var(--border2); border-radius:7px; font-size:12px; font-family:'Inter',sans-serif; color:var(--text1); background:var(--bg); outline:none; transition:border .15s; }
    .field input:focus { border-color:#9aafcf; background:var(--white); }
    .field input::placeholder { color:var(--text3); }

    /* Caution banner */
    .caution-banner { background:var(--amber-bg); border:1px solid var(--amber-bd); border-radius:8px; padding:10px 14px; font-size:11.5px; color:var(--amber); display:flex; align-items:center; gap:8px; }
    .caution-banner svg { width:15px; height:15px; flex-shrink:0; }

    /* Delete confirmation */
    .delete-confirm { background:var(--red-bg); border:1px solid var(--red-bd); border-radius:10px; padding:18px; text-align:center; }
    .delete-confirm-icon { width:44px; height:44px; border-radius:12px; background:#fee2e2; border:1px solid var(--red-bd); display:flex; align-items:center; justify-content:center; margin:0 auto 12px; }
    .delete-confirm-icon svg { width:20px; height:20px; color:var(--red); }
    .delete-confirm-title { font-size:14px; font-weight:600; color:var(--red); margin-bottom:5px; }
    .delete-confirm-sub { font-size:12px; color:var(--text2); }

    @media print {
      .topbar, .sidebar, .ph-right, .toolbar, .action-wrap, .tbl-footer { display:none !important; }
      .main { margin-left:0; padding:10px; }
      .layout { display:block; }
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
    <div class="b1">Sovereign</div>
    <div class="b2">Warehousing &amp; Distribution</div>
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
  <?php include('side_check.php'); ?>
  </aside>

  <!-- Main -->
  <div class="main">

    <!-- Breadcrumb -->
    <div class="crumb">
      <a href="#">Master Data</a>
      <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Zone List
    </div>

    <!-- Page Header -->
    <div class="ph">
      <div class="ph-left">
        <div class="title">Zone List</div>
        <div class="sub"><?php echo htmlspecialchars($branch); ?> &nbsp;·&nbsp; <?php echo $total; ?> zone<?php echo $total != 1 ? 's' : ''; ?> configured</div>
      </div>
      <div class="ph-right">
        <a href="reports/index_zone.php" target="_blank" class="btn-amber-wms">
          <svg viewBox="0 0 13 13" fill="none"><rect x="2" y="1" width="9" height="11" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 4h4M4.5 6.5h4M4.5 9h2.5" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
          Print List
        </a>
        <button onclick="openAddDrawer()" class="btn-primary-wms">
          <svg viewBox="0 0 13 13" fill="none"><path d="M6.5 2v9M2 6.5h9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
          Add Zone
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon si-navy">
          <svg viewBox="0 0 18 18" fill="none"><rect x="2" y="2" width="14" height="14" rx="2.5" stroke="currentColor" stroke-width="1.3"/><path d="M2 7h14M7 7v9" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Total Zones</div>
          <div class="stat-value"><?php echo $total; ?></div>
          <div class="stat-sub">Configured in this branch</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-orange">
          <svg viewBox="0 0 18 18" fill="none"><path d="M9 2l7 4v6l-7 4-7-4V6l7-4z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Branch</div>
          <div class="stat-value" style="font-size:15px"><?php echo htmlspecialchars($branch); ?></div>
          <div class="stat-sub">Current unit</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-blue">
          <svg viewBox="0 0 18 18" fill="none"><rect x="3" y="2" width="12" height="14" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 7h6M6 10h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Last Updated</div>
          <div class="stat-value" style="font-size:15px"><?php echo date('d M Y'); ?></div>
          <div class="stat-sub">Master data</div>
        </div>
      </div>
    </div>

    <!-- Table Card -->
    <div class="card">
      <div class="card-hdr">
        <div class="card-hdr-title">Zones</div>
        <?php if (!empty($zones)): ?>
        <div class="toolbar">
          <div class="search-wrap">
            <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
            <input type="text" id="si" placeholder="Search zone…" oninput="filterRows(this.value)">
          </div>
        </div>
        <?php endif; ?>
      </div>

      <div class="tbl-wrap">
        <table id="zt">
          <thead>
            <tr>
              <th>Zone</th>
              <th>Warehouse Unit</th>
              <th style="text-align:center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($zones)): ?>
            <tr><td colspan="3" style="border:none">
              <div class="empty-state">
                <div class="empty-icon">
                  <svg viewBox="0 0 22 22" fill="none"><rect x="2" y="2" width="18" height="18" rx="3" stroke="currentColor" stroke-width="1.3"/><path d="M2 9h18M9 9v11" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                </div>
                <div class="empty-title">No zones found</div>
                <div class="empty-sub">Click "Add Zone" above to create the first zone for this branch.</div>
              </div>
            </td></tr>
            <?php else: foreach ($zones as $row): ?>
            <tr>
              <!-- Zone -->
              <td>
                <div class="zone-cell">
                  <div class="zone-avatar"><?php echo strtoupper(substr($row['zone_name'], 0, 2)); ?></div>
                  <div>
                    <div class="zone-name"><?php echo htmlspecialchars($row['zone_name']); ?></div>
                    <div class="zone-id">#<?php echo htmlspecialchars($row['zone_id']); ?></div>
                  </div>
                </div>
              </td>

              <!-- Warehouse -->
              <td>
                <div class="cell">
                  <span style="display:inline-flex;align-items:center;gap:5px;background:var(--blue-bg);border:1px solid var(--blue-bd);border-radius:5px;padding:2px 9px;font-size:11px;font-weight:500;color:var(--blue)">
                    Unit <?php echo htmlspecialchars($row['branch_id']); ?>
                  </span>
                </div>
              </td>

              <!-- Actions -->
              <td>
                <div class="action-wrap" style="justify-content:center">
                  <button class="act-btn act-btn-edit" title="Edit"
                    onclick="openEditDrawer(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                    <svg viewBox="0 0 13 13" fill="none"><path d="M9 2l2 2-6 6H3V8l6-6z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  </button>
                  <button class="act-btn act-btn-del" title="Delete"
                    onclick="openDeleteDrawer('<?php echo htmlspecialchars($row['zone_id']); ?>','<?php echo htmlspecialchars(addslashes($row['zone_name'])); ?>')">
                    <svg viewBox="0 0 13 13" fill="none"><path d="M2 3.5h9M5 3.5V2.5h3v1M10 3.5l-.7 7H3.7L3 3.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <?php if (!empty($zones)): ?>
      <div class="tbl-footer">
        <div class="tbl-footer-note"><strong><?php echo $total; ?> zone<?php echo $total != 1 ? 's' : ''; ?></strong> in <?php echo htmlspecialchars($branch); ?></div>
        <div class="tbl-footer-note">Generated: <?php echo date('d M Y, H:i'); ?></div>
      </div>
      <?php endif; ?>
    </div>

  </div><!-- /.main -->
</div><!-- /.layout -->

<!-- ── Overlay ── -->
<div class="overlay" id="overlay" onclick="closeAllDrawers()"></div>

<!-- ── Add Zone Drawer ── -->
<div class="drawer" id="drawer-add">
  <div class="drawer-hdr">
    <div class="drawer-hdr-left">
      <div class="drawer-title">Add New Zone</div>
      <div class="drawer-sub">All fields are required</div>
    </div>
    <button class="drawer-close" onclick="closeAllDrawers()">
      <svg viewBox="0 0 14 14" fill="none"><path d="M3 3l8 8M11 3l-8 8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
    </button>
  </div>
  <form method="POST" action="zone_add.php" enctype="multipart/form-data">
    <div class="drawer-body">
      <div class="form-section">Zone Info</div>
      <div class="form-row full">
        <div class="field">
          <label>Zone Name</label>
          <input type="text" name="zone_name" placeholder="e.g. Zone A" required>
        </div>
      </div>
    </div>
    <div class="drawer-footer">
      <button type="button" class="btn-outline-wms" onclick="closeAllDrawers()">Cancel</button>
      <button type="submit" class="btn-primary-wms">
        <svg viewBox="0 0 13 13" fill="none"><path d="M2 7l3 3 6-6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Save Zone
      </button>
    </div>
  </form>
</div>

<!-- ── Edit Zone Drawer ── -->
<div class="drawer" id="drawer-edit">
  <div class="drawer-hdr">
    <div class="drawer-hdr-left">
      <div class="drawer-title">Edit Zone</div>
      <div class="drawer-sub" id="edit-drawer-sub">Updating zone details</div>
    </div>
    <button class="drawer-close" onclick="closeAllDrawers()">
      <svg viewBox="0 0 14 14" fill="none"><path d="M3 3l8 8M11 3l-8 8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
    </button>
  </div>
  <form method="POST" action="zone_update.php" enctype="multipart/form-data">
    <div class="drawer-body">
      <div class="caution-banner">
        <svg viewBox="0 0 15 15" fill="none"><path d="M7.5 2L1 13h13L7.5 2z" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/><path d="M7.5 6v3M7.5 11h.01" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        Changes will reflect across all warehouse location records.
      </div>
      <input type="hidden" name="id" id="edit-id">
      <div class="form-section">Zone Info</div>
      <div class="form-row full">
        <div class="field">
          <label>Zone Name</label>
          <input type="text" name="zone_name" id="edit-zone-name" placeholder="Zone Name" required>
        </div>
      </div>
    </div>
    <div class="drawer-footer">
      <button type="button" class="btn-outline-wms" onclick="closeAllDrawers()">Cancel</button>
      <button type="submit" class="btn-primary-wms">
        <svg viewBox="0 0 13 13" fill="none"><path d="M2 7l3 3 6-6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Save Changes
      </button>
    </div>
  </form>
</div>

<!-- ── Delete Confirmation Drawer ── -->
<div class="drawer" id="drawer-delete">
  <div class="drawer-hdr">
    <div class="drawer-hdr-left">
      <div class="drawer-title">Remove Zone</div>
      <div class="drawer-sub">This action cannot be undone</div>
    </div>
    <button class="drawer-close" onclick="closeAllDrawers()">
      <svg viewBox="0 0 14 14" fill="none"><path d="M3 3l8 8M11 3l-8 8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
    </button>
  </div>
  <form method="POST" action="zone_delete.php" enctype="multipart/form-data">
    <div class="drawer-body">
      <div class="delete-confirm">
        <div class="delete-confirm-icon">
          <svg viewBox="0 0 20 20" fill="none"><path d="M3 5h14M8 5V3h4v2M15 5l-1 12H6L5 5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div class="delete-confirm-title">Remove this zone?</div>
        <div class="delete-confirm-sub" id="delete-confirm-name" style="margin-top:6px;font-weight:500;color:var(--text1)"></div>
        <div class="delete-confirm-sub" style="margin-top:8px">All associated bin/location records linked to this zone may be affected.</div>
      </div>
      <input type="hidden" name="id" id="delete-id">
    </div>
    <div class="drawer-footer">
      <button type="button" class="btn-outline-wms" onclick="closeAllDrawers()">Cancel</button>
      <button type="submit" class="btn-danger-wms">
        <svg viewBox="0 0 13 13" fill="none"><path d="M2 3.5h9M5 3.5V2.5h3v1M10 3.5l-.7 7H3.7L3 3.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Yes, Remove
      </button>
    </div>
  </form>
</div>

<script>
// ── Nav groups ───────────────────────────────────────────────────────────────
document.querySelectorAll('.nav-grp-hdr').forEach(function(h) {
  h.addEventListener('click', function() { h.parentElement.classList.toggle('open'); });
});

// ── Table filter ─────────────────────────────────────────────────────────────
function filterRows(v) {
  v = v.toLowerCase();
  document.querySelectorAll('#zt tbody tr').forEach(function(r) {
    r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none';
  });
}

// ── Drawer helpers ───────────────────────────────────────────────────────────
function openDrawer(id) {
  document.getElementById('overlay').classList.add('open');
  document.getElementById(id).classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeAllDrawers() {
  document.querySelectorAll('.drawer').forEach(function(d) { d.classList.remove('open'); });
  document.getElementById('overlay').classList.remove('open');
  document.body.style.overflow = '';
}

// ── Add ───────────────────────────────────────────────────────────────────────
function openAddDrawer() { openDrawer('drawer-add'); }

// ── Edit ──────────────────────────────────────────────────────────────────────
function openEditDrawer(row) {
  document.getElementById('edit-id').value        = row.zone_id   || '';
  document.getElementById('edit-zone-name').value = row.zone_name || '';
  document.getElementById('edit-drawer-sub').textContent = 'Editing: ' + (row.zone_name || '');
  openDrawer('drawer-edit');
}

// ── Delete ────────────────────────────────────────────────────────────────────
function openDeleteDrawer(zid, zname) {
  document.getElementById('delete-id').value = zid;
  document.getElementById('delete-confirm-name').textContent = zname;
  openDrawer('drawer-delete');
}

// ── Keyboard close ────────────────────────────────────────────────────────────
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeAllDrawers();
});
</script>
</body>
</html>