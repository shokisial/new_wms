<?php
session_start();
if (empty($_SESSION['id']))     { header('Location:../index.php'); exit; }
if (empty($_SESSION['branch'])) { header('Location:../index.php'); exit; }
$branch = $_SESSION['branch'];
$idu    = $_SESSION['id'];
$name   = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$user_group = $_SESSION['user_group']; 

include('conn/dbcon.php');

date_default_timezone_set("Asia/Karachi");
$date = date("Y-m-d");

$bts      = $_POST['batch'] ?? '';
$saveMsg  = '';
$saveType = ''; // 'success' | 'error'

// ── Handle Save ──────────────────────────────────────────────────────────────
if (isset($_POST['saveBtn']) && !empty($_POST['id'])) {
  $idss     = $_POST['id'];
  $asn_qt   = (int)$_POST['asn_qt'];
  $rqty     = $_POST['rqty'];
  $pid      = $_POST['pid'];
  $batch    = $_POST['batch_h'];
  $reason   = $_POST['reason'];
  $rexp     = $_POST['rexp'];
  $supplier = $_POST['sup'];

  if ($asn_qt > 0) {
    $r1 = mysqli_query($con,
      "INSERT INTO `location_control`
         (`st_id`,`batch_id`,`prod_id`,`user_id`,`location_expiry`,`location_in`,`supplier_id`,`dat`,`out_blc`,`stock_location`,`branch_id`)
       VALUES
         ('$idss','$batch','$pid','$idu','$rexp','0','$supplier','$date','$asn_qt','RMF','$branch')"
    );
    $r2 = mysqli_query($con,
      "UPDATE `stockout`
         SET `return_qty`='$asn_qt', `return_dat`='$date', `return_reason`='$reason'
       WHERE `stockout_id`='$idss'"
    );
    $saveMsg  = ($r1 && $r2) ? 'Return recorded successfully.' : 'DB error: ' . mysqli_error($con);
    $saveType = ($r1 && $r2) ? 'success' : 'error';
  } else {
    $saveMsg  = 'Return quantity must be greater than zero.';
    $saveType = 'error';
  }
}

// ── Fetch lines ───────────────────────────────────────────────────────────────
$productResult = [];
$total_rows    = 0;
$qt_out        = 0;

