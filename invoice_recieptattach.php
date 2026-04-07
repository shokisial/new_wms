<?php
session_start();
if (empty($_SESSION['id']))     { header('Location:../index.php'); exit; }
if (empty($_SESSION['branch'])) { header('Location:../index.php'); exit; }
$branch  = $_SESSION['branch'];
$id      = $_SESSION['id'];
$name    = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$parts   = explode(' ', trim($name));
$initials = '';
foreach (array_slice($parts, 0, 2) as $p) $initials .= strtoupper($p[0]);

include('conn/dbcon.php');

// Active batch
$act = 0;
$qb = mysqli_query($con, "SELECT * FROM batch WHERE batch_status='1'") or die(mysqli_error($con));
while ($rb = mysqli_fetch_array($qb)) { $act = $rb['batch_no']; }

// Gate pass number from POST
$gn = isset($_POST['g_no1']) ? $_POST['g_no1'] : '';

// Check if already attached & get linked ASNs
$already_attached = false;
$asn_list = [];
if ($gn) {
    $query2 = mysqli_query($con,
        "SELECT * FROM `gatepass` WHERE indate='0' AND outdate='0' AND gatepass_id='$gn'")
        or die(mysqli_error($con));
    if (mysqli_num_rows($query2) === 0) { $already_attached = true; }

    $query0 = mysqli_query($con,
        "SELECT * FROM `stockin` WHERE gatepass_id='$gn' GROUP BY rec_dnno")
        or die(mysqli_error($con));
    while ($row0 = mysqli_fetch_array($query0)) { $asn_list[] = $row0['rec_dnno']; }
}

