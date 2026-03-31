<?php
session_start();
if (empty($_SESSION['id']))     { header('Location:../index.php'); exit; }
if (empty($_SESSION['branch'])) { header('Location:../index.php'); exit; }
$branch     = $_SESSION['branch'];
$id         = $_SESSION['id'];
$name       = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$user_group = $_SESSION['user_group'];

include('conn/dbcon.php');

// Load gate passes
$gatepasses = array();
$q = mysqli_query($con,
  "SELECT gatepass.*, stockin.rec_dnno, stockin.shipper_id,
          supplier.supplier_name,
          COUNT(stockin.stockin_id) as item_count,
          SUM(stockin.asn_qty) as total_qty
   FROM gatepass
   INNER JOIN stockin  ON stockin.gatepass_id  = gatepass.gatepass_id
   LEFT  JOIN supplier ON supplier.supplier_id  = stockin.shipper_id
   WHERE gatepass.branch_id = '$branch'
   GROUP BY stockin.gatepass_id
   ORDER BY gatepass.gatepass_id DESC"
) or die(mysqli_error($con));
while ($r = mysqli_fetch_array($q)) { $gatepasses[] = $r; }

$total     = count($gatepasses);
$finalized = count(array_filter($gatepasses, function($r){ return $r['outdate'] !== '0' && $r['outdate'] !== ''; }));
$pending   = $total - $finalized;
$total_qty = array_sum(array_column($gatepasses, 'total_qty'));

// Load ASN list for new gate pass form
$asns = array();
$qa = mysqli_query($con, "SELECT * FROM stockin WHERE final='1' AND branch_id='$branch' AND gatepass_id=0 GROUP BY rec_dnno") or die(mysqli_error($con));
while ($ra = mysqli_fetch_array($qa)) { $asns[] = $ra; }

// Load transporters
$transporters = array();
$qt = mysqli_query($con, "SELECT * FROM transporter ORDER BY trns_name") or die(mysqli_error($con));
while ($rt = mysqli_fetch_array($qt)) { $transporters[] = $rt; }

