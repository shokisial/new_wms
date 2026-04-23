<?php
session_start();
if (empty($_SESSION['id']))     { header('Location:../index.php'); exit; }
if (empty($_SESSION['branch'])) { header('Location:../index.php'); exit; }
$branch = $_SESSION['branch'];
$id     = $_SESSION['id'];
$name   = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$user_group = $_SESSION['user_group']; 

include('conn/dbcon.php');

// ── Suppliers for filter + add dropdown ───────────────────────────────────────
$suppliers = [];
$qs = mysqli_query($con, "SELECT supplier_id, supplier_name FROM supplier WHERE branch_id='$branch' ORDER BY supplier_name ASC") or die(mysqli_error());
while ($r = mysqli_fetch_assoc($qs)) $suppliers[] = $r;

// ── Active customer filter ────────────────────────────────────────────────────
$sup_filter = $_POST['optionlist'] ?? '';
echo 'splr team = ' . $sup_filter;
// ── Fetch products ────────────────────────────────────────────────────────────
$products   = [];
$where_sup  = $sup_filter ? "AND product.supplier_id='$sup_filter'" : '';
$qp = mysqli_query($con,
  "SELECT product.*, supplier.supplier_name
   FROM product
   INNER JOIN supplier ON supplier.supplier_id = product.supplier_id
   WHERE product.branch_id = '$branch' $where_sup
   ORDER BY product.prod_name ASC"
) or die(mysqli_error());

while ($r = mysqli_fetch_assoc($qp)) {
  // fetch barcode for each product
  $pds = $r['prod_desc'];
  $brc = '';
  $qb = mysqli_query($con, "SELECT barcode FROM product_barcode WHERE prod_desc='$pds' LIMIT 1");
  if ($qb && $brow = mysqli_fetch_assoc($qb)) $brc = $brow['barcode'];
  $r['_barcode'] = $brc;
  $products[] = $r;
}

$total      = count($products);
$sup_label  = '';
if ($sup_filter) {
  foreach ($suppliers as $s) {
    if ($s['supplier_id'] == $sup_filter) { $sup_label = $s['supplier_name']; break; }
  }
}

