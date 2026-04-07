<?php
session_start();
if (empty($_SESSION['id']))     { header('Location:../index.php'); exit; }
if (empty($_SESSION['branch'])) { header('Location:../index.php'); exit; }
$branch = $_SESSION['branch'];
$id     = $_SESSION['id'];
$name   = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';

include('conn/dbcon.php');

// Resolve doc number from POST or SESSION
if (isset($_POST['sub'])) {
    $test = $_POST['sub'];
} elseif (isset($_POST['asn_no'])) {
    $test = $_POST['asn_no'];
} else {
    $test = isset($_SESSION['sub1']) ? $_SESSION['sub1'] : '';
}

// ── Save (mark received) ─────────────────────────────────────────────────────
if (isset($_POST['submit'])) {
    $qtid2 = $_POST['asn_no'];
    mysqli_query($con, "UPDATE `stockin` SET final='0' WHERE `rec_dnno`='$qtid2' AND qty=asn_qty") or die(mysqli_error($con));
    $_SESSION['sub1'] = $test;
    echo "<script>document.location='final_barcode_asn1.php'</script>";
}

// ── Approve single item ──────────────────────────────────────────────────────
if (isset($_POST['grn_no'])) {
    $std = $_POST['grn_no'];
    mysqli_query($con, "UPDATE `stockin` SET final='0' WHERE `stockin_id`='$std'") or die(mysqli_error($con));
    $_SESSION['sub1'] = $test;
    echo "<script>document.location='final_barcode_asn1.php'</script>";
}

// ── Clear single item ────────────────────────────────────────────────────────
if (isset($_POST['clear'])) {
    $std = $_POST['clear'];
    mysqli_query($con, "UPDATE `stockin` SET qty='0',asn_balance='0',loose_rec='0',loose_blc='0' WHERE `stockin_id`='$std'") or die(mysqli_error($con));
    $_SESSION['sub1'] = $test;
    echo "<script>document.location='final_barcode_asn1.php'</script>";
}

// ── Fetch line items ─────────────────────────────────────────────────────────
$items = [];
if ($test) {
    $q = mysqli_query($con,
        "SELECT stockin.*, product.prod_name
         FROM `stockin`
         INNER JOIN product ON product.prod_desc = stockin.prod_id
         WHERE stockin.rec_dnno = '$test'
           AND stockin.final    = '1'
           AND stockin.branch_id = '$branch'
           AND product.branch_id = '$branch'
         GROUP BY stockin.batch"
    ) or die(mysqli_error($con));
    while ($r = mysqli_fetch_array($q)) { $items[] = $r; }
}