// Helper
function initials($s){ $w=array_filter(explode(' ',trim($s)));$o='';foreach(array_slice($w,0,2) as $x)$o.=strtoupper($x[0]);return $o; }
$av_cls = array('ca-1','ca-2','ca-3','ca-4','ca-5');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sovereign WMS — Inward Gate Pass</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
      --navy:#1a2238;--navy-mid:#1e2a42;--navy-light:#253350;
      --orange:#d95f2b;--orange-lt:#f4722e;--orange-muted:#fdf1eb;--orange-bd:#f6c9b0;
      --bg:#f6f5f3;--bg2:#eeede9;--white:#fff;
      --border:#e2e0db;--border2:#d0cec8;
      --text1:#1a1a18;--text2:#5c5b57;--text3:#9e9c96;
      --green:#1a6b3a;--green-bg:#eef7f2;--green-bd:#b6dfc8;
      --red:#b91c1c;--red-bg:#fef2f2;--red-bd:#fecaca;
      --amber:#92580a;--amber-bg:#fffbeb;--amber-bd:#fcd88a;
      --blue:#1e4fa0;--blue-bg:#eff4ff;--blue-bd:#bdd0f8;
      --sidebar-w:220px;--topbar-h:52px;
    }
    html,body{height:100%;font-family:'Inter',sans-serif;background:var(--bg);color:var(--text1);font-size:13px}

    /* Topbar */
    .topbar{position:fixed;top:0;left:0;right:0;height:var(--topbar-h);background:var(--navy);display:flex;align-items:center;padding:0 18px;gap:10px;z-index:100;border-bottom:2px solid var(--orange)}
    .logo-mark{display:flex;align-items:center;width:30px;height:30px;flex-shrink:0}
    .brand .b1{font-size:14px;font-weight:600;color:#fff;letter-spacing:-.2px}
    .brand .b2{font-size:9px;color:#8a9ab8;letter-spacing:.12em;text-transform:uppercase;margin-top:1px}
    .topbar-right{margin-left:auto;display:flex;align-items:center;gap:14px}
    .branch-pill{background:var(--navy-light);border:1px solid #304060;border-radius:6px;padding:4px 10px;display:flex;align-items:center;gap:7px;font-size:11px;color:#8a9ab8}
    .branch-pill strong{color:#fff;font-weight:500}
    .avatar{width:30px;height:30px;border-radius:50%;background:var(--orange);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:600;color:#fff;flex-shrink:0}

    .layout{display:flex;padding-top:var(--topbar-h);min-height:100vh}

    /* Sidebar */
    .sidebar{position:fixed;top:var(--topbar-h);left:0;bottom:0;width:var(--sidebar-w);background:var(--navy);overflow-y:auto;padding:6px 0 24px;border-right:1px solid #253350;z-index:90}
    .sidebar::-webkit-scrollbar{width:3px}
    .sidebar::-webkit-scrollbar-thumb{background:#2e3d5a;border-radius:3px}
    .nav-sect{padding:14px 14px 5px;font-size:9.5px;font-weight:600;color:#364d70;letter-spacing:.1em;text-transform:uppercase}
    .nav-item{display:flex;align-items:center;gap:9px;padding:7px 14px;color:#7a8ba8;font-size:12px;text-decoration:none;position:relative;transition:background .12s,color .12s}
    .nav-item:hover{background:#1e2a42;color:#c8d3e8}
    .nav-item.active{color:#fff}
    .nav-item.active::after{content:'';position:absolute;right:0;top:6px;bottom:6px;width:2.5px;background:var(--orange);border-radius:2px 0 0 2px}
    .nav-item svg{width:14px;height:14px;flex-shrink:0;opacity:.55}
    .nav-item:hover svg,.nav-item.active svg{opacity:1}
    .nav-gh{display:flex;align-items:center;gap:9px;padding:7px 14px;cursor:pointer;color:#7a8ba8;font-size:12px;transition:background .12s,color .12s}
    .nav-gh:hover{background:#1e2a42;color:#c8d3e8}
    .nav-gh svg.ic{width:14px;height:14px;flex-shrink:0;opacity:.55}
    .nav-gh:hover svg.ic,.nav-g.open .nav-gh svg.ic{opacity:1}
    .nav-gh svg.ch{margin-left:auto;width:11px;height:11px;opacity:.4;transition:transform .18s}
    .nav-g.open .nav-gh{color:#c8d3e8}
    .nav-g.open .nav-gh svg.ch{transform:rotate(90deg);opacity:.7}
    .nav-sl{display:none;padding:2px 0}
    .nav-g.open .nav-sl{display:block}
    .nav-sub{display:block;padding:5.5px 14px 5.5px 36px;color:#5c6e8a;font-size:11.5px;text-decoration:none;transition:color .12s,background .12s}
    .nav-sub:hover{color:#c8d3e8;background:#1c2640}
    .nav-sub.active{color:var(--orange-lt);font-weight:500}
    .nav-sep{height:1px;background:#253350;margin:8px 14px}

    /* Main */
    .main{margin-left:var(--sidebar-w);flex:1;padding:22px 26px 40px}
    .crumb{font-size:11px;color:var(--text3);display:flex;align-items:center;gap:5px;margin-bottom:10px}
    .crumb a{color:var(--text2);text-decoration:none}
    .ph{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px}
    .ph-left .title{font-size:18px;font-weight:600;color:var(--text1);letter-spacing:-.4px}
    .ph-left .sub{font-size:12px;color:var(--text2);margin-top:3px}

    /* Buttons */
    .btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:12px;font-weight:500;cursor:pointer;border:none;font-family:'Inter',sans-serif;transition:all .13s;white-space:nowrap}
    .btn svg{width:13px;height:13px;flex-shrink:0}
    .btn-navy{background:var(--navy);color:#fff}
    .btn-navy:hover{background:var(--navy-mid)}
    .btn-orange{background:var(--orange);color:#fff}
    .btn-orange:hover{background:var(--orange-lt)}
    .btn-ghost{background:var(--white);color:var(--text2);border:1px solid var(--border2)}
    .btn-ghost:hover{background:var(--bg2);color:var(--text1)}
    .btn-green{background:var(--green-bg);color:var(--green);border:1px solid var(--green-bd)}
    .btn-sm{padding:5px 11px;font-size:11px}

    /* Stats */
    .stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px}
    .stat-card{background:var(--white);border:1px solid var(--border);border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px}
    .stat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .stat-icon svg{width:18px;height:18px}
    .si-navy{background:var(--navy);color:#fff}
    .si-orange{background:var(--orange-muted);color:var(--orange)}
    .si-green{background:var(--green-bg);color:var(--green)}
    .si-amber{background:var(--amber-bg);color:var(--amber)}
    .stat-label{font-size:10.5px;color:var(--text3);margin-bottom:2px}
    .stat-value{font-size:20px;font-weight:700;color:var(--text1);letter-spacing:-.5px;line-height:1}
    .stat-sub{font-size:10px;color:var(--text3);margin-top:2px}

    /* Card */
    .card{background:var(--white);border:1px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:16px}
    .card-hdr{padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
    .card-hdr-title{font-size:13px;font-weight:600;color:var(--text1)}

    /* Toolbar */
    .toolbar{display:flex;align-items:center;gap:8px}
    .search-wrap{position:relative}
    .search-wrap svg{position:absolute;left:10px;top:50%;transform:translateY(-50%);width:13px;height:13px;color:var(--text3);pointer-events:none}
    .search-wrap input{padding:7px 10px 7px 30px;border:1px solid var(--border2);border-radius:7px;font-size:12px;font-family:'Inter',sans-serif;color:var(--text1);background:var(--bg);outline:none;width:200px;transition:border .15s}
    .search-wrap input:focus{border-color:#9aafcf;background:var(--white)}

    /* Table */
    .tbl-wrap{overflow-x:auto}
    table{width:100%;border-collapse:collapse}
    th{font-size:10px;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;padding:9px 14px;border-bottom:1px solid var(--border);text-align:left;white-space:nowrap;background:#fafaf8}
    td{padding:0;border-bottom:1px solid #f2f0ec;vertical-align:middle}
    tbody tr:last-child td{border-bottom:none}
    tbody tr:hover td{background:#faf9f7}
    tbody tr:hover .hover-actions{opacity:1}

    /* Cells */
    .gp-cell{display:flex;align-items:center;gap:10px;padding:12px 14px}
    .gp-icon{width:32px;height:32px;border-radius:7px;background:var(--bg2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .gp-icon svg{width:14px;height:14px;color:var(--text2)}
    .gp-num{font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:600;color:var(--text1)}
    .gp-sub{font-size:10.5px;color:var(--text3);margin-top:1px}

    .cell{padding:12px 14px;font-size:12px;color:var(--text1)}
    .mono{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text2)}

    .cust-cell{display:flex;align-items:center;gap:8px;padding:12px 14px}
    .cust-av{width:30px;height:30px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0}
    .ca-1{background:#e8f0fe;color:#1e4fa0}
    .ca-2{background:#fce8e6;color:#c0392b}
    .ca-3{background:#e6f4ea;color:#1a6b3a}
    .ca-4{background:#fef3e2;color:#92580a}
    .ca-5{background:#f3e8ff;color:#6b21a8}
    .cust-name{font-size:12px;font-weight:500;color:var(--text1)}
    .cust-asn{font-size:10.5px;color:var(--text3);font-family:'JetBrains Mono',monospace;margin-top:1px}

    .veh-cell{padding:12px 14px}
    .veh-num{font-family:'JetBrains Mono',monospace;font-size:11.5px;font-weight:600;color:var(--text1);background:var(--bg2);border:1px solid var(--border);border-radius:5px;padding:2px 8px;display:inline-block}
    .veh-type{font-size:10.5px;color:var(--text3);margin-top:3px}

    .date-cell{padding:12px 14px}
    .date-main{font-size:12px;font-weight:500;color:var(--text1)}
    .date-sub{font-size:10.5px;color:var(--text3);margin-top:2px}

    .qty-cell{padding:12px 14px}
    .qty-main{font-size:13px;font-weight:700;color:var(--text1)}
    .qty-sub{font-size:10.5px;color:var(--text3);margin-top:1px}

    .badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:500}
    .badge::before{content:'';width:5px;height:5px;border-radius:50%}
    .b-amber{background:var(--amber-bg);color:var(--amber);border:1px solid var(--amber-bd)}
    .b-amber::before{background:var(--amber)}
    .b-green{background:var(--green-bg);color:var(--green);border:1px solid var(--green-bd)}
    .b-green::before{background:var(--green)}

    .status-cell{padding:12px 14px}

    .hover-actions{opacity:0;padding:12px 14px;display:flex;align-items:center;gap:6px;transition:opacity .13s;white-space:nowrap}

    .tbl-footer{padding:10px 14px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:#fafaf8}
    .tbl-footer-note{font-size:11px;color:var(--text3)}
    .tbl-footer-note strong{color:var(--text2)}

    .empty-state{padding:52px 20px;text-align:center}
    .empty-icon{width:52px;height:52px;border-radius:14px;background:var(--bg2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;margin:0 auto 14px}
    .empty-icon svg{width:22px;height:22px;color:var(--text3)}
    .empty-title{font-size:14px;font-weight:600;color:var(--text1);margin-bottom:5px}
    .empty-sub{font-size:12px;color:var(--text2)}

    /* ── Modal / Drawer ── */
    .modal-overlay{display:none;position:fixed;inset:0;background:rgba(14,20,36,.45);z-index:200;align-items:flex-start;justify-content:flex-end}
    .modal-overlay.open{display:flex}
    .modal-drawer{background:var(--white);width:480px;max-width:96vw;height:100%;overflow-y:auto;display:flex;flex-direction:column;box-shadow:-8px 0 32px rgba(0,0,0,.12)}
    .modal-drawer::-webkit-scrollbar{width:3px}
    .modal-drawer::-webkit-scrollbar-thumb{background:var(--border2);border-radius:3px}
    .modal-hdr{padding:18px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
    .modal-title{font-size:15px;font-weight:600;color:var(--text1)}
    .modal-close{width:30px;height:30px;border-radius:7px;background:var(--bg2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text2);transition:all .12s}
    .modal-close:hover{background:var(--red-bg);border-color:var(--red-bd);color:var(--red)}
    .modal-close svg{width:14px;height:14px}
    .modal-body{padding:20px 22px;flex:1}
    .modal-footer{padding:16px 22px;border-top:1px solid var(--border);display:flex;gap:8px;justify-content:flex-end;flex-shrink:0}

    /* Form inside modal */
    .form-section{margin-bottom:22px}
    .form-section-title{font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.1em;margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid var(--border)}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .form-grid.full{grid-template-columns:1fr}
    .ff{display:flex;flex-direction:column;gap:5px}
    .ff label{font-size:10.5px;font-weight:600;color:var(--text2);text-transform:uppercase;letter-spacing:.06em}
    .ff input,.ff select,.ff textarea{padding:9px 11px;border:1.5px solid var(--border2);border-radius:8px;font-size:12.5px;font-family:'Inter',sans-serif;color:var(--text1);background:var(--white);outline:none;width:100%;transition:border .15s;-webkit-appearance:none;appearance:none}
    .ff input:focus,.ff select:focus,.ff textarea:focus{border-color:#9aafcf}
    .ff input::placeholder,.ff textarea::placeholder{color:var(--text3)}
    .ff textarea{resize:vertical;min-height:72px}
    .sel-wrap{position:relative}
    .sel-wrap::after{content:'';position:absolute;right:11px;top:50%;transform:translateY(-50%);pointer-events:none;width:0;height:0;border-left:4px solid transparent;border-right:4px solid transparent;border-top:5px solid var(--text3)}
    .sel-wrap select{padding-right:30px;cursor:pointer}

    /* ASN checkboxes */
    .asn-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:8px}
    .asn-check{display:flex;align-items:center;gap:8px;padding:8px 12px;border:1.5px solid var(--border2);border-radius:8px;cursor:pointer;transition:all .12s;background:var(--white)}
    .asn-check:hover{border-color:#9aafcf;background:var(--blue-bg)}
    .asn-check input{width:15px;height:15px;accent-color:var(--navy);flex-shrink:0;cursor:pointer}
    .asn-check-label{font-family:'JetBrains Mono',monospace;font-size:11.5px;font-weight:600;color:var(--text1)}
    .asn-check.checked{border-color:var(--navy);background:var(--blue-bg)}

    /* Alert strip */
    .alert-info{display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--blue-bg);border:1px solid var(--blue-bd);border-radius:8px;margin-bottom:16px;font-size:12px;color:var(--blue)}
    .alert-info svg{width:15px;height:15px;flex-shrink:0}
  </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
  <svg class="logo-mark" viewBox="0 0 30 36" fill="none">
    <rect x="5" y="1" width="16" height="16" rx="1.5" transform="rotate(45 5 1)" stroke="#d95f2b" stroke-width="2.4" fill="none"/>
    <rect x="9" y="13" width="16" height="16" rx="1.5" transform="rotate(45 9 13)" stroke="#fff" stroke-width="2.4" fill="none"/>
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
    <div class="avatar">
      <?php $p=explode(' ',trim($name));$i='';foreach(array_slice($p,0,2) as $x)$i.=strtoupper($x[0]);echo htmlspecialchars($i); ?>
    </div>
  </div>
</div>

<div class="layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="nav-sect">Main</div>
    <a href="new_dash.php" class="nav-item"><svg viewBox="0 0 14 14" fill="none"><rect x="1" y="1" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="8" y="1" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="1" y="8" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="8" y="8" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/></svg>Dashboard</a>
    <div class="nav-sect">Operations</div>
    <div class="nav-g open">
      <div class="nav-gh"><svg class="ic" viewBox="0 0 14 14" fill="none"><path d="M1 7h12M7 3l4 4-4 4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Inbound<svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
      <div class="nav-sl">
        <a href="inward_transaction.php" class="nav-sub">A.S.N</a>
        <a href="gatepass.php"           class="nav-sub active">Gate Pass</a>
        <a href="final_barcode.php"      class="nav-sub">Receive</a>
        <a href="final_location.php"     class="nav-sub">Location</a>
        <a href="index_stkveh.php"       class="nav-sub">Location List</a>
      </div>
    </div>
    <div class="nav-g">
      <div class="nav-gh"><svg class="ic" viewBox="0 0 14 14" fill="none"><path d="M13 7H1M7 11l-4-4 4-4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Outbound<svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
      <div class="nav-sl">
        <a href="outward_transaction.php" class="nav-sub">Transfer Note</a>
        <a href="final_out2.php"          class="nav-sub">Order Preparation</a>
        <a href="picking_summery.php"     class="nav-sub">Picking Summary</a>
        <a href="seg_list.php"            class="nav-sub">Segregation List</a>
        <a href="gatepass_out.php"        class="nav-sub">Gate Pass</a>
      </div>
    </div>
    <div class="nav-g">
      <div class="nav-gh"><svg class="ic" viewBox="0 0 14 14" fill="none"><path d="M2 5h8a3 3 0 0 1 0 6H6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 3L2 5l2 2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Return<svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
      <div class="nav-sl">
        <a href="final_barcode_return.php" class="nav-sub">Return Stock</a>
        <a href="gatepass_newreturn.php"   class="nav-sub">Return Gate Pass</a>
      </div>
    </div>
    <div class="nav-sep"></div>
    <div class="nav-sect">Warehouse</div>
    <div class="nav-g">
      <div class="nav-gh"><svg class="ic" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="2" stroke="currentColor" stroke-width="1.2"/><path d="M7 1v1.5M7 11.5V13M1 7h1.5M11.5 7H13M2.93 2.93l1.06 1.06M10.01 10.01l1.06 1.06M2.93 11.07l1.06-1.06M10.01 3.99l1.06-1.06" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>Configuration<svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
      <div class="nav-sl">
        <a href="product.php"  class="nav-sub">Items</a>
        <a href="supplier.php" class="nav-sub">Customer</a>
        <a href="zone.php"     class="nav-sub">Zone</a>
      </div>
    </div>
    <div class="nav-g">
      <div class="nav-gh"><svg class="ic" viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 5h5M4.5 7.5h5M4.5 10h3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>Reports<svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
      <div class="nav-sl">
        <a href="inbound_report.php"  class="nav-sub">Inbound Report</a>
        <a href="outbound_report.php" class="nav-sub">Outbound Report</a>
        <a href="expire.php"          class="nav-sub">Expiry Report</a>
      </div>
    </div>
    <div class="nav-sep"></div>
    <a href="logout.php" class="nav-item" style="color:#5c6e8a;margin-top:4px"><svg viewBox="0 0 14 14" fill="none"><path d="M9 7H1M5 4l-3 3 3 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 2h2.5A1.5 1.5 0 0 1 13 3.5v7A1.5 1.5 0 0 1 11.5 12H9" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>Logout</a>
  </aside>

  <!-- Main -->
  <div class="main">

    <div class="crumb">
      <a href="#">Inbound</a>
      <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Gate Pass
    </div>

    <div class="ph">
      <div class="ph-left">
        <div class="title">Inward Gate Pass</div>
        <div class="sub">Manage inbound vehicle gate passes and finalize receipts</div>
      </div>
      <?php if ($user_group != 2): ?>
      <button class="btn btn-navy" onclick="openModal()">
        <svg viewBox="0 0 13 13" fill="none"><path d="M6.5 1v11M1 6.5h11" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
        New Gate Pass
      </button>
      <?php endif; ?>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon si-navy"><svg viewBox="0 0 18 18" fill="none"><rect x="2" y="4" width="14" height="11" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 4V3a3 3 0 0 1 6 0v1" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg></div>
        <div><div class="stat-label">Total Gate Passes</div><div class="stat-value"><?php echo $total; ?></div><div class="stat-sub">All time</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-amber"><svg viewBox="0 0 18 18" fill="none"><circle cx="9" cy="9" r="7" stroke="currentColor" stroke-width="1.3"/><path d="M9 5v4l3 3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg></div>
        <div><div class="stat-label">Pending</div><div class="stat-value"><?php echo $pending; ?></div><div class="stat-sub">Not yet finalized</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-green"><svg viewBox="0 0 18 18" fill="none"><path d="M3 9l4 4 8-8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <div><div class="stat-label">Finalized</div><div class="stat-value"><?php echo $finalized; ?></div><div class="stat-sub">Completed</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-orange"><svg viewBox="0 0 18 18" fill="none"><rect x="3" y="2" width="12" height="14" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 6h6M6 9h6M6 12h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg></div>
        <div><div class="stat-label">Total ASN Qty</div><div class="stat-value"><?php echo number_format($total_qty); ?></div><div class="stat-sub">Units received</div></div>
      </div>
    </div>

    <!-- Gate pass table -->
    <div class="card">
      <div class="card-hdr">
        <div class="card-hdr-title">All gate passes</div>
        <div class="toolbar">
          <div class="search-wrap">
            <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
            <input type="text" id="si" placeholder="Search GP#, customer, vehicle…" oninput="filterRows(this.value)">
          </div>
          <?php if ($user_group != 2): ?>
          <button class="btn btn-orange btn-sm" onclick="openModal()">
            <svg viewBox="0 0 13 13" fill="none"><path d="M6.5 1v11M1 6.5h11" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
            Add New
          </button>
          <?php endif; ?>
        </div>
      </div>

      <div class="tbl-wrap">
        <table id="gp-table">
          <thead>
            <tr>
              <th>Gate Pass #</th>
              <th>Customer</th>
              <th>ASN / Doc No.</th>
              <th>Vehicle</th>
              <th>ASN Qty</th>
              <th>Reporting Date</th>
              <th>In / Out</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($gatepasses)): ?>
            <tr><td colspan="9" style="border:none">
              <div class="empty-state">
                <div class="empty-icon"><svg viewBox="0 0 22 22" fill="none"><rect x="3" y="5" width="16" height="13" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M7 5V4a4 4 0 0 1 8 0v1" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg></div>
                <div class="empty-title">No gate passes yet</div>
                <div class="empty-sub">Create your first gate pass using the "New Gate Pass" button above.</div>
              </div>
            </td></tr>
            <?php else:
              foreach ($gatepasses as $i => $row):
                $av = $av_cls[$i % 5];
                $sp = $row['shipper_id'];
                $is_finalized = ($row['outdate'] !== '0' && !empty($row['outdate']));
                $rpt = $row['rptdate'] ? date('d M Y, H:i', strtotime($row['rptdate'])) : '—';
                $in  = $row['indate']  ? date('d M, H:i', strtotime($row['indate']))   : '—';
                $out = $is_finalized   ? date('d M, H:i', strtotime($row['outdate']))  : '—';
                $cust = $row['supplier_name'] ? $row['supplier_name'] : 'Unknown';
                $ini = initials($cust);
            ?>
            <tr>
              <td>
                <div class="gp-cell">
                  <div class="gp-icon"><svg viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 4.5h5M4.5 6.5h5M4.5 8.5h3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg></div>
                  <div>
                    <div class="gp-num"><?php echo htmlspecialchars($row['gatepass_id']); ?></div>
                    <div class="gp-sub">GRN: <?php echo htmlspecialchars($row['grn_no'] ?? '—'); ?></div>
                  </div>
                </div>
              </td>
              <td>
                <div class="cust-cell">
                  <div class="cust-av <?php echo $av; ?>"><?php echo htmlspecialchars($ini); ?></div>
                  <div>
                    <div class="cust-name"><?php echo htmlspecialchars($cust); ?></div>
                    <div class="cust-asn"><?php echo htmlspecialchars($row['rec_dnno']); ?></div>
                  </div>
                </div>
              </td>
              <td class="cell mono"><?php echo htmlspecialchars($row['rec_dnno']); ?></td>
              <td>
                <div class="veh-cell">
                  <div class="veh-num"><?php echo htmlspecialchars($row['truck_no']) ?: '—'; ?></div>
                  <div class="veh-type"><?php echo htmlspecialchars($row['veh_size']) ?: 'Standard'; ?></div>
                </div>
              </td>
              <td class="qty-cell">
                <div class="qty-main"><?php echo number_format($row['total_qty']); ?></div>
                <div class="qty-sub"><?php echo $row['item_count']; ?> item<?php echo $row['item_count']!=1?'s':''; ?></div>
              </td>
              <td class="date-cell">
                <div class="date-main"><?php echo $rpt; ?></div>
              </td>
              <td class="date-cell">
                <div class="date-main">In: <?php echo $in; ?></div>
                <div class="date-sub">Out: <?php echo $out; ?></div>
              </td>
              <td class="status-cell">
                <?php if ($is_finalized): ?>
                <span class="badge b-green">Finalized</span>
                <?php else: ?>
                <span class="badge b-amber">Pending</span>
                <?php endif; ?>
              </td>
              <td class="hover-actions">
                <!-- Print GRN -->
                <?php if ($sp === '55'): ?>
                <form action="recieptgrn1.php" method="POST" style="display:inline">
                <?php else: ?>
                <form action="recieptgrn.php" method="POST" style="display:inline">
                <?php endif; ?>
                  <input type="hidden" name="sub" value="1">
                  <input type="hidden" name="grn_no" value="<?php echo $row['grn_no']; ?>">
                  <input type="hidden" name="g_no" value="<?php echo $row['gatepass_id']; ?>">
                  <button type="submit" class="btn btn-ghost btn-sm">
                    <svg viewBox="0 0 13 13" fill="none"><path d="M3.5 4.5V2h6v2.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><rect x="1" y="4.5" width="11" height="5.5" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M3.5 8h6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
                    Print
                  </button>
                </form>
                <!-- Finalize -->
                <form action="invoice_recieptattach.php" method="POST" style="display:inline">
                  <input type="hidden" name="grn_no" value="<?php echo $row['grn_no']; ?>">
                  <input type="hidden" name="g_no1" value="<?php echo $row['gatepass_id']; ?>">
                  <?php if (!$is_finalized): ?>
                  <button type="submit" class="btn btn-navy btn-sm">
                    <svg viewBox="0 0 13 13" fill="none"><path d="M2 7l3 3 6-6" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Finalize
                  </button>
                  <?php else: ?>
                  <button type="button" class="btn btn-green btn-sm" disabled>
                    <svg viewBox="0 0 13 13" fill="none"><path d="M2 7l3 3 6-6" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Done
                  </button>
                  <?php endif; ?>
                </form>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <div class="tbl-footer">
        <div class="tbl-footer-note"><strong><?php echo $total; ?> gate pass<?php echo $total!=1?'es':''; ?></strong> · <?php echo $pending; ?> pending · <?php echo $finalized; ?> finalized</div>
        <div class="tbl-footer-note">Last refreshed: <?php echo date('d M Y, H:i'); ?></div>
      </div>
    </div>

  </div><!-- /.main -->
</div><!-- /.layout -->

<!-- ── New Gate Pass Drawer ── -->
<div class="modal-overlay" id="gpModal" onclick="handleOverlayClick(event)">
  <div class="modal-drawer">
    <div class="modal-hdr">
      <div class="modal-title">New Inward Gate Pass</div>
      <div class="modal-close" onclick="closeModal()">
        <svg viewBox="0 0 14 14" fill="none"><path d="M2 2l10 10M12 2L2 12" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
      </div>
    </div>
    <div class="modal-body">
      <form method="POST" action="gatepass_add.php" enctype="multipart/form-data" id="gpForm">

        <?php if (!empty($asns)): ?>
        <!-- ASN Selection -->
        <div class="form-section">
          <div class="form-section-title">Select ASN Document(s)</div>
          <div class="asn-grid">
            <?php foreach ($asns as $asn): ?>
            <label class="asn-check" id="lbl-<?php echo htmlspecialchars($asn['rec_dnno']); ?>">
              <input type="checkbox" name="grn_no[]" value="<?php echo htmlspecialchars($asn['rec_dnno']); ?>"
                     onchange="toggleCheck(this,'lbl-<?php echo htmlspecialchars($asn['rec_dnno']); ?>')">
              <span class="asn-check-label"><?php echo htmlspecialchars($asn['rec_dnno']); ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <?php else: ?>
        <div class="alert-info">
          <svg viewBox="0 0 15 15" fill="none"><circle cx="7.5" cy="7.5" r="6.5" stroke="currentColor" stroke-width="1.3"/><path d="M7.5 5v4M7.5 10.5v.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
          No ASN documents are ready for gate pass. Create an ASN first.
        </div>
        <?php endif; ?>

        <!-- Transporter & Vehicle -->
        <div class="form-section">
          <div class="form-section-title">Transporter &amp; Vehicle</div>
          <div class="form-grid">
            <div class="ff" style="grid-column:1/-1">
              <label>Vehicle Type</label>
              <div class="sel-wrap">
                <select name="trns_name" required>
                  <option value="">— Select Type —</option>
                  <option value="40FT">30FT</option>     
                         <option value="45FT">45FT</option>
                         <option value="40FT">40FT</option>
                         <option value="20FT">20FT</option>
                         <option value="18FT">18FT</option>
                         <option value="16FT">16FT</option>
                         <option value="14FT">14FT</option>
                         <option value="Pickup">Pickup</option>
                         <option value="Other">Other</option>
                </select>  
              </div>
            </div>
            <div class="ff">
              <label>Vehicle No.</label>
              <input type="text" name="vehicle_no" placeholder="e.g. LEA-4521" required>
            </div>
            <div class="ff">
              <label>Transporter</label>
              <input type="text" name="trns_name" placeholder="e.g. ALC Pakistan" required>
            </div>
          </div>
        </div>

        <!-- Driver Details -->
        <div class="form-section">
          <div class="form-section-title">Driver Details</div>
          <div class="form-grid">
            <div class="ff" style="grid-column:1/-1">
              <label>Driver Name</label>
              <input type="text" name="driver" placeholder="Full name" required>
            </div>
            <div class="ff">
              <label>CNIC No.</label>
              <input type="text" name="cnic" placeholder="00000-0000000-0" minlength="15" required>
            </div>
            <div class="ff">
              <label>Mobile No.</label>
              <input type="text" name="mobile" placeholder="0300-0000000" required>
            </div>
          </div>
        </div>

        <!-- Dates & Times -->
        <div class="form-section">
          <div class="form-section-title">Dates &amp; Times</div>
          <div class="form-grid">
           
            <div class="ff">
              <label>Reporting Date &amp; Time</label>
              <input type="datetime-local" name="indate" required>
            </div>
            <div class="ff">
              <label>Veh. Temprature</label>
              <input type="text" name="veh_temp" placeholder="14" required>
            </div>
        <?php /*    
            <div class="ff">
              <label>In Date &amp; Time</label>
              <input type="datetime-local" name="indate" required>
            </div>
            <div class="ff" style="grid-column:1/-1">
              <label>Out Date &amp; Time</label>
              <input type="datetime-local" name="outdate">
            </div>
        */?>    
          </div>
        </div>

        <!-- Remarks -->
        <div class="form-section">
          <div class="form-section-title">Additional Notes</div>
          <div class="ff">
            <label>Remarks</label>
            <textarea name="remarks" placeholder="Optional remarks or instructions…"></textarea>
          </div>
        </div>

      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal()">Cancel</button>
      <button class="btn btn-navy" onclick="document.getElementById('gpForm').submit()">
        <svg viewBox="0 0 13 13" fill="none"><path d="M1.5 9.5v1A1.5 1.5 0 0 0 3 12h7a1.5 1.5 0 0 0 1.5-1.5v-1M9 4.5L6.5 7 4 4.5M6.5 7V1" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Save Gate Pass
      </button>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.nav-gh').forEach(function(h){
  h.addEventListener('click',function(){ h.parentElement.classList.toggle('open'); });
});
function filterRows(v){
  v=v.toLowerCase();
  document.querySelectorAll('#gp-table tbody tr').forEach(function(r){
    r.style.display=r.textContent.toLowerCase().includes(v)?'':'none';
  });
}
function openModal(){
  document.getElementById('gpModal').classList.add('open');
  document.body.style.overflow='hidden';
}
function closeModal(){
  document.getElementById('gpModal').classList.remove('open');
  document.body.style.overflow='';
}
function handleOverlayClick(e){
  if(e.target===document.getElementById('gpModal')) closeModal();
}
function toggleCheck(cb,lblId){
  document.getElementById(lblId).classList.toggle('checked',cb.checked);
}
</script>
</body>
</html>
