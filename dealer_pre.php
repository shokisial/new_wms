<?php
session_start();
if (empty($_SESSION['id']))     { header('Location:../index.php'); exit; }
if (empty($_SESSION['branch'])) { header('Location:../index.php'); exit; }
$branch     = $_SESSION['branch'];
$id         = $_SESSION['id'];
$name       = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$user_group = $_SESSION['user_group'];
if ($user_group < '1') { header('Location:../index.php'); exit; }

include('conn/dbcon.php');

$sup = '';
if (isset($_POST['optionlist'])) {
  $sup = $_POST['optionlist'];
}

$stockRows = [];
if ($sup) {
  $q = mysqli_query($con,
    "SELECT * FROM stockin
     INNER JOIN product ON product.prod_desc = stockin.prod_id
     AND stockin.location = '0'
     AND stockin.branch_id = '$branch'
     WHERE stockin.rec_dnno = '$sup'"
  ) or die(mysqli_error($con));
  while ($row = mysqli_fetch_array($q)) { $stockRows[] = $row; }
}

$total_rows = count($stockRows);
$total_asn  = array_sum(array_column($stockRows, 'asn_qty'));
$total_recv = array_sum(array_column($stockRows, 'qty'));
$total_diff = array_sum(array_column($stockRows, 'asn_balance'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sovereign WMS — ASN Adjustment</title>
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

    .layout { display:flex; padding-top:var(--topbar-h); min-height:100vh; width:100%; overflow-x:hidden; }

    /* ── Sidebar ── */
    .sidebar { position:fixed; top:var(--topbar-h); left:0; bottom:0; width:var(--sidebar-w); background:var(--navy); overflow-y:auto; padding:6px 0 24px; border-right:1px solid #253350; z-index:90; }
    .sidebar::-webkit-scrollbar { width:3px; }
    .sidebar::-webkit-scrollbar-thumb { background:#2e3d5a; border-radius:3px; }
    .nav-sect { padding:14px 14px 5px; font-size:9.5px; font-weight:600; color:#364d70; letter-spacing:.1em; text-transform:uppercase; }
    .nav-item { display:flex; align-items:center; gap:9px; padding:7px 14px; color:#7a8ba8; font-size:12px; text-decoration:none; transition:background .12s, color .12s; }
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
    .main { margin-left:var(--sidebar-w); flex:1; padding:22px 26px 40px; min-width:0; width:calc(100% - var(--sidebar-w)); }
    .crumb { font-size:11px; color:var(--text3); display:flex; align-items:center; gap:5px; margin-bottom:10px; }
    .crumb a { color:var(--text2); text-decoration:none; }
    .ph { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:20px; gap:12px; flex-wrap:wrap; }
    .ph-left .title { font-size:18px; font-weight:600; color:var(--text1); letter-spacing:-.4px; }
    .ph-left .sub   { font-size:12px; color:var(--text2); margin-top:3px; }
    .ph-right { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }

    /* ── Filter Card ── */
    .filter-card { background:var(--white); border:1px solid var(--border); border-radius:10px; padding:16px 20px; margin-bottom:20px; display:flex; align-items:flex-end; gap:14px; flex-wrap:wrap; width:100%; }
    .fc-group { display:flex; flex-direction:column; gap:5px; }
    .fc-label { font-size:10.5px; font-weight:600; color:var(--text3); text-transform:uppercase; letter-spacing:.06em; }
    .fc-input { height:36px; padding:0 12px; border:1px solid var(--border2); border-radius:7px; font-size:12px; font-family:'Inter',sans-serif; color:var(--text1); background:var(--bg); outline:none; transition:border .15s; min-width:260px; }
    .fc-input:focus { border-color:#9aafcf; background:var(--white); }
    .fc-btn { height:36px; padding:0 18px; border-radius:7px; font-size:12px; font-weight:500; cursor:pointer; border:none; font-family:'Inter',sans-serif; transition:all .13s; display:inline-flex; align-items:center; gap:6px; background:var(--navy); color:#fff; white-space:nowrap; }
    .fc-btn:hover { background:var(--navy-mid); }
    .fc-btn svg { width:13px; height:13px; }

    /* ── Stats ── */
    .stats-row { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
    .stat-card { background:var(--white); border:1px solid var(--border); border-radius:10px; padding:14px 16px; display:flex; align-items:center; gap:12px; }
    .stat-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .stat-icon svg { width:18px; height:18px; }
    .si-navy   { background:var(--navy);         color:#fff; }
    .si-orange { background:var(--orange-muted); color:var(--orange); }
    .si-blue   { background:var(--blue-bg);      color:var(--blue); }
    .si-amber  { background:var(--amber-bg);     color:var(--amber); }
    .si-green  { background:var(--green-bg);     color:var(--green); }
    .si-red    { background:var(--red-bg);       color:var(--red); }
    .stat-label { font-size:10.5px; color:var(--text3); margin-bottom:2px; }
    .stat-value { font-size:20px; font-weight:700; color:var(--text1); letter-spacing:-.5px; line-height:1; }
    .stat-sub   { font-size:10px; color:var(--text3); margin-top:2px; }

    /* ── Card ── */
    .card { background:var(--white); border:1px solid var(--border); border-radius:10px; overflow:hidden; width:100%; }
    .card-hdr { padding:12px 16px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
    .card-hdr-title { font-size:13px; font-weight:600; color:var(--text1); }
    .card-hdr-meta  { font-size:11px; color:var(--text3); }
    .toolbar { display:flex; align-items:center; gap:8px; }
    .search-wrap { position:relative; }
    .search-wrap svg { position:absolute; left:10px; top:50%; transform:translateY(-50%); width:13px; height:13px; color:var(--text3); pointer-events:none; }
    .search-wrap input { padding:7px 10px 7px 30px; border:1px solid var(--border2); border-radius:7px; font-size:12px; font-family:'Inter',sans-serif; color:var(--text1); background:var(--bg); outline:none; width:210px; transition:border .15s; }
    .search-wrap input:focus { border-color:#9aafcf; background:var(--white); }

    /* ── Table ── */
    .tbl-wrap { overflow-x:auto; }
    table { width:100%; border-collapse:collapse; }
    th { font-size:10px; font-weight:600; color:var(--text3); text-transform:uppercase; letter-spacing:.06em; padding:9px 14px; border-bottom:1px solid var(--border); text-align:left; white-space:nowrap; background:#fafaf8; }
    td { padding:0; border-bottom:1px solid #f2f0ec; vertical-align:middle; }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:#faf9f7; }
    .cell { padding:11px 14px; font-size:12px; color:var(--text1); }

    /* Product cell */
    .prod-cell { display:flex; align-items:center; gap:10px; padding:11px 14px; }
    .prod-icon { width:32px; height:32px; border-radius:7px; background:var(--bg2); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:all .13s; }
    .prod-icon svg { width:14px; height:14px; color:var(--text2); }
    tbody tr:hover .prod-icon { background:var(--orange-muted); border-color:var(--orange-border); }
    tbody tr:hover .prod-icon svg { color:var(--orange); }
    .prod-name { font-size:12px; font-weight:500; color:var(--text1); }
    .prod-code { font-family:'JetBrains Mono',monospace; font-size:10.5px; color:var(--text3); margin-top:1px; }

    /* Misc */
    .mono { font-family:'JetBrains Mono',monospace; font-size:11.5px; font-weight:500; }
    .qty-val { font-size:13px; font-weight:700; color:var(--text1); }
    .diff-pos { color:var(--red); font-weight:700; font-size:13px; }
    .diff-zero { color:var(--green); font-weight:700; font-size:13px; }
    .batch-tag { display:inline-flex; align-items:center; background:var(--amber-bg); border:1px solid var(--amber-bd); border-radius:5px; padding:2px 9px; font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:600; color:var(--amber); }
    .expiry-tag { display:inline-flex; align-items:center; background:var(--blue-bg); border:1px solid var(--blue-bd); border-radius:5px; padding:2px 9px; font-size:11px; font-weight:500; color:var(--blue); }

    /* Totals row */
    .totals-row td { background:#fafaf8 !important; font-weight:600; }

    /* Table footer */
    .tbl-footer { padding:10px 14px; border-top:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; background:#fafaf8; }
    .tbl-footer-note { font-size:11px; color:var(--text3); }
    .tbl-footer-note strong { color:var(--text2); }

    /* Action button */
    .act-btn { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; border-radius:6px; font-size:11px; font-weight:500; cursor:pointer; border:none; font-family:'Inter',sans-serif; transition:all .13s; }
    .act-btn-edit { background:var(--blue-bg); color:var(--blue); border:1px solid var(--blue-bd); }
    .act-btn-edit:hover { background:#dae8ff; }
    .act-btn-edit svg { width:11px; height:11px; }

    /* Empty state */
    .empty-state { padding:52px 20px; text-align:center; }
    .empty-icon { width:52px; height:52px; border-radius:14px; background:var(--bg2); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; margin:0 auto 14px; }
    .empty-icon svg { width:22px; height:22px; color:var(--text3); }
    .empty-title { font-size:14px; font-weight:600; color:var(--text1); margin-bottom:5px; }
    .empty-sub   { font-size:12px; color:var(--text2); }

    /* ── Modal ── */
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:200; align-items:center; justify-content:center; }
    .modal-overlay.open { display:flex; }
    .modal-box { background:var(--white); border-radius:12px; width:100%; max-width:520px; box-shadow:0 20px 60px rgba(0,0,0,.18); overflow:hidden; }
    .modal-head { padding:16px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
    .modal-head-title { font-size:14px; font-weight:600; color:var(--text1); }
    .modal-close { width:28px; height:28px; border-radius:6px; border:none; background:var(--bg2); cursor:pointer; display:flex; align-items:center; justify-content:center; color:var(--text2); transition:background .12s; }
    .modal-close:hover { background:var(--border2); }
    .modal-close svg { width:13px; height:13px; }
    .modal-body { padding:20px; display:flex; flex-direction:column; gap:14px; max-height:70vh; overflow-y:auto; }
    .modal-foot { padding:14px 20px; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:8px; background:#fafaf8; }
    .field-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .field-row.full { grid-template-columns:1fr; }
    .field-grp { display:flex; flex-direction:column; gap:5px; }
    .field-label { font-size:10.5px; font-weight:600; color:var(--text3); text-transform:uppercase; letter-spacing:.05em; }
    .field-input { height:36px; padding:0 12px; border:1px solid var(--border2); border-radius:7px; font-size:12px; font-family:'Inter',sans-serif; color:var(--text1); background:var(--bg); outline:none; transition:border .15s; }
    .field-input:focus { border-color:#9aafcf; background:var(--white); }
    .btn-save { height:34px; padding:0 18px; border-radius:7px; font-size:12px; font-weight:500; cursor:pointer; border:none; font-family:'Inter',sans-serif; background:var(--navy); color:#fff; transition:background .13s; }
    .btn-save:hover { background:var(--navy-mid); }
    .btn-cancel { height:34px; padding:0 14px; border-radius:7px; font-size:12px; font-weight:500; cursor:pointer; border:1px solid var(--border2); font-family:'Inter',sans-serif; background:var(--white); color:var(--text2); transition:background .13s; }
    .btn-cancel:hover { background:var(--bg2); }

    /* ── Print ── */
    @media print {
      .topbar, .sidebar, .crumb, .filter-card, .toolbar, .tbl-footer, .no-print { display:none !important; }
      .main { margin-left:0; padding:10px; }
      .layout { display:block; }
      .stats-row { display:none; }
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
      $parts = explode(' ', trim($name)); $ini = '';
      foreach (array_slice($parts, 0, 2) as $p) $ini .= strtoupper($p[0]);
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
      <a href="#">Inbound</a>
      <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
      ASN Adjustment
    </div>

    <!-- Page Header -->
    <div class="ph">
      <div class="ph-left">
        <div class="title">ASN Adjustment</div>
        <div class="sub">
          <?php if ($sup): ?>
            Showing adjustments for ASN: <strong><?php echo htmlspecialchars($sup); ?></strong> &nbsp;·&nbsp; Unit: <?php echo htmlspecialchars($branch); ?>
          <?php else: ?>
            Enter an ASN number to load stock adjustment records
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card no-print">
      <form method="POST" action="" style="display:contents">
        <div class="fc-group">
          <span class="fc-label">ASN / DN Number</span>
          <input type="text" name="optionlist" class="fc-input"
                 placeholder="Enter ASN or DN number…"
                 value="<?php echo htmlspecialchars($sup); ?>" autofocus required>
        </div>
        <button type="submit" class="fc-btn">
          <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
          Load Records
        </button>
      </form>
    </div>

    <?php if ($sup && !empty($stockRows)): ?>
    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon si-navy">
          <svg viewBox="0 0 18 18" fill="none"><rect x="3" y="2" width="12" height="14" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 6h6M6 9h6M6 12h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Total Lines</div>
          <div class="stat-value"><?php echo $total_rows; ?></div>
          <div class="stat-sub">Stock entries</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-blue">
          <svg viewBox="0 0 18 18" fill="none"><rect x="2" y="4" width="14" height="11" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 4V3a3 3 0 0 1 6 0v1" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">ASN Qty</div>
          <div class="stat-value"><?php echo number_format($total_asn); ?></div>
          <div class="stat-sub">Expected units</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-green">
          <svg viewBox="0 0 18 18" fill="none"><path d="M4 9a5 5 0 1 0 10 0A5 5 0 0 0 4 9z" stroke="currentColor" stroke-width="1.3"/><path d="M6.5 9l2 2 3-3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Received</div>
          <div class="stat-value"><?php echo number_format($total_recv); ?></div>
          <div class="stat-sub">Actual units</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon <?php echo $total_diff != 0 ? 'si-red' : 'si-green'; ?>">
          <svg viewBox="0 0 18 18" fill="none"><path d="M9 3v12M5 9l4-4 4 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Difference</div>
          <div class="stat-value" style="font-size:18px; color:<?php echo $total_diff != 0 ? 'var(--red)' : 'var(--green)'; ?>"><?php echo ($total_diff > 0 ? '+' : '') . number_format($total_diff); ?></div>
          <div class="stat-sub"><?php echo $total_diff == 0 ? 'Balanced' : 'Variance detected'; ?></div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Table Card -->
    <div class="card">
      <div class="card-hdr">
        <div>
          <div class="card-hdr-title">Stock Adjustment Lines</div>
          <?php if ($sup): ?>
          <div class="card-hdr-meta">ASN: <?php echo htmlspecialchars($sup); ?> &nbsp;·&nbsp; Branch <?php echo htmlspecialchars($branch); ?></div>
          <?php endif; ?>
        </div>
        <?php if (!empty($stockRows)): ?>
        <div class="toolbar no-print">
          <div class="search-wrap">
            <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
            <input type="text" id="si" placeholder="Search code, name, batch…" oninput="filterRows(this.value)">
          </div>
        </div>
        <?php endif; ?>
      </div>

      <div class="tbl-wrap">
        <table id="adjTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Code</th>
              <th>Product Name</th>
              <th>Batch No.</th>
              <th>Expiry</th>
              <th>ASN Qty</th>
              <th>Received</th>
              <th>Difference</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($stockRows)): ?>
            <tr><td colspan="9" style="border:none">
              <div class="empty-state">
                <div class="empty-icon">
                  <svg viewBox="0 0 22 22" fill="none"><rect x="3" y="3" width="16" height="16" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M7 11h8M11 7v8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                </div>
                <div class="empty-title">No records found</div>
                <div class="empty-sub"><?php echo $sup ? 'No stock entries match this ASN number.' : 'Enter an ASN or DN number above to load adjustment records.'; ?></div>
              </div>
            </td></tr>
            <?php else: ?>
            <?php $sno = 1; foreach ($stockRows as $row): ?>
            <?php $diff = (int)$row['asn_balance']; ?>
            <tr>
              <td><div class="cell" style="color:var(--text3)"><?php echo $sno; ?></div></td>

              <td><div class="cell mono"><?php echo htmlspecialchars($row['prod_desc']); ?></div></td>

              <td>
                <div class="prod-cell">
                  <div class="prod-icon">
                    <svg viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 5h5M4.5 7h5M4.5 9h3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
                  </div>
                  <div class="prod-name"><?php echo htmlspecialchars($row['prod_name']); ?></div>
                </div>
              </td>

              <td><div class="cell"><span class="batch-tag"><?php echo htmlspecialchars($row['batch'] ?: '—'); ?></span></div></td>

              <td><div class="cell"><span class="expiry-tag"><?php echo htmlspecialchars($row['expiry'] ?: '—'); ?></span></div></td>

              <td><div class="cell"><span class="qty-val"><?php echo number_format($row['asn_qty']); ?></span></div></td>

              <td><div class="cell"><span class="qty-val"><?php echo number_format($row['qty']); ?></span></div></td>

              <td><div class="cell">
                <span class="<?php echo $diff != 0 ? 'diff-pos' : 'diff-zero'; ?>">
                  <?php echo ($diff > 0 ? '+' : '') . number_format($diff); ?>
                </span>
              </div></td>

              <td><div class="cell">
                <button type="button" class="act-btn act-btn-edit"
                  onclick="openModal(<?php
                    echo json_encode([
                      'id'          => $row['stockin_id'],
                      'rec'         => $row['rec_dnno'],
                      'desc'        => $row['prod_desc'],
                      'batch'       => $row['batch'],
                      'expiry'      => $row['expiry'],
                      'asn_qty'     => $row['asn_qty'],
                      'qty'         => $row['qty'],
                      'asn_balance' => $row['asn_balance'],
                      'gpass'       => $row['gatepass_id'],
                      'veh'         => $row['truck_no'],
                    ]);
                  ?>)">
                  <svg viewBox="0 0 11 11" fill="none"><path d="M2 8l5.5-5.5 1.5 1.5L3.5 9.5H2V8z" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  Edit
                </button>
              </div></td>
            </tr>
            <?php $sno++; endforeach; ?>

            <!-- Totals -->
            <tr class="totals-row">
              <td colspan="5"><div class="cell" style="color:var(--text2)">Totals</div></td>
              <td><div class="cell"><span class="qty-val"><?php echo number_format($total_asn); ?></span></div></td>
              <td><div class="cell"><span class="qty-val"><?php echo number_format($total_recv); ?></span></div></td>
              <td><div class="cell"><span class="<?php echo $total_diff != 0 ? 'diff-pos' : 'diff-zero'; ?>"><?php echo ($total_diff > 0 ? '+' : '') . number_format($total_diff); ?></span></div></td>
              <td></td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if (!empty($stockRows)): ?>
      <div class="tbl-footer">
        <div class="tbl-footer-note"><strong><?php echo $total_rows; ?> line<?php echo $total_rows != 1 ? 's' : ''; ?></strong> · Branch <?php echo htmlspecialchars($branch); ?></div>
        <div class="tbl-footer-note">Generated: <?php echo date('d M Y, H:i'); ?></div>
      </div>
      <?php endif; ?>
    </div>

  </div><!-- /.main -->
</div><!-- /.layout -->

<!-- ── Edit Modal ── -->
<div class="modal-overlay" id="editModal">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-head-title">Update Stock Details</div>
      <button class="modal-close" onclick="closeModal()">
        <svg viewBox="0 0 13 13" fill="none"><path d="M2 2l9 9M11 2l-9 9" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
      </button>
    </div>
    <form method="POST" action="dealerpre_update.php" enctype="multipart/form-data">
      <div class="modal-body">
        <input type="hidden" name="id"    id="m_id">
        <input type="hidden" name="gpass" id="m_gpass">

        <div class="field-row">
          <div class="field-grp">
            <label class="field-label">ASN No.</label>
            <input type="text" class="field-input" name="rec" id="m_rec" required>
          </div>
          <div class="field-grp">
            <label class="field-label">Product Code</label>
            <input type="text" class="field-input" name="desc" id="m_desc" required>
          </div>
        </div>

        <div class="field-row">
          <div class="field-grp">
            <label class="field-label">Batch #</label>
            <input type="text" class="field-input" name="batch" id="m_batch" required>
          </div>
          <div class="field-grp">
            <label class="field-label">Expiry</label>
            <input type="text" class="field-input" name="expiry" id="m_expiry" required>
          </div>
        </div>

        <div class="field-row">
          <div class="field-grp">
            <label class="field-label">ASN Qty</label>
            <input type="text" class="field-input" name="asn_qty" id="m_asn_qty" required>
          </div>
          <div class="field-grp">
            <label class="field-label">Received</label>
            <input type="text" class="field-input" name="qty" id="m_qty" required>
          </div>
        </div>

        <div class="field-row">
          <div class="field-grp">
            <label class="field-label">Balance / Difference</label>
            <input type="text" class="field-input" name="asn_balance" id="m_asn_balance" required>
          </div>
          <div class="field-grp">
            <label class="field-label">Vehicle No.</label>
            <input type="text" class="field-input" name="veh" id="m_veh" required>
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn-save">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
// Sidebar accordion
document.querySelectorAll('.nav-grp-hdr').forEach(function(h) {
  h.addEventListener('click', function() { h.parentElement.classList.toggle('open'); });
});

// Table search
function filterRows(v) {
  v = v.toLowerCase();
  document.querySelectorAll('#adjTable tbody tr:not(.totals-row)').forEach(function(r) {
    r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none';
  });
}

// Modal
function openModal(data) {
  document.getElementById('m_id').value          = data.id;
  document.getElementById('m_rec').value         = data.rec;
  document.getElementById('m_desc').value        = data.desc;
  document.getElementById('m_batch').value       = data.batch;
  document.getElementById('m_expiry').value      = data.expiry;
  document.getElementById('m_asn_qty').value     = data.asn_qty;
  document.getElementById('m_qty').value         = data.qty;
  document.getElementById('m_asn_balance').value = data.asn_balance;
  document.getElementById('m_gpass').value       = data.gpass;
  document.getElementById('m_veh').value         = data.veh;
  document.getElementById('editModal').classList.add('open');
}
function closeModal() {
  document.getElementById('editModal').classList.remove('open');
}
document.getElementById('editModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
</script>
</body>
</html>