// Handle saveBtn (batch stock-in finalise)
if (isset($_POST["saveBtn"])) {
    foreach ($_POST["id"] as $rec => $value) {
        $qty         = $_POST["qty"][$rec];
        $asn_qt      = $_POST["asn_qt"][$rec];
        $pk_damage   = $_POST["pk_damage"][$rec];
        $unit_damage = $_POST["unit_damage"][$rec];
        $qtid2       = $_POST["id"][$rec];
        $pid         = $_POST['pid'][$rec];
        $blc         = $qty - $asn_qt;
        $dat         = date('d/m/Y');
        $uid         = $id;
        mysqli_query($con,
            "UPDATE `stockin` SET `user_id`='$uid',`final`='0',`asn_qty`='$asn_qt',
             `asn_balance`='$blc',`pk_damage`='$pk_damage',`unit_damage`='$unit_damage',
             rec_dat='$dat' WHERE `stockin_id`='$qtid2'") or die(mysqli_error($con));
        $qt = 0; $qt1 = 0;
        $qp = mysqli_query($con, "SELECT * FROM `product` WHERE `prod_id`='$pid'") or die(mysqli_error($con));
        while ($rp = mysqli_fetch_array($qp)) { $qt=$rp['prod_qty']; $qt1=$asn_qt+$qt; }
        mysqli_query($con, "UPDATE `product` SET `prod_qty`='$qt1' WHERE `prod_id`='$pid'") or die(mysqli_error($con));
    }
    echo "<script>alert('Record Saved Successfully!'); document.location='final_barcode.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sovereign WMS — Finalize Gate Pass</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
      --navy:#1a2238;--navy-mid:#1e2a42;--navy-light:#253350;
      --orange:#d95f2b;--orange-lt:#f4722e;--orange-muted:#fdf1eb;--orange-bd:#f6c9b0;
      --bg:#f2f1ee;--bg2:#ebe9e5;--white:#fff;
      --border:#e0ded8;--border2:#cccac3;
      --text1:#181816;--text2:#58574f;--text3:#9a9890;
      --green:#1a6b3a;--green-bg:#eef7f2;--green-bd:#b6dfc8;
      --red:#b91c1c;--red-bg:#fef2f2;--red-bd:#fecaca;
      --amber:#92580a;--amber-bg:#fffbeb;--amber-bd:#fcd88a;
      --blue:#1e4fa0;--blue-bg:#eff4ff;--blue-bd:#bdd0f8;
    }
    html{height:100%}
    body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text1);font-size:13px;line-height:1.5;height:100%;display:flex;flex-direction:column;overflow:hidden}

    /* ── Topbar ── */
    .topbar{height:52px;background:var(--navy);display:flex;align-items:center;padding:0 20px;gap:12px;border-bottom:2px solid var(--orange);flex-shrink:0}
    .brand-t{font-size:14px;font-weight:600;color:#fff;letter-spacing:-.2px}
    .brand-s{font-size:8.5px;color:rgba(255,255,255,.4);letter-spacing:.13em;text-transform:uppercase;display:block;margin-top:1px}
    .tbr{margin-left:auto;display:flex;align-items:center;gap:12px}
    .bpill{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:6px;padding:4px 11px;display:flex;align-items:center;gap:6px;font-size:11px;color:rgba(255,255,255,.5)}
    .bpill strong{color:#fff;font-weight:500}
    .uav{width:30px;height:30px;border-radius:50%;background:var(--orange);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0}

    /* ── Shell ── */
    .shell{display:flex;flex:1;overflow:hidden}

    /* ── Sidebar ── */
    .sidebar{width:210px;background:var(--navy);flex-shrink:0;display:flex;flex-direction:column;overflow:hidden}
    .sb-scroll{flex:1;overflow-y:auto;padding:4px 0 8px}
    .sb-scroll::-webkit-scrollbar{width:3px}
    .sb-scroll::-webkit-scrollbar-thumb{background:#2a3a55;border-radius:3px}
    .ns{padding:14px 12px 4px;font-size:9px;font-weight:700;color:#364d70;letter-spacing:.12em;text-transform:uppercase}
    .ni{display:flex;align-items:center;gap:9px;padding:7px 12px;color:#7a8ba8;font-size:12px;text-decoration:none;transition:background .1s,color .1s}
    .ni:hover{background:rgba(255,255,255,.05);color:#c8d3e8}
    .ni svg{width:14px;height:14px;flex-shrink:0;opacity:.5}
    .ng-hdr{display:flex;align-items:center;gap:9px;padding:7px 12px;cursor:pointer;color:#7a8ba8;font-size:12px;transition:background .1s,color .1s;-webkit-user-select:none;user-select:none}
    .ng-hdr:hover{background:rgba(255,255,255,.05);color:#c8d3e8}
    .ng-hdr svg.gi{width:14px;height:14px;flex-shrink:0;opacity:.5}
    .ng-hdr:hover svg.gi,.ng.open .ng-hdr svg.gi{opacity:.9}
    .ng-hdr svg.gc{margin-left:auto;width:10px;height:10px;opacity:.35;transition:transform .2s;flex-shrink:0}
    .ng.open .ng-hdr svg.gc{transform:rotate(90deg);opacity:.65}
    .ng.open .ng-hdr{color:#c8d3e8}
    .ng-items{display:none;padding:1px 0}
    .ng.open .ng-items{display:block}
    .na{display:flex;align-items:center;gap:8px;padding:5px 12px 5px 34px;color:#506275;font-size:11.5px;text-decoration:none;transition:color .1s,background .1s}
    .na:hover{color:#c8d3e8;background:rgba(255,255,255,.04)}
    .na.active{color:var(--orange-lt);font-weight:500}
    .na svg{width:5px;height:5px;flex-shrink:0;opacity:.7}
    .na.active svg{opacity:1}
    .nsep{height:1px;background:rgba(255,255,255,.06);margin:6px 12px}
    .sb-foot{padding:10px 12px;border-top:1px solid rgba(255,255,255,.06);flex-shrink:0}
    .sf{display:flex;align-items:center;gap:9px;padding:8px 10px;background:rgba(255,255,255,.05);border-radius:8px}
    .sf-av{width:28px;height:28px;border-radius:50%;background:var(--orange);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0}
    .sf-name{font-size:11.5px;font-weight:500;color:#c8d3e8}
    .sf-role{font-size:10px;color:#5a6e87;margin-top:1px}
    .sf-out{margin-left:auto;color:#5a6e87;text-decoration:none;display:flex;align-items:center}
    .sf-out:hover{color:#c8d3e8}
    .sf-out svg{width:14px;height:14px}

    /* ── Content ── */
    .content{flex:1;overflow-y:auto;display:flex;flex-direction:column}
    .content::-webkit-scrollbar{width:4px}
    .content::-webkit-scrollbar-thumb{background:var(--border2);border-radius:4px}

    /* ── Page header ── */
    .page-hdr{padding:18px 24px 0;flex-shrink:0}
    .crumb{font-size:11px;color:var(--text3);display:flex;align-items:center;gap:4px;margin-bottom:6px}
    .crumb a{color:var(--text2);text-decoration:none}
    .crumb a:hover{color:var(--text1)}
    .crumb svg{width:8px;height:8px;opacity:.6}
    .ph-row{display:flex;align-items:flex-start;justify-content:space-between;gap:16px}
    .ph-title{font-size:19px;font-weight:700;color:var(--text1);letter-spacing:-.4px}
    .ph-sub{font-size:12px;color:var(--text2);margin-top:3px}
    .batch-pill{display:inline-flex;align-items:center;gap:6px;background:var(--green-bg);border:1px solid var(--green-bd);border-radius:20px;padding:5px 13px;font-size:11px;font-weight:500;color:var(--green);white-space:nowrap;flex-shrink:0}
    .bp-dot{width:6px;height:6px;border-radius:50%;background:var(--green);flex-shrink:0}

    /* ── Body ── */
    .body{padding:16px 24px 32px;flex:1}

    /* ── Gate pass reference strip ── */
    .gp-strip{display:flex;align-items:center;gap:14px;background:var(--white);border:1px solid var(--border);border-left:3px solid var(--orange);border-radius:10px;padding:14px 18px;margin-bottom:18px}
    .gp-strip-icon{width:36px;height:36px;border-radius:9px;background:var(--orange-muted);border:1px solid var(--orange-bd);display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .gp-strip-icon svg{width:16px;height:16px;color:var(--orange)}
    .gp-strip-lbl{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:2px}
    .gp-strip-val{font-family:'JetBrains Mono',monospace;font-size:15px;font-weight:700;color:var(--text1)}
    .gp-strip-div{width:1px;height:36px;background:var(--border);flex-shrink:0}
    .gp-strip-asn-lbl{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:4px}
    .gp-strip-asn-tags{display:flex;flex-wrap:wrap;gap:5px}
    .asn-tag{display:inline-flex;align-items:center;padding:2px 9px;background:var(--blue-bg);border:1px solid var(--blue-bd);border-radius:20px;font-family:'JetBrains Mono',monospace;font-size:11px;font-weight:500;color:var(--blue)}

    /* ── Alert ── */
    .wms-alert{display:flex;align-items:flex-start;gap:11px;padding:14px 16px;border-radius:10px;margin-bottom:18px}
    .wms-alert-warn{background:var(--amber-bg);border:1px solid var(--amber-bd);color:var(--amber)}
    .wms-alert svg{width:16px;height:16px;flex-shrink:0;margin-top:1px}
    .wms-alert-title{font-size:12.5px;font-weight:600;margin-bottom:2px}
    .wms-alert-body{font-size:12px;color:var(--text2)}

    /* ── Form card ── */
    .form-card{background:var(--white);border:1px solid var(--border);border-radius:10px;overflow:hidden}
    .form-card-hdr{padding:13px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:9px;background:#fafaf7}
    .form-card-hdr svg{width:15px;height:15px;color:var(--orange);flex-shrink:0}
    .form-card-hdr-title{font-size:13px;font-weight:600;color:var(--text1)}
    .form-card-hdr-note{margin-left:auto;font-size:11px;color:var(--text3)}
    .form-card-body{padding:22px 20px 18px}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
    .form-field{}
    .form-label{display:block;font-size:10.5px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:7px}
    .form-label .req{color:var(--red)}
    .field-wrap{position:relative}
    .field-ico{position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;display:flex;align-items:center}
    .field-ico svg{width:13px;height:13px;color:var(--text3)}
    input[type="datetime-local"].wms-input{
      width:100%;padding:9px 11px 9px 34px;
      border:1px solid var(--border2);border-radius:7px;
      font-size:12.5px;font-family:'JetBrains Mono',monospace;
      color:var(--text1);background:var(--white);
      outline:none;transition:border .15s,box-shadow .15s;
      -webkit-appearance:none
    }
    input[type="datetime-local"].wms-input:focus{border-color:#93aac8;box-shadow:0 0 0 3px rgba(30,79,160,.08)}
    input[type="datetime-local"].wms-input::-webkit-calendar-picker-indicator{opacity:.45;cursor:pointer}
    .field-hint{font-size:11px;color:var(--text3);margin-top:5px;display:flex;align-items:center;gap:4px}
    .field-hint svg{width:11px;height:11px;flex-shrink:0}
    .form-card-foot{padding:13px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:#fafaf7}
    .foot-note{font-size:11px;color:var(--text3);display:flex;align-items:center;gap:5px}
    .foot-note svg{width:12px;height:12px;flex-shrink:0}
    .foot-actions{display:flex;align-items:center;gap:8px}
    .btn-ghost{display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:7px;background:transparent;border:1px solid var(--border2);color:var(--text2);font-size:12.5px;font-weight:500;font-family:'Inter',sans-serif;cursor:pointer;text-decoration:none;transition:border .12s,color .12s}
    .btn-ghost:hover{border-color:var(--border);color:var(--text1)}
    .btn-ghost svg{width:12px;height:12px}
    .btn-primary{display:inline-flex;align-items:center;gap:6px;padding:7px 18px;border-radius:7px;background:var(--navy);border:1px solid var(--navy);color:#fff;font-size:12.5px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;transition:background .12s,transform .1s}
    .btn-primary:hover{background:var(--navy-mid);transform:translateY(-1px)}
    .btn-primary svg{width:12px;height:12px}
  </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
  <svg width="28" height="33" viewBox="0 0 30 36" fill="none">
    <rect x="5" y="1" width="16" height="16" rx="1.5" transform="rotate(45 5 1)" stroke="#d95f2b" stroke-width="2.4" fill="none"/>
    <rect x="9" y="13" width="16" height="16" rx="1.5" transform="rotate(45 9 13)" stroke="#fff" stroke-width="2.4" fill="none"/>
  </svg>
  <div>
    <div class="brand-t">Sovereign</div>
    <span class="brand-s">Warehousing &amp; Distribution</span>
  </div>
  <div class="tbr">
    <div class="bpill">
      <svg width="11" height="11" viewBox="0 0 12 12" fill="none"><rect x="1" y="4" width="10" height="7" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4 4V3a2 2 0 1 1 4 0v1" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
      Branch: <strong><?php echo htmlspecialchars($branch); ?></strong>
    </div>
    <div class="uav"><?php echo htmlspecialchars($initials); ?></div>
  </div>
</div>

<div class="shell">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sb-scroll">
      <div class="ns">Main</div>
      <a href="new_dash.php" class="ni">
        <svg viewBox="0 0 14 14" fill="none"><rect x="1" y="1" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="8" y="1" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="1" y="8" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="8" y="8" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/></svg>
        Dashboard
      </a>
      <div class="ns">Operations</div>
      <div class="ng open">
        <div class="ng-hdr">
          <svg class="gi" viewBox="0 0 14 14" fill="none"><path d="M1 7h12M7 3l4 4-4 4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Inbound
          <svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div class="ng-items">
          <a href="inward_transaction.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>A.S.N</a>
          <a href="gatepass.php" class="na active"><svg viewBox="0 0 6 6" fill="var(--orange-lt)"><circle cx="3" cy="3" r="2.5"/></svg>Gate Pass</a>
          <a href="final_barcode.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Receive</a>
          <a href="final_location.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Location</a>
          <a href="index_stkveh.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Location List</a>
        </div>
      </div>
      <div class="ng">
        <div class="ng-hdr">
          <svg class="gi" viewBox="0 0 14 14" fill="none"><path d="M13 7H1M7 11l-4-4 4-4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Outbound
          <svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div class="ng-items">
          <a href="outward_transaction.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Transfer Note</a>
          <a href="final_out2.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Order Preparation</a>
          <a href="picking_summery.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Picking Summary</a>
          <a href="seg_list.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Segregation List</a>
          <a href="gatepass_out.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Gate Pass</a>
        </div>
      </div>
      <div class="ng">
        <div class="ng-hdr">
          <svg class="gi" viewBox="0 0 14 14" fill="none"><path d="M2 5h8a3 3 0 0 1 0 6H6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 3L2 5l2 2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Return
          <svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div class="ng-items">
          <a href="final_barcode_return.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Return Stock</a>
          <a href="gatepass_newreturn.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Return Gate Pass</a>
        </div>
      </div>
      <div class="nsep"></div>
      <div class="ns">Warehouse</div>
      <div class="ng">
        <div class="ng-hdr">
          <svg class="gi" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="2" stroke="currentColor" stroke-width="1.2"/><path d="M7 1v1.5M7 11.5V13M1 7h1.5M11.5 7H13M2.93 2.93l1.06 1.06M10.01 10.01l1.06 1.06M2.93 11.07l1.06-1.06M10.01 3.99l1.06-1.06" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
          Configuration
          <svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div class="ng-items">
          <a href="product.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Items</a>
          <a href="supplier.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Customer</a>
          <a href="zone.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Zone</a>
        </div>
      </div>
      <div class="ng">
        <div class="ng-hdr">
          <svg class="gi" viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 5h5M4.5 7.5h5M4.5 10h3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
          Reports
          <svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div class="ng-items">
          <a href="inbound_report.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Inbound Report</a>
          <a href="outbound_report.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Outbound Report</a>
          <a href="expire.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Expiry Report</a>
          <a href="index_ledger.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Customer Ledger</a>
        </div>
      </div>
      <div class="ng">
        <div class="ng-hdr">
          <svg class="gi" viewBox="0 0 14 14" fill="none"><path d="M1 11L4.5 7l3 2.5L11 4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Performance
          <svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div class="ng-items">
          <a href="rec_time.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Receive Accuracy</a>
          <a href="picking_time.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Picking Time</a>
          <a href="quality_checkrpt.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>QMS</a>
        </div>
      </div>
      <div class="nsep"></div>
      <div class="ns">Quality</div>
      <div class="ng">
        <div class="ng-hdr">
          <svg class="gi" viewBox="0 0 14 14" fill="none"><path d="M2 7l3.5 3.5L12 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Quality Check
          <svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div class="ng-items">
          <a href="qualitycheck.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Daily QMS</a>
          <a href="qualitycheck_week.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Weekly QMS</a>
        </div>
      </div>
    </div>
    <div class="sb-foot">
      <div class="sf">
        <div class="sf-av"><?php echo htmlspecialchars($initials); ?></div>
        <div>
          <div class="sf-name"><?php echo htmlspecialchars($name); ?></div>
          <div class="sf-role">Warehouse Manager</div>
        </div>
        <a href="logout.php" class="sf-out">
          <svg viewBox="0 0 14 14" fill="none"><path d="M9 7H1M5 4l-3 3 3 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 2h2.5A1.5 1.5 0 0 1 13 3.5v7A1.5 1.5 0 0 1 11.5 12H9" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        </a>
      </div>
    </div>
  </aside>

  <!-- Content -->
  <div class="content">
    <div class="page-hdr">
      <div class="crumb">
        <a href="gatepass.php">Inbound</a>
        <svg viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <a href="gatepass.php">Gate Pass</a>
        <svg viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Finalize
      </div>
      <div class="ph-row">
        <div>
          <div class="ph-title">Finalize Gate Pass</div>
          <div class="ph-sub">Offload complete — record vehicle in &amp; out time to close the gate pass</div>
        </div>
        <?php if ($act): ?>
        <div class="batch-pill"><div class="bp-dot"></div>Active Batch: <strong><?php echo htmlspecialchars($act); ?></strong></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="body">

      <?php if ($already_attached): ?>
      <!-- Already attached -->
      <div class="wms-alert wms-alert-warn">
        <svg viewBox="0 0 16 16" fill="none"><path d="M8 2L1.5 13.5h13L8 2z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/><path d="M8 6.5v3M8 11v.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        <div>
          <div class="wms-alert-title">ASN Already Attached</div>
          <div class="wms-alert-body">Gate Pass <strong>#<?php echo htmlspecialchars($gn); ?></strong> has already been finalised. Redirecting to Gate Pass list…</div>
        </div>
      </div>
      <script>setTimeout(function(){ document.location='gatepass.php'; }, 2500);</script>

      <?php else: ?>

      <!-- Gate Pass + ASN reference strip -->
      <div class="gp-strip">
        <div class="gp-strip-icon">
          <svg viewBox="0 0 16 16" fill="none"><rect x="1" y="4" width="14" height="9" rx="1.5" stroke="currentColor" stroke-width="1.3"/><path d="M5 4V3a3 3 0 0 1 6 0v1" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="gp-strip-lbl">Gate Pass</div>
          <div class="gp-strip-val">#<?php echo htmlspecialchars($gn); ?></div>
        </div>
        <?php if (!empty($asn_list)): ?>
        <div class="gp-strip-div"></div>
        <div>
          <div class="gp-strip-asn-lbl">Linked A.S.N(s)</div>
          <div class="gp-strip-asn-tags">
            <?php foreach ($asn_list as $asn): ?>
            <span class="asn-tag"><?php echo htmlspecialchars($asn); ?></span>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Time Entry Form Card -->
      <div class="form-card">
        <div class="form-card-hdr">
          <svg viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.3"/><path d="M8 4.5v4l2.5 2" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <div class="form-card-hdr-title">Vehicle Time Entry</div>
          <div class="form-card-hdr-note">Both fields required to finalise</div>
        </div>

        <form method="post" action="gatepass_finilize.php" enctype="multipart/form-data">
          <input type="hidden" name="gtno" value="<?php echo htmlspecialchars($gn); ?>">

          <div class="form-card-body">
            <div class="form-grid">

              <!-- In Date Time -->
              <div class="form-field">
                <label class="form-label">
                  Vehicle In Date &amp; Time <span class="req">*</span>
                </label>
                <div class="field-wrap">
                  <span class="field-ico">
                    <svg viewBox="0 0 13 13" fill="none"><path d="M1 5.5h11M4 1v2M9 1v2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/><rect x="1" y="2.5" width="11" height="9.5" rx="1.5" stroke="currentColor" stroke-width="1.2"/><circle cx="4.5" cy="8.5" r=".9" fill="var(--green)"/></svg>
                  </span>
                  <input type="datetime-local" class="wms-input" name="indate" required>
                </div>
                <div class="field-hint">
                  <svg viewBox="0 0 11 11" fill="none"><circle cx="5.5" cy="5.5" r="4.5" stroke="currentColor" stroke-width="1"/><path d="M5.5 3.5v2.5l1.5 1" stroke="currentColor" stroke-width="1" stroke-linecap="round"/></svg>
                  When the vehicle arrived at the warehouse gate
                </div>
              </div>

              <!-- Out Date Time -->
              <div class="form-field">
                <label class="form-label">
                  Vehicle Out Date &amp; Time <span class="req">*</span>
                </label>
                <div class="field-wrap">
                  <span class="field-ico">
                    <svg viewBox="0 0 13 13" fill="none"><path d="M1 5.5h11M4 1v2M9 1v2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/><rect x="1" y="2.5" width="11" height="9.5" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M3.5 8.5l1 1 2-2" stroke="var(--orange)" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  </span>
                  <input type="datetime-local" class="wms-input" name="outdate" required>
                </div>
                <div class="field-hint">
                  <svg viewBox="0 0 11 11" fill="none"><circle cx="5.5" cy="5.5" r="4.5" stroke="currentColor" stroke-width="1"/><path d="M5.5 3.5v2.5l1.5 1" stroke="currentColor" stroke-width="1" stroke-linecap="round"/></svg>
                  When the vehicle departed after offloading
                </div>
              </div>

            </div>
          </div>

          <div class="form-card-foot">
            <div class="foot-note">
              <svg viewBox="0 0 12 12" fill="none"><circle cx="6" cy="6" r="5" stroke="currentColor" stroke-width="1.1"/><path d="M6 5v3M6 3.5v.5" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
              This will close Gate Pass #<?php echo htmlspecialchars($gn); ?>
            </div>
            <div class="foot-actions">
              <a href="gatepass.php" class="btn-ghost">
                <svg viewBox="0 0 12 12" fill="none"><path d="M8 2L3 6 8 10" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Cancel
              </a>
              <button type="submit" class="btn-primary">
                <svg viewBox="0 0 12 12" fill="none"><path d="M2 6l3 3 5-6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Confirm &amp; Save
              </button>
            </div>
          </div>
        </form>
      </div>

      <?php endif; ?>

    </div><!-- /.body -->
  </div><!-- /.content -->
</div><!-- /.shell -->

<script>
document.querySelectorAll('.ng-hdr').forEach(function(h){
  h.addEventListener('click', function(){ h.parentElement.classList.toggle('open'); });
});
</script>
</body>
</html>