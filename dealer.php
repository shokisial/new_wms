<?php
session_start();
if (empty($_SESSION['id']))     { header('Location:../index.php'); exit; }
if (empty($_SESSION['branch'])) { header('Location:../index.php'); exit; }
$branch     = $_SESSION['branch'];
$id         = $_SESSION['id'];
$user_group = $_SESSION['user_group'];
$name       = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';

if ($user_group < '1') { header('Location:../index.php'); exit; }
if ($id != '15' && $id != '1' && $id != '24' && $id != '31') {
    header('Location:../index.php'); exit;
}

include('conn/dbcon.php');

// ── Search / filter ───────────────────────────────────────────────────────────
$sup   = '';
$rows  = [];
if (isset($_POST['optionlist']) && trim($_POST['optionlist']) !== '') {
    $sup = mysqli_real_escape_string($con, trim($_POST['optionlist']));
    $q   = mysqli_query($con,
        "SELECT location_control.*, product.prod_name
         FROM location_control
         INNER JOIN product ON product.prod_desc = location_control.prod_id
         WHERE (location_control.stock_location = '$sup' OR location_control.batch_id = '$sup')
           AND location_control.branch_id = '$branch'
         ORDER BY stock_location DESC")
         or die(mysqli_error());
    while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
}
$total = count($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sovereign WMS — Stock Adjustment</title>
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
    .si-green  { background:var(--green-bg);     color:var(--green); }
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
    .btn-danger-wms { display:inline-flex; align-items:center; gap:6px; padding:7px 18px; border-radius:7px; font-size:12px; font-weight:500; cursor:pointer; border:none; font-family:'Inter',sans-serif; transition:all .13s; background:var(--red); color:#fff; }
    .btn-danger-wms:hover { background:#991b1b; }
    .btn-primary-wms svg, .btn-outline-wms svg, .btn-amber-wms svg, .btn-danger-wms svg { width:13px; height:13px; }

    /* ── Search bar (page-level) ── */
    .search-card { background:var(--white); border:1px solid var(--border); border-radius:10px; padding:16px 20px; margin-bottom:20px; display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap; }
    .search-card label { font-size:10.5px; font-weight:600; color:var(--text3); text-transform:uppercase; letter-spacing:.06em; display:block; margin-bottom:5px; }
    .search-card input[type="text"] { height:36px; padding:0 12px 0 36px; border:1px solid var(--border2); border-radius:7px; font-size:12px; font-family:'Inter',sans-serif; color:var(--text1); background:var(--bg); outline:none; transition:border .15s; width:280px; }
    .search-card input[type="text"]:focus { border-color:#9aafcf; background:var(--white); }
    .search-input-wrap { position:relative; }
    .search-input-wrap svg { position:absolute; left:11px; top:50%; transform:translateY(-50%); width:13px; height:13px; color:var(--text3); pointer-events:none; }
    .search-hint { font-size:11px; color:var(--text3); margin-top:4px; }

    /* ── Card / Table ── */
    .card { background:var(--white); border:1px solid var(--border); border-radius:10px; overflow:hidden; }
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
    .cell-muted { padding:11px 14px; font-size:12px; color:var(--text2); }

    /* Product cell */
    .prod-cell { display:flex; align-items:center; gap:10px; padding:10px 14px; }
    .prod-icon { width:34px; height:34px; border-radius:8px; background:var(--navy-light); border:1px solid #304060; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; color:#8a9ab8; flex-shrink:0; transition:all .13s; }
    tbody tr:hover .prod-icon { background:var(--orange-muted); border-color:var(--orange-border); color:var(--orange); }
    .prod-name { font-size:12px; font-weight:500; color:var(--text1); }
    .prod-code { font-family:'JetBrains Mono',monospace; font-size:10.5px; color:var(--text3); margin-top:1px; }

    .mono { font-family:'JetBrains Mono',monospace; font-size:11px; }
    .badge { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:20px; font-size:10.5px; font-weight:500; }
    .badge-green  { background:var(--green-bg);  color:var(--green);  border:1px solid var(--green-bd); }
    .badge-red    { background:var(--red-bg);    color:var(--red);    border:1px solid var(--red-bd); }
    .badge-amber  { background:var(--amber-bg);  color:var(--amber);  border:1px solid var(--amber-bd); }
    .badge-blue   { background:var(--blue-bg);   color:var(--blue);   border:1px solid var(--blue-bd); }
    .badge-navy   { background:#e8ecf4;          color:var(--navy);   border:1px solid #c8d3e8; }

    /* Action buttons */
    .action-wrap { display:flex; align-items:center; gap:6px; padding:10px 14px; }
    .act-btn { width:28px; height:28px; border-radius:6px; border:1px solid var(--border2); background:var(--white); display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all .13s; }
    .act-btn svg { width:13px; height:13px; color:var(--text3); }
    .act-btn-edit:hover  { background:var(--blue-bg);  border-color:var(--blue-bd); }
    .act-btn-edit:hover svg { color:var(--blue); }

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
    .drawer { position:fixed; top:0; right:0; bottom:0; width:460px; max-width:95vw; background:var(--white); z-index:310; transform:translateX(100%); transition:transform .24s cubic-bezier(.4,0,.2,1); display:flex; flex-direction:column; box-shadow:-8px 0 32px rgba(0,0,0,.12); }
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
    .field input, .field textarea { height:36px; padding:0 12px; border:1px solid var(--border2); border-radius:7px; font-size:12px; font-family:'Inter',sans-serif; color:var(--text1); background:var(--bg); outline:none; transition:border .15s; }
    .field textarea { height:70px; padding:10px 12px; resize:vertical; }
    .field input:focus, .field textarea:focus { border-color:#9aafcf; background:var(--white); }
    .field input::placeholder, .field textarea::placeholder { color:var(--text3); }
    .field input[readonly] { background:var(--bg2); color:var(--text2); cursor:default; }

    /* Caution banner */
    .caution-banner { background:var(--amber-bg); border:1px solid var(--amber-bd); border-radius:8px; padding:10px 14px; font-size:11.5px; color:var(--amber); display:flex; align-items:center; gap:8px; }
    .caution-banner svg { width:15px; height:15px; flex-shrink:0; }

    /* No-search state */
    .no-search-state { padding:52px 20px; text-align:center; }
    .no-search-state .no-search-icon { width:52px; height:52px; border-radius:14px; background:var(--bg2); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; margin:0 auto 14px; }
    .no-search-state .no-search-icon svg { width:22px; height:22px; color:var(--text3); }

    @media print {
      .topbar, .sidebar, .ph-right, .toolbar, .action-wrap, .tbl-footer, .search-card { display:none !important; }
      .main { margin-left:0; padding:10px; }
      .layout { display:block; }
    }
  </style>
</head>
<body>

<!-- ── Topbar ── -->
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

  <!-- ── Sidebar ── -->
  <?php include('side_check.php'); ?>
  </aside>

  <!-- ── Main Content ── -->
  <div class="main">

    <!-- Breadcrumb -->
    <div class="crumb">
      <a href="#">Warehouse</a>
      <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Stock Adjustment
    </div>

    <!-- Page Header -->
    <div class="ph">
      <div class="ph-left">
        <div class="title">Stock Adjustment</div>
        <div class="sub"><?php echo htmlspecialchars($branch); ?> &nbsp;·&nbsp; Search by location or batch number</div>
      </div>
      <div class="ph-right">
        <?php if (!empty($rows)): ?>
        <button onclick="window.print()" class="btn-amber-wms">
          <svg viewBox="0 0 13 13" fill="none"><rect x="2" y="1" width="9" height="11" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 4h4M4.5 6.5h4M4.5 9h2.5" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
          Print Results
        </button>
        <?php endif; ?>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon si-navy">
          <svg viewBox="0 0 18 18" fill="none"><rect x="2" y="3" width="14" height="12" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 3V1M12 3V1M2 7h14" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Results Found</div>
          <div class="stat-value"><?php echo $total; ?></div>
          <div class="stat-sub">Stock record<?php echo $total != 1 ? 's' : ''; ?></div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-orange">
          <svg viewBox="0 0 18 18" fill="none"><path d="M9 2v14M2 9h14" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Branch</div>
          <div class="stat-value" style="font-size:15px"><?php echo htmlspecialchars($branch); ?></div>
          <div class="stat-sub">Current unit</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-blue">
          <svg viewBox="0 0 18 18" fill="none"><path d="M4 9h10M9 4l5 5-5 5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Search Query</div>
          <div class="stat-value" style="font-size:13px;font-family:'JetBrains Mono',monospace;"><?php echo $sup !== '' ? htmlspecialchars($sup) : '—'; ?></div>
          <div class="stat-sub">Location / Batch No.</div>
        </div>
      </div>
    </div>

    <!-- Search Card -->
    <div class="search-card">
      <div>
        <label>Search by Location or Batch No.</label>
        <form action="" method="POST" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:center;">
          <div class="search-input-wrap">
            <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
            <input type="text" name="optionlist" placeholder="e.g. A-01 or BATCH-2024…" autofocus
                   value="<?php echo htmlspecialchars($sup); ?>" required>
          </div>
          <button type="submit" class="btn-primary-wms">
            <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
            Search
          </button>
          <?php if ($sup !== ''): ?>
          <a href="dealer.php" class="btn-outline-wms">Clear</a>
          <?php endif; ?>
        </form>
        <div class="search-hint">Enter a warehouse location code or batch number to find stock records.</div>
      </div>
    </div>

    <!-- Table Card -->
    <div class="card">
      <div class="card-hdr">
        <div class="card-hdr-title">
          Stock Records
          <?php if ($sup !== ''): ?>
          &nbsp;<span style="font-size:11px;font-weight:400;color:var(--text3);">for "<?php echo htmlspecialchars($sup); ?>"</span>
          <?php endif; ?>
        </div>
        <?php if (!empty($rows)): ?>
        <div class="toolbar">
          <div class="search-wrap">
            <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
            <input type="text" id="si" placeholder="Filter results…" oninput="filterRows(this.value)">
          </div>
        </div>
        <?php endif; ?>
      </div>

      <div class="tbl-wrap">
        <table id="st">
          <thead>
            <tr>
              <th>Product</th>
              <th>Batch #</th>
              <th>Expiry</th>
              <th>Stock</th>
              <th>Location</th>
              <th>Block</th>
              <th>Unit</th>
              <th style="text-align:center">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($sup === ''): ?>
            <tr><td colspan="8" style="border:none">
              <div class="no-search-state">
                <div class="no-search-icon">
                  <svg viewBox="0 0 22 22" fill="none"><circle cx="9.5" cy="9.5" r="7" stroke="currentColor" stroke-width="1.3"/><path d="M19 19l-4-4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                </div>
                <div class="empty-title">Enter a location or batch number</div>
                <div class="empty-sub">Use the search box above to look up stock records.</div>
              </div>
            </td></tr>
            <?php elseif (empty($rows)): ?>
            <tr><td colspan="8" style="border:none">
              <div class="empty-state">
                <div class="empty-icon">
                  <svg viewBox="0 0 22 22" fill="none"><rect x="3" y="3" width="16" height="16" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M8 11h6M11 8v6" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                </div>
                <div class="empty-title">No records found</div>
                <div class="empty-sub">No stock found for "<?php echo htmlspecialchars($sup); ?>" in this branch.</div>
              </div>
            </td></tr>
            <?php else:
              foreach ($rows as $row):
                $initials = strtoupper(substr(strip_tags($row['prod_name'] ?? $row['prod_desc'] ?? 'P'), 0, 2));
                $isExpired = !empty($row['expired']) && $row['expired'] == 1;
                $isBlocked = !empty($row['block']) && strtolower($row['block']) !== 'no' && strtolower($row['block']) !== '';
            ?>
            <tr>
              <!-- Product -->
              <td>
                <div class="prod-cell">
                  <div class="prod-icon"><?php echo htmlspecialchars($initials); ?></div>
                  <div>
                    <div class="prod-name"><?php echo htmlspecialchars($row['prod_name'] ?? ''); ?></div>
                    <div class="prod-code"><?php echo htmlspecialchars($row['prod_desc'] ?? ''); ?></div>
                  </div>
                </div>
              </td>
              <!-- Batch -->
              <td><div class="cell mono"><?php echo htmlspecialchars($row['batch_id'] ?? '—'); ?></div></td>
              <!-- Expiry -->
              <td><div class="cell">
                <?php if (!empty($row['location_expiry'])): ?>
                  <span class="badge <?php echo $isExpired ? 'badge-red' : 'badge-green'; ?>">
                    <?php echo htmlspecialchars($row['location_expiry']); ?>
                  </span>
                <?php else: ?>
                  <span style="color:var(--text3);font-size:12px;">—</span>
                <?php endif; ?>
              </div></td>
              <!-- Stock -->
              <td><div class="cell"><strong><?php echo htmlspecialchars($row['out_blc'] ?? '0'); ?></strong></div></td>
              <!-- Location -->
              <td><div class="cell mono"><?php echo htmlspecialchars($row['stock_location'] ?? '—'); ?></div></td>
              <!-- Block -->
              <td><div class="cell">
                <?php if ($isBlocked): ?>
                  <span class="badge badge-amber"><?php echo htmlspecialchars($row['block']); ?></span>
                <?php else: ?>
                  <span class="badge badge-green">Clear</span>
                <?php endif; ?>
              </div></td>
              <!-- Unit -->
              <td><div class="cell-muted"><?php echo htmlspecialchars($row['branch_id'] ?? ''); ?></div></td>
              <!-- Action -->
              <td>
                <div class="action-wrap" style="justify-content:center;">
                  <button class="act-btn act-btn-edit"
                          title="Edit stock record"
                          onclick="openEditDrawer(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                    <svg viewBox="0 0 13 13" fill="none"><path d="M9 2l2 2-7 7H2V9L9 2z" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <?php if (!empty($rows)): ?>
      <div class="tbl-footer">
        <div class="tbl-footer-note">Showing <strong><?php echo $total; ?></strong> record<?php echo $total != 1 ? 's' : ''; ?> for "<?php echo htmlspecialchars($sup); ?>"</div>
        <div class="tbl-footer-note"><?php echo date('d M Y, H:i'); ?></div>
      </div>
      <?php endif; ?>
    </div>

  </div><!-- /.main -->
</div><!-- /.layout -->

<!-- ── Overlay ── -->
<div class="overlay" id="overlay" onclick="closeAllDrawers()"></div>

<!-- ── Edit Drawer ── -->
<div class="drawer" id="drawer-edit">
  <div class="drawer-hdr">
    <div class="drawer-hdr-left">
      <div class="drawer-title">Update Stock Record</div>
      <div class="drawer-sub" id="edit-drawer-sub">Editing location control entry</div>
    </div>
    <button class="drawer-close" onclick="closeAllDrawers()">
      <svg viewBox="0 0 14 14" fill="none"><path d="M3 3l8 8M11 3l-8 8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
    </button>
  </div>
  <form method="POST" action="dealer_update.php" enctype="multipart/form-data">
    <div class="drawer-body">

      <div class="caution-banner">
        <svg viewBox="0 0 15 15" fill="none"><path d="M7.5 2L14 13H1L7.5 2z" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/><path d="M7.5 6v3.5M7.5 11v.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        Changes to stock records affect inventory balances. Review carefully before saving.
      </div>

      <div class="form-section">Product Information</div>
      <input type="hidden" name="id" id="edit-id">
      <div class="form-row">
        <div class="field">
          <label>Product Code</label>
          <input type="text" name="cust_name" id="edit-prod-code" placeholder="Product code" required>
        </div>
        <div class="field">
          <label>Batch #</label>
          <input type="text" name="add" id="edit-batch" placeholder="Batch number" required>
        </div>
      </div>
      <div class="form-row">
        <div class="field">
          <label>Stock Qty</label>
          <input type="text" name="email" id="edit-stock" placeholder="Quantity" required>
        </div>
        <div class="field">
          <label>Expiry Date</label>
          <input type="text" name="expiry" id="edit-expiry" placeholder="DD/MM/YYYY">
        </div>
      </div>

      <div class="form-section">Location Details</div>
      <div class="form-row">
        <div class="field">
          <label>Location</label>
          <input type="text" name="location" id="edit-location" placeholder="e.g. A-01" required>
        </div>
        <div class="field">
          <label>Block</label>
          <input type="text" name="block" id="edit-block" placeholder="Block status">
        </div>
      </div>

      <div class="form-section">Status &amp; Meta</div>
      <div class="form-row">
        <div class="field">
          <label>Expired</label>
          <input type="text" name="expired" id="edit-expired" placeholder="0 / 1">
        </div>
        <div class="field">
          <label>Record Status</label>
          <input type="text" name="status" id="edit-status" placeholder="Active / Inactive">
        </div>
      </div>
      <div class="form-row">
        <div class="field">
          <label>Unit / Branch</label>
          <input type="text" name="unit" id="edit-unit" placeholder="Branch code">
        </div>
      </div>

      <div class="form-section">Remarks</div>
      <div class="form-row full">
        <div class="field">
          <label>Comments</label>
          <textarea name="comm" id="edit-comm" placeholder="Reason for adjustment or any notes…"></textarea>
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

<script>
// ── Nav groups ──────────────────────────────────────────────────────────────
document.querySelectorAll('.nav-grp-hdr').forEach(function(h) {
  h.addEventListener('click', function() { h.parentElement.classList.toggle('open'); });
});

// ── Table filter ─────────────────────────────────────────────────────────────
function filterRows(v) {
  v = v.toLowerCase();
  document.querySelectorAll('#st tbody tr').forEach(function(r) {
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

// ── Edit ─────────────────────────────────────────────────────────────────────
function openEditDrawer(row) {
  document.getElementById('edit-id').value        = row.id              || '';
  document.getElementById('edit-prod-code').value = row.prod_desc       || '';
  document.getElementById('edit-batch').value      = row.batch_id        || '';
  document.getElementById('edit-stock').value      = row.out_blc         || '';
  document.getElementById('edit-expiry').value     = row.location_expiry || '';
  document.getElementById('edit-location').value   = row.stock_location  || '';
  document.getElementById('edit-block').value      = row.block           || '';
  document.getElementById('edit-expired').value    = row.expired         || '';
  document.getElementById('edit-status').value     = row.record_status   || '';
  document.getElementById('edit-unit').value       = row.branch_id       || '';
  document.getElementById('edit-comm').value       = '';
  document.getElementById('edit-drawer-sub').textContent = 'Editing: ' + (row.prod_name || row.prod_desc || '');
  openDrawer('drawer-edit');
}

// ── Keyboard close ───────────────────────────────────────────────────────────
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeAllDrawers();
});
</script>
</body> 
</html>