// ── Temp + picking options ────────────────────────────────────────────────────
$temp_opts   = ['ambient', 'chilled', 'temperature controlled', 'frozen'];
$method_opts = ['Batch', 'FIFO'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sovereign WMS — Product List</title>
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
      --purple:#6b21a8; --purple-bg:#f5f3ff; --purple-bd:#ddd6fe;
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

    /* ── Filter bar ── */
    .filter-bar { background:var(--white); border:1px solid var(--border); border-radius:10px; padding:14px 18px; margin-bottom:20px; display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
    .filter-bar form { display:contents; }
    .fc-group { display:flex; flex-direction:column; gap:5px; }
    .fc-label { font-size:10.5px; font-weight:600; color:var(--text3); text-transform:uppercase; letter-spacing:.06em; }
    .fc-select { height:36px; padding:0 30px 0 12px; border:1px solid var(--border2); border-radius:7px; font-size:12px; font-family:'Inter',sans-serif; color:var(--text1); background:var(--bg); outline:none; transition:border .15s; appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%239e9c96' stroke-width='1.3' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 10px center; min-width:220px; }
    .fc-select:focus { border-color:#9aafcf; background-color:var(--white); }
    .fc-btn { height:36px; padding:0 16px; border-radius:7px; font-size:12px; font-weight:500; cursor:pointer; border:none; font-family:'Inter',sans-serif; background:var(--navy); color:#fff; display:inline-flex; align-items:center; gap:6px; white-space:nowrap; transition:all .13s; }
    .fc-btn:hover { background:var(--navy-mid); }
    .fc-btn svg { width:13px; height:13px; }
    .fc-clear { height:36px; padding:0 14px; border-radius:7px; font-size:12px; font-weight:500; cursor:pointer; border:1px solid var(--border2); font-family:'Inter',sans-serif; background:var(--white); color:var(--text2); display:inline-flex; align-items:center; gap:5px; text-decoration:none; transition:all .13s; }
    .fc-clear:hover { background:var(--bg2); }

    /* ── Stats ── */
    .stats-row { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
    @media(max-width:900px){ .stats-row { grid-template-columns:repeat(2,1fr); } }
    .stat-card { background:var(--white); border:1px solid var(--border); border-radius:10px; padding:14px 16px; display:flex; align-items:center; gap:12px; }
    .stat-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .stat-icon svg { width:18px; height:18px; }
    .si-navy   { background:var(--navy);        color:#fff; }
    .si-orange { background:var(--orange-muted);color:var(--orange); }
    .si-green  { background:var(--green-bg);    color:var(--green); }
    .si-purple { background:var(--purple-bg);   color:var(--purple); }
    .stat-label { font-size:10.5px; color:var(--text3); margin-bottom:2px; }
    .stat-value { font-size:20px; font-weight:700; color:var(--text1); letter-spacing:-.5px; line-height:1; }
    .stat-sub { font-size:10px; color:var(--text3); margin-top:2px; }

    /* ── Buttons ── */
    .btn-primary-wms { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; border-radius:7px; font-size:12px; font-weight:500; cursor:pointer; border:none; font-family:'Inter',sans-serif; transition:all .13s; background:var(--navy); color:#fff; text-decoration:none; }
    .btn-primary-wms:hover { background:var(--navy-mid); }
    .btn-outline-wms { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; border-radius:7px; font-size:12px; font-weight:500; cursor:pointer; border:1px solid var(--border2); font-family:'Inter',sans-serif; transition:all .13s; background:var(--white); color:var(--text2); text-decoration:none; }
    .btn-outline-wms:hover { background:var(--bg2); color:var(--text1); }
    .btn-amber-wms { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; border-radius:7px; font-size:12px; font-weight:500; cursor:pointer; border:1px solid var(--amber-bd); font-family:'Inter',sans-serif; transition:all .13s; background:var(--amber-bg); color:var(--amber); text-decoration:none; }
    .btn-amber-wms:hover { background:#fdefc0; }
    .btn-primary-wms svg, .btn-outline-wms svg, .btn-amber-wms svg { width:13px; height:13px; }

    /* ── Card / Table ── */
   .card { background:var(--white); border:1px solid var(--border); border-radius:10px; overflow:hidden; width:100%; }
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
    th { font-size:10px; font-weight:600; color:var(--text3); text-transform:uppercase; letter-spacing:.06em; padding:9px 14px; border-bottom:1px solid var(--border); text-align:left; white-space:nowrap; background:#fafaf8; }
    td { padding:0; border-bottom:1px solid #f2f0ec; vertical-align:middle; }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:#faf9f7; }
    .cell { padding:10px 14px; font-size:12px; color:var(--text1); }

    /* Product name cell */
    .prod-cell { display:flex; align-items:center; gap:10px; padding:10px 14px; }
    .prod-icon { width:34px; height:34px; border-radius:8px; background:var(--bg2); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:all .13s; }
    .prod-icon svg { width:15px; height:15px; color:var(--text2); }
    tbody tr:hover .prod-icon { background:var(--orange-muted); border-color:var(--orange-border); }
    tbody tr:hover .prod-icon svg { color:var(--orange); }
    .prod-name { font-size:12px; font-weight:500; color:var(--text1); }
    .prod-code { font-family:'JetBrains Mono',monospace; font-size:10.5px; color:var(--text3); margin-top:1px; }

    .mono { font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:500; }

    /* Temp badge */
    .temp-badge { display:inline-flex; align-items:center; gap:4px; border-radius:5px; padding:2px 8px; font-size:10.5px; font-weight:500; }
    .temp-ambient  { background:var(--green-bg);  border:1px solid var(--green-bd);  color:var(--green); }
    .temp-chilled  { background:var(--blue-bg);   border:1px solid var(--blue-bd);   color:var(--blue); }
    .temp-frozen   { background:var(--purple-bg); border:1px solid var(--purple-bd); color:var(--purple); }
    .temp-controlled{ background:var(--amber-bg); border:1px solid var(--amber-bd);  color:var(--amber); }

    /* Expiry badge */
    .exp-on  { display:inline-flex;align-items:center;gap:4px;background:var(--green-bg);border:1px solid var(--green-bd);border-radius:5px;padding:2px 8px;font-size:10.5px;font-weight:500;color:var(--green); }
    .exp-off { display:inline-flex;align-items:center;gap:4px;background:var(--bg2);border:1px solid var(--border2);border-radius:5px;padding:2px 8px;font-size:10.5px;font-weight:500;color:var(--text3); }

    /* Action buttons */
    .action-wrap { display:flex; align-items:center; gap:6px; padding:10px 14px; justify-content:center; }
    .act-btn { width:28px; height:28px; border-radius:6px; border:1px solid var(--border2); background:var(--white); display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all .13s; }
    .act-btn svg { width:13px; height:13px; color:var(--text3); }
    .act-btn-edit:hover  { background:var(--blue-bg); border-color:var(--blue-bd); }
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
    .empty-sub { font-size:12px; color:var(--text2); }

    /* ── Slide-over drawer ── */
    .overlay { position:fixed; inset:0; background:rgba(0,0,0,.35); z-index:300; opacity:0; pointer-events:none; transition:opacity .22s; }
    .overlay.open { opacity:1; pointer-events:all; }
    .drawer { position:fixed; top:0; right:0; bottom:0; width:520px; max-width:96vw; background:var(--white); z-index:310; transform:translateX(100%); transition:transform .24s cubic-bezier(.4,0,.2,1); display:flex; flex-direction:column; box-shadow:-8px 0 32px rgba(0,0,0,.12); }
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

    /* Form fields */
    .form-section { font-size:10px; font-weight:700; color:var(--text3); text-transform:uppercase; letter-spacing:.1em; padding-bottom:8px; border-bottom:1px solid var(--border); margin-bottom:2px; }
    .form-row  { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .form-row3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
    .form-row.full, .form-row3.full { grid-template-columns:1fr; }
    .field { display:flex; flex-direction:column; gap:5px; }
    .field label { font-size:10.5px; font-weight:600; color:var(--text3); text-transform:uppercase; letter-spacing:.06em; }
    .field input, .field select { height:36px; padding:0 12px; border:1px solid var(--border2); border-radius:7px; font-size:12px; font-family:'Inter',sans-serif; color:var(--text1); background:var(--bg); outline:none; transition:border .15s; width:100%; }
    .field input:focus, .field select:focus { border-color:#9aafcf; background:var(--white); }
    .field input::placeholder { color:var(--text3); }
    .field select { appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%239e9c96' stroke-width='1.3' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 10px center; padding-right:28px; }

    /* Caution banner */
    .caution-banner { background:var(--amber-bg); border:1px solid var(--amber-bd); border-radius:8px; padding:10px 14px; font-size:11.5px; color:var(--amber); display:flex; align-items:center; gap:8px; }
    .caution-banner svg { width:15px; height:15px; flex-shrink:0; }

    /* Active filter pill */
    .filter-active { display:inline-flex; align-items:center; gap:5px; background:var(--blue-bg); border:1px solid var(--blue-bd); border-radius:20px; padding:3px 10px; font-size:11px; font-weight:500; color:var(--blue); }
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
      <a href="#">Master Data</a>
      <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Product List
    </div>

    <!-- Page Header -->
    <div class="ph">
      <div class="ph-left">
        <div class="title">Product List</div>
        <div class="sub">
          <?php if ($sup_label): ?>
            Filtered by <strong><?php echo htmlspecialchars($sup_label); ?></strong> &nbsp;·&nbsp;
          <?php endif; ?>
          <?php echo $total; ?> product<?php echo $total != 1 ? 's' : ''; ?> &nbsp;·&nbsp; <?php echo htmlspecialchars($branch); ?>
        </div>
      </div>
      <div class="ph-right">
        <a href="reports/index_product.php" target="_blank" class="btn-amber-wms">
          <svg viewBox="0 0 13 13" fill="none"><rect x="2" y="1" width="9" height="11" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 4h4M4.5 6.5h4M4.5 9h2.5" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
          Print List
        </a>
        <button onclick="openAddDrawer()" class="btn-primary-wms">
          <svg viewBox="0 0 13 13" fill="none"><path d="M6.5 2v9M2 6.5h9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
          Add Product
        </button>
      </div>
    </div>

    <!-- Customer Filter Bar -->
    <div class="filter-bar">
      <form method="POST" action="" style="display:contents">
        <div class="fc-group">
          <span class="fc-label">Filter by Customer</span>
          <select name="optionlist" class="fc-select" onchange="this.form.submit()">
            <option value="">All Customers</option>
            <?php foreach ($suppliers as $s): ?>
            <option value="<?php echo htmlspecialchars($s['supplier_id']); ?>"
              <?php echo ($s['supplier_id'] == $sup_filter) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($s['supplier_name']); ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php if ($sup_filter): ?>
        <a href="product.php" class="fc-clear">
          <svg viewBox="0 0 13 13" fill="none"><path d="M3 3l7 7M10 3l-7 7" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
          Clear
        </a>
        <?php endif; ?>
      </form>
      <?php if ($sup_filter && $sup_label): ?>
      <span class="filter-active">
        <svg width="10" height="10" viewBox="0 0 10 10" fill="none"><circle cx="5" cy="5" r="3.5" stroke="currentColor" stroke-width="1.2"/><path d="M3.5 5l1 1 2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <?php echo htmlspecialchars($sup_label); ?>
      </span>
      <?php endif; ?>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon si-navy">
          <svg viewBox="0 0 18 18" fill="none"><rect x="3" y="2" width="12" height="14" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 6h6M6 9h6M6 12h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Total Products</div>
          <div class="stat-value"><?php echo $total; ?></div>
          <div class="stat-sub"><?php echo $sup_label ?: 'All customers'; ?></div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-orange">
          <svg viewBox="0 0 18 18" fill="none"><circle cx="9" cy="6" r="3" stroke="currentColor" stroke-width="1.3"/><path d="M3 15c0-3.314 2.686-6 6-6s6 2.686 6 6" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Customers</div>
          <div class="stat-value"><?php echo count($suppliers); ?></div>
          <div class="stat-sub">In this branch</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-green">
          <svg viewBox="0 0 18 18" fill="none"><path d="M4 9h10M9 4l-5 5 5 5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div>
          <div class="stat-label">With Expiry Control</div>
          <div class="stat-value"><?php echo count(array_filter($products, fn($p) => $p['exp_control'] == '1')); ?></div>
          <div class="stat-sub">Expiry tracked</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-purple">
          <svg viewBox="0 0 18 18" fill="none"><rect x="3" y="6" width="12" height="9" rx="1.5" stroke="currentColor" stroke-width="1.3"/><path d="M6 6V4.5a3 3 0 0 1 6 0V6" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Branch</div>
          <div class="stat-value" style="font-size:15px"><?php echo htmlspecialchars($branch); ?></div>
          <div class="stat-sub">Current unit</div>
        </div>
      </div>
    </div>

    <!-- Table Card -->
    <div class="card">
      <div class="card-hdr">
        <div>
          <div class="card-hdr-title">Products</div>
          <?php if ($sup_label): ?>
          <div class="card-hdr-meta">Customer: <?php echo htmlspecialchars($sup_label); ?></div>
          <?php endif; ?>
        </div>
        <?php if (!empty($products)): ?>
        <div class="toolbar">
          <div class="search-wrap">
            <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
            <input type="text" id="si" placeholder="Search product, code, UOM…" oninput="filterRows(this.value)">
          </div>
        </div>
        <?php endif; ?>
      </div>

      <div class="tbl-wrap">
        <table id="st">
          <thead>
            <tr>
              <th>Product</th>
              <th>Carton / UOM</th>
              <th>Pallet Config</th>
              <th>Shelf Life (min/max/wh)</th>
              <th>Temperature</th>
              <th>Picking</th>
              <th>Expiry</th>
              <th>Customer</th>
              <th style="text-align:center">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($products)): ?>
            <tr><td colspan="9" style="border:none">
              <div class="empty-state">
                <div class="empty-icon">
                  <svg viewBox="0 0 22 22" fill="none"><rect x="3" y="2" width="16" height="18" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M7 7h8M7 10.5h8M7 14h5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
                </div>
                <div class="empty-title">No products found</div>
                <div class="empty-sub"><?php echo $sup_filter ? 'No products for this customer. Try a different filter or add a new product.' : 'Select a customer above or click "Add Product" to get started.'; ?></div>
              </div>
            </td></tr>
            <?php else: foreach ($products as $row): ?>

            <?php
              // Temp badge class
              $tc = 'temp-ambient';
              $tv = strtolower($row['prod_temp'] ?? '');
              if (str_contains($tv, 'chilled'))     $tc = 'temp-chilled';
              elseif (str_contains($tv, 'frozen'))  $tc = 'temp-frozen';
              elseif (str_contains($tv, 'control')) $tc = 'temp-controlled';
            ?>
            <tr>
              <!-- Product -->
              <td>
                <div class="prod-cell">
                  <div class="prod-icon">
                    <svg viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 5h5M4.5 7h5M4.5 9h3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
                  </div>
                  <div>
                    <div class="prod-name"><?php echo htmlspecialchars($row['prod_name']); ?></div>
                    <div class="prod-code"><?php echo htmlspecialchars($row['prod_desc']); ?></div>
                  </div>
                </div>
              </td>

              <!-- Carton / UOM -->
              <td>
                <div class="cell">
                  <div style="font-weight:500"><?php echo htmlspecialchars($row['volume'] ?: '—'); ?></div>
                  <div style="font-size:10.5px;color:var(--text3);margin-top:1px"><?php echo htmlspecialchars($row['uom'] ?: ''); ?></div>
                </div>
              </td>

              <!-- Pallet config -->
              <td>
                <div class="cell" style="font-size:11px;color:var(--text2)">
                  <?php
                    $lpp = $row['layer_perpallot'] ?? '—';
                    $cpl = $row['cases_perlayer']  ?? '—';
                    $che = $row['cases_he']        ?? ($row['CE_HE'] ?? '—');
                    $cve = $row['cases_vr']        ?? '—';
                  ?>
                  <div><?php echo $lpp; ?> layers &nbsp;·&nbsp; <?php echo $cpl; ?> cases/layer</div>
                  <div style="color:var(--text3);font-size:10.5px;margin-top:1px">H:<?php echo $che; ?> &nbsp; V:<?php echo $cve; ?></div>
                </div>
              </td>

              <!-- Shelf Life -->
              <td>
                <div class="cell mono" style="font-size:11px">
                  <?php
                    $slmin = $row['min_shelflife'] ?? '—';
                    $slmax = $row['shelf_life']    ?? '—';
                    $slwh  = $row['shelf_lifewh']  ?? '—';
                  ?>
                  <span style="color:var(--red)"><?php echo $slmin; ?></span>
                  &nbsp;/&nbsp;
                  <span style="color:var(--green)"><?php echo $slmax; ?></span>
                  &nbsp;/&nbsp;
                  <span style="color:var(--blue)"><?php echo $slwh; ?></span>
                  <div style="font-size:9.5px;color:var(--text3);margin-top:1px;font-family:'Inter',sans-serif">min / max / wh</div>
                </div>
              </td>

              <!-- Temperature -->
              <td>
                <div class="cell">
                  <?php if ($row['prod_temp']): ?>
                  <span class="temp-badge <?php echo $tc; ?>"><?php echo htmlspecialchars($row['prod_temp']); ?></span>
                  <?php else: ?>—<?php endif; ?>
                </div>
              </td>

              <!-- Picking Method -->
              <td>
                <div class="cell">
                  <?php if ($row['picking_method']): ?>
                  <span style="display:inline-flex;align-items:center;gap:4px;background:var(--bg2);border:1px solid var(--border2);border-radius:5px;padding:2px 8px;font-size:10.5px;font-weight:500;color:var(--text2)">
                    <?php echo htmlspecialchars($row['picking_method']); ?>
                  </span>
                  <?php else: ?>—<?php endif; ?>
                </div>
              </td>

              <!-- Expiry -->
              <td>
                <div class="cell">
                  <?php if ($row['exp_control'] == '1'): ?>
                  <span class="exp-on">
                    <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M2 4.5l2 2 3-3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    On
                  </span>
                  <?php else: ?>
                  <span class="exp-off">Off</span>
                  <?php endif; ?>
                </div>
              </td>

              <!-- Customer -->
              <td>
                <div class="cell" style="font-size:11.5px;color:var(--text2)"><?php echo htmlspecialchars($row['supplier_name'] ?? '—'); ?></div>
              </td>

              <!-- Action -->
              <td>
                <div class="action-wrap">
                  <button class="act-btn act-btn-edit" title="Edit product"
                    onclick="openEditDrawer(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                    <svg viewBox="0 0 13 13" fill="none"><path d="M9 2l2 2-6 6H3V8l6-6z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <?php if (!empty($products)): ?>
      <div class="tbl-footer">
        <div class="tbl-footer-note"><strong><?php echo $total; ?> product<?php echo $total != 1 ? 's' : ''; ?></strong><?php echo $sup_label ? ' · ' . htmlspecialchars($sup_label) : ''; ?></div>
        <div class="tbl-footer-note">Generated: <?php echo date('d M Y, H:i'); ?></div>
      </div>
      <?php endif; ?>
    </div>

  </div><!-- /.main -->
</div><!-- /.layout -->

<!-- ── Overlay ── -->
<div class="overlay" id="overlay" onclick="closeAllDrawers()"></div>

<!-- ══════════════════════════════════════════════════════════════
     ADD PRODUCT DRAWER
     ══════════════════════════════════════════════════════════════ -->
<div class="drawer" id="drawer-add">
  <div class="drawer-hdr">
    <div class="drawer-hdr-left">
      <div class="drawer-title">Add New Product</div>
      <div class="drawer-sub">All fields required unless noted</div>
    </div>
    <button class="drawer-close" onclick="closeAllDrawers()">
      <svg viewBox="0 0 14 14" fill="none"><path d="M3 3l8 8M11 3l-8 8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
    </button>
  </div>
  <form method="POST" action="product_add.php" enctype="multipart/form-data">
    <input type="hidden" name="mf_dat" value="<?php echo date('Y/m/d'); ?>">
    <div class="drawer-body">

      <div class="form-section">Product Identity</div>
      <div class="form-row">
        <div class="field">
          <label>Product Code</label>
          <input type="text" name="prod_desc" placeholder="e.g. RM1R10RK1B5B" required>
        </div>
        <div class="field">
          <label>Product Name</label>
          <input type="text" name="prod_name" placeholder="Full product name" required>
        </div>
      </div>
      <div class="form-row">
        <div class="field">
          <label>Barcode</label>
          <input type="text" name="barcode" placeholder="Scan or enter barcode" required>
        </div>
        <div class="field">
          <label>Weight per Unit</label>
          <input type="text" name="weight" placeholder="e.g. 1.5 kg" required>
        </div>
      </div>

      <div class="form-section">Carton &amp; UOM</div>
      <div class="form-row">
        <div class="field">
          <label>Carton Size</label>
          <input type="text" name="volume" placeholder="e.g. 12×500ml" required>
        </div>
        <div class="field">
          <label>Unit of Measurement</label>
          <input type="text" name="uom" placeholder="e.g. Pcs, Cartons" required>
        </div>
      </div>

      <div class="form-section">Pallet Configuration</div>
      <div class="form-row3">
        <div class="field">
          <label>Layers / Pallet</label>
          <input type="number" name="layer_perpallot" placeholder="0" min="0" required>
        </div>
        <div class="field">
          <label>Cases / Layer</label>
          <input type="number" name="case_perlayer" placeholder="0" min="0" required>
        </div>
        <div class="field">
          <label>Cases H×V (CE/HE)</label>
          <input type="number" name="ce_he" placeholder="0" min="0" required>
        </div>
      </div>
      <div class="form-row">
        <div class="field">
          <label>Cases Horizontal</label>
          <input type="number" name="ce_hr" placeholder="0" min="0" required>
        </div>
        <div class="field">
          <label>Cases Vertical</label>
          <input type="number" name="ce_vr" placeholder="0" min="0" required>
        </div>
      </div>

      <div class="form-section">Shelf Life (days)</div>
      <div class="form-row3">
        <div class="field">
          <label>Minimum</label>
          <input type="number" name="slmin" placeholder="e.g. 90" min="0" required>
        </div>
        <div class="field">
          <label>Maximum</label>
          <input type="number" name="slmax" placeholder="e.g. 365" min="0" required>
        </div>
        <div class="field">
          <label>Warehouse</label>
          <input type="number" name="slwh" placeholder="e.g. 180" min="0" required>
        </div>
      </div>

      <div class="form-section">Storage &amp; Handling</div>
      <div class="form-row">
        <div class="field">
          <label>Temperature</label>
          <select name="temp" required>
            <option value="">Select temperature</option>
            <?php foreach ($temp_opts as $t): ?>
            <option value="<?php echo $t; ?>"><?php echo ucfirst($t); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Picking Method</label>
          <select name="method" required>
            <option value="">Select method</option>
            <?php foreach ($method_opts as $m): ?>
            <option value="<?php echo $m; ?>"><?php echo $m; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="field">
          <label>Customer</label>
          <select name="supplier" required>
            <option value="">Select customer</option>
            <?php foreach ($suppliers as $s): ?>
            <option value="<?php echo htmlspecialchars($s['supplier_id']); ?>"><?php echo htmlspecialchars($s['supplier_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Expiry Control</label>
          <select name="exp" required>
            <option value="">Select</option>
            <option value="1">On</option>
            <option value="0">Off</option>
          </select>
        </div>
      </div>

    </div>
    <div class="drawer-footer">
      <button type="button" class="btn-outline-wms" onclick="closeAllDrawers()">Cancel</button>
      <button type="submit" class="btn-primary-wms">
        <svg viewBox="0 0 13 13" fill="none"><path d="M2 7l3 3 6-6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Save Product
      </button>
    </div>
  </form>
</div>

<!-- ══════════════════════════════════════════════════════════════
     EDIT PRODUCT DRAWER
     ══════════════════════════════════════════════════════════════ -->
<div class="drawer" id="drawer-edit">
  <div class="drawer-hdr">
    <div class="drawer-hdr-left">
      <div class="drawer-title">Edit Product</div>
      <div class="drawer-sub" id="edit-drawer-sub">Updating master data</div>
    </div>
    <button class="drawer-close" onclick="closeAllDrawers()">
      <svg viewBox="0 0 14 14" fill="none"><path d="M3 3l8 8M11 3l-8 8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
    </button>
  </div>
  <form method="POST" action="product_update.php" enctype="multipart/form-data">
    <input type="hidden" name="id"     id="edit-id">
    <input type="hidden" name="mf_dat" value="<?php echo date('Y/m/d'); ?>">
    <div class="drawer-body">

      <div class="caution-banner">
        <svg viewBox="0 0 15 15" fill="none"><path d="M7.5 2L1 13h13L7.5 2z" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/><path d="M7.5 6v3M7.5 11h.01" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        Changes affect master data across all inbound and outbound records.
      </div>

      <div class="form-section">Product Identity</div>
      <div class="form-row">
        <div class="field">
          <label>Product Code</label>
          <input type="text" name="prod_desc" id="edit-prod_desc" required>
        </div>
        <div class="field">
          <label>Product Name</label>
          <input type="text" name="prod_name" id="edit-prod_name" required>
        </div>
      </div>
      <div class="form-row">
        <div class="field">
          <label>Barcode</label>
          <input type="text" name="barcode" id="edit-barcode">
        </div>
        <div class="field">
          <label>Weight per Unit</label>
          <input type="text" name="weight" id="edit-weight" required>
        </div>
      </div>

      <div class="form-section">Carton &amp; UOM</div>
      <div class="form-row">
        <div class="field">
          <label>Carton Size</label>
          <input type="text" name="volume" id="edit-volume" required>
        </div>
        <div class="field">
          <label>Unit of Measurement</label>
          <input type="text" name="uom" id="edit-uom" required>
        </div>
      </div>

      <div class="form-section">Pallet Configuration</div>
      <div class="form-row3">
        <div class="field">
          <label>Layers / Pallet</label>
          <input type="number" name="layer_perpallot" id="edit-layer_perpallot" min="0" required>
        </div>
        <div class="field">
          <label>Cases / Layer</label>
          <input type="number" name="case_perlayer"   id="edit-case_perlayer"   min="0" required>
        </div>
        <div class="field">
          <label>Cases H×V (CE/HE)</label>
          <input type="number" name="ce_he"           id="edit-ce_he"           min="0" required>
        </div>
      </div>
      <div class="form-row">
        <div class="field">
          <label>Cases Horizontal</label>
          <input type="number" name="ce_hr" id="edit-ce_hr" min="0" required>
        </div>
        <div class="field">
          <label>Cases Vertical</label>
          <input type="number" name="ce_vr" id="edit-ce_vr" min="0" required>
        </div>
      </div>

      <div class="form-section">Shelf Life (days)</div>
      <div class="form-row3">
        <div class="field">
          <label>Minimum</label>
          <input type="number" name="slmin" id="edit-slmin" min="0" required>
        </div>
        <div class="field">
          <label>Maximum</label>
          <input type="number" name="slmax" id="edit-slmax" min="0" required>
        </div>
        <div class="field">
          <label>Warehouse</label>
          <input type="number" name="slwh"  id="edit-slwh"  min="0" required>
        </div>
      </div>

      <div class="form-section">Storage &amp; Handling</div>
      <div class="form-row">
        <div class="field">
          <label>Temperature</label>
          <select name="temp" id="edit-temp" required>
            <option value="">Select temperature</option>
            <?php foreach ($temp_opts as $t): ?>
            <option value="<?php echo $t; ?>"><?php echo ucfirst($t); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Picking Method</label>
          <select name="method" id="edit-method" required>
            <option value="">Select method</option>
            <?php foreach ($method_opts as $m): ?>
            <option value="<?php echo $m; ?>"><?php echo $m; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="field">
          <label>Customer</label>
          <select name="supplier" id="edit-supplier" required>
            <option value="">Select customer</option>
            <?php foreach ($suppliers as $s): ?>
            <option value="<?php echo htmlspecialchars($s['supplier_id']); ?>"><?php echo htmlspecialchars($s['supplier_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Expiry Control</label>
          <select name="exp" id="edit-exp" required>
            <option value="">Select</option>
            <option value="1">On</option>
            <option value="0">Off</option>
          </select>
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

// ── Add ──────────────────────────────────────────────────────────────────────
function openAddDrawer() { openDrawer('drawer-add'); }

// ── Edit ─────────────────────────────────────────────────────────────────────
function openEditDrawer(row) {
  var map = {
    'edit-id':             row.prod_id        || '',
    'edit-prod_desc':      row.prod_desc       || '',
    'edit-prod_name':      row.prod_name       || '',
    'edit-barcode':        row.sno             || '',   // barcode stored in sno column
    'edit-weight':         row.weight          || '',
    'edit-volume':         row.volume          || '',
    'edit-uom':            row.uom             || '',
    'edit-layer_perpallot':row.layer_perpallot || '',
    'edit-case_perlayer':  row.cases_perlayer  || '',
    'edit-ce_he':          row.CE_HE           || row.cases_he || '',
    'edit-ce_hr':          row.cases_he        || '',
    'edit-ce_vr':          row.cases_vr        || '',
    'edit-slmin':          row.min_shelflife   || '',
    'edit-slmax':          row.shelf_life      || '',
    'edit-slwh':           row.shelf_lifewh    || '',
  };
  for (var k in map) {
    var el = document.getElementById(k);
    if (el) el.value = map[k];
  }
  // selects
  setSelect('edit-temp',     row.prod_temp       || '');
  setSelect('edit-method',   row.picking_method  || '');
  setSelect('edit-supplier', row.supplier_id     || '');
  setSelect('edit-exp',      row.exp_control     || '0');

  document.getElementById('edit-drawer-sub').textContent = 'Editing: ' + (row.prod_name || '');
  openDrawer('drawer-edit');
}

function setSelect(id, val) {
  var el = document.getElementById(id);
  if (!el) return;
  for (var i = 0; i < el.options.length; i++) {
    if (el.options[i].value === String(val)) { el.selectedIndex = i; return; }
  }
}

// ── Keyboard close ───────────────────────────────────────────────────────────
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeAllDrawers();
});
</script>
</body>
</html>