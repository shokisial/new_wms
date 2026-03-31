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

// Resolve doc number
if (isset($_POST['sub'])) {
    $test = $_POST['sub'];
    $_SESSION['sub1'] = $test;
} else {
    $test = isset($_SESSION['sub1']) ? $_SESSION['sub1'] : '';
}

// Load pending location items
$items = array();
if ($test) {
    $q = mysqli_query($con,
        "SELECT stockin.*, product.prod_desc AS item_code, product.prod_name
         FROM stockin
         INNER JOIN product ON product.prod_desc = stockin.prod_id
         WHERE stockin.rec_dnno = '$test'
           AND stockin.location != stockin.qty
           AND stockin.branch_id = '$branch'
         GROUP BY stockin.batch"
    ) or die(mysqli_error($con));
    while ($r = mysqli_fetch_array($q)) { $items[] = $r; }
}

$total_items    = count($items);
$total_received = array_sum(array_column($items, 'qty'));
$total_located  = array_sum(array_column($items, 'location'));
$remaining      = $total_received - $total_located;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sovereign WMS — Location</title>
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

    /* Topbar */
    .topbar{height:52px;background:var(--navy);display:flex;align-items:center;padding:0 20px;gap:12px;border-bottom:2px solid var(--orange);flex-shrink:0}
    .brand-t{font-size:14px;font-weight:600;color:#fff;letter-spacing:-.2px}
    .brand-s{font-size:8.5px;color:rgba(255,255,255,.4);letter-spacing:.13em;text-transform:uppercase;display:block;margin-top:1px}
    .tbr{margin-left:auto;display:flex;align-items:center;gap:12px}
    .bpill{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:6px;padding:4px 11px;display:flex;align-items:center;gap:6px;font-size:11px;color:rgba(255,255,255,.5)}
    .bpill strong{color:#fff;font-weight:500}
    .uav{width:30px;height:30px;border-radius:50%;background:var(--orange);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0}

    /* Shell */
    .shell{display:flex;flex:1;overflow:hidden}

    /* Sidebar */
    .sidebar{width:210px;background:var(--navy);flex-shrink:0;display:flex;flex-direction:column;overflow:hidden}
    .sb-scroll{flex:1;overflow-y:auto;padding:4px 0 8px}
    .sb-scroll::-webkit-scrollbar{width:3px}
    .sb-scroll::-webkit-scrollbar-thumb{background:#2a3a55;border-radius:3px}
    .ns{padding:14px 12px 4px;font-size:9px;font-weight:700;color:#364d70;letter-spacing:.12em;text-transform:uppercase}
    .ni{display:flex;align-items:center;gap:9px;padding:7px 12px;color:#7a8ba8;font-size:12px;text-decoration:none;cursor:pointer;transition:background .1s,color .1s}
    .ni:hover{background:rgba(255,255,255,.05);color:#c8d3e8}
    .ni svg{width:14px;height:14px;flex-shrink:0;opacity:.5}
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

    /* Content */
    .content{flex:1;overflow-y:auto;display:flex;flex-direction:column}
    .content::-webkit-scrollbar{width:4px}
    .content::-webkit-scrollbar-thumb{background:var(--border2);border-radius:4px}

    /* Page header */
    .page-hdr{padding:18px 24px 0;flex-shrink:0}
    .crumb{font-size:11px;color:var(--text3);display:flex;align-items:center;gap:4px;margin-bottom:6px}
    .crumb a{color:var(--text2);text-decoration:none}
    .crumb a:hover{color:var(--text1)}
    .crumb svg{width:8px;height:8px;opacity:.6}
    .ph-row{display:flex;align-items:flex-start;justify-content:space-between;gap:16px}
    .ph-title{font-size:19px;font-weight:700;color:var(--text1);letter-spacing:-.4px}
    .ph-sub{font-size:12px;color:var(--text2);margin-top:3px}
    .doc-pill{display:inline-flex;align-items:center;gap:7px;background:var(--navy);border-radius:8px;padding:6px 14px;font-size:12px;color:rgba(255,255,255,.6);flex-shrink:0}
    .doc-pill strong{color:#fff;font-family:'JetBrains Mono',monospace;font-size:12px}
    .doc-pill svg{width:13px;height:13px;color:rgba(255,255,255,.4)}

    /* Body */
    .body{padding:16px 24px 32px;flex:1}

    /* Stats */
    .stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px}
    .stat-card{background:var(--white);border:1px solid var(--border);border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px}
    .si{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .si svg{width:17px;height:17px}
    .si-navy{background:var(--navy);color:#fff}
    .si-orange{background:var(--orange-muted);color:var(--orange)}
    .si-green{background:var(--green-bg);color:var(--green)}
    .si-amber{background:var(--amber-bg);color:var(--amber)}
    .stat-lbl{font-size:10.5px;color:var(--text3);margin-bottom:2px}
    .stat-val{font-size:20px;font-weight:700;color:var(--text1);letter-spacing:-.5px;line-height:1}
    .stat-sub{font-size:10px;color:var(--text3);margin-top:2px}

    /* Progress bar */
    .progress-card{background:var(--white);border:1px solid var(--border);border-radius:10px;padding:14px 18px;margin-bottom:18px}
    .prog-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
    .prog-title{font-size:12px;font-weight:600;color:var(--text1)}
    .prog-pct{font-size:12px;font-weight:700;color:var(--orange)}
    .prog-bar-bg{height:8px;background:var(--bg2);border-radius:4px;overflow:hidden}
    .prog-bar-fill{height:100%;border-radius:4px;background:var(--orange);transition:width .4s}
    .prog-legend{display:flex;gap:18px;margin-top:8px}
    .prog-leg{font-size:11px;color:var(--text3);display:flex;align-items:center;gap:5px}
    .prog-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}

    /* Card */
    .card{background:var(--white);border:1px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:14px}
    .card-hdr{padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:10px}
    .card-hdr-title{font-size:13px;font-weight:600;color:var(--text1)}
    .card-hdr-note{font-size:11px;color:var(--text3)}

    /* Search */
    .search-wrap{position:relative}
    .search-wrap svg{position:absolute;left:10px;top:50%;transform:translateY(-50%);width:13px;height:13px;color:var(--text3);pointer-events:none}
    .search-wrap input{padding:7px 10px 7px 30px;border:1px solid var(--border2);border-radius:7px;font-size:12px;font-family:'Inter',sans-serif;color:var(--text1);background:var(--bg);outline:none;width:200px;transition:border .15s}
    .search-wrap input:focus{border-color:#93aac8;background:var(--white)}

    /* Table */
    .tbl-wrap{overflow-x:auto}
    table{width:100%;border-collapse:collapse}
    th{font-size:9.5px;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;padding:9px 14px;border-bottom:1px solid var(--border);text-align:left;white-space:nowrap;background:#fafaf7}
    td{padding:0;border-bottom:1px solid #f0efe8;vertical-align:middle}
    tbody tr:last-child td{border-bottom:none}
    tbody tr:hover td{background:#f8f7f4}

    .cell{padding:11px 14px;font-size:12px;color:var(--text1)}
    .sno{padding:11px 14px;font-size:11px;font-weight:600;color:var(--text3);text-align:center}
    .mono{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text2)}

    /* Item cell */
    .item-cell{display:flex;align-items:center;gap:10px;padding:11px 14px}
    .item-av{width:32px;height:32px;border-radius:7px;background:var(--bg2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:var(--text2);flex-shrink:0}
    .item-name{font-size:12px;font-weight:500;color:var(--text1)}
    .item-code{font-size:10.5px;color:var(--text3);font-family:'JetBrains Mono',monospace;margin-top:1px}

    /* Qty cell */
    .qty-cell{padding:11px 14px}
    .qty-main{font-size:13px;font-weight:700;color:var(--text1)}
    .qty-bar{height:4px;background:var(--bg2);border-radius:2px;margin-top:5px;width:80px;overflow:hidden}
    .qty-bar-fill{height:100%;border-radius:2px;background:var(--orange)}

    /* Status badge */
    .badge{display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:500}
    .badge::before{content:'';width:5px;height:5px;border-radius:50%}
    .b-amber{background:var(--amber-bg);color:var(--amber);border:1px solid var(--amber-bd)}
    .b-amber::before{background:var(--amber)}
    .b-green{background:var(--green-bg);color:var(--green);border:1px solid var(--green-bd)}
    .b-green::before{background:var(--green)}
    .b-partial{background:var(--blue-bg);color:var(--blue);border:1px solid var(--blue-bd)}
    .b-partial::before{background:var(--blue)}

    /* Action cell */
    .act-cell{padding:11px 14px}
    .edit-btn{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:6px;background:var(--navy);color:#fff;font-size:11.5px;font-weight:500;cursor:pointer;border:none;font-family:'Inter',sans-serif;transition:background .12s}
    .edit-btn:hover{background:var(--navy-mid)}
    .edit-btn svg{width:12px;height:12px}

    /* Table footer */
    .tbl-footer{padding:10px 14px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:#fafaf7}
    .tbl-footer-note{font-size:11px;color:var(--text3)}
    .tbl-footer-note strong{color:var(--text2)}

    /* Action buttons row */
    .action-row{display:flex;align-items:center;justify-content:space-between;margin-top:4px}
    .btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:12px;font-weight:500;cursor:pointer;border:none;font-family:'Inter',sans-serif;transition:all .13s;text-decoration:none;white-space:nowrap}
    .btn svg{width:13px;height:13px;flex-shrink:0}
    .btn-navy{background:var(--navy);color:#fff}
    .btn-navy:hover{background:var(--navy-mid)}
    .btn-ghost{background:var(--white);color:var(--text2);border:1px solid var(--border2)}
    .btn-ghost:hover{background:var(--bg2);color:var(--text1)}
    .btn-green{background:var(--green-bg);color:var(--green);border:1px solid var(--green-bd)}
    .btn-green:hover{background:#dcfce7}

    /* Empty state */
    .empty-state{padding:52px 20px;text-align:center}
    .empty-icon{width:52px;height:52px;border-radius:14px;background:var(--bg2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;margin:0 auto 14px}
    .empty-icon svg{width:22px;height:22px;color:var(--text3)}
    .empty-title{font-size:14px;font-weight:600;color:var(--text1);margin-bottom:5px}
    .empty-sub{font-size:12px;color:var(--text2)}

    /* ── Modal overlay ── */
    .modal-overlay{display:none;position:fixed;inset:0;background:rgba(14,20,36,.5);z-index:300;align-items:center;justify-content:center;padding:20px}
    .modal-overlay.open{display:flex}
    .modal-box{background:var(--white);border-radius:14px;width:100%;max-width:440px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.2)}
    .modal-hdr{padding:18px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
    .modal-title{font-size:15px;font-weight:600;color:var(--text1)}
    .modal-close{width:28px;height:28px;border-radius:7px;background:var(--bg2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text2);transition:all .12s}
    .modal-close:hover{background:var(--red-bg);border-color:var(--red-bd);color:var(--red)}
    .modal-close svg{width:13px;height:13px}
    .modal-body{padding:20px 22px}
    .modal-info{background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:12px 14px;margin-bottom:18px;display:grid;grid-template-columns:1fr 1fr;gap:10px}
    .mi-row{display:flex;flex-direction:column;gap:2px}
    .mi-label{font-size:9.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.06em}
    .mi-value{font-size:12.5px;font-weight:600;color:var(--text1);font-family:'JetBrains Mono',monospace}
    .ff{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}
    .ff:last-child{margin-bottom:0}
    .ff label{font-size:10px;font-weight:600;color:var(--text2);text-transform:uppercase;letter-spacing:.06em}
    .req{color:var(--orange)}
    .ff input{padding:9px 11px;border:1.5px solid var(--border2);border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;color:var(--text1);background:var(--white);outline:none;width:100%;transition:border-color .15s}
    .ff input:focus{border-color:#93aac8;box-shadow:0 0 0 3px rgba(30,79,160,.06)}
    .ff input::placeholder{color:var(--text3)}
    .ff .hint{font-size:11px;color:var(--text3);margin-top:3px}
    .modal-footer{padding:14px 22px;border-top:1px solid var(--border);display:flex;gap:8px;justify-content:flex-end}
    .btn-modal-save{padding:9px 20px;background:var(--navy);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;transition:background .13s}
    .btn-modal-save:hover{background:var(--navy-mid)}
    .btn-modal-cancel{padding:9px 16px;background:var(--bg);color:var(--text2);border:1px solid var(--border);border-radius:8px;font-size:12px;font-weight:500;font-family:'Inter',sans-serif;cursor:pointer}
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
          <a href="gatepass.php"           class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Gate Pass</a>
          <a href="final_barcode.php"      class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Receive</a>
          <a href="final_location.php"     class="na active"><svg viewBox="0 0 6 6" fill="var(--orange-lt)"><circle cx="3" cy="3" r="2.5"/></svg>Location</a>
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
          <a href="rec_time.php"         class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Receive Accuracy</a>
          <a href="picking_time.php"     class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Picking Time</a>
          <a href="quality_checkrpt.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>QMS</a>
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

  <!-- Content -->
  <div class="content">
    <div class="page-hdr">
      <div class="crumb">
        <a href="#">Inbound</a>
        <svg viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Location Assignment
      </div>
      <div class="ph-row">
        <div>
          <div class="ph-title">Location Assignment</div>
          <div class="ph-sub">Assign warehouse locations to received items pending putaway</div>
        </div>
        <?php if ($test): ?>
        <div class="doc-pill">
          <svg viewBox="0 0 13 13" fill="none"><rect x="2" y="1" width="9" height="11" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4 4.5h5M4 6.5h5M4 8.5h3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
          Document: <strong><?php echo htmlspecialchars($test); ?></strong>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="body">

      <?php if ($test && $total_items > 0): ?>
      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="si si-navy"><svg viewBox="0 0 18 18" fill="none"><rect x="2" y="2" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 9h6M9 6v6" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg></div>
          <div><div class="stat-lbl">Pending Items</div><div class="stat-val"><?php echo $total_items; ?></div><div class="stat-sub">Need location</div></div>
        </div>
        <div class="stat-card">
          <div class="si si-orange"><svg viewBox="0 0 18 18" fill="none"><path d="M9 2v10M5 8l4 4 4-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 14h14" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg></div>
          <div><div class="stat-lbl">Total Received</div><div class="stat-val"><?php echo number_format($total_received); ?></div><div class="stat-sub">Units in document</div></div>
        </div>
        <div class="stat-card">
          <div class="si si-green"><svg viewBox="0 0 18 18" fill="none"><path d="M3 9l4 4 8-8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
          <div><div class="stat-lbl">Located</div><div class="stat-val"><?php echo number_format($total_located); ?></div><div class="stat-sub">Units assigned</div></div>
        </div>
        <div class="stat-card">
          <div class="si si-amber"><svg viewBox="0 0 18 18" fill="none"><circle cx="9" cy="9" r="7" stroke="currentColor" stroke-width="1.3"/><path d="M9 5v4l3 3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg></div>
          <div><div class="stat-lbl">Remaining</div><div class="stat-val"><?php echo number_format($remaining); ?></div><div class="stat-sub">Units unassigned</div></div>
        </div>
      </div>

      <!-- Progress -->
      <?php $pct = $total_received > 0 ? round(($total_located / $total_received) * 100) : 0; ?>
      <div class="progress-card">
        <div class="prog-header">
          <div class="prog-title">Location progress — <?php echo htmlspecialchars($test); ?></div>
          <div class="prog-pct"><?php echo $pct; ?>% complete</div>
        </div>
        <div class="prog-bar-bg">
          <div class="prog-bar-fill" style="width:<?php echo $pct; ?>%"></div>
        </div>
        <div class="prog-legend">
          <span class="prog-leg"><span class="prog-dot" style="background:var(--orange)"></span><?php echo number_format($total_located); ?> located</span>
          <span class="prog-leg"><span class="prog-dot" style="background:var(--bg2);border:1px solid var(--border2)"></span><?php echo number_format($remaining); ?> remaining</span>
          <span class="prog-leg"><span class="prog-dot" style="background:var(--text3)"></span><?php echo number_format($total_received); ?> total</span>
        </div>
      </div>
      <?php endif; ?>

      <!-- Table card -->
      <div class="card">
        <div class="card-hdr">
          <div class="card-hdr-title">Items pending location</div>
          <div class="search-wrap">
            <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
            <input type="text" id="si" placeholder="Search item, batch, barcode…" oninput="filterRows(this.value)">
          </div>
        </div>

        <div class="tbl-wrap">
          <table id="locTable">
            <thead>
              <tr>
                <th style="width:44px;text-align:center">#</th>
                <th>Item</th>
                <th>Batch</th>
                <th>Barcode</th>
                <th>Received</th>
                <th>Located</th>
                <th>Remaining</th>
                <th>Status</th>
                <th style="width:100px"></th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($items)): ?>
              <tr><td colspan="9" style="border:none">
                <div class="empty-state">
                  <div class="empty-icon"><svg viewBox="0 0 22 22" fill="none"><rect x="3" y="3" width="16" height="16" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M7 11h8M11 7v8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg></div>
                  <div class="empty-title"><?php echo $test ? 'All items located' : 'No document selected'; ?></div>
                  <div class="empty-sub"><?php echo $test ? 'All items in this document have been assigned locations.' : 'Return to Receive and select a document to assign locations.'; ?></div>
                </div>
              </td></tr>
              <?php else: ?>
              <?php $sno = 1; foreach ($items as $row):
                $rem = $row['qty'] - $row['location'];
                $row_pct = $row['qty'] > 0 ? min(100, round(($row['location'] / $row['qty']) * 100)) : 0;
                if ($row['location'] == 0)           $badge_class = 'b-amber';
                elseif ($row['location'] < $row['qty']) $badge_class = 'b-partial';
                else                                 $badge_class = 'b-green';
                $badge_label = $row['location'] == 0 ? 'Unlocated' : ($row['location'] < $row['qty'] ? 'Partial' : 'Complete');
                $av_letters = strtoupper(substr($row['item_code'], 0, 2));
              ?>
              <tr>
                <td class="sno"><?php echo $sno; ?></td>
                <td>
                  <div class="item-cell">
                    <div class="item-av"><?php echo htmlspecialchars($av_letters); ?></div>
                    <div>
                      <div class="item-name"><?php echo htmlspecialchars($row['prod_name']); ?></div>
                      <div class="item-code"><?php echo htmlspecialchars($row['item_code']); ?></div>
                    </div>
                  </div>
                </td>
                <td class="cell mono"><?php echo htmlspecialchars($row['batch']); ?></td>
                <td class="cell mono"><?php echo htmlspecialchars($row['sno']) ?: '—'; ?></td>
                <td class="cell" style="font-weight:600"><?php echo number_format($row['qty']); ?></td>
                <td>
                  <div class="qty-cell">
                    <div class="qty-main"><?php echo number_format($row['location']); ?></div>
                    <div class="qty-bar"><div class="qty-bar-fill" style="width:<?php echo $row_pct; ?>%"></div></div>
                  </div>
                </td>
                <td class="cell" style="font-weight:600;color:<?php echo $rem > 0 ? 'var(--amber)' : 'var(--green)'; ?>"><?php echo number_format($rem); ?></td>
                <td class="cell"><span class="badge <?php echo $badge_class; ?>"><?php echo $badge_label; ?></span></td>
                <td class="act-cell">
                  <button class="edit-btn" onclick="openModal(
                    '<?php echo $row['stockin_id']; ?>',
                    '<?php echo htmlspecialchars(addslashes($row['prod_name'])); ?>',
                    '<?php echo htmlspecialchars(addslashes($row['item_code'])); ?>',
                    '<?php echo htmlspecialchars(addslashes($row['batch'])); ?>',
                    '<?php echo $row['location']; ?>',
                    '<?php echo $row['qty']; ?>',
                    '<?php echo htmlspecialchars(addslashes($row['prod_desc'] ?? $row['item_code'])); ?>'
                  )">
                    <svg viewBox="0 0 12 12" fill="none"><path d="M7.5 1.5l3 3-6 6H1.5v-3l6-6z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Assign
                  </button>
                </td>
              </tr>
              <?php $sno++; endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if (!empty($items)): ?>
        <div class="tbl-footer">
          <div class="tbl-footer-note"><strong><?php echo $total_items; ?> item<?php echo $total_items!=1?'s':''; ?></strong> pending · <?php echo number_format($remaining); ?> units unassigned</div>
          <div class="tbl-footer-note">Doc: <?php echo htmlspecialchars($test); ?></div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Action buttons -->
      <div class="action-row">
        <form action="inbound_report.php" method="POST" target="_blank">
          <input type="hidden" name="rec_dnno" value="<?php echo htmlspecialchars($test); ?>">
          <button type="submit" name="cash" class="btn btn-ghost">
            <svg viewBox="0 0 13 13" fill="none"><rect x="2" y="1" width="9" height="11" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4 4.5h5M4 6.5h5M4 8.5h3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
            Inbound Detail Report
          </button>
        </form>
        <form action="location_inbound.php" method="POST" target="_blank">
          <input type="hidden" name="rec_dnno" value="<?php echo htmlspecialchars($test); ?>">
          <button type="submit" name="cash" class="btn btn-green">
            <svg viewBox="0 0 13 13" fill="none"><path d="M2 7l3 3 6-6" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
            View All Locations
          </button>
        </form>
      </div>

    </div>
  </div>
</div>

<!-- Location Assignment Modal -->
<div class="modal-overlay" id="locModal" onclick="if(event.target===this)closeModal()">
  <div class="modal-box">
    <div class="modal-hdr">
      <div class="modal-title">Assign Location</div>
      <div class="modal-close" onclick="closeModal()">
        <svg viewBox="0 0 14 14" fill="none"><path d="M2 2l10 10M12 2L2 12" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
      </div>
    </div>
    <form method="POST" action="received_location.php">
      <input type="hidden" name="id"     id="m_id">
      <input type="hidden" name="doc_no" value="<?php echo htmlspecialchars($test); ?>">
      <input type="hidden" name="desc"   id="m_desc">
      <input type="hidden" name="bth"    id="m_batch">
      <div class="modal-body">
        <div class="modal-info">
          <div class="mi-row">
            <div class="mi-label">Item</div>
            <div class="mi-value" id="m_name" style="font-family:'Inter',sans-serif;font-size:12px">—</div>
          </div>
          <div class="mi-row">
            <div class="mi-label">Item Code</div>
            <div class="mi-value" id="m_code">—</div>
          </div>
          <div class="mi-row">
            <div class="mi-label">Batch</div>
            <div class="mi-value" id="m_batch_disp">—</div>
          </div>
          <div class="mi-row">
            <div class="mi-label">Located / Received</div>
            <div class="mi-value" id="m_progress">—</div>
          </div>
        </div>
        <div class="ff">
          <label>Location Qty <span class="req">*</span></label>
          <input type="number" name="rec" id="m_rec" placeholder="Enter quantity to assign to this location" min="1" required>
          <div class="hint" id="m_hint">Max assignable quantity shown above</div>
        </div>
        <div class="ff">
          <label>Location Code <span class="req">*</span></label>
          <input type="text" name="location" id="m_loc" placeholder="e.g. A-01-02 (Aisle-Row-Bay)" required>
          <div class="hint">Enter the warehouse bin / rack code</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-modal-cancel" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn-modal-save">Save Location</button>
      </div>
    </form>
  </div>
</div>

<script>
document.querySelectorAll('.ng-hdr').forEach(function(h){
  h.addEventListener('click',function(){ h.parentElement.classList.toggle('open'); });
});

function filterRows(v){
  v = v.toLowerCase();
  document.querySelectorAll('#locTable tbody tr').forEach(function(r){
    r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none';
  });
}

function openModal(id, name, code, batch, located, received, desc){
  document.getElementById('m_id').value        = id;
  document.getElementById('m_desc').value      = desc;
  document.getElementById('m_batch').value     = batch;
  document.getElementById('m_name').textContent       = name;
  document.getElementById('m_code').textContent       = code;
  document.getElementById('m_batch_disp').textContent = batch;
  document.getElementById('m_progress').textContent   = located + ' / ' + received;
  var rem = parseInt(received) - parseInt(located);
  document.getElementById('m_hint').textContent = 'Maximum assignable: ' + rem + ' units';
  document.getElementById('m_rec').max = rem;
  document.getElementById('m_rec').value   = '';
  document.getElementById('m_loc').value   = '';
  document.getElementById('locModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal(){
  document.getElementById('locModal').classList.remove('open');
  document.body.style.overflow = '';
}
</script>
</body>
</html>
