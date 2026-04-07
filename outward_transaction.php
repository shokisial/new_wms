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

// Fetch temp outbound rows
$rows = [];
$query = mysqli_query($con, "SELECT * FROM `temp_trans_out` WHERE branch_id='$branch'") or die(mysqli_error($con));
while ($row = mysqli_fetch_array($query)) { $rows[] = $row; }
$total_rows = count($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sovereign WMS — Customer Delivery Order</title>
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
    .ph-row{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap}
    .ph-title{font-size:19px;font-weight:700;color:var(--text1);letter-spacing:-.4px}
    .ph-sub{font-size:12px;color:var(--text2);margin-top:3px}
    .batch-pill{display:inline-flex;align-items:center;gap:6px;background:var(--green-bg);border:1px solid var(--green-bd);border-radius:20px;padding:5px 13px;font-size:11px;font-weight:500;color:var(--green);white-space:nowrap;flex-shrink:0}
    .bp-dot{width:6px;height:6px;border-radius:50%;background:var(--green);flex-shrink:0}
    .ph-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap}

    /* ── Body ── */
    .body{padding:16px 24px 32px;flex:1}

    /* ── Stat row ── */
    .stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px}
    .sc{background:var(--white);border:1px solid var(--border);border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px}
    .si{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .si svg{width:17px;height:17px}
    .si-navy{background:var(--navy);color:#fff}
    .si-orange{background:var(--orange-muted);color:var(--orange)}
    .si-blue{background:var(--blue-bg);color:var(--blue)}
    .stat-lbl{font-size:10.5px;color:var(--text3);margin-bottom:2px}
    .stat-val{font-size:20px;font-weight:700;color:var(--text1);letter-spacing:-.5px;line-height:1}
    .stat-sub{font-size:10px;color:var(--text3);margin-top:2px}

    /* ── Tool bar ── */
    .toolbar{display:flex;align-items:center;gap:8px;margin-bottom:14px;flex-wrap:wrap}
    .toolbar-left{display:flex;align-items:center;gap:8px;flex:1;flex-wrap:wrap}
    .sw{position:relative}
    .sw svg{position:absolute;left:10px;top:50%;transform:translateY(-50%);width:13px;height:13px;color:var(--text3);pointer-events:none}
    .sw input{padding:8px 10px 8px 30px;border:1px solid var(--border2);border-radius:8px;font-size:12px;font-family:'Inter',sans-serif;color:var(--text1);background:var(--white);outline:none;width:220px;transition:border .15s}
    .sw input:focus{border-color:#93aac8}
    .sw input::placeholder{color:var(--text3)}

    /* ── Buttons ── */
    .btn-primary-wms{display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:7px;background:var(--navy);border:1px solid var(--navy);color:#fff;font-size:12px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;text-decoration:none;transition:background .12s,transform .1s;white-space:nowrap}
    .btn-primary-wms:hover{background:var(--navy-mid);transform:translateY(-1px)}
    .btn-primary-wms svg{width:13px;height:13px}
    .btn-orange{display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:7px;background:var(--orange);border:1px solid var(--orange);color:#fff;font-size:12px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;text-decoration:none;transition:background .12s,transform .1s;white-space:nowrap}
    .btn-orange:hover{background:var(--orange-lt);transform:translateY(-1px)}
    .btn-orange svg{width:13px;height:13px}
    .btn-ghost{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:7px;background:transparent;border:1px solid var(--border2);color:var(--text2);font-size:12px;font-weight:500;font-family:'Inter',sans-serif;cursor:pointer;text-decoration:none;transition:border .12s,color .12s;white-space:nowrap}
    .btn-ghost:hover{border-color:var(--border);color:var(--text1)}
    .btn-ghost svg{width:13px;height:13px}
    .btn-danger{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:6px;background:var(--red-bg);border:1px solid var(--red-bd);color:var(--red);font-size:11.5px;font-weight:500;font-family:'Inter',sans-serif;cursor:pointer;transition:background .12s;white-space:nowrap}
    .btn-danger:hover{background:#fde8e8}
    .btn-danger svg{width:12px;height:12px}
    .btn-edit{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:6px;background:var(--blue-bg);border:1px solid var(--blue-bd);color:var(--blue);font-size:11.5px;font-weight:500;font-family:'Inter',sans-serif;cursor:pointer;transition:background .12s;white-space:nowrap}
    .btn-edit:hover{background:#e4edff}
    .btn-edit svg{width:12px;height:12px}

    /* ── Upload strip ── */
    .upload-strip{background:var(--white);border:1px solid var(--border);border-radius:10px;padding:14px 18px;display:flex;align-items:center;gap:16px;margin-bottom:14px;flex-wrap:wrap}
    .upload-strip-icon{width:36px;height:36px;border-radius:9px;background:var(--orange-muted);border:1px solid var(--orange-bd);display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .upload-strip-icon svg{width:16px;height:16px;color:var(--orange)}
    .upload-strip-label{font-size:12.5px;font-weight:600;color:var(--text1);margin-bottom:2px}
    .upload-strip-sub{font-size:11px;color:var(--text3)}
    .upload-strip-right{display:flex;align-items:center;gap:10px;margin-left:auto;flex-wrap:wrap}
    .file-input-wrap{position:relative;display:inline-flex;align-items:center}
    .file-input-wrap input[type="file"]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%}
    .file-input-label{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:7px;background:var(--bg2);border:1px solid var(--border2);color:var(--text2);font-size:12px;font-weight:500;font-family:'Inter',sans-serif;cursor:pointer;transition:border .12s,color .12s}
    .file-input-label svg{width:13px;height:13px}
    .file-input-label:hover{border-color:var(--border);color:var(--text1)}

    /* ── Main table card ── */
    .card{background:var(--white);border:1px solid var(--border);border-radius:10px;overflow:hidden}
    .card-hdr{padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:#fafaf7}
    .card-hdr-title{font-size:13px;font-weight:600;color:var(--text1)}
    .card-hdr-note{font-size:11px;color:var(--text3)}
    table{width:100%;border-collapse:collapse}
    th{font-size:9.5px;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;padding:9px 14px;border-bottom:1px solid var(--border);text-align:left;white-space:nowrap;background:#fafaf7}
    td{padding:0;border-bottom:1px solid #f0efe8;vertical-align:middle}
    tbody tr:last-child td{border-bottom:none}
    tbody tr:hover td{background:#f8f7f4}
    .cell{padding:11px 14px;font-size:12px;color:var(--text1)}
    .cell-mono{padding:11px 14px;font-family:'JetBrains Mono',monospace;font-size:11.5px;color:var(--text1);font-weight:600}
    .cell-muted{padding:11px 14px;font-size:12px;color:var(--text2)}
    .act-cell{padding:11px 14px;display:flex;align-items:center;gap:6px;opacity:0;transition:opacity .12s}
    tbody tr:hover .act-cell{opacity:1}
    .tbl-footer{padding:10px 14px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:#fafaf7}
    .tbl-footer-note{font-size:11px;color:var(--text3)}
    .tbl-footer-note strong{color:var(--text2)}

    /* empty state */
    .empty-state{padding:52px 20px;text-align:center}
    .empty-icon{width:52px;height:52px;border-radius:14px;background:var(--bg2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;margin:0 auto 14px}
    .empty-icon svg{width:22px;height:22px;color:var(--text3)}
    .empty-title{font-size:14px;font-weight:600;color:var(--text1);margin-bottom:5px}
    .empty-sub{font-size:12px;color:var(--text2)}

    /* ── WMS Modal ── */
    .wms-overlay{position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:1000;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .18s}
    .wms-overlay.open{opacity:1;pointer-events:all}
    .wms-modal{background:var(--white);border-radius:12px;border:1px solid var(--border);width:100%;max-width:480px;box-shadow:0 8px 40px rgba(0,0,0,.18);transform:translateY(8px);transition:transform .18s;overflow:hidden}
    .wms-overlay.open .wms-modal{transform:translateY(0)}
    .wms-modal-hdr{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:#fafaf7}
    .wms-modal-title{font-size:14px;font-weight:700;color:var(--text1)}
    .wms-modal-close{width:26px;height:26px;border-radius:6px;background:transparent;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text3);transition:background .1s,color .1s}
    .wms-modal-close:hover{background:var(--bg2);color:var(--text1)}
    .wms-modal-close svg{width:12px;height:12px}
    .wms-modal-body{padding:20px}
    .wms-modal-foot{padding:14px 20px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:8px;background:#fafaf7}
    .mform-field{margin-bottom:16px}
    .mform-field:last-of-type{margin-bottom:0}
    .mform-label{display:block;font-size:10.5px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:6px}
    .mform-input{width:100%;padding:9px 12px;border:1px solid var(--border2);border-radius:7px;font-size:12.5px;font-family:'JetBrains Mono',monospace;color:var(--text1);background:var(--white);outline:none;transition:border .15s,box-shadow .15s}
    .mform-input:focus{border-color:#93aac8;box-shadow:0 0 0 3px rgba(30,79,160,.08)}

    /* delete confirm */
    .del-confirm-body{padding:20px;text-align:center}
    .del-icon{width:46px;height:46px;border-radius:12px;background:var(--red-bg);border:1px solid var(--red-bd);display:flex;align-items:center;justify-content:center;margin:0 auto 12px}
    .del-icon svg{width:20px;height:20px;color:var(--red)}
    .del-msg{font-size:13px;color:var(--text2);margin-top:4px}
    .del-msg strong{color:var(--text1)}
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
      <div class="ng">
        <div class="ng-hdr">
          <svg class="gi" viewBox="0 0 14 14" fill="none"><path d="M1 7h12M7 3l4 4-4 4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Inbound
          <svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div class="ng-items">
          <a href="inward_transaction.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>A.S.N</a>
          <a href="gatepass.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Gate Pass</a>
          <a href="final_barcode.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Receive</a>
          <a href="final_location.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Location</a>
          <a href="index_stkveh.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Location List</a>
        </div>
      </div>
      <div class="ng open">
        <div class="ng-hdr">
          <svg class="gi" viewBox="0 0 14 14" fill="none"><path d="M13 7H1M7 11l-4-4 4-4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Outbound
          <svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div class="ng-items">
          <a href="outward_transaction.php" class="na active"><svg viewBox="0 0 6 6" fill="var(--orange-lt)"><circle cx="3" cy="3" r="2.5"/></svg>Transfer Note</a>
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
        <a href="new_dash.php">Outbound</a>
        <svg viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Transfer Note
      </div>
      <div class="ph-row">
        <div>
          <div class="ph-title">Customer Delivery Order</div>
          <div class="ph-sub">Review pending outbound lines, then confirm &amp; save as a transfer note</div>
        </div>
        <div class="ph-actions">
          <?php if ($act): ?>
          <div class="batch-pill"><div class="bp-dot"></div>Active Batch: <strong><?php echo htmlspecialchars($act); ?></strong></div>
          <?php endif; ?>
          <!-- Save / Confirm -->
          <form method="post" action="outward_add.php" style="display:inline">
            <button type="submit" name="cash" class="btn-orange">
              <svg viewBox="0 0 13 13" fill="none"><path d="M2 6.5l3 3 6-6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
              Save Transfer Note
            </button>
          </form>
          <!-- DC Format -->
          <form action="index7.php" target="_blank" method="POST" style="display:inline">
            <button type="submit" name="load_excel_data" class="btn-ghost">
              <svg viewBox="0 0 13 13" fill="none"><rect x="2" y="1" width="9" height="11" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 4.5h4M4.5 6.5h4M4.5 8.5h2.5" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
              D.C Format
            </button>
          </form>
        </div>
      </div>
    </div>

    <div class="body">

      <!-- Stats -->
      <div class="stats-row">
        <div class="sc">
          <div class="si si-navy">
            <svg viewBox="0 0 18 18" fill="none"><rect x="2" y="2" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 9h6M9 6v6" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
          </div>
          <div>
            <div class="stat-lbl">Pending Lines</div>
            <div class="stat-val"><?php echo $total_rows; ?></div>
            <div class="stat-sub">Awaiting confirmation</div>
          </div>
        </div>
        <div class="sc">
          <div class="si si-orange">
            <svg viewBox="0 0 18 18" fill="none"><path d="M15 9H3M9 3l6 6-6 6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </div>
          <div>
            <div class="stat-lbl">Total Units</div>
            <div class="stat-val"><?php $tot=0; foreach($rows as $r) $tot+=$r['qty']; echo number_format($tot); ?></div>
            <div class="stat-sub">Across all lines</div>
          </div>
        </div>
        <div class="sc">
          <div class="si si-blue">
            <svg viewBox="0 0 18 18" fill="none"><path d="M3 5h12M3 9h8M3 13h5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
          </div>
          <div>
            <div class="stat-lbl">Branch</div>
            <div class="stat-val" style="font-size:15px"><?php echo htmlspecialchars($branch); ?></div>
            <div class="stat-sub">Active session</div>
          </div>
        </div>
      </div>

      <!-- DC File Upload strip -->
      <div class="upload-strip">
        <div class="upload-strip-icon">
          <svg viewBox="0 0 16 16" fill="none"><path d="M8 11V3M5 6l3-3 3 3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 12h12" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="upload-strip-label">Import D.C File</div>
          <div class="upload-strip-sub">Upload an Excel file to bulk-import delivery order lines</div>
        </div>
        <form action="excel/outbound_upload.php" method="POST" enctype="multipart/form-data" class="upload-strip-right">
          <div class="file-input-wrap">
            <input type="file" name="import_file" id="dcFile" required onchange="updateFileLabel(this)">
            <label class="file-input-label" for="dcFile">
              <svg viewBox="0 0 13 13" fill="none"><path d="M2 10.5h9M6.5 2v7M4 5l2.5-3L9 5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <span id="fileLabel">Choose File</span>
            </label>
          </div>
          <button type="submit" name="save_out_data" class="btn-primary-wms">
            <svg viewBox="0 0 13 13" fill="none"><path d="M2 6.5l3 3 6-6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Import
          </button>
        </form>
      </div>

      <!-- Toolbar -->
      <div class="toolbar">
        <div class="toolbar-left">
          <div class="sw">
            <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
            <input type="text" id="searchInput" placeholder="Search D.C no, product, distributor…" oninput="filterTable(this.value)">
          </div>
        </div>
        <div style="font-size:11px;color:var(--text3)"><?php echo $total_rows; ?> line<?php echo $total_rows!=1?'s':''; ?> · <?php echo date('d M Y, H:i'); ?></div>
      </div>

      <!-- Table card -->
      <div class="card">
        <div class="card-hdr">
          <div class="card-hdr-title">Outbound Lines</div>
          <div class="card-hdr-note"><?php echo $total_rows; ?> pending · Branch <?php echo htmlspecialchars($branch); ?></div>
        </div>

        <?php if (empty($rows)): ?>
        <div class="empty-state">
          <div class="empty-icon">
            <svg viewBox="0 0 22 22" fill="none"><path d="M3 17l4-4 4 4 4-4 4 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 5h16" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/><path d="M3 9h10" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
          </div>
          <div class="empty-title">No pending outbound lines</div>
          <div class="empty-sub">Upload a D.C file or add lines to begin processing this delivery order.</div>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto">
          <table id="outTable">
            <thead>
              <tr>
                <th>D.C No</th>
                <th>Product Code</th>
                <th>Qty</th>
                <th>Batch</th>
                <th>Distributor</th>
                <th>City</th>
                <th>Remarks</th>
                <th style="width:120px"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $row): ?>
              <tr>
                <td><div class="cell-mono"><?php echo htmlspecialchars($row['serial_no']); ?></div></td>
                <td><div class="cell-mono"><?php echo htmlspecialchars($row['prod_id']); ?></div></td>
                <td><div class="cell" style="font-weight:600"><?php echo number_format($row['qty']); ?></div></td>
                <td><div class="cell-muted"><?php echo htmlspecialchars($row['batch_out']); ?></div></td>
                <td><div class="cell"><?php echo htmlspecialchars($row['dist']); ?></div></td>
                <td><div class="cell-muted"><?php echo htmlspecialchars($row['city']); ?></div></td>
                <td><div class="cell-muted"><?php echo htmlspecialchars($row['rem']); ?></div></td>
                <td>
                  <div class="act-cell">
                    <button class="btn-edit" onclick="openEdit(<?php echo $row['temp_trans_id']; ?>)">
                      <svg viewBox="0 0 12 12" fill="none"><path d="M7.5 2.5l2 2L4 10H2V8l5.5-5.5z" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
                      Edit
                    </button>
                    <button class="btn-danger" onclick="openDelete(<?php echo $row['temp_trans_id']; ?>, '<?php echo addslashes(htmlspecialchars($row['prod_id'])); ?>')">
                      <svg viewBox="0 0 12 12" fill="none"><path d="M2 3h8M5 3V2h2v1M10 3l-.7 7H2.7L2 3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="tbl-footer">
          <div class="tbl-footer-note"><strong><?php echo $total_rows; ?> line<?php echo $total_rows!=1?'s':''; ?></strong> pending confirmation</div>
          <div class="tbl-footer-note">Last refreshed: <?php echo date('d M Y, H:i'); ?></div>
        </div>
        <?php endif; ?>
      </div>

    </div><!-- /.body -->
  </div><!-- /.content -->
</div><!-- /.shell -->


<!-- ═══════════════════════════════════════════════════
     EDIT MODAL — built from PHP data, shown via JS
     ═══════════════════════════════════════════════════ -->
<?php foreach ($rows as $row): ?>
<div id="edit-modal-<?php echo $row['temp_trans_id']; ?>" class="wms-overlay" onclick="closeOnBackdrop(event, this)">
  <div class="wms-modal">
    <div class="wms-modal-hdr">
      <div class="wms-modal-title">Edit Outbound Line</div>
      <button class="wms-modal-close" onclick="closeModal(this.closest('.wms-overlay'))">
        <svg viewBox="0 0 12 12" fill="none"><path d="M2 2l8 8M10 2l-8 8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
      </button>
    </div>
    <form method="post" action="outward_update.php" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?php echo $row['temp_trans_id']; ?>">
      <input type="hidden" name="vol" value="<?php echo $row['vol']; ?>">
      <div class="wms-modal-body">
        <div class="mform-field">
          <label class="mform-label">D.C No</label>
          <input type="text" class="mform-input" name="sno" value="<?php echo htmlspecialchars($row['serial_no']); ?>" required>
        </div>
        <div class="mform-field">
          <label class="mform-label">Product Code</label>
          <input type="text" class="mform-input" name="code" value="<?php echo htmlspecialchars($row['prod_id']); ?>" required>
        </div>
        <div class="mform-field">
          <label class="mform-label">Batch No.</label>
          <input type="text" class="mform-input" name="batch" value="<?php echo htmlspecialchars($row['batch_out']); ?>" required>
        </div>
        <div class="mform-field">
          <label class="mform-label">Quantity</label>
          <input type="number" class="mform-input" name="qty" value="<?php echo htmlspecialchars($row['qty']); ?>" min="0" required>
        </div>
      </div>
      <div class="wms-modal-foot">
        <button type="button" class="btn-ghost" onclick="closeModal(this.closest('.wms-overlay'))">Cancel</button>
        <button type="submit" class="btn-primary-wms">
          <svg viewBox="0 0 13 13" fill="none"><path d="M2 6.5l3 3 6-6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Save Changes
        </button>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>


<!-- ═══════════════════════════════════════════════════
     DELETE MODAL (single, updated via JS)
     ═══════════════════════════════════════════════════ -->
<div id="delete-modal" class="wms-overlay" onclick="closeOnBackdrop(event, this)">
  <div class="wms-modal" style="max-width:400px">
    <div class="wms-modal-hdr">
      <div class="wms-modal-title">Remove Line</div>
      <button class="wms-modal-close" onclick="closeModal(document.getElementById('delete-modal'))">
        <svg viewBox="0 0 12 12" fill="none"><path d="M2 2l8 8M10 2l-8 8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
      </button>
    </div>
    <form method="post" action="outward_transactiondel.php" enctype="multipart/form-data">
      <input type="hidden" name="id" id="del-id-input">
      <div class="del-confirm-body">
        <div class="del-icon">
          <svg viewBox="0 0 20 20" fill="none"><path d="M3 5h14M8 5V3h4v2M16 5l-1 12H5L4 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div style="font-size:14px;font-weight:700;color:var(--text1);margin-bottom:6px">Remove this line?</div>
        <div class="del-msg">Product <strong id="del-prod-name"></strong> will be removed from this delivery order. This cannot be undone.</div>
      </div>
      <div class="wms-modal-foot">
        <button type="button" class="btn-ghost" onclick="closeModal(document.getElementById('delete-modal'))">Cancel</button>
        <button type="submit" class="btn-danger" style="padding:7px 18px;font-size:12.5px">
          <svg viewBox="0 0 12 12" fill="none"><path d="M2 3h8M5 3V2h2v1M10 3l-.7 7H2.7L2 3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Yes, Remove
        </button>
      </div>
    </form>
  </div>
</div>

<script>
/* ── Sidebar accordion ── */
document.querySelectorAll('.ng-hdr').forEach(function(h){
  h.addEventListener('click', function(){ h.parentElement.classList.toggle('open'); });
});

/* ── Table search filter ── */
function filterTable(val){
  val = val.toLowerCase();
  document.querySelectorAll('#outTable tbody tr').forEach(function(r){
    r.style.display = r.textContent.toLowerCase().includes(val) ? '' : 'none';
  });
}

/* ── File input label ── */
function updateFileLabel(input){
  var label = document.getElementById('fileLabel');
  label.textContent = input.files.length ? input.files[0].name : 'Choose File';
}

/* ── Modal helpers ── */
function openModal(overlay){ overlay.classList.add('open'); }
function closeModal(overlay){ overlay.classList.remove('open'); }
function closeOnBackdrop(e, overlay){ if(e.target === overlay) closeModal(overlay); }

function openEdit(id){
  openModal(document.getElementById('edit-modal-' + id));
}

function openDelete(id, prodName){
  document.getElementById('del-id-input').value = id;
  document.getElementById('del-prod-name').textContent = prodName;
  openModal(document.getElementById('delete-modal'));
}

/* ── ESC key ── */
document.addEventListener('keydown', function(e){
  if(e.key === 'Escape'){
    document.querySelectorAll('.wms-overlay.open').forEach(function(o){ o.classList.remove('open'); });
  }
});
</script>
</body>
</html>