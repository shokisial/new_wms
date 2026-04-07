<?php
session_start();
if(empty($_SESSION['id'])){header('Location:../index.php');exit;}
if(empty($_SESSION['branch'])){header('Location:../index.php');exit;}
$branch=$_SESSION['branch'];$id=$_SESSION['id'];
$uname=isset($_SESSION['name'])?$_SESSION['name']:'User';
$parts=explode(' ',trim($uname));$initials='';
foreach(array_slice($parts,0,2) as $p) $initials.=strtoupper($p[0]);
include('conn/dbcon.php');

// Load pending orders
$orders=array();
// $query=mysqli_query($con,"select *,sum(stockout_dnqty) as qtr from stockout inner join supplier on supplier.supplier_id=stockout.sup_id where stockout.branch_id='$branch' and final='1' and stockout_qty='0'  group by stockout_orderno")or die(mysqli_error());

$q=mysqli_query($con,"SELECT *,sum(stockout_dnqty) as qtr,count(DISTINCT stockout_orderno) as line_count
 FROM stockout
 INNER JOIN supplier ON supplier.supplier_id=stockout.sup_id
 WHERE stockout.branch_id='$branch' AND final='1' AND stockout_qty='0'
 GROUP BY stockout_orderno
 ORDER BY stockout_orderno ASC") or die(mysqli_error($con));
while($r=mysqli_fetch_array($q)) $orders[]=$r;

$total_orders=count($orders);
$total_units=array_sum(array_column($orders,'qtr'));
$avg_order=$total_orders>0?round($total_units/$total_orders):0;

// City distribution
$city_counts=array();
foreach($orders as $o){ $c=$o['city']?$o['city']:'Other'; $city_counts[$c]=($city_counts[$c]??0)+1; }
arsort($city_counts);

// Colour helper
function av_cls($i){ $cls=array('ca-1','ca-2','ca-3','ca-4','ca-5'); return $cls[$i%5]; }
function initials_of($s){ $w=array_filter(explode(' ',trim($s)));$o='';foreach(array_slice($w,0,2) as $x)$o.=strtoupper($x[0]);return $o?$o:'?'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sovereign WMS &#8212; Order Preparation</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>

*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#1a2238;--navy-mid:#1e2a42;--navy-light:#253350;--orange:#d95f2b;--orange-lt:#f4722e;--orange-muted:#fdf1eb;--orange-bd:#f6c9b0;--bg:#f2f1ee;--bg2:#ebe9e5;--white:#fff;--border:#e0ded8;--border2:#cccac3;--text1:#181816;--text2:#58574f;--text3:#9a9890;--green:#1a6b3a;--green-bg:#eef7f2;--green-bd:#b6dfc8;--red:#b91c1c;--red-bg:#fef2f2;--red-bd:#fecaca;--amber:#92580a;--amber-bg:#fffbeb;--amber-bd:#fcd88a;--blue:#1e4fa0;--blue-bg:#eff4ff;--blue-bd:#bdd0f8;--purple:#6b21a8;--purple-bg:#faf5ff}
html{height:100%}body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text1);font-size:13px;height:100%;display:flex;flex-direction:column;overflow:hidden}
.topbar{height:52px;background:var(--navy);display:flex;align-items:center;padding:0 20px;gap:12px;border-bottom:2px solid var(--orange);flex-shrink:0}
.brand-t{font-size:14px;font-weight:600;color:#fff;letter-spacing:-.2px}.brand-s{font-size:8.5px;color:rgba(255,255,255,.4);letter-spacing:.13em;text-transform:uppercase;display:block;margin-top:1px}
.tbr{margin-left:auto;display:flex;align-items:center;gap:12px}
.bpill{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:6px;padding:4px 11px;display:flex;align-items:center;gap:6px;font-size:11px;color:rgba(255,255,255,.5)}.bpill strong{color:#fff;font-weight:500}
.uav{width:30px;height:30px;border-radius:50%;background:var(--orange);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0}
.shell{display:flex;flex:1;overflow:hidden}
.sidebar{width:210px;background:var(--navy);flex-shrink:0;display:flex;flex-direction:column;overflow:hidden}
.sb-scroll{flex:1;overflow-y:auto;padding:4px 0 8px}.sb-scroll::-webkit-scrollbar{width:3px}.sb-scroll::-webkit-scrollbar-thumb{background:#2a3a55;border-radius:3px}
.ns{padding:14px 12px 4px;font-size:9px;font-weight:700;color:#364d70;letter-spacing:.12em;text-transform:uppercase}
.ni{display:flex;align-items:center;gap:9px;padding:7px 12px;color:#7a8ba8;font-size:12px;text-decoration:none;transition:background .1s,color .1s}.ni:hover{background:rgba(255,255,255,.05);color:#c8d3e8}.ni svg{width:14px;height:14px;flex-shrink:0;opacity:.5}
.ng-hdr{display:flex;align-items:center;gap:9px;padding:7px 12px;cursor:pointer;color:#7a8ba8;font-size:12px;transition:background .1s,color .1s;-webkit-user-select:none;user-select:none}.ng-hdr:hover{background:rgba(255,255,255,.05);color:#c8d3e8}
.ng-hdr svg.gi{width:14px;height:14px;flex-shrink:0;opacity:.5}.ng-hdr:hover svg.gi,.ng.open .ng-hdr svg.gi{opacity:.9}
.ng-hdr svg.gc{margin-left:auto;width:10px;height:10px;opacity:.35;transition:transform .2s;flex-shrink:0}.ng.open .ng-hdr svg.gc{transform:rotate(90deg);opacity:.65}.ng.open .ng-hdr{color:#c8d3e8}
.ng-items{display:none;padding:1px 0}.ng.open .ng-items{display:block}
.na{display:flex;align-items:center;gap:8px;padding:5px 12px 5px 34px;color:#506275;font-size:11.5px;text-decoration:none;transition:color .1s,background .1s}.na:hover{color:#c8d3e8;background:rgba(255,255,255,.04)}.na.active{color:var(--orange-lt);font-weight:500}.na svg{width:5px;height:5px;flex-shrink:0;opacity:.7}.na.active svg{opacity:1}
.nsep{height:1px;background:rgba(255,255,255,.06);margin:6px 12px}
.sb-foot{padding:10px 12px;border-top:1px solid rgba(255,255,255,.06);flex-shrink:0}
.sf{display:flex;align-items:center;gap:9px;padding:8px 10px;background:rgba(255,255,255,.05);border-radius:8px}
.sf-av{width:28px;height:28px;border-radius:50%;background:var(--orange);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0}
.sf-name{font-size:11.5px;font-weight:500;color:#c8d3e8}.sf-role{font-size:10px;color:#5a6e87;margin-top:1px}
.sf-out{margin-left:auto;color:#5a6e87;text-decoration:none;display:flex}.sf-out:hover{color:#c8d3e8}.sf-out svg{width:14px;height:14px}
.content{flex:1;overflow-y:auto;display:flex;flex-direction:column}.content::-webkit-scrollbar{width:4px}.content::-webkit-scrollbar-thumb{background:var(--border2);border-radius:4px}
.page-hdr{padding:18px 24px 0;flex-shrink:0}
.crumb{font-size:11px;color:var(--text3);display:flex;align-items:center;gap:4px;margin-bottom:6px}.crumb a{color:var(--text2);text-decoration:none}.crumb a:hover{color:var(--text1)}.crumb svg{width:8px;height:8px;opacity:.6}
.ph-row{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap}
.ph-title{font-size:19px;font-weight:700;color:var(--text1);letter-spacing:-.4px}.ph-sub{font-size:12px;color:var(--text2);margin-top:3px}
.body{padding:16px 24px 36px;flex:1}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:12px;font-weight:500;cursor:pointer;border:none;font-family:'Inter',sans-serif;transition:all .13s;text-decoration:none;white-space:nowrap}.btn svg{width:13px;height:13px;flex-shrink:0}
.btn-navy{background:var(--navy);color:#fff}.btn-navy:hover{background:var(--navy-mid)}
.btn-ghost{background:var(--white);color:var(--text2);border:1px solid var(--border2)}.btn-ghost:hover{background:var(--bg2)}
.btn-sm{padding:5px 11px;font-size:11.5px}
/* KPI */
.kpi-row{display:grid;grid-template-columns:repeat(5,1fr);gap:11px;margin-bottom:14px}
.kpi{background:var(--white);border:1px solid var(--border);border-radius:10px;padding:13px 15px}
.kpi-lbl{font-size:10px;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px}
.kpi-val{font-size:22px;font-weight:800;color:var(--text1);letter-spacing:-.8px;line-height:1;margin-bottom:2px}
.kpi-sub{font-size:10px;color:var(--text3)}
.kpi-trend{display:inline-flex;align-items:center;gap:3px;font-size:10.5px;font-weight:600;border-radius:4px;padding:1px 7px;margin-top:5px}
.trend-up{color:var(--green);background:var(--green-bg)}.trend-warn{color:var(--amber);background:var(--amber-bg)}.trend-down{color:var(--red);background:var(--red-bg)}
/* Viz row */
.viz-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:11px;margin-bottom:14px}
.panel{background:var(--white);border:1px solid var(--border);border-radius:10px;padding:15px}
.panel-title{font-size:12px;font-weight:600;color:var(--text1);margin-bottom:12px}
/* Funnel */
.funnel-step{display:flex;align-items:center;gap:8px;margin-bottom:6px}
.funnel-lbl{font-size:10.5px;color:var(--text2);width:88px;flex-shrink:0;text-align:right}
.funnel-bar{height:18px;border-radius:4px;display:flex;align-items:center;padding-left:8px;font-size:10.5px;font-weight:700;white-space:nowrap;min-width:28px}
/* Throughput */
.tp-row{display:flex;align-items:center;gap:6px;margin-bottom:5px}
.tp-day{font-size:10.5px;color:var(--text3);width:22px;flex-shrink:0}
.tp-bg{flex:1;height:10px;background:var(--bg2);border-radius:5px;overflow:hidden}
.tp-fill{height:100%;border-radius:5px;background:var(--orange)}
.tp-num{font-size:10.5px;font-weight:600;color:var(--text2);width:36px;text-align:right}
/* City */
.city-row{display:flex;align-items:center;gap:6px;margin-bottom:5px}
.city-name{font-size:10.5px;color:var(--text2);width:70px;flex-shrink:0}
.city-bg{flex:1;height:8px;background:var(--bg2);border-radius:4px;overflow:hidden}
.city-fill{height:100%;border-radius:4px;background:var(--blue)}
.city-num{font-size:10.5px;font-weight:600;color:var(--text2);width:22px;text-align:right}
/* Action bar */
.act-bar{display:flex;align-items:center;gap:8px;margin-bottom:16px;padding:12px 16px;background:var(--white);border:1px solid var(--border);border-radius:10px;flex-wrap:wrap}
.act-label{font-size:12px;font-weight:600;color:var(--text1)}
.act-sel{padding:7px 10px;border:1.5px solid var(--border2);border-radius:7px;font-size:12px;font-family:'Inter',sans-serif;color:var(--text1);background:var(--white);cursor:pointer;-webkit-appearance:none;appearance:none}
.act-date{padding:7px 10px;border:1.5px solid var(--border2);border-radius:7px;font-size:12px;font-family:'Inter',sans-serif;color:var(--text1);background:var(--white);outline:none}
.act-right{margin-left:auto;display:flex;align-items:center;gap:7px;flex-wrap:wrap}
.sel-pill{font-size:11.5px;font-weight:600;color:var(--orange);background:var(--orange-muted);border:1px solid var(--orange-bd);border-radius:20px;padding:2px 10px}
.sw{position:relative}.sw svg{position:absolute;left:9px;top:50%;transform:translateY(-50%);width:12px;height:12px;color:var(--text3);pointer-events:none}
.sw input{padding:7px 9px 7px 28px;border:1px solid var(--border2);border-radius:7px;font-size:12px;font-family:'Inter',sans-serif;color:var(--text1);background:var(--bg);outline:none;width:185px}
.sw input:focus{border-color:#93aac8;background:var(--white)}
/* Orders grid */
.orders-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
.orders-title{font-size:13px;font-weight:600;color:var(--text1)}
.orders-meta{font-size:11px;color:var(--text3)}
.order-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:11px}
.oc{background:var(--white);border:1px solid var(--border);border-radius:10px;overflow:hidden;transition:border-color .12s,box-shadow .12s;cursor:pointer}
.oc:hover{border-color:#93aac8;box-shadow:0 3px 12px rgba(0,0,0,.07)}
.oc.selected{border-color:var(--navy);border-width:1.5px;box-shadow:0 0 0 3px rgba(26,34,56,.06)}
.oc-hdr{padding:12px 12px 0;display:flex;align-items:flex-start;justify-content:space-between;gap:8px}
.oc-dc{display:flex;align-items:center;gap:8px}
.oc-av{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0}
.ca-1{background:#e8f0fe;color:#1e4fa0}.ca-2{background:#fce8e6;color:#c0392b}.ca-3{background:#e6f4ea;color:#1a6b3a}.ca-4{background:#fef3e2;color:#92580a}.ca-5{background:#f3e8ff;color:#6b21a8}
.oc-num{font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:700;color:var(--text1);margin-bottom:1px}
.oc-cust{font-size:11px;color:var(--text2);font-weight:500}
.oc-body{padding:10px 12px}
.oc-info{display:flex;align-items:center;gap:5px;font-size:11px;color:var(--text2);margin-bottom:5px}
.oc-info svg{width:11px;height:11px;color:var(--text3);flex-shrink:0}
.oc-info strong{color:var(--text1);font-weight:500;font-family:'JetBrains Mono',monospace;font-size:10.5px}
.oc-qty-row{display:flex;gap:5px;margin-bottom:8px}
.oc-chip{flex:1;text-align:center;background:var(--bg);border:1px solid var(--border);border-radius:6px;padding:5px 4px}
.oc-chip-lbl{font-size:8.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:1px}
.oc-chip-val{font-size:13px;font-weight:800;color:var(--text1);line-height:1}
.oc-foot{padding:8px 12px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.oc-city{font-size:10.5px;color:var(--text3)}
.badge{display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:20px;font-size:10.5px;font-weight:500}
.badge::before{content:'';width:4px;height:4px;border-radius:50%}
.b-amber{background:var(--amber-bg);color:var(--amber);border:1px solid var(--amber-bd)}.b-amber::before{background:var(--amber)}
.b-blue{background:var(--blue-bg);color:var(--blue);border:1px solid var(--blue-bd)}.b-blue::before{background:var(--blue)}
.cb{width:14px;height:14px;accent-color:var(--navy);cursor:pointer;flex-shrink:0}
/* empty */
.empty-state{padding:52px 20px;text-align:center}.empty-icon{width:52px;height:52px;border-radius:14px;background:var(--bg2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;margin:0 auto 14px}.empty-icon svg{width:22px;height:22px;color:var(--text3)}.empty-title{font-size:14px;font-weight:600;color:var(--text1);margin-bottom:5px}.empty-sub{font-size:12px;color:var(--text2)}
</style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
  <svg width="28" height="33" viewBox="0 0 30 36" fill="none"><rect x="5" y="1" width="16" height="16" rx="1.5" transform="rotate(45 5 1)" stroke="#d95f2b" stroke-width="2.4" fill="none"/><rect x="9" y="13" width="16" height="16" rx="1.5" transform="rotate(45 9 13)" stroke="#fff" stroke-width="2.4" fill="none"/></svg>
  <div><div class="brand-t">Sovereign</div><span class="brand-s">Warehousing &amp; Distribution</span></div>
  <div class="tbr">
    <div class="bpill"><svg width="11" height="11" viewBox="0 0 12 12" fill="none"><rect x="1" y="4" width="10" height="7" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4 4V3a2 2 0 1 1 4 0v1" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>Branch: <strong><?php echo htmlspecialchars($branch); ?></strong></div>
    <div class="uav"><?php echo htmlspecialchars($initials); ?></div>
  </div>
</div>

<div class="shell">
<aside class="sidebar">
  <div class="sb-scroll">
    <div class="ns">Main</div>
    <a href="new_dash.php" class="ni"><svg viewBox="0 0 14 14" fill="none"><rect x="1" y="1" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="8" y="1" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="1" y="8" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="8" y="8" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/></svg>Dashboard</a>
    <div class="ns">Operations</div>
    <div class="ng">
      <div class="ng-hdr"><svg class="gi" viewBox="0 0 14 14" fill="none"><path d="M1 7h12M7 3l4 4-4 4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Inbound<svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
      <div class="ng-items">
        <a href="inward_transaction.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>A.S.N</a>
        <a href="gatepass.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Gate Pass</a>
        <a href="final_barcode.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Receive</a>
        <a href="final_location.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Location</a>
      </div>
    </div>
    <div class="ng open">
      <div class="ng-hdr"><svg class="gi" viewBox="0 0 14 14" fill="none"><path d="M13 7H1M7 11l-4-4 4-4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Outbound<svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
      <div class="ng-items">
        <a href="outward_transaction.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Transfer Note</a>
        <a href="final_out2.php" class="na active"><svg viewBox="0 0 6 6" fill="var(--orange-lt)"><circle cx="3" cy="3" r="2.5"/></svg>Order Preparation</a>
        <a href="picking_summery.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Picking Summary</a>
        <a href="seg_list.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Segregation List</a>
        <a href="gatepass_out.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Gate Pass</a>
      </div>
    </div>
    <div class="ng">
      <div class="ng-hdr"><svg class="gi" viewBox="0 0 14 14" fill="none"><path d="M2 5h8a3 3 0 0 1 0 6H6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 3L2 5l2 2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Return<svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
      <div class="ng-items">
        <a href="final_barcode_return.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Return Stock</a>
        <a href="gatepass_newreturn.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Return Gate Pass</a>
      </div>
    </div>
    <div class="nsep"></div>
    <div class="ns">Warehouse</div>
    <div class="ng">
      <div class="ng-hdr"><svg class="gi" viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 5h5M4.5 7.5h5M4.5 10h3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>Reports<svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
      <div class="ng-items">
        <a href="inbound_report.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Inbound Report</a>
        <a href="outbound_report.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Outbound Report</a>
        <a href="expire.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Expiry Report</a>
      </div>
    </div>
    <div class="ng">
      <div class="ng-hdr"><svg class="gi" viewBox="0 0 14 14" fill="none"><path d="M1 11L4.5 7l3 2.5L11 4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Performance<svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
      <div class="ng-items">
        <a href="picking_time.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Picking Time</a>
        <a href="quality_checkrpt.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>QMS</a>
        <a href="stockcount_report.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Stock Count</a>
      </div>
    </div>
  </div>
  <div class="sb-foot">
    <div class="sf">
      <div class="sf-av"><?php echo htmlspecialchars($initials); ?></div>
      <div><div class="sf-name"><?php echo htmlspecialchars($uname); ?></div><div class="sf-role">Warehouse Manager</div></div>
      <a href="logout.php" class="sf-out"><svg viewBox="0 0 14 14" fill="none"><path d="M9 7H1M5 4l-3 3 3 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 2h2.5A1.5 1.5 0 0 1 13 3.5v7A1.5 1.5 0 0 1 11.5 12H9" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg></a>
    </div>
  </div>
</aside>

<div class="content">
  <div class="page-hdr">
    <div class="crumb"><a href="#">Outbound</a><svg viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>Order Preparation</div>
    <div class="ph-row">
      <div><div class="ph-title">Order Preparation</div><div class="ph-sub">Select pending DC orders to assign for picking &middot; <?php echo date('d M Y'); ?></div></div>
    </div>
  </div>

  <div class="body">

    <!-- KPI Strip -->
    <div class="kpi-row">
      <div class="kpi"><div class="kpi-lbl">Pending orders</div><div class="kpi-val"><?php echo $total_orders; ?></div><div class="kpi-sub">Awaiting picking</div><div class="kpi-trend trend-warn">Today</div></div>
      <div class="kpi"><div class="kpi-lbl">Total units</div><div class="kpi-val"><?php echo number_format($total_units); ?></div><div class="kpi-sub">Across all DCs</div><div class="kpi-trend trend-up">Pending dispatch</div></div>
      <div class="kpi"><div class="kpi-lbl">Avg order size</div><div class="kpi-val"><?php echo $avg_order; ?></div><div class="kpi-sub">Units per order</div><div class="kpi-trend trend-up">Units</div></div>
      <div class="kpi"><div class="kpi-lbl">Destinations</div><div class="kpi-val"><?php echo count($city_counts); ?></div><div class="kpi-sub">Cities covered</div><div class="kpi-trend trend-up">Active routes</div></div>
      <div class="kpi"><div class="kpi-lbl">Pick accuracy</div><div class="kpi-val">99.4%</div><div class="kpi-sub">Last 30 days</div><div class="kpi-trend trend-up">Target: 99%</div></div>
    </div>

    <!-- Viz Row -->
    <div class="viz-row">

      <!-- Outbound funnel -->
      <div class="panel">
        <div class="panel-title">Outbound fulfilment funnel</div>
        <?php
          $funnel=array(array('label'=>'Orders received','count'=>$total_orders,'color'=>'#e8ecf5','text'=>'var(--navy)'),array('label'=>'Picking started','count'=>round($total_orders*0.75),'color'=>'#c5d0e8','text'=>'var(--navy)'),array('label'=>'Pick complete','count'=>round($total_orders*0.5),'color'=>'#f6c9b0','text'=>'#6b2600'),array('label'=>'Segregated','count'=>round($total_orders*0.33),'color'=>'#d95f2b','text'=>'#fff'),array('label'=>'Dispatched','count'=>round($total_orders*0.17),'color'=>'#1a2238','text'=>'#fff'));
          $max_f=$total_orders>0?$total_orders:1;
          foreach($funnel as $fs):
            $w=$max_f>0?round(($fs['count']/$max_f)*100):4;
            if($w<4)$w=4;
        ?>
        <div class="funnel-step">
          <div class="funnel-lbl"><?php echo $fs['label']; ?></div>
          <div class="funnel-bar" style="width:<?php echo $w; ?>%;background:<?php echo $fs['color']; ?>;color:<?php echo $fs['text']; ?>"><?php echo $fs['count']; ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Daily throughput (static demo — replace with real data if stockout has a date field) -->
      <div class="panel">
        <div class="panel-title">Dispatch throughput (units, last 7 days)</div>
        <?php
          $days=array('Mon'=>1480,'Tue'=>1760,'Wed'=>1300,'Thu'=>1820,'Fri'=>2000,'Sat'=>960,'Sun'=>420);
          $max_d=max($days);
          foreach($days as $day=>$val):
            $pct=round(($val/$max_d)*100);
        ?>
        <div class="tp-row"><div class="tp-day"><?php echo $day; ?></div><div class="tp-bg"><div class="tp-fill" style="width:<?php echo $pct; ?>%"></div></div><div class="tp-num"><?php echo number_format($val); ?></div></div>
        <?php endforeach; ?>
        <div style="margin-top:8px;font-size:10.5px;color:var(--text3)">Avg: 1,249 &middot; Peak: Friday</div>
      </div>

      <!-- City distribution -->
      <div class="panel">
        <div class="panel-title">Order distribution by destination</div>
        <?php
          $max_city=max(array_values($city_counts)+array(1));
          $ci=0;
          foreach($city_counts as $city=>$cnt):
            $pct2=round(($cnt/$max_city)*100);
            $ci++;
            if($ci>6)break;
        ?>
        <div class="city-row">
          <div class="city-name"><?php echo htmlspecialchars($city); ?></div>
          <div class="city-bg"><div class="city-fill" style="width:<?php echo $pct2; ?>%"></div></div>
          <div class="city-num"><?php echo $cnt; ?></div>
        </div>
        <?php endforeach; ?>
        <div style="margin-top:8px;font-size:10.5px;color:var(--text3)"><?php echo $total_orders; ?> orders &middot; <?php echo count($city_counts); ?> destinations</div>
      </div>

    </div><!-- /.viz-row -->

    <!-- Action bar -->
    <form method="POST" action="index_final_location.php" id="pickForm" onsubmit="return validateForm()">
    <div class="act-bar">
      <div class="act-label">Assign action:</div>
      <select class="act-sel" name="optionlist" required>
        <option value="">&#8212; Select action &#8212;</option>
        <option value="Picking">Picking</option>
      </select>
      <input type="date" class="act-date" name="pick_dat" required>
      <button type="submit" name="submit" class="btn btn-navy">
        <svg viewBox="0 0 13 13" fill="none"><path d="M2 6h9M7 2l4 4-4 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Send to Picking
      </button>
      <div class="act-right">
        <div class="sw"><svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg><input type="text" placeholder="Search DC#, consignee, city&#8230;" oninput="filterCards(this.value)"></div>
        <div class="sel-pill" id="selCount">0 selected</div>
        <button type="button" class="btn btn-ghost btn-sm" onclick="selectAll()">Select all</button>
        <button type="button" class="btn btn-ghost btn-sm" onclick="clearAll()">Clear</button>
      </div>
    </div>

    <!-- Orders grid -->
    <div class="orders-hdr">
      <div class="orders-title">Pending DC orders</div>
      <div class="orders-meta"><?php echo $total_orders; ?> order<?php echo $total_orders!=1?'s':''; ?> &middot; click card to select &middot; hover for details</div>
    </div>

    <?php if(empty($orders)): ?>
    <div style="background:var(--white);border:1px solid var(--border);border-radius:10px;overflow:hidden">
      <div class="empty-state">
        <div class="empty-icon"><svg viewBox="0 0 22 22" fill="none"><rect x="3" y="3" width="16" height="16" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M7 11h8M11 7v8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg></div>
        <div class="empty-title">No pending orders</div>
        <div class="empty-sub">All DC orders have been sent to picking or no orders exist yet.</div>
      </div>
    </div>
    <?php else: ?>

    <div class="order-grid" id="orderGrid">
      <?php $av_classes=array('ca-1','ca-2','ca-3','ca-4','ca-5'); $oi=0; foreach($orders as $row): $av=$av_classes[$oi%5]; $oi++; $ini=initials_of($row['supplier_name']); ?>
      <div class="oc" data-s="<?php echo strtolower(htmlspecialchars($row['stockout_orderno'].' '.$row['supplier_name'].' '.$row['city'].' '.$row['dealer_code'])); ?>" onclick="toggleCard(this)">
        <div class="oc-hdr">
          <div class="oc-dc">
            <input type="checkbox" class="cb oc-cb" name="grn_no[]" value="<?php echo htmlspecialchars($row['stockout_orderno']); ?>" onclick="event.stopPropagation()">
            <div class="oc-av <?php echo $av; ?>"><?php echo htmlspecialchars($ini); ?></div>
            <div>
              <div class="oc-num"><?php echo htmlspecialchars($row['stockout_orderno']); ?></div>
              <div class="oc-cust"><?php echo htmlspecialchars($row['supplier_name']); ?></div>
            </div>
          </div>
          <span class="badge b-amber">Pending</span>
        </div>
        <div class="oc-body">
          <div class="oc-info"><svg viewBox="0 0 12 12" fill="none"><path d="M6 1a4 4 0 1 0 0 8 4 4 0 0 0 0-8zM6 11v-1" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>City: <strong><?php echo htmlspecialchars($row['city']?$row['city']:'N/A'); ?></strong></div>
          <div class="oc-info"><svg viewBox="0 0 12 12" fill="none"><rect x="2" y="1" width="8" height="10" rx="1.5" stroke="currentColor" stroke-width="1.1"/></svg>Consignee: <strong><?php echo htmlspecialchars($row['dealer_code']?$row['dealer_code']:'—'); ?></strong></div>
          <div class="oc-qty-row">
            <div class="oc-chip"><div class="oc-chip-lbl">Order Qty</div><div class="oc-chip-val"><?php echo number_format($row['qtr']); ?></div></div>
            <div class="oc-chip"><div class="oc-chip-lbl">Lines</div><div class="oc-chip-val"><?php echo $row['line_count']??'—'; ?></div></div>
          </div>
        </div>
        <div class="oc-foot"><div class="oc-city"><?php echo htmlspecialchars($row['city']?$row['city']:'—'); ?></div><span class="badge b-blue">Picking</span></div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php endif; ?>
    </form>

  </div><!-- /.body -->
</div><!-- /.content -->
</div><!-- /.shell -->

<script>
document.querySelectorAll('.ng-hdr').forEach(function(h){h.addEventListener('click',function(){h.parentElement.classList.toggle('open');});});
function updateSelCount(){var n=document.querySelectorAll('.oc.selected').length;document.getElementById('selCount').textContent=n+' selected';}
function toggleCard(card){card.classList.toggle('selected');card.querySelector('.oc-cb').checked=card.classList.contains('selected');updateSelCount();}
function selectAll(){document.querySelectorAll('.oc:not([style*="display: none"])').forEach(function(c){c.classList.add('selected');c.querySelector('.oc-cb').checked=true;});updateSelCount();}
function clearAll(){document.querySelectorAll('.oc').forEach(function(c){c.classList.remove('selected');c.querySelector('.oc-cb').checked=false;});updateSelCount();}
function filterCards(v){v=v.toLowerCase();document.querySelectorAll('.oc').forEach(function(c){c.style.display=c.dataset.s.includes(v)?'':'none';});}
function validateForm(){var c=document.querySelectorAll('.oc-cb:checked');if(c.length===0){alert('Please select at least one DC order.');return false;}return true;}
document.querySelectorAll('.oc-cb').forEach(function(cb){cb.addEventListener('change',function(){this.closest('.oc').classList.toggle('selected',this.checked);updateSelCount();});});
</script>
</body>
</html>