if ($bts) {
  $q = mysqli_query($con,
    "SELECT * FROM `stockout`
     INNER JOIN product ON product.prod_desc = stockout.product_id
     WHERE stockout.`stockout_qty` > '0'
       AND stockout.`return_qty` = '0'
       AND stockout.stockout_orderno = '$bts'
       AND stockout.branch_id = '$branch'"
  ) or die(mysqli_error());
  while ($row = mysqli_fetch_array($q, MYSQLI_ASSOC)) {
    $productResult[] = $row;
    $qt_out += $row['stockout_qty'];
  }
  $total_rows = count($productResult);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sovereign WMS — Return Stock</title>
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

    /* ── Filter Card ── */
    .filter-card { background:var(--white); border:1px solid var(--border); border-radius:10px; padding:16px 20px; margin-bottom:20px; display:flex; align-items:flex-end; gap:14px; flex-wrap:wrap; }
    .fc-group { display:flex; flex-direction:column; gap:5px; }
    .fc-label { font-size:10.5px; font-weight:600; color:var(--text3); text-transform:uppercase; letter-spacing:.06em; }
    .fc-input { height:36px; padding:0 12px; border:1px solid var(--border2); border-radius:7px; font-size:12px; font-family:'Inter',sans-serif; color:var(--text1); background:var(--bg); outline:none; transition:border .15s; min-width:220px; }
    .fc-input:focus { border-color:#9aafcf; background:var(--white); }
    .fc-btn { height:36px; padding:0 18px; border-radius:7px; font-size:12px; font-weight:500; cursor:pointer; border:none; font-family:'Inter',sans-serif; transition:all .13s; display:inline-flex; align-items:center; gap:6px; background:var(--navy); color:#fff; white-space:nowrap; }
    .fc-btn:hover { background:var(--navy-mid); }
    .fc-btn svg { width:13px; height:13px; }

    /* ── Alert Banner ── */
    .alert-banner { border-radius:8px; padding:10px 16px; font-size:12px; font-weight:500; margin-bottom:18px; display:flex; align-items:center; gap:9px; }
    .alert-banner svg { width:15px; height:15px; flex-shrink:0; }
    .alert-success { background:var(--green-bg); border:1px solid var(--green-bd); color:var(--green); }
    .alert-error   { background:var(--red-bg);   border:1px solid var(--red-bd);   color:var(--red); }

    /* ── Stats ── */
    .stats-row { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:20px; }
    .stat-card { background:var(--white); border:1px solid var(--border); border-radius:10px; padding:14px 16px; display:flex; align-items:center; gap:12px; }
    .stat-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .stat-icon svg { width:18px; height:18px; }
    .si-navy   { background:var(--navy);         color:#fff; }
    .si-orange { background:var(--orange-muted); color:var(--orange); }
    .si-amber  { background:var(--amber-bg);     color:var(--amber); }
    .stat-label { font-size:10.5px; color:var(--text3); margin-bottom:2px; }
    .stat-value { font-size:20px; font-weight:700; color:var(--text1); letter-spacing:-.5px; line-height:1; }
    .stat-sub { font-size:10px; color:var(--text3); margin-top:2px; }

    /* ── Card ── */
    .card { background:var(--white); border:1px solid var(--border); border-radius:10px; overflow:hidden; width:100%; }
    .card-hdr { padding:12px 16px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
    .card-hdr-title { font-size:13px; font-weight:600; color:var(--text1); }
    .card-hdr-meta  { font-size:11px; color:var(--text3); }

    /* ── Table ── */
    .tbl-wrap { overflow-x:auto; }
    table { width:100%; border-collapse:collapse; }
    th { font-size:10px; font-weight:600; color:var(--text3); text-transform:uppercase; letter-spacing:.06em; padding:9px 14px; border-bottom:1px solid var(--border); text-align:left; white-space:nowrap; background:#fafaf8; }
    td { padding:0; border-bottom:1px solid #f2f0ec; vertical-align:middle; }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:#faf9f7; }
    .cell { padding:11px 14px; font-size:12px; color:var(--text1); }

    /* SKU cell */
    .sku-cell { display:flex; align-items:center; gap:10px; padding:11px 14px; }
    .sku-icon { width:32px; height:32px; border-radius:7px; background:var(--bg2); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:all .13s; }
    .sku-icon svg { width:14px; height:14px; color:var(--text2); }
    tbody tr:hover .sku-icon { background:var(--orange-muted); border-color:var(--orange-border); }
    tbody tr:hover .sku-icon svg { color:var(--orange); }
    .sku-name { font-size:12px; font-weight:500; color:var(--text1); }
    .sku-code { font-family:'JetBrains Mono',monospace; font-size:10.5px; color:var(--text3); margin-top:1px; }

    /* Misc cells */
    .mono { font-family:'JetBrains Mono',monospace; font-size:11.5px; font-weight:500; }
    .qty-val { font-size:14px; font-weight:700; color:var(--text1); }

    /* Inline form inputs */
    .inp { height:32px; padding:0 10px; border:1px solid var(--border2); border-radius:6px; font-size:12px; font-family:'Inter',sans-serif; color:var(--text1); background:var(--bg); outline:none; transition:border .15s; width:90px; }
    .inp:focus { border-color:#9aafcf; background:var(--white); }
    .inp-reason { width:160px; }
    .btn-save { height:30px; padding:0 14px; border-radius:6px; font-size:11.5px; font-weight:500; cursor:pointer; border:none; font-family:'Inter',sans-serif; background:var(--green-bg); color:var(--green); border:1px solid var(--green-bd); display:inline-flex; align-items:center; gap:5px; transition:all .13s; white-space:nowrap; }
    .btn-save:hover { background:#d6f0e2; }
    .btn-save svg { width:12px; height:12px; }

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

    /* Return badge */
    .return-badge { display:inline-flex; align-items:center; gap:5px; background:var(--amber-bg); border:1px solid var(--amber-bd); border-radius:5px; padding:2px 9px; font-size:11px; font-weight:500; color:var(--amber); }
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

<?php include('side_check.php'); ?>

  <!-- Main -->
  <div class="main">

    <!-- Breadcrumb -->
    <div class="crumb">
      <a href="#">Return</a>
      <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Return Stock
    </div>

    <!-- Page Header -->
    <div class="ph">
      <div class="ph-left">
        <div class="title">Return Stock</div>
        <div class="sub">
          <?php if ($bts): ?>
            Document <strong><?php echo htmlspecialchars($bts); ?></strong> &nbsp;·&nbsp; Unit: <?php echo htmlspecialchars($branch); ?>
          <?php else: ?>
            Enter a document number to load returnable items
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Alert Banner -->
    <?php if ($saveMsg): ?>
    <div class="alert-banner alert-<?php echo $saveType; ?>">
      <?php if ($saveType === 'success'): ?>
        <svg viewBox="0 0 15 15" fill="none"><circle cx="7.5" cy="7.5" r="6" stroke="currentColor" stroke-width="1.3"/><path d="M5 7.5l2 2 3-3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <?php else: ?>
        <svg viewBox="0 0 15 15" fill="none"><circle cx="7.5" cy="7.5" r="6" stroke="currentColor" stroke-width="1.3"/><path d="M7.5 5v3M7.5 10h.01" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
      <?php endif; ?>
      <?php echo htmlspecialchars($saveMsg); ?>
    </div>
    <?php endif; ?>

    <!-- Search / Filter Card -->
    <div class="filter-card">
      <form method="POST" action="" style="display:contents">
        <div class="fc-group">
          <span class="fc-label">Document No.</span>
          <input type="text" name="batch" class="fc-input" placeholder="e.g. DC-00123"
                 value="<?php echo htmlspecialchars($bts); ?>" required>
        </div>
        <button type="submit" class="fc-btn">
          <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
          Search
        </button>
      </form>
    </div>

    <?php if ($bts && !empty($productResult)): ?>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon si-navy">
          <svg viewBox="0 0 18 18" fill="none"><rect x="3" y="2" width="12" height="14" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 6h6M6 9h6M6 12h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Returnable Lines</div>
          <div class="stat-value"><?php echo $total_rows; ?></div>
          <div class="stat-sub">Items pending return</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-orange">
          <svg viewBox="0 0 18 18" fill="none"><rect x="2" y="4" width="14" height="11" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 4V3a3 3 0 0 1 6 0v1" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Total Out Qty</div>
          <div class="stat-value"><?php echo number_format($qt_out); ?></div>
          <div class="stat-sub">Units dispatched</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-amber">
          <svg viewBox="0 0 18 18" fill="none"><path d="M4 9h10M9 4l-5 5 5 5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Document</div>
          <div class="stat-value" style="font-size:15px"><?php echo htmlspecialchars($bts); ?></div>
          <div class="stat-sub">Return in progress</div>
        </div>
      </div>
    </div>

    <?php endif; ?>

    <!-- Table Card -->
    <div class="card">
      <div class="card-hdr">
        <div>
          <div class="card-hdr-title">Return Items</div>
          <?php if ($bts): ?>
          <div class="card-hdr-meta">Document <?php echo htmlspecialchars($bts); ?> &nbsp;·&nbsp; Branch <?php echo htmlspecialchars($branch); ?></div>
          <?php endif; ?>
        </div>
        <?php if (!empty($productResult)): ?>
        <span class="return-badge">
          <svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M2 5h6M5 2l-3 3 3 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <?php echo $total_rows; ?> line<?php echo $total_rows != 1 ? 's' : ''; ?> pending
        </span>
        <?php endif; ?>
      </div>

      <div class="tbl-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Item</th>
              <th>Batch No.</th>
              <th>Out Qty</th>
              <th>Return Qty</th>
              <th>Reason</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($productResult)): ?>
            <tr><td colspan="7" style="border:none">
              <div class="empty-state">
                <div class="empty-icon">
                  <svg viewBox="0 0 22 22" fill="none"><path d="M4 11h14M11 4l-7 7 7 7" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div class="empty-title">No returnable items</div>
                <div class="empty-sub"><?php echo $bts ? 'No open return lines found for this document.' : 'Enter a document number above to search for returnable items.'; ?></div>
              </div>
            </td></tr>
            <?php else: ?>
            <?php $sno = 1; foreach ($productResult as $row): ?>
            <tr>
              <!-- # -->
              <td><div class="cell" style="color:var(--text3)"><?php echo $sno; ?></div></td>

              <!-- Item -->
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

              <!-- Out Qty -->
              <td><div class="cell"><span class="qty-val"><?php echo number_format($row['stockout_qty']); ?></span></div></td>

              <!-- Return form (qty + reason + save — one form per row) -->
              <form method="POST" action="">
                <input type="hidden" name="id"      value="<?php echo htmlspecialchars($row['stockout_id']); ?>">
                <input type="hidden" name="pid"     value="<?php echo htmlspecialchars($row['prod_desc']); ?>">
                <input type="hidden" name="batch_h" value="<?php echo htmlspecialchars($row['batch']); ?>">
                <input type="hidden" name="rqty"    value="<?php echo htmlspecialchars($row['stockout_qty']); ?>">
                <input type="hidden" name="rexp"    value="<?php echo htmlspecialchars($row['expiry']); ?>">
                <input type="hidden" name="sup"     value="<?php echo htmlspecialchars($row['sup_id']); ?>">
                <input type="hidden" name="batch"   value="<?php echo htmlspecialchars($bts); ?>">

                <td>
                  <div class="cell">
                    <input type="number" name="asn_qt" class="inp" value="0" min="0"
                           max="<?php echo (int)$row['stockout_qty']; ?>" required>
                  </div>
                </td>
                <td>
                  <div class="cell">
                    <input type="text" name="reason" class="inp inp-reason" placeholder="Reason…" required>
                  </div>
                </td>
                <td>
                  <div class="cell">
                    <button type="submit" name="saveBtn" class="btn-save">
                      <svg viewBox="0 0 12 12" fill="none"><path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                      Save
                    </button>
                  </div>
                </td>
              </form>
            </tr>
            <?php $sno++; endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if (!empty($productResult)): ?>
      <div class="tbl-footer">
        <div class="tbl-footer-note"><strong><?php echo $total_rows; ?> line<?php echo $total_rows != 1 ? 's' : ''; ?></strong> · <?php echo number_format($qt_out); ?> total units out</div>
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
</script>
</body>
</html>