$total_asn = array_sum(array_column($items, 'asn_qty'));
$total_rec = array_sum(array_column($items, 'qty'));
$item_count = count($items);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sovereign WMS — ASN Receiving</title>
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

    /* ── Main ── */
    .main { margin-left: var(--sidebar-w); flex: 1; padding: 22px 26px 40px; }
    .crumb { font-size: 11px; color: var(--text3); display: flex; align-items: center; gap: 5px; margin-bottom: 10px; }
    .crumb a { color: var(--text2); text-decoration: none; }
    .ph { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
    .ph-left .title { font-size: 18px; font-weight: 600; color: var(--text1); letter-spacing: -.4px; }
    .ph-left .sub { font-size: 12px; color: var(--text2); margin-top: 3px; }
    .ph-right { display: flex; align-items: center; gap: 8px; }

    /* Doc pill */
    .doc-pill { display: inline-flex; align-items: center; gap: 7px; background: var(--blue-bg); border: 1px solid var(--blue-bd); border-radius: 8px; padding: 6px 13px; font-size: 12px; color: var(--blue); font-weight: 500; }
    .doc-pill span { font-family: 'JetBrains Mono', monospace; font-weight: 600; font-size: 13px; }

    /* Buttons */
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 15px; border-radius: 7px; font-size: 12px; font-weight: 500; cursor: pointer; border: none; font-family: 'Inter', sans-serif; transition: all .13s; white-space: nowrap; text-decoration: none; }
    .btn svg { width: 13px; height: 13px; }
    .btn-primary { background: var(--navy); color: #fff; }
    .btn-primary:hover { background: var(--navy-mid); }
    .btn-success { background: var(--green-bg); color: var(--green); border: 1px solid var(--green-bd); }
    .btn-success:hover { background: #daf0e6; }
    .btn-outline { background: var(--white); color: var(--text2); border: 1px solid var(--border2); }
    .btn-outline:hover { background: var(--bg2); color: var(--text1); }
    .btn-orange { background: var(--orange); color: #fff; }
    .btn-orange:hover { background: var(--orange-lt); }
    .btn-sm { padding: 5px 11px; font-size: 11px; }

    /* ── Stats ── */
    .stats-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 20px; }
    .stat-card { background: var(--white); border: 1px solid var(--border); border-radius: 10px; padding: 14px 16px; display: flex; align-items: center; gap: 12px; }
    .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .stat-icon svg { width: 18px; height: 18px; }
    .si-navy { background: var(--navy); color: #fff; }
    .si-orange { background: var(--orange-muted); color: var(--orange); }
    .si-green { background: var(--green-bg); color: var(--green); }
    .si-blue { background: var(--blue-bg); color: var(--blue); }
    .stat-label { font-size: 10.5px; color: var(--text3); margin-bottom: 2px; }
    .stat-value { font-size: 20px; font-weight: 700; color: var(--text1); letter-spacing: -.5px; line-height: 1; }
    .stat-sub { font-size: 10px; color: var(--text3); margin-top: 2px; }

    /* ── Progress bar (ASN vs received) ── */
    .progress-track { height: 6px; background: var(--bg2); border-radius: 3px; overflow: hidden; margin-top: 5px; }
    .progress-fill { height: 100%; border-radius: 3px; background: var(--orange); transition: width .3s; }
    .progress-fill.complete { background: var(--green); }

    /* ── Card ── */
    .card { background: var(--white); border: 1px solid var(--border); border-radius: 10px; overflow: hidden; margin-bottom: 20px; }
    .card-hdr { padding: 12px 16px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
    .card-hdr-title { font-size: 13px; font-weight: 600; color: var(--text1); }
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
    tbody tr:hover .row-action { opacity: 1; }

    .sno-cell { padding: 14px 14px; font-size: 11px; color: var(--text3); font-weight: 600; }

    .item-cell { display: flex; align-items: center; gap: 10px; padding: 13px 14px; }
    .item-icon { width: 32px; height: 32px; border-radius: 7px; background: var(--bg2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all .13s; }
    .item-icon svg { width: 14px; height: 14px; color: var(--text2); }
    tbody tr:hover .item-icon { background: var(--orange-muted); border-color: var(--orange-border); }
    tbody tr:hover .item-icon svg { color: var(--orange); }
    .item-code { font-family: 'JetBrains Mono', monospace; font-size: 12px; font-weight: 600; color: var(--text1); }
    .item-name { font-size: 10.5px; color: var(--text3); margin-top: 1px; }

    .barcode-cell { padding: 13px 14px; }
    .barcode-val { font-family: 'JetBrains Mono', monospace; font-size: 11px; color: var(--text1); background: var(--bg2); border: 1px solid var(--border); border-radius: 5px; padding: 2px 8px; display: inline-block; }

    .cell { padding: 13px 14px; font-size: 12px; color: var(--text1); }
    .cell-muted { padding: 13px 14px; font-size: 12px; color: var(--text2); }

    .batch-tag { font-family: 'JetBrains Mono', monospace; font-size: 11px; background: var(--amber-bg); border: 1px solid var(--amber-bd); color: var(--amber); border-radius: 5px; padding: 2px 8px; display: inline-block; }

    .qty-cell { padding: 13px 14px; }
    .qty-asn { font-size: 14px; font-weight: 700; color: var(--text1); line-height: 1; }
    .qty-rec { font-size: 11px; color: var(--text3); margin-top: 2px; }

    .status-cell { padding: 13px 14px; }
    .badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
    .badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; }
    .b-amber { background: var(--amber-bg); color: var(--amber); border: 1px solid var(--amber-bd); }
    .b-amber::before { background: var(--amber); }
    .b-green { background: var(--green-bg); color: var(--green); border: 1px solid var(--green-bd); }
    .b-green::before { background: var(--green); }
    .b-red { background: var(--red-bg); color: var(--red); border: 1px solid var(--red-bd); }
    .b-red::before { background: var(--red); }

    .row-action { opacity: 0; padding: 13px 14px; transition: opacity .13s; display: flex; gap: 6px; align-items: center; }
    .act-icon-btn { width: 30px; height: 30px; border-radius: 6px; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border2); background: var(--white); cursor: pointer; transition: all .13s; flex-shrink: 0; }
    .act-icon-btn svg { width: 13px; height: 13px; }
    .act-icon-btn.edit:hover { background: var(--blue-bg); border-color: var(--blue-bd); color: var(--blue); }
    .act-icon-btn.approve:hover { background: var(--green-bg); border-color: var(--green-bd); color: var(--green); }
    .act-icon-btn.clear:hover { background: var(--red-bg); border-color: var(--red-bd); color: var(--red); }
    .act-icon-btn.edit svg { color: var(--blue); }
    .act-icon-btn.approve svg { color: var(--green); }
    .act-icon-btn.clear svg { color: var(--red); }

    .tbl-footer { padding: 10px 14px; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; background: #fafaf8; }
    .tbl-footer-note { font-size: 11px; color: var(--text3); }
    .tbl-footer-note strong { color: var(--text2); }

    .empty-state { padding: 52px 20px; text-align: center; }
    .empty-icon { width: 52px; height: 52px; border-radius: 14px; background: var(--bg2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; }
    .empty-icon svg { width: 22px; height: 22px; color: var(--text3); }
    .empty-title { font-size: 14px; font-weight: 600; color: var(--text1); margin-bottom: 5px; }
    .empty-sub { font-size: 12px; color: var(--text2); }

    /* ── Modal ── */
    .modal-backdrop { display: none; position: fixed; inset: 0; background: rgba(10,14,24,.45); z-index: 200; align-items: center; justify-content: center; }
    .modal-backdrop.show { display: flex; }
    .modal-box { background: var(--white); border-radius: 12px; width: 100%; max-width: 480px; box-shadow: 0 8px 32px rgba(0,0,0,.18); overflow: hidden; animation: slideUp .18s ease; }
    @keyframes slideUp { from { transform: translateY(14px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .modal-hdr { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .modal-hdr-title { font-size: 14px; font-weight: 600; color: var(--text1); }
    .modal-close { width: 28px; height: 28px; border-radius: 6px; border: none; background: var(--bg2); cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text2); transition: background .12s; }
    .modal-close:hover { background: var(--border2); }
    .modal-close svg { width: 13px; height: 13px; }
    .modal-body { padding: 20px; }
    .modal-footer { padding: 14px 20px; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: flex-end; gap: 8px; }
    .form-row { margin-bottom: 14px; }
    .form-label { display: block; font-size: 11.5px; font-weight: 500; color: var(--text2); margin-bottom: 5px; }
    .form-control { width: 100%; padding: 8px 11px; border: 1px solid var(--border2); border-radius: 7px; font-size: 12.5px; font-family: 'Inter', sans-serif; color: var(--text1); background: var(--bg); outline: none; transition: border .15s; }
    .form-control:focus { border-color: #9aafcf; background: var(--white); }
    .form-control:disabled { opacity: .6; cursor: not-allowed; }
    select.form-control { cursor: pointer; }
    .confirm-text { font-size: 13px; color: var(--text2); line-height: 1.6; }
    .confirm-text strong { color: var(--text1); }
    .warning-box { background: var(--red-bg); border: 1px solid var(--red-bd); border-radius: 8px; padding: 10px 13px; margin-top: 12px; font-size: 12px; color: var(--red); display: flex; gap: 8px; align-items: flex-start; }
    .warning-box svg { width: 14px; height: 14px; flex-shrink: 0; margin-top: 1px; }
  </style>
</head>
<body>

<!-- ══ TOPBAR ══════════════════════════════════════════════════════════════ -->
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
    <div class="avatar"><?php $p=explode(' ',trim($name));$ini='';foreach(array_slice($p,0,2) as $x) $ini.=strtoupper($x[0]);echo htmlspecialchars($ini); ?></div>
  </div>
</div>

<div class="layout">

  <!-- ══ SIDEBAR ══════════════════════════════════════════════════════════ -->
  <aside class="sidebar">
    <div class="nav-sect">Main</div>
    <a href="new_dash.php" class="nav-item"><svg viewBox="0 0 14 14" fill="none"><rect x="1" y="1" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="8" y="1" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="1" y="8" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="8" y="8" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/></svg>Dashboard</a>
    <div class="nav-sect">Operations</div>
    <div class="nav-grp open">
      <div class="nav-grp-hdr"><svg class="ic" viewBox="0 0 14 14" fill="none"><path d="M1 7h12M7 3l4 4-4 4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Inbound<svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
      <div class="nav-sub-list">
        <a href="inward_transaction.php" class="nav-sub">A.S.N</a>
        <a href="gatepass.php"           class="nav-sub">Gate Pass</a>
        <a href="final_barcode.php"      class="nav-sub">Receive</a>
        <a href="final_barcode_asn1.php" class="nav-sub active">ASN Detail</a>
        <a href="final_location.php"     class="nav-sub">Location</a>
        <a href="index_stkveh.php"       class="nav-sub">Location List</a>
      </div>
    </div>
    <div class="nav-grp">
      <div class="nav-grp-hdr"><svg class="ic" viewBox="0 0 14 14" fill="none"><path d="M13 7H1M7 11l-4-4 4-4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Outbound<svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
      <div class="nav-sub-list">
        <a href="outward_transaction.php" class="nav-sub">Transfer Note</a>
        <a href="final_out2.php"          class="nav-sub">Order Preparation</a>
        <a href="picking_summery.php"     class="nav-sub">Picking Summary</a>
        <a href="seg_list.php"            class="nav-sub">Segregation List</a>
        <a href="gatepass_out.php"        class="nav-sub">Gate Pass</a>
      </div>
    </div>
    <div class="nav-grp">
      <div class="nav-grp-hdr"><svg class="ic" viewBox="0 0 14 14" fill="none"><path d="M2 5h8a3 3 0 0 1 0 6H6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 3L2 5l2 2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Return<svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
      <div class="nav-sub-list">
        <a href="final_barcode_return.php" class="nav-sub">Return Stock</a>
        <a href="gatepass_newreturn.php"   class="nav-sub">Return Gate Pass</a>
      </div>
    </div>
    <div class="nav-sep"></div>
    <div class="nav-sect">Warehouse</div>
    <div class="nav-grp">
      <div class="nav-grp-hdr"><svg class="ic" viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 5h5M4.5 7.5h5M4.5 10h3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>Reports<svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
      <div class="nav-sub-list">
        <a href="inbound_report.php"  class="nav-sub">Inbound Report</a>
        <a href="outbound_report.php" class="nav-sub">Outbound Report</a>
        <a href="expire.php"          class="nav-sub">Expiry Report</a>
        <a href="index_ledger.php"    class="nav-sub">Customer Ledger</a>
      </div>
    </div>
    <div class="nav-sep"></div>
    <a href="logout.php" class="nav-item" style="color:#5c6e8a;margin-top:4px"><svg viewBox="0 0 14 14" fill="none"><path d="M9 7H1M5 4l-3 3 3 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 2h2.5A1.5 1.5 0 0 1 13 3.5v7A1.5 1.5 0 0 1 11.5 12H9" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>Logout</a>
  </aside>

  <!-- ══ MAIN CONTENT ═════════════════════════════════════════════════════ -->
  <div class="main">

    <div class="crumb">
      <a href="new_dash.php">Dashboard</a>
      <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <a href="final_barcode.php">Receipt</a>
      <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
      ASN Detail
    </div>

    <!-- Page header -->
    <div class="ph">
      <div class="ph-left">
        <div class="title">ASN Receiving Detail</div>
        <div class="sub">Review and update quantities for each line item</div>
      </div>
      <div class="ph-right">
        <?php if ($test): ?>
        <div class="doc-pill">
          <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><rect x="2" y="1" width="9" height="11" rx="1.5" stroke="currentColor" stroke-width="1.1"/><path d="M4 4.5h5M4 6.5h5M4 8.5h3" stroke="currentColor" stroke-width="1" stroke-linecap="round"/></svg>
          Doc: <span><?php echo htmlspecialchars($test); ?></span>
        </div>
        <?php endif; ?>
        <!-- Save button -->
        <form method="POST" action="" style="display:inline">
          <input type="hidden" name="asn_no" value="<?php echo htmlspecialchars($test); ?>">
          <button type="submit" name="submit" class="btn btn-success">
            <svg viewBox="0 0 13 13" fill="none"><path d="M2 7l3 3 6-6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Save
          </button>
        </form>
        <!-- Download button -->
        <form action="index_final.php" method="GET" target="_blank" style="display:inline">
          <button type="submit" class="btn btn-outline">
            <svg viewBox="0 0 13 13" fill="none"><path d="M6.5 2v7M3.5 6.5l3 3 3-3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 11h9" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
            Download
          </button>
        </form>
        <!-- Detail report -->
        <form action="inbound_report.php" method="POST" target="_blank" style="display:inline">
          <input type="hidden" name="rec_dnno" value="<?php echo htmlspecialchars($test); ?>">
          <button type="submit" name="cash" class="btn btn-primary">
            <svg viewBox="0 0 13 13" fill="none"><rect x="2" y="1" width="9" height="11" rx="1.5" stroke="currentColor" stroke-width="1.1"/><path d="M4.5 4.5h4M4.5 6.5h4M4.5 8.5h2.5" stroke="currentColor" stroke-width="1" stroke-linecap="round"/></svg>
            Detail
          </button>
        </form>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon si-navy">
          <svg viewBox="0 0 18 18" fill="none"><rect x="3" y="2" width="12" height="14" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 6h6M6 9h6M6 12h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        </div>
        <div><div class="stat-label">Line Items</div><div class="stat-value"><?php echo $item_count; ?></div><div class="stat-sub">In this document</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-orange">
          <svg viewBox="0 0 18 18" fill="none"><rect x="2" y="4" width="14" height="11" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 4V3a3 3 0 0 1 6 0v1" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        </div>
        <div><div class="stat-label">ASN Qty</div><div class="stat-value"><?php echo number_format($total_asn); ?></div><div class="stat-sub">Total ordered</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-green">
          <svg viewBox="0 0 18 18" fill="none"><path d="M4 9a5 5 0 1 0 10 0A5 5 0 0 0 4 9z" stroke="currentColor" stroke-width="1.3"/><path d="M6.5 9l2 2 3-3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div><div class="stat-label">Received Qty</div><div class="stat-value"><?php echo number_format($total_rec); ?></div><div class="stat-sub">Confirmed received</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-blue">
          <svg viewBox="0 0 18 18" fill="none"><path d="M9 3a6 6 0 1 0 0 12A6 6 0 0 0 9 3z" stroke="currentColor" stroke-width="1.3"/><path d="M9 6v3l2 2" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        </div>
        <?php $remaining = max(0, $total_asn - $total_rec); ?>
        <div><div class="stat-label">Remaining</div><div class="stat-value"><?php echo number_format($remaining); ?></div><div class="stat-sub"><?php echo $remaining == 0 ? 'Fully received' : 'Awaiting receipt'; ?></div></div>
      </div>
    </div>

    <!-- Item table -->
    <div class="card">
      <div class="card-hdr">
        <div class="card-hdr-title">Line Items</div>
        <div class="toolbar">
          <div class="search-wrap">
            <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
            <input type="text" id="si" placeholder="Search item, barcode, batch…" oninput="filterRows(this.value)">
          </div>
        </div>
      </div>

      <div class="tbl-wrap">
        <table id="rt">
          <thead>
            <tr>
              <th>#</th>
              <th>Item Code</th>
              <th>Barcode</th>
              <th>S.K.U</th>
              <th>Batch #</th>
              <th>ASN Qty</th>
              <th>Received</th>
              <th>Damage</th>
              <th>Progress</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($items)): ?>
            <tr><td colspan="11" style="border:none">
              <div class="empty-state">
                <div class="empty-icon"><svg viewBox="0 0 22 22" fill="none"><rect x="3" y="5" width="16" height="13" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M7 5V4a4 4 0 0 1 8 0v1" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg></div>
                <div class="empty-title">No line items found</div>
                <div class="empty-sub">No items are associated with this document, or the document number was not provided.</div>
              </div>
            </td></tr>
          <?php else: $sno = 1; foreach ($items as $row): ?>
            <?php
              // Fetch barcodes for this item
              $barcodes = [];
              $qb2 = mysqli_query($con, "SELECT barcode FROM `product_barcode` WHERE `prod_desc`='" . mysqli_real_escape_string($con, $row['prod_desc']) . "'");
              while ($rb2 = mysqli_fetch_array($qb2)) { $barcodes[] = $rb2['barcode']; }
              $bc_str = implode(', ', $barcodes);
              $rem_qty = max(0, $row['asn_qty'] - $row['qty']);
              $pct = ($row['asn_qty'] > 0) ? min(100, round($row['qty'] / $row['asn_qty'] * 100)) : 0;
              $is_complete = ($pct >= 100);
              $damage_qty = intval($row['cond_qty'] ?? 0);
            ?>
            <tr>
              <td class="sno-cell"><?php echo $sno; ?></td>
              <td>
                <div class="item-cell">
                  <div class="item-icon"><svg viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 5h5M4.5 7h5M4.5 9h3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg></div>
                  <div>
                    <div class="item-code"><?php echo htmlspecialchars($row['prod_id']); ?></div>
                    <div class="item-name"></div>
                  </div>
                </div>
              </td>
              <td class="barcode-cell">
                <?php if ($bc_str): ?><span class="barcode-val"><?php echo htmlspecialchars($bc_str); ?></span><?php else: ?><span style="color:var(--text3);font-size:11px">—</span><?php endif; ?>
              </td>
              <td class="cell"><?php echo htmlspecialchars($row['prod_name']); ?></td>
              <td class="cell"><span class="batch-tag"><?php echo htmlspecialchars($row['batch']); ?></span></td>
              <td class="cell" style="font-weight:600"><?php echo number_format($row['asn_qty']); ?></td>
              <td class="cell" style="font-weight:600;color:<?php echo $is_complete ? 'var(--green)' : 'var(--text1)'; ?>"><?php echo number_format($row['qty']); ?></td>
              <td class="status-cell">
                <?php if ($damage_qty > 0): ?>
                  <span class="badge b-red" style="gap:5px">
                    <svg width="11" height="11" viewBox="0 0 11 11" fill="none"><path d="M5.5 1.5l4 7.5H1.5l4-7.5z" stroke="currentColor" stroke-width="1.1" stroke-linejoin="round"/><path d="M5.5 5v2" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/><circle cx="5.5" cy="8" r=".4" fill="currentColor"/></svg>
                    <?php echo $damage_qty; ?>
                  </span>
                <?php else: ?>
                  <span style="color:var(--text3);font-size:11px">—</span>
                <?php endif; ?>
              </td>
              <td class="qty-cell">
                <div style="font-size:10.5px;color:var(--text3);margin-bottom:4px"><?php echo $pct; ?>%</div>
                <div class="progress-track" style="width:80px">
                  <div class="progress-fill <?php echo $is_complete ? 'complete' : ''; ?>" style="width:<?php echo $pct; ?>%"></div>
                </div>
              </td>
              <td class="status-cell">
                <?php if ($is_complete): ?>
                  <span class="badge b-green">Complete</span>
                <?php elseif ($row['qty'] > 0): ?>
                  <span class="badge b-amber">Partial</span>
                <?php else: ?>
                  <span class="badge b-red">Pending</span>
                <?php endif; ?>
              </td>
              <td class="row-action">
                <!-- Edit -->
                <button type="button" class="act-icon-btn edit" title="Update receiving"
                  onclick="openEditModal(
                    '<?php echo $row['stockin_id']; ?>',
                    '<?php echo htmlspecialchars($test, ENT_QUOTES); ?>',
                    '<?php echo $rem_qty; ?>',
                    '<?php echo htmlspecialchars($row['qty'] . ' / ' . $row['asn_qty'], ENT_QUOTES); ?>',
                    '<?php echo htmlspecialchars($row['mfg'] ?? '', ENT_QUOTES); ?>',
                    '<?php echo htmlspecialchars($row['expiry'] ?? '', ENT_QUOTES); ?>',
                    '<?php echo intval($row['condqty'] ?? 0); ?>'
                  )">
                  <svg viewBox="0 0 13 13" fill="none"><path d="M9 2l2 2-7 7H2v-2L9 2z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <!-- Approve -->
                <button type="button" class="act-icon-btn approve" title="Approve item"
                  onclick="openApproveModal(
                    '<?php echo $row['stockin_id']; ?>',
                    '<?php echo htmlspecialchars($row['prod_name'] . ' — Batch# ' . $row['batch'] . ', Received: ' . $row['qty'], ENT_QUOTES); ?>',
                    '<?php echo htmlspecialchars($test, ENT_QUOTES); ?>'
                  )">
                  <svg viewBox="0 0 13 13" fill="none"><path d="M2 7l3 3 6-6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <!-- Clear -->
                <button type="button" class="act-icon-btn clear" title="Clear quantities"
                  onclick="openClearModal(
                    '<?php echo $row['stockin_id']; ?>',
                    '<?php echo htmlspecialchars($row['prod_name'] . ' — Batch# ' . $row['batch'], ENT_QUOTES); ?>',
                    '<?php echo htmlspecialchars($test, ENT_QUOTES); ?>'
                  )">
                  <svg viewBox="0 0 13 13" fill="none"><path d="M2 2l9 9M11 2l-9 9" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                </button>
              </td>
            </tr>
          <?php $sno++; endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <div class="tbl-footer">
        <div class="tbl-footer-note"><strong><?php echo $item_count; ?> item<?php echo $item_count != 1 ? 's' : ''; ?></strong> · ASN: <?php echo number_format($total_asn); ?> · Received: <?php echo number_format($total_rec); ?></div>
        <div class="tbl-footer-note">Last refreshed: <?php echo date('d M Y, H:i'); ?></div>
      </div>
    </div>

  </div><!-- /.main -->
</div><!-- /.layout -->


<!-- ══ MODAL: Edit / Update Receiving ════════════════════════════════════ -->
<div class="modal-backdrop" id="modal-edit">
  <div class="modal-box">
    <div class="modal-hdr">
      <div class="modal-hdr-title">Update Receiving Details</div>
      <button class="modal-close" onclick="closeModal('modal-edit')">
        <svg viewBox="0 0 13 13" fill="none"><path d="M2 2l9 9M11 2l-9 9" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
      </button>
    </div>
    <form method="POST" action="received.php" enctype="multipart/form-data">
      <div class="modal-body">
        <input type="hidden" id="edit_id"     name="id">
        <input type="hidden" id="edit_doc"    name="doc_no">
        <input type="hidden" id="edit_rem"    name="textbox1">
        <div class="form-row">
          <label class="form-label">ASN Qty (Received / Total)</label>
          <input type="text" id="edit_qty_display" class="form-control" disabled>
        </div>
        <div class="form-row">
          <label class="form-label">Received Qty <span style="color:var(--red)">*</span></label>
          <input type="number" id="rec" name="rec" class="form-control" placeholder="Enter received quantity" required
            onchange="checkReceiveQty(this.value)">
        </div>
        <div class="form-row">
          <label class="form-label">MFG Date <span style="color:var(--red)">*</span></label>
          <input type="date" id="edit_mfg" name="mfg" class="form-control" max="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <div class="form-row">
          <label class="form-label">Expiry Date <span style="color:var(--red)">*</span></label>
          <input type="date" id="edit_expiry" name="expiry" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <div class="form-row">
          <label class="form-label">Damage Qty</label>
          <input type="number" id="edit_cond" name="condqty" class="form-control" value="0" min="0" required>
        </div>
        <div class="form-row">
          <label class="form-label">Condition</label>
          <select name="cond" class="form-control">
            <option value="good">Good</option>
            <option value="product Damaged">Product Damaged</option>
            <option value="expired">Expired</option>
            <option value="packaging damaged">Packaging Damaged</option>
            <option value="internal mishandling">Internal Mishandling</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-edit')">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <svg viewBox="0 0 13 13" fill="none"><path d="M2 7l3 3 6-6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ══ MODAL: Approve ════════════════════════════════════════════════════ -->
<div class="modal-backdrop" id="modal-approve">
  <div class="modal-box">
    <div class="modal-hdr">
      <div class="modal-hdr-title">Approve Item</div>
      <button class="modal-close" onclick="closeModal('modal-approve')">
        <svg viewBox="0 0 13 13" fill="none"><path d="M2 2l9 9M11 2l-9 9" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
      </button>
    </div>
    <form method="POST" action="" enctype="multipart/form-data">
      <input type="hidden" id="approve_sub1" name="sub1">
      <div class="modal-body">
        <p class="confirm-text">Are you sure you want to approve:</p>
        <p class="confirm-text" style="margin-top:8px"><strong id="approve_desc"></strong></p>
        <div class="warning-box">
          <svg viewBox="0 0 14 14" fill="none"><path d="M7 2l5.5 9.5H1.5L7 2z" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/><path d="M7 6v3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/><circle cx="7" cy="10.5" r=".5" fill="currentColor"/></svg>
          This action will mark the item as approved and move it out of the pending queue.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-approve')">Cancel</button>
        <button type="submit" id="approve_btn" name="grn_no" class="btn btn-success">
          <svg viewBox="0 0 13 13" fill="none"><path d="M2 7l3 3 6-6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Approve
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ══ MODAL: Clear ══════════════════════════════════════════════════════ -->
<div class="modal-backdrop" id="modal-clear">
  <div class="modal-box">
    <div class="modal-hdr">
      <div class="modal-hdr-title">Clear Item Quantities</div>
      <button class="modal-close" onclick="closeModal('modal-clear')">
        <svg viewBox="0 0 13 13" fill="none"><path d="M2 2l9 9M11 2l-9 9" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
      </button>
    </div>
    <form method="POST" action="" enctype="multipart/form-data">
      <input type="hidden" id="clear_sub1" name="sub1">
      <div class="modal-body">
        <p class="confirm-text">Are you sure you want to clear all received quantities for:</p>
        <p class="confirm-text" style="margin-top:8px"><strong id="clear_desc"></strong></p>
        <div class="warning-box">
          <svg viewBox="0 0 14 14" fill="none"><path d="M7 2l5.5 9.5H1.5L7 2z" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/><path d="M7 6v3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/><circle cx="7" cy="10.5" r=".5" fill="currentColor"/></svg>
          This will reset received qty, balance, loose received, and loose balance to zero. This cannot be undone.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-clear')">Cancel</button>
        <button type="submit" id="clear_btn" name="clear" class="btn" style="background:var(--red);color:#fff">
          <svg viewBox="0 0 13 13" fill="none"><path d="M2 2l9 9M11 2l-9 9" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
          Clear
        </button>
      </div>
    </form>
  </div>
</div>


<script>
// Sidebar toggles
document.querySelectorAll('.nav-grp-hdr').forEach(function(h) {
  h.addEventListener('click', function() { h.parentElement.classList.toggle('open'); });
});

// Table search
function filterRows(v) {
  v = v.toLowerCase();
  document.querySelectorAll('#rt tbody tr').forEach(function(r) {
    r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none';
  });
}

// Modal helpers
function openModal(id)  { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
document.querySelectorAll('.modal-backdrop').forEach(function(bd) {
  bd.addEventListener('click', function(e) { if (e.target === bd) bd.classList.remove('show'); });
});

// Edit modal
function openEditModal(sid, doc, rem, qtyDisplay, mfg, expiry, condqty) {
  document.getElementById('edit_id').value          = sid;
  document.getElementById('edit_doc').value         = doc;
  document.getElementById('edit_rem').value         = rem;
  document.getElementById('edit_qty_display').value = qtyDisplay;
  document.getElementById('edit_mfg').value         = mfg;
  document.getElementById('edit_expiry').value      = expiry;
  document.getElementById('edit_cond').value        = condqty;
  document.getElementById('rec').value              = '';
  openModal('modal-edit');
}

// Approve modal
function openApproveModal(sid, desc, doc) {
  document.getElementById('approve_btn').value  = sid;
  document.getElementById('approve_sub1').value = doc;
  document.getElementById('approve_desc').textContent = desc;
  openModal('modal-approve');
}

// Clear modal
function openClearModal(sid, desc, doc) {
  document.getElementById('clear_btn').value  = sid;
  document.getElementById('clear_sub1').value = doc;
  document.getElementById('clear_desc').textContent = desc;
  openModal('modal-clear');
}

// Received qty check
function checkReceiveQty(val) {
  var rem = parseFloat(document.getElementById('edit_rem').value);
  var rec = parseFloat(val);
  if (!isNaN(rem) && !isNaN(rec) && rec > rem) {
    alert('Received QTY (' + rec + ') is greater than ASN remaining QTY (' + rem + ')');
  }
}
</script>
</body>
</html>