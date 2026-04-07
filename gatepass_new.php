<?php
session_start();
if (empty($_SESSION['id']))     { header('Location:../index.php'); exit; }
if (empty($_SESSION['branch'])) { header('Location:../index.php'); exit; }
$branch = $_SESSION['branch'];
$id     = $_SESSION['id'];
$name   = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$parts  = explode(' ', trim($name));
$initials = '';
foreach (array_slice($parts, 0, 2) as $p) $initials .= strtoupper($p[0]);

include('conn/dbcon.php');

$act = 0;
$qb = mysqli_query($con, "SELECT * FROM batch WHERE batch_status='1'") or die(mysqli_error($con));
while ($rb = mysqli_fetch_array($qb)) { $act = $rb['batch_no']; }

$asns = array();
$qa = mysqli_query($con, "SELECT *, SUM(asn_qty) as asnqty FROM stockin WHERE branch_id='$branch' AND gatepass_id='0' GROUP BY rec_dnno") or die(mysqli_error($con));
$asn_count = mysqli_num_rows($qa);
while ($ra = mysqli_fetch_array($qa)) { $asns[] = $ra; }

$transporters = array();
$qt = mysqli_query($con, "SELECT * FROM transporter ORDER BY trns_name") or die(mysqli_error($con));
while ($rt = mysqli_fetch_array($qt)) { $transporters[] = $rt; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sovereign WMS — New Gate Pass</title>
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
    .topbar{height:52px;background:var(--navy);display:flex;align-items:center;padding:0 20px;gap:12px;border-bottom:2px solid var(--orange);flex-shrink:0;z-index:10}
    .brand-t{font-size:14px;font-weight:600;color:#fff;letter-spacing:-.2px}
    .brand-s{font-size:8.5px;color:rgba(255,255,255,.4);letter-spacing:.13em;text-transform:uppercase;display:block;margin-top:1px}
    .tbr{margin-left:auto;display:flex;align-items:center;gap:12px}
    .bpill{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:6px;padding:4px 11px;display:flex;align-items:center;gap:6px;font-size:11px;color:rgba(255,255,255,.5)}
    .bpill strong{color:#fff;font-weight:500}
    .uav{width:30px;height:30px;border-radius:50%;background:var(--orange);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0}

    /* ── Shell: sidebar + content side by side ── */
    .shell{display:flex;flex:1;overflow:hidden}

    /* ── Sidebar ── */
    .sidebar{width:210px;background:var(--navy);flex-shrink:0;display:flex;flex-direction:column;overflow:hidden}
    .sb-scroll{flex:1;overflow-y:auto;padding:4px 0 8px}
    .sb-scroll::-webkit-scrollbar{width:3px}
    .sb-scroll::-webkit-scrollbar-thumb{background:#2a3a55;border-radius:3px}
    .ns{padding:14px 12px 4px;font-size:9px;font-weight:700;color:#364d70;letter-spacing:.12em;text-transform:uppercase}
    .ni{display:flex;align-items:center;gap:9px;padding:7px 12px;color:#7a8ba8;font-size:12px;text-decoration:none;cursor:pointer;transition:background .1s,color .1s}
    .ni:hover{background:rgba(255,255,255,.05);color:#c8d3e8}
    .ni svg{width:14px;height:14px;flex-shrink:0;opacity:.5}
    .ni:hover svg{opacity:.9}
    .ng-hdr{display:flex;align-items:center;gap:9px;padding:7px 12px;color:#7a8ba8;font-size:12px;cursor:pointer;transition:background .1s,color .1s;-webkit-user-select:none;user-select:none}
    .ng-hdr:hover{background:rgba(255,255,255,.05);color:#c8d3e8}
    .ng-hdr svg.gi{width:14px;height:14px;flex-shrink:0;opacity:.5}
    .ng-hdr:hover svg.gi,.ng.open .ng-hdr svg.gi{opacity:.9}
    .ng-hdr svg.gc{margin-left:auto;width:10px;height:10px;opacity:.35;transition:transform .2s;flex-shrink:0}
    .ng.open .ng-hdr svg.gc{transform:rotate(90deg);opacity:.65}
    .ng.open .ng-hdr{color:#c8d3e8}
    .ng-items{display:none;padding:1px 0}
    .ng.open .ng-items{display:block}
    .na{display:flex;align-items:center;gap:8px;padding:5px 12px 5px 34px;color:#506275;font-size:11.5px;text-decoration:none;cursor:pointer;transition:color .1s,background .1s}
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
    .sf-out{margin-left:auto;color:#5a6e87;cursor:pointer;flex-shrink:0;text-decoration:none;display:flex;align-items:center}
    .sf-out:hover{color:#c8d3e8}
    .sf-out svg{width:14px;height:14px}

    /* ── Content ── */
    .content{flex:1;overflow-y:auto;display:flex;flex-direction:column}
    .content::-webkit-scrollbar{width:4px}
    .content::-webkit-scrollbar-thumb{background:var(--border2);border-radius:4px}

    .page-hdr{padding:18px 24px 0;display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-shrink:0}
    .crumb{font-size:11px;color:var(--text3);display:flex;align-items:center;gap:4px;margin-bottom:6px}
    .crumb a{color:var(--text2);text-decoration:none}
    .crumb a:hover{color:var(--text1)}
    .crumb svg{width:8px;height:8px;opacity:.6}
    .ph-title{font-size:19px;font-weight:700;color:var(--text1);letter-spacing:-.4px}
    .ph-sub{font-size:12px;color:var(--text2);margin-top:3px}
    .batch-pill{display:inline-flex;align-items:center;gap:6px;background:var(--green-bg);border:1px solid var(--green-bd);border-radius:20px;padding:5px 13px;font-size:11px;font-weight:500;color:var(--green);white-space:nowrap;flex-shrink:0}
    .bp-dot{width:6px;height:6px;border-radius:50%;background:var(--green);flex-shrink:0}

    .step-bar{display:flex;align-items:center;padding:14px 24px 0;flex-shrink:0}
    .step{display:flex;align-items:center;gap:7px}
    .sn{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0}
    .sn.done{background:var(--green);color:#fff}
    .sn.active{background:var(--navy);color:#fff}
    .sn.idle{background:var(--bg2);color:var(--text3);border:1px solid var(--border)}
    .sl-label{font-size:11px;font-weight:500;white-space:nowrap}
    .sl-label.active{color:var(--text1);font-weight:600}
    .sl-label.idle{color:var(--text3)}
    .sl-label.done{color:var(--green)}
    .sl-line{flex:1;height:1.5px;background:var(--border);margin:0 6px;min-width:12px}
    .sl-line.done{background:var(--green)}

    .body-wrap{flex:1;display:grid;grid-template-columns:1fr 316px;gap:16px;padding:16px 24px 32px;align-items:start}

    /* Cards */
    .card{background:var(--white);border:1px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:14px}
    .card:last-child{margin-bottom:0}
    .card-hdr{padding:11px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px}
    .ci{width:30px;height:30px;border-radius:7px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .ci svg{width:14px;height:14px}
    .ci-blue{background:var(--blue-bg);color:var(--blue)}
    .ci-orange{background:var(--orange-muted);color:var(--orange)}
    .ci-green{background:var(--green-bg);color:var(--green)}
    .ci-navy{background:#e8ecf5;color:var(--navy)}
    .ct{flex:1}
    .ct-title{font-size:13px;font-weight:600;color:var(--text1)}
    .ct-note{font-size:10.5px;color:var(--text3);margin-top:1px}
    .cn{width:20px;height:20px;border-radius:50%;background:var(--navy);color:#fff;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-left:auto}
    .cb{padding:16px}

    /* ASN table */
    .at{width:100%;border-collapse:collapse}
    .at th{font-size:9.5px;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;padding:8px 14px;border-bottom:1px solid var(--border);text-align:left;background:#fafaf7;white-space:nowrap}
    .at td{padding:10px 14px;border-bottom:1px solid #f0efe8;font-size:12px;color:var(--text1);vertical-align:middle}
    .at tr:last-child td{border-bottom:none}
    .at tbody tr{cursor:pointer;transition:background .1s}
    .at tbody tr:hover td{background:#f8f7f4}
    .at tbody tr.sel td{background:var(--blue-bg)}
    .at tbody tr.sel .adn{color:var(--blue)}
    .adn{font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:600}
    .mono{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text2)}
    .qchip{display:inline-flex;align-items:center;background:var(--bg2);border:1px solid var(--border);border-radius:6px;padding:2px 9px;font-size:11.5px;font-weight:600;color:var(--text1)}
    .sel-banner{display:flex;align-items:center;gap:8px;padding:8px 14px;background:var(--blue-bg);border-bottom:1px solid var(--blue-bd);font-size:12px;color:var(--blue);font-weight:500}
    .sel-banner svg{width:13px;height:13px;flex-shrink:0}
    .empty-asn{padding:36px 20px;text-align:center}
    .empty-asn svg{width:36px;height:36px;color:var(--text3);margin:0 auto 12px;display:block}
    .empty-title{font-size:14px;font-weight:600;color:var(--text1);margin-bottom:4px}
    .empty-sub{font-size:12px;color:var(--text2)}
    .alert-warn{display:flex;align-items:center;gap:10px;padding:11px 16px;background:var(--amber-bg);border:1px solid var(--amber-bd);border-radius:9px;margin-bottom:14px;font-size:12px;color:var(--amber)}
    .alert-warn svg{width:15px;height:15px;flex-shrink:0}

    /* Form */
    .fsec{margin-bottom:16px}
    .fsec:last-child{margin-bottom:0}
    .fsec-title{font-size:9.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.1em;margin-bottom:10px;padding-bottom:5px;border-bottom:1px solid var(--border)}
    .fg{display:grid;grid-template-columns:1fr 1fr;gap:11px}
    .fg.c1{grid-template-columns:1fr}
    .ff{display:flex;flex-direction:column;gap:4px}
    .ff label{font-size:10px;font-weight:600;color:var(--text2);text-transform:uppercase;letter-spacing:.06em}
    .req{color:var(--orange)}
    .ff input,.ff select,.ff textarea{padding:8px 10px;border:1.5px solid var(--border2);border-radius:7px;font-size:12.5px;font-family:'Inter',sans-serif;color:var(--text1);background:#fff;outline:none;width:100%;transition:border-color .15s;-webkit-appearance:none;appearance:none}
    .ff input:focus,.ff select:focus,.ff textarea:focus{border-color:#93aac8;box-shadow:0 0 0 3px rgba(30,79,160,.06)}
    .ff input::-webkit-input-placeholder,.ff textarea::-webkit-input-placeholder{color:var(--text3)}
    .ff textarea{resize:vertical;min-height:65px}
    .sw{position:relative}
    .sw::after{content:'';position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;border-left:4px solid transparent;border-right:4px solid transparent;border-top:5px solid var(--text3)}
    .sw select{padding-right:28px;cursor:pointer}
    .span2{grid-column:1/-1}

    /* Right column */
    .right-col{display:flex;flex-direction:column;gap:14px}
    .sum-card{background:var(--white);border:1px solid var(--border);border-radius:10px;overflow:hidden}
    .sum-hdr{padding:11px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
    .sum-hdr-title{font-size:12px;font-weight:600;color:var(--text1)}
    .sum-badge{font-size:10.5px;color:var(--text3);background:var(--bg2);border:1px solid var(--border);border-radius:20px;padding:2px 9px}
    .sum-lines{padding:2px 0}
    .sum-row{display:flex;align-items:center;justify-content:space-between;padding:9px 14px;border-bottom:1px solid #f0efe8;font-size:12px}
    .sum-row:last-child{border-bottom:none}
    .sk{color:var(--text2);display:flex;align-items:center;gap:6px}
    .sk svg{width:12px;height:12px;opacity:.5}
    .sv{font-weight:600;color:var(--text1);font-family:'JetBrains Mono',monospace;font-size:11.5px}
    .sv.hi{color:var(--orange);font-size:13px}
    .sv.ok{color:var(--green)}
    .act-card{background:var(--white);border:1px solid var(--border);border-radius:10px;padding:14px;display:flex;flex-direction:column;gap:9px}
    .btn-save{width:100%;padding:12px;background:var(--navy);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:background .13s}
    .btn-save:hover{background:var(--navy-mid)}
    .btn-save svg{width:14px;height:14px}
    .btn-back{width:100%;padding:10px;background:var(--bg);color:var(--text2);border:1px solid var(--border);border-radius:8px;font-size:12px;font-weight:500;font-family:'Inter',sans-serif;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;text-decoration:none;transition:all .13s}
    .btn-back:hover{background:var(--bg2);color:var(--text1)}
    .btn-back svg{width:12px;height:12px}
    .tips-card{background:var(--white);border:1px solid var(--border);border-radius:10px;overflow:hidden}
    .tips-hdr{padding:10px 14px;border-bottom:1px solid var(--border);font-size:12px;font-weight:600;color:var(--text1);display:flex;align-items:center;gap:7px}
    .tips-hdr svg{width:13px;height:13px;color:var(--orange)}
    .tips-body{padding:12px 14px;display:flex;flex-direction:column;gap:9px}
    .tip{font-size:11.5px;color:var(--text2);display:flex;align-items:flex-start;gap:8px;line-height:1.55}
    .tip-dot{width:5px;height:5px;border-radius:50%;background:var(--orange);flex-shrink:0;margin-top:5px}
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
      <a href="new_dash.php" class="ni"><svg viewBox="0 0 14 14" fill="none"><rect x="1" y="1" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="8" y="1" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="1" y="8" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="8" y="8" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/></svg>Dashboard</a>

      <div class="ns">Operations</div>

      <div class="ng open">
        <div class="ng-hdr"><svg class="gi" viewBox="0 0 14 14" fill="none"><path d="M1 7h12M7 3l4 4-4 4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Inbound<svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <div class="ng-items">
          <a href="inward_transaction.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>A.S.N</a>
          <a href="gatepass.php"           class="na active"><svg viewBox="0 0 6 6" fill="var(--orange-lt)"><circle cx="3" cy="3" r="2.5"/></svg>Gate Pass</a>
          <a href="final_barcode.php"      class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Receive</a>
          <a href="final_location.php"     class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Location</a>
          <a href="index_stkveh.php"       class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Location List</a>
        </div>
      </div>

      <div class="ng">
        <div class="ng-hdr"><svg class="gi" viewBox="0 0 14 14" fill="none"><path d="M13 7H1M7 11l-4-4 4-4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Outbound<svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <div class="ng-items">
          <a href="outward_transaction.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Transfer Note</a>
          <a href="final_out2.php"          class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Order Preparation</a>
          <a href="picking_summery.php"     class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Picking Summary</a>
          <a href="seg_list.php"            class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Segregation List</a>
          <a href="gatepass_out.php"        class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Gate Pass</a>
        </div>
      </div>

      <div class="ng">
        <div class="ng-hdr"><svg class="gi" viewBox="0 0 14 14" fill="none"><path d="M2 5h8a3 3 0 0 1 0 6H6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 3L2 5l2 2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Return<svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <div class="ng-items">
          <a href="final_barcode_return.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Return Stock</a>
          <a href="gatepass_newreturn.php"   class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Return Gate Pass</a>
        </div>
      </div>

      <div class="nsep"></div>
      <div class="ns">Warehouse</div>

      <div class="ng">
        <div class="ng-hdr"><svg class="gi" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="2" stroke="currentColor" stroke-width="1.2"/><path d="M7 1v1.5M7 11.5V13M1 7h1.5M11.5 7H13M2.93 2.93l1.06 1.06M10.01 10.01l1.06 1.06M2.93 11.07l1.06-1.06M10.01 3.99l1.06-1.06" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>Configuration<svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <div class="ng-items">
          <a href="product.php"  class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Items</a>
          <a href="supplier.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Customer</a>
          <a href="zone.php"     class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Zone</a>
        </div>
      </div>

      <div class="ng">
        <div class="ng-hdr"><svg class="gi" viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 5h5M4.5 7.5h5M4.5 10h3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>Reports<svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <div class="ng-items">
          <a href="inbound_report.php"  class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Inbound Report</a>
          <a href="outbound_report.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Outbound Report</a>
          <a href="expire.php"          class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Expiry Report</a>
          <a href="index_ledger.php"    class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Customer Ledger</a>
        </div>
      </div>

      <div class="ng">
        <div class="ng-hdr"><svg class="gi" viewBox="0 0 14 14" fill="none"><path d="M1 11L4.5 7l3 2.5L11 4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Performance<svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <div class="ng-items">
          <a href="rec_time.php"          class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Receive Accuracy</a>
          <a href="picking_time.php"      class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Picking Time</a>
          <a href="quality_checkrpt.php"  class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>QMS</a>
          <a href="stockcount_report.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Stock Count</a>
        </div>
      </div>

      <div class="nsep"></div>
      <div class="ns">Quality</div>

      <div class="ng">
        <div class="ng-hdr"><svg class="gi" viewBox="0 0 14 14" fill="none"><path d="M2 7l3.5 3.5L12 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Quality Check<svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <div class="ng-items">
          <a href="qualitycheck.php"      class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Daily QMS</a>
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
        <a href="logout.php" class="sf-out"><svg viewBox="0 0 14 14" fill="none"><path d="M9 7H1M5 4l-3 3 3 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 2h2.5A1.5 1.5 0 0 1 13 3.5v7A1.5 1.5 0 0 1 11.5 12H9" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg></a>
      </div>
    </div>
  </aside>

  <!-- Main content -->
  <div class="content">
    <div class="page-hdr">
      <div>
        <div class="crumb">
          <a href="#">Inbound</a><svg viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <a href="gatepass.php">Gate Pass</a><svg viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
          New Gate Pass
        </div>
        <div class="ph-title">New Inward Gate Pass</div>
        <div class="ph-sub">Select ASN documents, fill vehicle &amp; driver details, then save</div>
      </div>
      <?php if ($act): ?>
      <div class="batch-pill"><div class="bp-dot"></div>Active Batch: <strong><?php echo htmlspecialchars($act); ?></strong></div>
      <?php endif; ?>
    </div>

    <!-- Step bar -->
    <div class="step-bar">
      <div class="step"><div class="sn active" id="s1">1</div><div class="sl-label active">ASN Selection</div></div>
      <div class="sl-line" id="l1"></div>
      <div class="step"><div class="sn idle" id="s2">2</div><div class="sl-label idle" id="l2t">Vehicle &amp; Transport</div></div>
      <div class="sl-line" id="l2"></div>
      <div class="step"><div class="sn idle" id="s3">3</div><div class="sl-label idle" id="l3t">Driver Details</div></div>
      <div class="sl-line" id="l3"></div>
      <div class="step"><div class="sn idle" id="s4">4</div><div class="sl-label idle" id="l4t">Dates &amp; Times</div></div>
    </div>

    <form method="POST" action="gatepass_add.php" id="gpForm" onsubmit="return validateForm()">
    <div class="body-wrap">
      <div>

        <?php if ($asn_count === 0): ?>
        <div class="alert-warn">
          <svg viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.3"/><path d="M8 5v3.5M8 10.5v.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
          No ASN documents are available. Please create an ASN first before adding a gate pass.
        </div>
        <?php endif; ?>

        <!-- Step 1 -->
        <div class="card">
          <div class="card-hdr">
            <div class="ci ci-blue"><svg viewBox="0 0 15 15" fill="none"><rect x="2" y="1" width="11" height="13" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M5 5h5M5 7.5h5M5 10h3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg></div>
            <div class="ct"><div class="ct-title">ASN Documents</div><div class="ct-note">Select all documents arriving on the same vehicle</div></div>
            <div class="cn">1</div>
          </div>
          <div class="sel-banner" id="sb" style="display:none">
            <svg viewBox="0 0 14 14" fill="none"><path d="M2 7l3.5 3.5L12 3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span id="sbText">0 selected</span>
          </div>
          <?php if (empty($asns)): ?>
          <div class="empty-asn">
            <svg viewBox="0 0 22 22" fill="none"><rect x="3" y="3" width="16" height="16" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M7 11h8M11 7v8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
            <div class="empty-title">No ASN documents ready</div>
            <div class="empty-sub">Create an ASN first, then return here to add a gate pass.</div>
          </div>
          <?php else: ?>
          <table class="at">
            <thead>
              <tr>
                <th style="width:36px"><input type="checkbox" id="sa" style="width:15px;height:15px;accent-color:var(--navy);cursor:pointer" onchange="toggleAll(this)"></th>
                <th>Doc / ASN No.</th>
                <th>Vehicle No.</th>
                <th style="text-align:right">ASN Qty</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($asns as $asn): ?>
              <tr onclick="togRow(this)">
                <td><input type="checkbox" class="ac" name="grn_no[]" value="<?php echo htmlspecialchars($asn['rec_dnno']); ?>" data-qty="<?php echo intval($asn['asnqty']); ?>" style="width:15px;height:15px;accent-color:var(--navy);cursor:pointer" onclick="event.stopPropagation();upd()"></td>
                <td><div class="adn"><?php echo htmlspecialchars($asn['rec_dnno']); ?></div></td>
                <td><span class="mono"><?php echo htmlspecialchars($asn['truck_no']) ?: '—'; ?></span></td>
                <td style="text-align:right"><span class="qchip"><?php echo number_format($asn['asnqty']); ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>

        <!-- Step 2 -->
        <div class="card">
          <div class="card-hdr">
            <div class="ci ci-orange"><svg viewBox="0 0 15 15" fill="none"><rect x="1" y="5" width="13" height="7" rx="2" stroke="currentColor" stroke-width="1.2"/><path d="M4 5V4a3 3 0 0 1 7 0v1" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/><circle cx="4" cy="12" r="1.5" stroke="currentColor" stroke-width="1.2"/><circle cx="11" cy="12" r="1.5" stroke="currentColor" stroke-width="1.2"/></svg></div>
            <div class="ct"><div class="ct-title">Vehicle &amp; Transport</div><div class="ct-note">Transporter, vehicle number, type, dock and temperature</div></div>
            <div class="cn">2</div>
          </div>
          <div class="cb">
            <?php if (!empty($transporters)): ?>
            <div class="fsec">
              <div class="fsec-title">Transporter</div>
              <div class="fg c1">
                <div class="ff"><label>Transporter <span class="req">*</span></label><div class="sw"><select name="trns_name" required><option value="">— Select transporter —</option><?php foreach ($transporters as $t): ?><option value="<?php echo $t['trns_id']; ?>"><?php echo htmlspecialchars($t['trns_name']); ?></option><?php endforeach; ?></select></div></div>
              </div>
            </div>
            <?php endif; ?>
            <div class="fsec">
              <div class="fsec-title">Vehicle Details</div>
              <div class="fg">
                <div class="ff"><label>Vehicle No. <span class="req">*</span></label><input type="text" name="vehicle_no" placeholder="e.g. LEA-4521" required></div>
                <div class="ff"><label>Vehicle Type <span class="req">*</span></label><div class="sw"><select name="vehicle_type" required><option value="">— Select —</option><option value="40FT">40 FT</option><option value="30FT">30 FT</option><option value="45FT">45 FT</option><option value="20FT">20 FT</option><option value="18FT">18 FT</option><option value="16FT">16 FT</option><option value="14FT">14 FT</option><option value="Pickup">Pickup</option><option value="Other">Other</option></select></div></div>
                <div class="ff"><label>Dock No.</label><div class="sw"><select name="dock"><?php for ($d=1;$d<=10;$d++) echo "<option value=\"$d\">Dock $d</option>"; ?></select></div></div>
                <div class="ff"><label>Vehicle Temp. <span class="req">*</span></label><input type="text" name="veh_temp" placeholder="e.g. 4°C" required></div>
                <div class="ff span2"><label>Other Detail</label><input type="text" name="veh_other" placeholder="Additional vehicle notes…"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 3 -->
        <div class="card">
          <div class="card-hdr">
            <div class="ci ci-green"><svg viewBox="0 0 15 15" fill="none"><circle cx="7.5" cy="5" r="3" stroke="currentColor" stroke-width="1.2"/><path d="M2 13c0-3 2.5-5 5.5-5s5.5 2 5.5 5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg></div>
            <div class="ct"><div class="ct-title">Driver Details</div><div class="ct-note">CNIC, mobile, bilty, seal and product temperature</div></div>
            <div class="cn">3</div>
          </div>
          <div class="cb">
            <div class="fg">
              <div class="ff span2"><label>Driver Name <span class="req">*</span></label><input type="text" name="driver" placeholder="Full name" required></div>
              <div class="ff"><label>CNIC No. <span class="req">*</span></label><input type="text" name="cnic" placeholder="00000-0000000-0" minlength="15" required></div>
              <div class="ff"><label>Mobile No. <span class="req">*</span></label><input type="text" name="mobile" placeholder="0300-0000000" required></div>
              <div class="ff"><label>Bilty No. <span class="req">*</span></label><input type="text" name="bilty" placeholder="Bilty / LR number" required></div>
              <div class="ff"><label>Seal No. <span class="req">*</span></label><input type="text" name="seal" placeholder="Container seal no." required></div>
              <div class="ff span2"><label>Product Temperature <span class="req">*</span></label><input type="text" name="item_temp" placeholder="e.g. 2–8°C" required></div>
            </div>
          </div>
        </div>

        <!-- Step 4 -->
        <div class="card">
          <div class="card-hdr">
            <div class="ci ci-navy"><svg viewBox="0 0 15 15" fill="none"><rect x="1.5" y="2.5" width="12" height="11" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M5 1.5v2M10 1.5v2M1.5 6.5h12" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg></div>
            <div class="ct"><div class="ct-title">Dates, Times &amp; Remarks</div><div class="ct-note">Reporting, in and out timestamps</div></div>
            <div class="cn">4</div>
          </div>
          <div class="cb">
            <div class="fg">
              <div class="ff"><label>Reporting Date &amp; Time <span class="req">*</span></label><input type="datetime-local" name="rptdate" required></div>
              <div class="ff"><label>In Date &amp; Time <span class="req">*</span></label><input type="datetime-local" name="indate" required></div>
              <div class="ff span2"><label>Out Date &amp; Time <span style="color:var(--text3);font-weight:400;text-transform:none;letter-spacing:0">(optional — fill when vehicle departs)</span></label><input type="datetime-local" name="outdate"></div>
              <div class="ff span2"><label>Remarks</label><textarea name="remarks" placeholder="Any additional notes or instructions…"></textarea></div>
            </div>
          </div>
        </div>

      </div>

      <!-- Right column -->
      <div class="right-col">
        <div class="sum-card">
          <div class="sum-hdr">
            <div class="sum-hdr-title">Summary</div>
            <div class="sum-badge" id="sbadge">0 selected</div>
          </div>
          <div class="sum-lines">
            <div class="sum-row"><span class="sk"><svg viewBox="0 0 12 12" fill="none"><rect x="1" y="4" width="10" height="7" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4 4V3a2 2 0 1 1 4 0v1" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>Branch</span><span class="sv"><?php echo htmlspecialchars($branch); ?></span></div>
            <div class="sum-row"><span class="sk"><svg viewBox="0 0 12 12" fill="none"><rect x="1" y="1" width="10" height="10" rx="2" stroke="currentColor" stroke-width="1.2"/><path d="M4 6h4M6 4v4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>Active batch</span><span class="sv ok"><?php echo $act ? htmlspecialchars($act) : '—'; ?></span></div>
            <div class="sum-row"><span class="sk"><svg viewBox="0 0 12 12" fill="none"><rect x="2" y="1" width="8" height="10" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4 4h4M4 6.5h4M4 9h2" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>ASNs available</span><span class="sv"><?php echo $asn_count; ?></span></div>
            <div class="sum-row"><span class="sk"><svg viewBox="0 0 12 12" fill="none"><path d="M2 6l2.5 2.5L10 3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>ASNs selected</span><span class="sv hi" id="sc">0</span></div>
            <div class="sum-row"><span class="sk"><svg viewBox="0 0 12 12" fill="none"><path d="M6 1v6M3 5l3 3 3-3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M1 9v1.5A1.5 1.5 0 0 0 2.5 12h7a1.5 1.5 0 0 0 1.5-1.5V9" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>Total qty</span><span class="sv hi" id="sq">—</span></div>
          </div>
        </div>

        <div class="act-card">
          <button type="submit" class="btn-save">
            <svg viewBox="0 0 13 13" fill="none"><path d="M1.5 9.5v1A1.5 1.5 0 0 0 3 12h7a1.5 1.5 0 0 0 1.5-1.5v-1M9 4.5L6.5 7 4 4.5M6.5 7V1" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Save Gate Pass
          </button>
          <a href="gatepass.php" class="btn-back">
            <svg viewBox="0 0 13 13" fill="none"><path d="M8 2L3 6.5 8 11" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Back to Gate Passes
          </a>
        </div>

        <div class="tips-card">
          <div class="tips-hdr">
            <svg viewBox="0 0 13 13" fill="none"><circle cx="6.5" cy="6.5" r="5.5" stroke="currentColor" stroke-width="1.2"/><path d="M6.5 4v3.5M6.5 9v.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
            Tips
          </div>
          <div class="tips-body">
            <div class="tip"><div class="tip-dot"></div>Select all ASNs arriving on the same vehicle in one gate pass.</div>
            <div class="tip"><div class="tip-dot"></div>Out Date/Time is optional — fill it when the vehicle departs.</div>
            <div class="tip"><div class="tip-dot"></div>CNIC must be 15 characters including dashes: 00000-0000000-0.</div>
            <div class="tip"><div class="tip-dot"></div>Bilty/LR number is the transporter's consignment reference.</div>
          </div>
        </div>
      </div>
    </div>
    </form>
  </div>
</div>

<script>
document.querySelectorAll('.ng-hdr').forEach(function(h){
  h.addEventListener('click',function(){ h.parentElement.classList.toggle('open'); });
});
function upd(){
  var cbs=document.querySelectorAll('.ac'),chk=Array.from(cbs).filter(function(c){return c.checked;});
  var cnt=chk.length,qty=0;
  chk.forEach(function(c){qty+=parseInt(c.dataset.qty||0);});
  document.getElementById('sc').textContent=cnt;
  document.getElementById('sq').textContent=cnt>0?qty.toLocaleString():'—';
  document.getElementById('sbadge').textContent=cnt+' ASN'+(cnt!==1?'s':'')+' selected';
  var sb=document.getElementById('sb');
  sb.style.display=cnt>0?'flex':'none';
  document.getElementById('sbText').textContent=cnt+' ASN'+(cnt!==1?'s':'')+' selected';
  var sa=document.getElementById('sa');if(sa)sa.checked=cnt===cbs.length&&cbs.length>0;
  document.querySelectorAll('.at tbody tr').forEach(function(row){
    row.classList.toggle('sel',row.querySelector('.ac').checked);
  });
  var s1=document.getElementById('s1');
  if(s1){s1.className='sn '+(cnt>0?'done':'active');}
  var l1=document.getElementById('l1');
  if(l1){l1.className='sl-line '+(cnt>0?'done':'');}
}
function toggleAll(m){document.querySelectorAll('.ac').forEach(function(c){c.checked=m.checked;});upd();}
function togRow(row){var cb=row.querySelector('.ac');if(cb){cb.checked=!cb.checked;upd();}}
function validateForm(){
  var chk=document.querySelectorAll('.ac:checked');
  if(chk.length===0){alert('Please select at least one ASN document.');return false;}
  return true;
}
</script>
</body>
</html>
