<?php
session_start();
if (empty($_SESSION['id']))     { header('Location:../index.php'); exit; }
if (empty($_SESSION['branch'])) { header('Location:../index.php'); exit; }
$branch = $_SESSION['branch'];
$id     = $_SESSION['id'];
$name   = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$user_group = $_SESSION['user_group']; 

include('conn/dbcon.php');

// Fetch gate passes
$gatepasses = [];
$query = mysqli_query($con,
  "SELECT * FROM gatepass_out
   WHERE branch_id='$branch'
   ORDER BY gatepass_id DESC"
) or die(mysqli_error($con));
while ($row = mysqli_fetch_array($query)) { $gatepasses[] = $row; }

$total = count($gatepasses);

// Helper: relative date
function relDate($d) {
  if (!$d) return '—';
  $ts = strtotime($d);
  if (!$ts) return $d;
  $diff = floor((time() - $ts) / 86400);
  if ($diff == 0) return 'Today';
  if ($diff == 1) return '1 day ago';
  return $diff . ' days ago';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sovereign WMS — Outward Gate Pass</title>
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

    /* Topbar */
    .topbar { position:fixed; top:0; left:0; right:0; height:var(--topbar-h); background:var(--navy); display:flex; align-items:center; padding:0 18px; gap:10px; z-index:100; border-bottom:2px solid var(--orange); }
    .logo-mark { display:flex; align-items:center; width:30px; height:30px; flex-shrink:0; }
    .brand .b1 { font-size:14px; font-weight:600; color:#fff; letter-spacing:-.2px; }
    .brand .b2 { font-size:9px; color:#8a9ab8; letter-spacing:.12em; text-transform:uppercase; margin-top:1px; }
    .topbar-right { margin-left:auto; display:flex; align-items:center; gap:14px; }
    .branch-pill { background:var(--navy-light); border:1px solid #304060; border-radius:6px; padding:4px 10px; display:flex; align-items:center; gap:7px; font-size:11px; color:#8a9ab8; }
    .branch-pill strong { color:#fff; font-weight:500; }
    .avatar { width:30px; height:30px; border-radius:50%; background:var(--orange); display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:600; color:#fff; flex-shrink:0; }

    .layout { display:flex; padding-top:var(--topbar-h); min-height:100vh; width:100%; overflow-x:hidden; }

    /* Sidebar */
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

    /* Main */
    .main { margin-left:var(--sidebar-w); flex:1; padding:22px 26px 40px; min-width:0; width:calc(100% - var(--sidebar-w)); }
    .crumb { font-size:11px; color:var(--text3); display:flex; align-items:center; gap:5px; margin-bottom:10px; }
    .crumb a { color:var(--text2); text-decoration:none; }
    .ph { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:20px; gap:12px; flex-wrap:wrap; }
    .ph-left .title { font-size:18px; font-weight:600; color:var(--text1); letter-spacing:-.4px; }
    .ph-left .sub   { font-size:12px; color:var(--text2); margin-top:3px; }
    .ph-right { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }

    /* Stats */
    .stats-row { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:20px; }
    .stat-card { background:var(--white); border:1px solid var(--border); border-radius:10px; padding:14px 16px; display:flex; align-items:center; gap:12px; }
    .stat-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .stat-icon svg { width:18px; height:18px; }
    .si-navy   { background:var(--navy);         color:#fff; }
    .si-orange { background:var(--orange-muted); color:var(--orange); }
    .si-green  { background:var(--green-bg);     color:var(--green); }
    .stat-label { font-size:10.5px; color:var(--text3); margin-bottom:2px; }
    .stat-value { font-size:20px; font-weight:700; color:var(--text1); letter-spacing:-.5px; line-height:1; }
    .stat-sub   { font-size:10px; color:var(--text3); margin-top:2px; }

    /* Card */
    .card { background:var(--white); border:1px solid var(--border); border-radius:10px; overflow:hidden; width:100%; }
    .card-hdr { padding:12px 16px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
    .card-hdr-title { font-size:13px; font-weight:600; color:var(--text1); }
    .toolbar { display:flex; align-items:center; gap:8px; }
    .search-wrap { position:relative; }
    .search-wrap svg { position:absolute; left:10px; top:50%; transform:translateY(-50%); width:13px; height:13px; color:var(--text3); pointer-events:none; }
    .search-wrap input { padding:7px 10px 7px 30px; border:1px solid var(--border2); border-radius:7px; font-size:12px; font-family:'Inter',sans-serif; color:var(--text1); background:var(--bg); outline:none; width:210px; transition:border .15s; }
    .search-wrap input:focus { border-color:#9aafcf; background:var(--white); }

    /* Table */
    .tbl-wrap { overflow-x:auto; }
    table { width:100%; border-collapse:collapse; }
    th { font-size:10px; font-weight:600; color:var(--text3); text-transform:uppercase; letter-spacing:.06em; padding:9px 14px; border-bottom:1px solid var(--border); text-align:left; white-space:nowrap; background:#fafaf8; }
    td { padding:0; border-bottom:1px solid #f2f0ec; vertical-align:middle; }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:#faf9f7; }
    tbody tr:hover .row-action { opacity:1; }
    .cell { padding:12px 14px; font-size:12px; color:var(--text1); }

    /* Gate pass cell */
    .gp-cell { display:flex; align-items:center; gap:10px; padding:12px 14px; }
    .gp-icon { width:32px; height:32px; border-radius:7px; background:var(--bg2); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:all .13s; }
    .gp-icon svg { width:14px; height:14px; color:var(--text2); }
    tbody tr:hover .gp-icon { background:var(--orange-muted); border-color:var(--orange-border); }
    tbody tr:hover .gp-icon svg { color:var(--orange); }
    .gp-num { font-family:'JetBrains Mono',monospace; font-size:12px; font-weight:600; color:var(--text1); }
    .gp-sub { font-size:10.5px; color:var(--text3); margin-top:1px; }

    /* Vehicle tag */
    .veh-tag { display:inline-block; background:var(--bg2); border:1px solid var(--border); border-radius:5px; padding:2px 9px; font-family:'JetBrains Mono',monospace; font-size:11.5px; font-weight:600; color:var(--text1); }

    /* Date cell */
    .date-cell { padding:12px 14px; }
    .date-val { font-size:12px; font-weight:500; color:var(--text1); }
    .date-rel { font-size:10.5px; color:var(--text3); margin-top:2px; }

    /* Driver cell */
    .driver-cell { display:flex; align-items:center; gap:8px; padding:12px 14px; }
    .driver-av { width:28px; height:28px; border-radius:50%; background:var(--blue-bg); border:1px solid var(--blue-bd); display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; color:var(--blue); flex-shrink:0; }
    .driver-name { font-size:12px; font-weight:500; color:var(--text1); }

    /* Badge */
    .badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:500; }
    .badge::before { content:''; width:5px; height:5px; border-radius:50%; }
    .b-green { background:var(--green-bg); color:var(--green); border:1px solid var(--green-bd); }
    .b-green::before { background:var(--green); }

    /* Action */
    .row-action { opacity:0; padding:12px 14px; transition:opacity .13s; display:flex; gap:6px; align-items:center; }
    .act-btn { display:inline-flex; align-items:center; gap:5px; padding:6px 12px; border-radius:6px; font-size:11.5px; font-weight:500; cursor:pointer; border:none; font-family:'Inter',sans-serif; transition:all .13s; white-space:nowrap; }
    .act-btn-navy { background:var(--navy); color:#fff; }
    .act-btn-navy:hover { background:var(--navy-mid); }
    .act-btn svg { width:12px; height:12px; }

    /* Table footer */
    .tbl-footer { padding:10px 14px; border-top:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; background:#fafaf8; }
    .tbl-footer-note { font-size:11px; color:var(--text3); }
    .tbl-footer-note strong { color:var(--text2); }

    /* Buttons */
    .btn-primary-wms { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; border-radius:7px; font-size:12px; font-weight:500; cursor:pointer; border:none; font-family:'Inter',sans-serif; transition:all .13s; background:var(--navy); color:#fff; text-decoration:none; white-space:nowrap; }
    .btn-primary-wms:hover { background:var(--navy-mid); }
    .btn-primary-wms svg { width:13px; height:13px; }

    /* Empty */
    .empty-state { padding:52px 20px; text-align:center; }
    .empty-icon { width:52px; height:52px; border-radius:14px; background:var(--bg2); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; margin:0 auto 14px; }
    .empty-icon svg { width:22px; height:22px; color:var(--text3); }
    .empty-title { font-size:14px; font-weight:600; color:var(--text1); margin-bottom:5px; }
    .empty-sub   { font-size:12px; color:var(--text2); }

    /* Modal overlay */
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:200; align-items:center; justify-content:center; }
    .modal-overlay.open { display:flex; }
    .modal-box { background:var(--white); border-radius:12px; width:100%; max-width:460px; box-shadow:0 20px 60px rgba(0,0,0,.25); overflow:hidden; animation:mfade .18s ease; }
    @keyframes mfade { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
    .modal-hdr { padding:16px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
    .modal-hdr-title { font-size:14px; font-weight:600; color:var(--text1); }
    .modal-close { width:28px; height:28px; border-radius:6px; border:none; background:var(--bg2); cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background .12s; }
    .modal-close:hover { background:var(--border2); }
    .modal-close svg { width:12px; height:12px; color:var(--text2); }
    .modal-body { padding:20px; display:flex; flex-direction:column; gap:14px; }
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .form-group { display:flex; flex-direction:column; gap:5px; }
    .form-group.full { grid-column:1/-1; }
    .form-label { font-size:10.5px; font-weight:600; color:var(--text3); text-transform:uppercase; letter-spacing:.06em; }
    .form-input, .form-select, .form-textarea { padding:8px 12px; border:1px solid var(--border2); border-radius:7px; font-size:12px; font-family:'Inter',sans-serif; color:var(--text1); background:var(--bg); outline:none; transition:border .15s; width:100%; }
    .form-input:focus, .form-select:focus, .form-textarea:focus { border-color:#9aafcf; background:var(--white); }
    .form-select { appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%239e9c96' stroke-width='1.3' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 10px center; padding-right:28px; }
    .form-textarea { resize:vertical; min-height:60px; }
    .modal-ftr { padding:14px 20px; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:8px; background:#fafaf8; }
    .btn-modal-save { padding:7px 18px; border-radius:7px; font-size:12px; font-weight:500; cursor:pointer; border:none; font-family:'Inter',sans-serif; background:var(--navy); color:#fff; transition:all .13s; }
    .btn-modal-save:hover { background:var(--navy-mid); }
    .btn-modal-cancel { padding:7px 16px; border-radius:7px; font-size:12px; font-weight:500; cursor:pointer; border:1px solid var(--border2); font-family:'Inter',sans-serif; background:var(--white); color:var(--text2); transition:all .13s; }
    .btn-modal-cancel:hover { background:var(--bg2); }
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

  <?php include('side_check.php'); ?>

  <!-- Main -->
  <div class="main">

    <!-- Breadcrumb -->
    <div class="crumb">
      <a href="#">Outbound</a>
      <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Outward Gate Pass
    </div>

    <!-- Page Header -->
    <div class="ph">
      <div class="ph-left">
        <div class="title">Outward Gate Pass</div>
        <div class="sub">All outbound vehicle gate passes — Unit: <?php echo htmlspecialchars($branch); ?></div>
      </div>
      <div class="ph-right">
        <a href="gatepass_newout.php" class="btn-primary-wms">
          <svg viewBox="0 0 13 13" fill="none"><path d="M6.5 2v9M2 6.5h9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
          Add New Gate Pass
        </a>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon si-navy">
          <svg viewBox="0 0 18 18" fill="none"><rect x="1" y="6" width="16" height="9" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M4 6V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/><circle cx="5" cy="15" r="1.5" fill="currentColor"/><circle cx="13" cy="15" r="1.5" fill="currentColor"/></svg>
        </div>
        <div>
          <div class="stat-label">Total Gate Passes</div>
          <div class="stat-value"><?php echo $total; ?></div>
          <div class="stat-sub">All outbound records</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-orange">
          <svg viewBox="0 0 18 18" fill="none"><path d="M9 3a6 6 0 1 0 0 12A6 6 0 0 0 9 3z" stroke="currentColor" stroke-width="1.3"/><path d="M9 6v3l2 2" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Today's Dispatches</div>
          <?php
            $today_count = count(array_filter($gatepasses, fn($r) => date('Y-m-d', strtotime($r['outdate'])) === date('Y-m-d')));
          ?>
          <div class="stat-value"><?php echo $today_count; ?></div>
          <div class="stat-sub">Outbound today</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-green">
          <svg viewBox="0 0 18 18" fill="none"><path d="M4 9a5 5 0 1 0 10 0A5 5 0 0 0 4 9z" stroke="currentColor" stroke-width="1.3"/><path d="M6.5 9l2 2 3-3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Latest Pass #</div>
          <div class="stat-value" style="font-size:15px"><?php echo $gatepasses ? htmlspecialchars($gatepasses[0]['gatepass_id']) : '—'; ?></div>
          <div class="stat-sub">Most recent</div>
        </div>
      </div>
    </div>

    <!-- Table Card -->
    <div class="card">
      <div class="card-hdr">
        <div class="card-hdr-title">Gate pass records</div>
        <div class="toolbar">
          <div class="search-wrap">
            <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
            <input type="text" id="si" placeholder="Search gate pass, vehicle, driver…" oninput="filterRows(this.value)">
          </div>
        </div>
      </div>

      <div class="tbl-wrap">
        <table id="gt">
          <thead>
            <tr>
              <th>Gate Pass #</th>
              <th>Date</th>
              <th>Vehicle</th>
              <th>Driver</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($gatepasses)): ?>
            <tr><td colspan="6" style="border:none">
              <div class="empty-state">
                <div class="empty-icon">
                  <svg viewBox="0 0 22 22" fill="none"><rect x="1" y="6" width="20" height="12" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M5 6V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                </div>
                <div class="empty-title">No gate passes found</div>
                <div class="empty-sub">No outward gate passes have been created for this branch yet.</div>
              </div>
            </td></tr>
            <?php else: ?>
            <?php foreach ($gatepasses as $row):
              $rel      = relDate($row['outdate']);
              $date_fmt = $row['outdate'] ? date('d M Y', strtotime($row['outdate'])) : '—';
              $drv      = trim($row['driver'] ?? '');
              $drv_ini  = $drv ? strtoupper($drv[0]) : '?';
            ?>
            <tr>

              <!-- Gate Pass # -->
              <td>
                <div class="gp-cell">
                  <div class="gp-icon">
                    <svg viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 5h5M4.5 7h5M4.5 9h3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
                  </div>
                  <div>
                    <div class="gp-num">GP-<?php echo htmlspecialchars($row['gatepass_id']); ?></div>
                    <div class="gp-sub">Outward Pass</div>
                  </div>
                </div>
              </td>

              <!-- Date -->
              <td>
                <div class="date-cell">
                  <div class="date-val"><?php echo $date_fmt; ?></div>
                  <div class="date-rel"><?php echo $rel; ?></div>
                </div>
              </td>

              <!-- Vehicle -->
              <td><div class="cell"><span class="veh-tag"><?php echo htmlspecialchars($row['vehicle_no'] ?: '—'); ?></span></div></td>

              <!-- Driver -->
              <td>
                <div class="driver-cell">
                  <div class="driver-av"><?php echo htmlspecialchars($drv_ini); ?></div>
                  <div class="driver-name"><?php echo htmlspecialchars($drv ?: '—'); ?></div>
                </div>
              </td>

              <!-- Status -->
              <td><div class="cell"><span class="badge b-green">Dispatched</span></div></td>

              <!-- Actions -->
              <td class="row-action">
                <form method="POST" action="invoice_deliver1.php" target="_blank" style="display:inline">
                  <input type="hidden" name="gdn_no" value="<?php echo htmlspecialchars($row['gatepass_id']); ?>">
                  <button type="submit" name="sub" class="act-btn act-btn-navy">
                    <svg viewBox="0 0 12 12" fill="none"><rect x="1" y="2" width="10" height="8" rx="1" stroke="currentColor" stroke-width="1.2"/><path d="M3 5h6M3 7h4" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
                    Print
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
        <div class="tbl-footer-note"><strong><?php echo $total; ?> gate pass<?php echo $total!=1?'es':''; ?></strong> · Branch <?php echo htmlspecialchars($branch); ?></div>
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
  document.querySelectorAll('#gt tbody tr').forEach(function(r) {
    r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none';
  });
}
</script>
</body>
</html>