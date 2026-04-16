<?php
session_start();
if(empty($_SESSION['id'])){header('Location:../index.php');exit;}
if(empty($_SESSION['branch'])){header('Location:../index.php');exit;}
$branch   = $_SESSION['branch'];
$id       = $_SESSION['id'];
$user_group = $_SESSION['user_group'];
if($user_group < '1'){header('Location:../index.php');exit;}
$uname    = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$parts    = explode(' ', trim($uname));
$initials = '';
foreach(array_slice($parts,0,2) as $p) $initials .= strtoupper($p[0]);

include('conn/dbcon.php');

/* ── POST: Check / Verify / Approve ── */
if(isset($_POST['sub'])){
    $dd=intval($_POST['d']);
    $dat=date('Y/m/d H:i:s');
    mysqli_query($con,"UPDATE stockin SET checked='$dat' WHERE stockin_id='$dd'") or die(mysqli_error($con));
    echo "<script>document.location='discrepancy_rpt.php'</script>"; exit;
}
if(isset($_POST['sub1'])){
    $dd=intval($_POST['d']);
    $dat=date('Y/m/d H:i:s');
    mysqli_query($con,"UPDATE stockin SET verified='$dat' WHERE stockin_id='$dd'") or die(mysqli_error($con));
    echo "<script>document.location='discrepancy_rpt.php'</script>"; exit;
}
if(isset($_POST['sub2'])){
    $dd=intval($_POST['d']);
    $dat=date('Y/m/d H:i:s');
    mysqli_query($con,"UPDATE stockin SET approved='$dat' WHERE stockin_id='$dd'") or die(mysqli_error($con));
    echo "<script>document.location='discrepancy_rpt.php'</script>"; exit;
}

/* ── Load discrepancy rows ── */
$rows = array();
$safe_branch = mysqli_real_escape_string($con, $branch);

$filter_stage  = isset($_GET['stage'])  ? $_GET['stage']  : '';
$filter_search = isset($_GET['q'])      ? $_GET['q']      : '';
$filter_sort   = isset($_GET['sort'])   ? $_GET['sort']   : 'rec_date_desc';

$q = mysqli_query($con,
    "SELECT stockin.stockin_id, stockin.rec_dnno, stockin.prod_id,
            product.prod_desc, product.prod_name,
            stockin.batch, stockin.asn_qty, stockin.qty, stockin.asn_balance,
            stockin.rec_date, stockin.checked, stockin.verified, stockin.approved
     FROM stockin
     INNER JOIN product ON product.prod_desc = stockin.prod_id
     WHERE stockin.branch_id = '$safe_branch'
       AND stockin.asn_balance != '0'
     ORDER BY stockin.rec_date ASC"
) or die(mysqli_error($con));
while($r = mysqli_fetch_assoc($q)) $rows[] = $r;

/* ── Compute per-row metrics ── */
foreach($rows as &$row){
    $row['variance']   = intval($row['asn_qty']) - intval($row['qty']); // positive = shortage, negative = over
    $row['var_pct']    = $row['asn_qty']>0 ? round(abs($row['variance']/$row['asn_qty'])*100,1) : 0;
    $row['type']       = $row['variance'] > 0 ? 'Shortage' : ($row['variance'] < 0 ? 'Over' : 'Exact');
    // Stage
    if($row['approved'])      $row['stage'] = 'Approved';
    elseif($row['verified'])  $row['stage'] = 'Verified';
    elseif($row['checked'])   $row['stage'] = 'Checked';
    else                      $row['stage'] = 'Pending';
    // Age in days
    $row['age_days']   = $row['rec_date'] ? (int)floor((time()-strtotime($row['rec_date']))/86400) : 0;
}
unset($row);

/* ── Apply filters ── */
$filtered = $rows;
if($filter_stage){
    $filtered = array_filter($filtered, function($r) use($filter_stage){ return $r['stage']===$filter_stage; });
}
if($filter_search){
    $s = strtolower($filter_search);
    $filtered = array_filter($filtered, function($r) use($s){
        return strpos(strtolower($r['prod_name']),$s)!==false
            || strpos(strtolower($r['prod_desc']),$s)!==false
            || strpos(strtolower($r['rec_dnno']),  $s)!==false
            || strpos(strtolower($r['batch']),      $s)!==false;
    });
}
$filtered = array_values($filtered);

/* ── Aggregate KPIs ── */
$total_rows      = count($rows);
$total_filtered  = count($filtered);
$total_asn       = array_sum(array_column($rows,'asn_qty'));
$total_received  = array_sum(array_column($rows,'qty'));
$total_variance  = array_sum(array_column($rows,'variance'));
$total_shortage  = count(array_filter($rows, function($r){ return $r['type']==='Shortage'; }));
$total_over      = count(array_filter($rows, function($r){ return $r['type']==='Over'; }));
$pending_count   = count(array_filter($rows, function($r){ return $r['stage']==='Pending'; }));
$checked_count   = count(array_filter($rows, function($r){ return $r['stage']==='Checked'; }));
$verified_count  = count(array_filter($rows, function($r){ return $r['stage']==='Verified'; }));
$approved_count  = count(array_filter($rows, function($r){ return $r['stage']==='Approved'; }));
$var_pct_overall = $total_asn > 0 ? round((abs($total_variance)/$total_asn)*100,1) : 0;
$critical_count  = count(array_filter($rows, function($r){ return $r['var_pct']>=10; }));
// Unique ASNs with discrepancies
$unique_asns     = count(array_unique(array_column($rows,'rec_dnno')));

/* ── Excel export ── */
if(isset($_POST['export'])){
    header("Content-Type: application/vnd.ms-excel");
    header('Content-Disposition: attachment; filename="Discrepancy_Report_'.date('Y-m-d').'.xls"');
    echo "ASN #\tItem Code\tItem Name\tBatch\tASN Qty\tReceived\tVariance\tVariance %\tType\tRec. Date\tAge (days)\tStage\tChecked\tVerified\tApproved\n";
    foreach($rows as $r){
        echo htmlspecialchars_decode($r['rec_dnno'])."\t";
        echo $r['prod_desc']."\t".$r['prod_name']."\t".$r['batch']."\t";
        echo $r['asn_qty']."\t".$r['qty']."\t".$r['variance']."\t".$r['var_pct']."%\t";
        echo $r['type']."\t".$r['rec_date']."\t".$r['age_days']."\t".$r['stage']."\t";
        echo $r['checked']."\t".$r['verified']."\t".$r['approved']."\n";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sovereign WMS &#8212; Discrepancy Report</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>

*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#1a2238;--navy-mid:#1e2a42;--navy-light:#253350;--orange:#d95f2b;--orange-lt:#f4722e;--orange-muted:#fdf1eb;--orange-bd:#f6c9b0;--bg:#f2f1ee;--bg2:#ebe9e5;--white:#fff;--border:#e0ded8;--border2:#cccac3;--text1:#181816;--text2:#58574f;--text3:#9a9890;--green:#1a6b3a;--green-bg:#eef7f2;--green-bd:#b6dfc8;--red:#b91c1c;--red-bg:#fef2f2;--red-bd:#fecaca;--amber:#92580a;--amber-bg:#fffbeb;--amber-bd:#fcd88a;--blue:#1e4fa0;--blue-bg:#eff4ff;--blue-bd:#bdd0f8;--purple:#6b21a8;--purple-bg:#faf5ff;--purple-bd:#e9d5ff}
html{height:100%}body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text1);font-size:13px;line-height:1.5;height:100%;display:flex;flex-direction:column;overflow:hidden}
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
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:7px;font-size:12px;font-weight:500;cursor:pointer;border:none;font-family:'Inter',sans-serif;transition:all .13s;text-decoration:none;white-space:nowrap}.btn svg{width:13px;height:13px;flex-shrink:0}
.btn-navy{background:var(--navy);color:#fff}.btn-navy:hover{background:var(--navy-mid)}
.btn-ghost{background:var(--white);color:var(--text2);border:1px solid var(--border2)}.btn-ghost:hover{background:var(--bg2)}
.btn-green{background:var(--green-bg);color:var(--green);border:1px solid var(--green-bd)}.btn-green:hover{background:#dcfce7}
.btn-sm{padding:5px 10px;font-size:11.5px}
/* KPI */
.kpi-row{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px}
.kpi{background:var(--white);border:1px solid var(--border);border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px}
.ki{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0}.ki svg{width:17px;height:17px}
.ki-red{background:var(--red-bg);color:var(--red)}.ki-amber{background:var(--amber-bg);color:var(--amber)}.ki-green{background:var(--green-bg);color:var(--green)}.ki-navy{background:var(--navy);color:#fff}.ki-blue{background:var(--blue-bg);color:var(--blue)}.ki-purple{background:var(--purple-bg);color:var(--purple)}
.kpi-lbl{font-size:10.5px;color:var(--text3);margin-bottom:2px}.kpi-val{font-size:21px;font-weight:800;color:var(--text1);letter-spacing:-.5px;line-height:1}.kpi-sub{font-size:10px;color:var(--text3);margin-top:2px}
.kpi-badge{display:inline-flex;align-items:center;gap:3px;font-size:10.5px;font-weight:600;border-radius:4px;padding:1px 6px;margin-top:4px}
.kb-red{color:var(--red);background:var(--red-bg)}.kb-amber{color:var(--amber);background:var(--amber-bg)}.kb-green{color:var(--green);background:var(--green-bg)}
/* Viz row */
.viz-row{display:grid;grid-template-columns:2fr 1fr 1fr;gap:12px;margin-bottom:14px}
.panel{background:var(--white);border:1px solid var(--border);border-radius:10px;padding:15px}
.panel-title{font-size:12px;font-weight:600;color:var(--text1);margin-bottom:12px}
/* Approval pipeline */
.pipeline{display:flex;align-items:center;gap:0;margin-bottom:14px}
.pip-step{flex:1;text-align:center;padding:10px 8px;position:relative}
.pip-step:not(:last-child)::after{content:'';position:absolute;right:-1px;top:50%;transform:translateY(-50%);width:0;height:0;border-top:8px solid transparent;border-bottom:8px solid transparent;border-left:10px solid;z-index:2}
.pip-num{font-size:20px;font-weight:800;line-height:1;margin-bottom:3px}
.pip-lbl{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:1px}
.pip-sub{font-size:9.5px}
.pip-pending{background:var(--red-bg);border:1px solid var(--red-bd);border-radius:8px 0 0 8px}.pip-pending::after{border-left-color:var(--red-bg)}.pip-pending .pip-num{color:var(--red)}.pip-pending .pip-lbl{color:var(--red)}.pip-pending .pip-sub{color:#d07070}
.pip-checked{background:var(--amber-bg);border:1px solid var(--amber-bd);border-top:none;border-bottom:none}.pip-checked::after{border-left-color:var(--amber-bg)}.pip-checked .pip-num{color:var(--amber)}.pip-checked .pip-lbl{color:var(--amber)}.pip-checked .pip-sub{color:#b07040}
.pip-verified{background:var(--blue-bg);border:1px solid var(--blue-bd);border-top:none;border-bottom:none}.pip-verified::after{border-left-color:var(--blue-bg)}.pip-verified .pip-num{color:var(--blue)}.pip-verified .pip-lbl{color:var(--blue)}.pip-verified .pip-sub{color:#5070c0}
.pip-approved{background:var(--green-bg);border:1px solid var(--green-bd);border-radius:0 8px 8px 0}.pip-approved .pip-num{color:var(--green)}.pip-approved .pip-lbl{color:var(--green)}.pip-approved .pip-sub{color:#406040}
/* Var type breakdown */
.vt-row{display:flex;align-items:center;gap:8px;margin-bottom:7px}
.vt-lbl{font-size:11px;color:var(--text2);font-weight:500;width:72px;flex-shrink:0}
.vt-bg{flex:1;height:10px;background:var(--bg2);border-radius:5px;overflow:hidden}
.vt-fill{height:100%;border-radius:5px}
.vt-num{font-size:11px;font-weight:700;color:var(--text1);width:22px;text-align:right}
/* Top discrepancies */
.top-row{display:flex;align-items:center;gap:8px;margin-bottom:7px;font-size:11.5px}
.tr-rank{width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:800;flex-shrink:0;color:#fff}
.tr-name{flex:1;color:var(--text1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tr-code{font-family:'JetBrains Mono',monospace;font-size:10.5px;color:var(--text3)}
.tr-pct{font-size:11px;font-weight:700;border-radius:4px;padding:1px 6px}
/* Filter bar */
.filter-bar{display:flex;align-items:center;gap:8px;margin-bottom:12px;flex-wrap:wrap}
.sw{position:relative}.sw svg{position:absolute;left:9px;top:50%;transform:translateY(-50%);width:12px;height:12px;color:var(--text3);pointer-events:none}
.sw input{padding:7px 9px 7px 28px;border:1px solid var(--border2);border-radius:7px;font-size:12px;font-family:'Inter',sans-serif;color:var(--text1);background:var(--white);outline:none;width:210px;transition:border .15s}.sw input:focus{border-color:#93aac8}
.filter-btn{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:7px;font-size:11.5px;font-weight:500;cursor:pointer;border:1px solid var(--border2);background:var(--white);color:var(--text2);font-family:'Inter',sans-serif;transition:all .12s}
.filter-btn:hover{background:var(--bg2)}.filter-btn.active-all{background:var(--navy);color:#fff;border-color:var(--navy)}
.filter-btn.active-pending{background:var(--red-bg);color:var(--red);border-color:var(--red-bd)}
.filter-btn.active-checked{background:var(--amber-bg);color:var(--amber);border-color:var(--amber-bd)}
.filter-btn.active-verified{background:var(--blue-bg);color:var(--blue);border-color:var(--blue-bd)}
.filter-btn.active-approved{background:var(--green-bg);color:var(--green);border-color:var(--green-bd)}
.filter-right{margin-left:auto;display:flex;gap:8px}
/* Table */
.card{background:var(--white);border:1px solid var(--border);border-radius:10px;overflow:hidden}
.card-hdr{padding:11px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
.card-hdr-title{font-size:13px;font-weight:600;color:var(--text1)}
table{width:100%;border-collapse:collapse}
th{font-size:9.5px;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;padding:9px 12px;border-bottom:1px solid var(--border);text-align:left;white-space:nowrap;background:#fafaf7;cursor:pointer;-webkit-user-select:none;user-select:none}
th:hover{color:var(--text1)}th.sorted-asc::after{content:' \2191'}th.sorted-desc::after{content:' \2193'}
td{padding:0;border-bottom:1px solid #f0efe8;vertical-align:middle}
tbody tr:last-child td{border-bottom:none}tbody tr:hover td{background:#f8f7f4}
.cell{padding:10px 12px;font-size:12px;color:var(--text1)}
.sno{padding:10px 12px;font-size:11px;font-weight:600;color:var(--text3);text-align:center;width:36px}
.mono{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text2)}
.item-cell{display:flex;align-items:center;gap:9px;padding:10px 12px}
.item-av{width:30px;height:30px;border-radius:7px;background:var(--bg2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:var(--text2);flex-shrink:0;transition:all .12s}
tbody tr:hover .item-av{background:var(--red-bg);border-color:var(--red-bd);color:var(--red)}
.item-name{font-size:12px;font-weight:500;color:var(--text1)}.item-code{font-size:10.5px;color:var(--text3);font-family:'JetBrains Mono',monospace;margin-top:1px}
/* Variance cell */
.var-cell{padding:10px 12px}
.var-main{font-size:13px;font-weight:800;line-height:1;margin-bottom:3px}
.var-shortage{color:var(--red)}.var-over{color:var(--amber)}.var-exact{color:var(--green)}
.var-bar{height:4px;border-radius:2px;width:60px}
.var-pct-badge{display:inline-flex;align-items:center;font-size:10px;font-weight:700;border-radius:4px;padding:1px 6px;margin-top:2px}
/* Stage cell / approval */
.stage-cell{padding:10px 12px}
.stage-track{display:flex;gap:3px;align-items:center;margin-bottom:4px}
.stage-dot{width:8px;height:8px;border-radius:50%;border:1.5px solid var(--border2)}
.sd-done{border-color:transparent}.sd-next{border-color:var(--orange)}
.stage-lbl{font-size:11px;font-weight:600}
.age-badge{font-size:10px;color:var(--text3);margin-top:2px}
/* Action cell */
.act-cell{padding:10px 12px;white-space:nowrap;opacity:0;transition:opacity .12s}
tbody tr:hover .act-cell{opacity:1}
.ap-btn{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:500;cursor:pointer;border:none;font-family:'Inter',sans-serif;transition:all .12s;white-space:nowrap}
.ap-btn svg{width:11px;height:11px}
.ap-check{background:var(--amber-bg);color:var(--amber);border:1px solid var(--amber-bd)}.ap-check:hover{background:var(--amber);color:#fff}
.ap-verify{background:var(--blue-bg);color:var(--blue);border:1px solid var(--blue-bd)}.ap-verify:hover{background:var(--blue);color:#fff}
.ap-approve{background:var(--green-bg);color:var(--green);border:1px solid var(--green-bd)}.ap-approve:hover{background:var(--green);color:#fff}
.done-badge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:500;background:var(--green-bg);color:var(--green);border:1px solid var(--green-bd)}
/* Table footer */
.tbl-foot{padding:10px 14px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:#fafaf7;font-size:11px;color:var(--text3)}
.tbl-foot strong{color:var(--text2)}
/* Empty */
.empty-state{padding:52px 20px;text-align:center}.empty-icon{width:52px;height:52px;border-radius:14px;background:var(--bg2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;margin:0 auto 14px}.empty-icon svg{width:22px;height:22px;color:var(--text3)}.empty-title{font-size:14px;font-weight:600;color:var(--text1);margin-bottom:5px}.empty-sub{font-size:12px;color:var(--text2)}
/* Badges */
.badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:500}
.badge::before{content:'';width:5px;height:5px;border-radius:50%}
.b-red{background:var(--red-bg);color:var(--red);border:1px solid var(--red-bd)}.b-red::before{background:var(--red)}
.b-amber{background:var(--amber-bg);color:var(--amber);border:1px solid var(--amber-bd)}.b-amber::before{background:var(--amber)}
.b-blue{background:var(--blue-bg);color:var(--blue);border:1px solid var(--blue-bd)}.b-blue::before{background:var(--blue)}
.b-green{background:var(--green-bg);color:var(--green);border:1px solid var(--green-bd)}.b-green::before{background:var(--green)}
/* Critical row highlight */
tbody tr.critical-row td{background:#fef9f9}tbody tr.critical-row:hover td{background:#fef2f2}
/* Modal */
.mo{display:none;position:fixed;inset:0;background:rgba(14,20,36,.5);z-index:300;align-items:center;justify-content:center;padding:20px}.mo.open{display:flex}
.mb{background:var(--white);border-radius:14px;width:100%;max-width:420px;box-shadow:0 24px 64px rgba(0,0,0,.22);overflow:hidden}
.mhdr{padding:15px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;justify-content:space-between}
.mhi{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0}.mhi svg{width:14px;height:14px}
.mhi-a{background:var(--amber-bg);color:var(--amber)}.mhi-b{background:var(--blue-bg);color:var(--blue)}.mhi-g{background:var(--green-bg);color:var(--green)}
.mt-label{font-size:14px;font-weight:600;color:var(--text1);flex:1}
.mx{width:28px;height:28px;border-radius:7px;background:var(--bg2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text2);flex-shrink:0;transition:all .12s}.mx:hover{background:var(--red-bg);border-color:var(--red-bd);color:var(--red)}.mx svg{width:13px;height:13px}
.minfo{padding:14px 20px;background:var(--bg);border-bottom:1px solid var(--border);display:grid;grid-template-columns:1fr 1fr;gap:10px}
.mi-r{display:flex;flex-direction:column;gap:2px}.mi-l{font-size:9.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.06em}.mi-v{font-size:12px;font-weight:600;color:var(--text1);font-family:'JetBrains Mono',monospace}.mi-v.plain{font-family:'Inter',sans-serif}
.mbody{padding:18px 20px;font-size:13px;color:var(--text1);line-height:1.6}
.mfoot{padding:12px 20px;border-top:1px solid var(--border);display:flex;gap:8px;justify-content:flex-end}
.bsave{padding:9px 20px;border:none;border-radius:8px;font-size:13px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;background:var(--navy);color:#fff;transition:background .13s}.bsave:hover{background:var(--navy-mid)}
.bcancel{padding:9px 15px;background:var(--bg);color:var(--text2);border:1px solid var(--border);border-radius:8px;font-size:12px;font-family:'Inter',sans-serif;cursor:pointer}
@media print{.sidebar,.topbar,.filter-bar,.act-cell,.tbl-foot,.no-print{display:none!important}.content{overflow:visible}.body{padding:0}.card{border:none}}

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
      <div class="ng-items"><a href="inward_transaction.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>A.S.N</a><a href="gatepass.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Gate Pass</a><a href="final_barcode.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Receive</a><a href="final_location.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Location</a></div>
    </div>
    <div class="ng">
      <div class="ng-hdr"><svg class="gi" viewBox="0 0 14 14" fill="none"><path d="M13 7H1M7 11l-4-4 4-4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Outbound<svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
      <div class="ng-items"><a href="outward_transaction.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Transfer Note</a><a href="final_out2.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Order Preparation</a></div>
    </div>
    <div class="nsep"></div>
    <div class="ns">Quality</div>
    <div class="ng">
      <div class="ng-hdr"><svg class="gi" viewBox="0 0 14 14" fill="none"><path d="M2 7l3.5 3.5L12 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Quality Check<svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
      <div class="ng-items"><a href="qualitycheck.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Daily QMS</a></div>
    </div>
    <div class="nsep"></div>
    <div class="ns">Warehouse</div>
    <div class="ng">
      <div class="ng-hdr"><svg class="gi" viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 5h5M4.5 7.5h5M4.5 10h3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>Reports<svg class="gc" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
      <div class="ng-items">
        <a href="inbound_report.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Inbound Report</a>
        <a href="discrepancy_rpt.php" class="na active"><svg viewBox="0 0 6 6" fill="var(--orange-lt)"><circle cx="3" cy="3" r="2.5"/></svg>Discrepancy Report</a>
        <a href="expire.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Expiry Report</a>
        <a href="outbound_report.php" class="na"><svg viewBox="0 0 6 6" fill="none"><circle cx="3" cy="3" r="2" stroke="currentColor" stroke-width="1.2"/></svg>Outbound Report</a>
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
    <div class="crumb"><a href="#">Reports</a><svg viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>Discrepancy Report</div>
    <div class="ph-row">
      <div>
        <div class="ph-title">Inbound Discrepancy Report</div>
        <div class="ph-sub">ASN vs received variance &middot; 3-stage approval workflow &middot; <?php echo htmlspecialchars($branch); ?></div>
      </div>
      <div class="no-print" style="display:flex;gap:8px;flex-wrap:wrap">
        <form method="POST" action="" style="display:inline"><button type="submit" name="export" value="1" class="btn btn-ghost"><svg viewBox="0 0 13 13" fill="none"><path d="M6.5 1v8M3.5 6.5l3 3 3-3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M1.5 10v1A1.5 1.5 0 0 0 3 12.5h7a1.5 1.5 0 0 0 1.5-1.5v-1" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>Export Excel</button></form>
        <button class="btn btn-ghost" onclick="window.print()"><svg viewBox="0 0 13 13" fill="none"><path d="M3.5 4.5V2h6v2.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/><rect x="1" y="4.5" width="11" height="5.5" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M3.5 8h6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>Print</button>
      </div>
    </div>
  </div>
  <div class="body">

<?php
/* Top-5 worst discrepancies by var_pct */
$top5 = $rows;
usort($top5, function($a,$b){ return $b['var_pct'] <=> $a['var_pct']; });
$top5 = array_slice($top5, 0, 5);
$max_top_pct = !empty($top5) ? max(array_column($top5,'var_pct')) : 1;
$max_top_pct = $max_top_pct > 0 ? $max_top_pct : 1;
?>

    <!-- KPI Row -->
    <div class="kpi-row">
      <div class="kpi"><div class="ki ki-red"><svg viewBox="0 0 18 18" fill="none"><circle cx="9" cy="9" r="7" stroke="currentColor" stroke-width="1.3"/><path d="M9 5.5V9.5M9 11.5v.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg></div><div><div class="kpi-lbl">Total discrepancies</div><div class="kpi-val"><?php echo $total_rows; ?></div><div class="kpi-sub">Lines with variance</div><div class="kpi-badge kb-red"><?php echo $unique_asns; ?> ASN<?php echo $unique_asns!=1?'s':''; ?> affected</div></div></div>
      <div class="kpi"><div class="ki ki-amber"><svg viewBox="0 0 18 18" fill="none"><path d="M9 2l1.8 5.5H17l-5 3.6 1.9 5.9L9 13.4l-4.9 3.6 1.9-5.9-5-3.6h6.2z" stroke="currentColor" stroke-width="1.3"/></svg></div><div><div class="kpi-lbl">Variance rate</div><div class="kpi-val"><?php echo $var_pct_overall; ?>%</div><div class="kpi-sub">Of total ASN qty</div><div class="kpi-badge <?php echo $var_pct_overall>=5?'kb-red':($var_pct_overall>=2?'kb-amber':'kb-green'); ?>"><?php echo $total_shortage; ?> shortage · <?php echo $total_over; ?> over</div></div></div>
      <div class="kpi"><div class="ki ki-navy"><svg viewBox="0 0 18 18" fill="none"><path d="M9 2v10M5 8l4 4 4-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 14h14" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg></div><div><div class="kpi-lbl">Total variance</div><div class="kpi-val"><?php echo number_format(abs($total_variance)); ?></div><div class="kpi-sub">Units <?php echo $total_variance>=0?'short':'over'; ?></div><div class="kpi-badge kb-amber">ASN: <?php echo number_format($total_asn); ?> · Rcvd: <?php echo number_format($total_received); ?></div></div></div>
      <div class="kpi"><div class="ki ki-red"><svg viewBox="0 0 18 18" fill="none"><path d="M9 2l7 14H2L9 2z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/><path d="M9 7v4M9 13v.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg></div><div><div class="kpi-lbl">Critical (&ge;10% var)</div><div class="kpi-val"><?php echo $critical_count; ?></div><div class="kpi-sub">High-priority items</div><div class="kpi-badge <?php echo $critical_count>0?'kb-red':'kb-green'; ?>"><?php echo $critical_count>0?'Needs attention':'All clear'; ?></div></div></div>
    </div>

    <!-- Approval pipeline -->
    <div class="pipeline no-print" style="margin-bottom:14px">
      <div class="pip-step pip-pending">
        <div class="pip-num"><?php echo $pending_count; ?></div>
        <div class="pip-lbl">Pending</div>
        <div class="pip-sub">Awaiting check</div>
      </div>
      <div class="pip-step pip-checked">
        <div class="pip-num"><?php echo $checked_count; ?></div>
        <div class="pip-lbl">Checked</div>
        <div class="pip-sub">Inv. Manager</div>
      </div>
      <div class="pip-step pip-verified">
        <div class="pip-num"><?php echo $verified_count; ?></div>
        <div class="pip-lbl">Verified</div>
        <div class="pip-sub">Ops Manager</div>
      </div>
      <div class="pip-step pip-approved">
        <div class="pip-num"><?php echo $approved_count; ?></div>
        <div class="pip-lbl">Approved</div>
        <div class="pip-sub">Q.A. sign-off</div>
      </div>
    </div>

    <!-- Viz row -->
    <div class="viz-row" style="margin-bottom:14px">

      <!-- Top 5 worst -->
      <div class="panel">
        <div class="panel-title">Top 5 worst discrepancies (by variance %)</div>
        <?php if(empty($top5)): ?>
        <div style="font-size:12px;color:var(--text3);text-align:center;padding:20px 0">No data</div>
        <?php else: $rank_colors=array('#b91c1c','#d95f2b','#92580a','#1e4fa0','#1a6b3a'); foreach($top5 as $ri=>$tr): $bw=round(($tr['var_pct']/$max_top_pct)*100); ?>
        <div class="top-row">
          <div class="tr-rank" style="background:<?php echo $rank_colors[$ri]; ?>"><?php echo $ri+1; ?></div>
          <div>
            <div class="tr-name"><?php echo htmlspecialchars($tr['prod_name']); ?></div>
            <div class="tr-code"><?php echo htmlspecialchars($tr['prod_desc']); ?> &middot; Batch <?php echo htmlspecialchars($tr['batch']); ?></div>
            <div style="height:3px;background:var(--bg2);border-radius:2px;width:<?php echo $bw; ?>%;margin-top:3px;background:<?php echo $rank_colors[$ri]; ?>;opacity:.4"></div>
          </div>
          <div class="tr-pct" style="background:<?php echo $ri<2?'var(--red-bg)':($ri<4?'var(--amber-bg)':'var(--blue-bg)'); ?>;color:<?php echo $ri<2?'var(--red)':($ri<4?'var(--amber)':'var(--blue)'); ?>"><?php echo $tr['var_pct']; ?>%</div>
        </div>
        <?php endforeach; endif; ?>
      </div>

      <!-- Variance type breakdown -->
      <div class="panel">
        <div class="panel-title">Variance type distribution</div>
        <?php
          $type_data = array('Shortage'=>$total_shortage,'Over'=>$total_over);
          $max_type  = max(1, max($type_data));
          $type_cfg  = array('Shortage'=>array('color'=>'var(--red)','bg'=>'var(--red-bg)'),'Over'=>array('color'=>'var(--amber)','bg'=>'var(--amber-bg)'));
          foreach($type_data as $typ=>$cnt): $pct_t=$cnt>0?round(($cnt/$max_type)*100):0;
        ?>
        <div class="vt-row">
          <div class="vt-lbl"><?php echo $typ; ?></div>
          <div class="vt-bg"><div class="vt-fill" style="width:<?php echo $pct_t; ?>%;background:<?php echo $type_cfg[$typ]['color']; ?>"></div></div>
          <div class="vt-num"><?php echo $cnt; ?></div>
        </div>
        <?php endforeach; ?>
        <div style="height:1px;background:var(--border);margin:12px 0"></div>
        <div style="font-size:11px;color:var(--text2);margin-bottom:4px">Approval completion</div>
        <?php
          $ap_data=array('Approved'=>$approved_count,'Verified'=>$verified_count,'Checked'=>$checked_count,'Pending'=>$pending_count);
          $ap_cfg=array('Approved'=>'var(--green)','Verified'=>'var(--blue)','Checked'=>'var(--amber)','Pending'=>'var(--red)');
          $max_ap=max(1,max($ap_data));
          foreach($ap_data as $stage_n=>$cnt_a): $pct_a=$cnt_a>0?round(($cnt_a/$max_ap)*100):0;
        ?>
        <div class="vt-row">
          <div class="vt-lbl"><?php echo $stage_n; ?></div>
          <div class="vt-bg"><div class="vt-fill" style="width:<?php echo $pct_a; ?>%;background:<?php echo $ap_cfg[$stage_n]; ?>"></div></div>
          <div class="vt-num"><?php echo $cnt_a; ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Summary stats -->
      <div class="panel">
        <div class="panel-title">Receipt accuracy summary</div>
        <?php $fill_rate=$total_asn>0?round(($total_received/$total_asn)*100,1):100; ?>
        <div style="text-align:center;margin-bottom:14px">
          <div style="font-size:36px;font-weight:800;color:<?php echo $fill_rate>=98?'var(--green)':($fill_rate>=95?'var(--amber)':'var(--red)'); ?>;letter-spacing:-2px;line-height:1"><?php echo $fill_rate; ?>%</div>
          <div style="font-size:11px;color:var(--text3);margin-top:3px">receipt fill rate</div>
          <div style="height:8px;background:var(--bg2);border-radius:4px;overflow:hidden;margin:10px 0 4px">
            <div style="height:100%;border-radius:4px;background:<?php echo $fill_rate>=98?'var(--green)':($fill_rate>=95?'var(--amber)':'var(--red)'); ?>;width:<?php echo min(100,$fill_rate); ?>%"></div>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:9.5px;color:var(--text3)"><span>0</span><span>Target: 100%</span></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
          <div style="background:var(--bg);border:1px solid var(--border);border-radius:7px;padding:8px;text-align:center"><div style="font-size:9px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:2px">ASN Qty</div><div style="font-size:15px;font-weight:800;color:var(--text1)"><?php echo number_format($total_asn); ?></div></div>
          <div style="background:var(--bg);border:1px solid var(--border);border-radius:7px;padding:8px;text-align:center"><div style="font-size:9px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:2px">Received</div><div style="font-size:15px;font-weight:800;color:var(--green)"><?php echo number_format($total_received); ?></div></div>
          <div style="background:var(--red-bg);border:1px solid var(--red-bd);border-radius:7px;padding:8px;text-align:center"><div style="font-size:9px;font-weight:700;color:var(--red);text-transform:uppercase;letter-spacing:.06em;margin-bottom:2px">Total Var.</div><div style="font-size:15px;font-weight:800;color:var(--red)"><?php echo ($total_variance>=0?'+':'').number_format($total_variance); ?></div></div>
          <div style="background:var(--bg);border:1px solid var(--border);border-radius:7px;padding:8px;text-align:center"><div style="font-size:9px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:2px">Var. Rate</div><div style="font-size:15px;font-weight:800;color:var(--text1)"><?php echo $var_pct_overall; ?>%</div></div>
        </div>
      </div>
    </div>

    <!-- Filter bar -->
    <div class="filter-bar no-print">
      <div class="sw"><svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg><input type="text" id="searchInput" placeholder="Search item, code, ASN, batch&#8230;" oninput="applyFilters()"></div>
      <button class="filter-btn active-all" id="fb-all"     onclick="setStage('')">All <span id="cnt-all"><?php echo $total_rows; ?></span></button>
      <button class="filter-btn" id="fb-Pending"  onclick="setStage('Pending')">Pending <span id="cnt-Pending"><?php echo $pending_count; ?></span></button>
      <button class="filter-btn" id="fb-Checked"  onclick="setStage('Checked')">Checked <span id="cnt-Checked"><?php echo $checked_count; ?></span></button>
      <button class="filter-btn" id="fb-Verified" onclick="setStage('Verified')">Verified <span id="cnt-Verified"><?php echo $verified_count; ?></span></button>
      <button class="filter-btn" id="fb-Approved" onclick="setStage('Approved')">Approved <span id="cnt-Approved"><?php echo $approved_count; ?></span></button>
      <div class="filter-right">
        <span id="rowCount" style="font-size:11.5px;color:var(--text3)"><?php echo $total_rows; ?> rows</span>
      </div>
    </div>

    <!-- Table -->
    <div class="card">
      <div class="card-hdr">
        <div class="card-hdr-title">Discrepancy records &mdash; <?php echo htmlspecialchars($branch); ?></div>
        <div style="font-size:11px;color:var(--text3)"><?php echo $total_rows; ?> records &middot; sorted by date &middot; critical rows highlighted</div>
      </div>
      <div style="overflow-x:auto">
        <table id="discTable">
          <thead>
            <tr>
              <th style="width:36px;text-align:center">#</th>
              <th onclick="sortTable(1)">ASN / Line</th>
              <th onclick="sortTable(2)">Item</th>
              <th onclick="sortTable(3)">Batch</th>
              <th onclick="sortTable(4)" style="text-align:right">ASN Qty</th>
              <th onclick="sortTable(5)" style="text-align:right">Received</th>
              <th onclick="sortTable(6)">Variance</th>
              <th onclick="sortTable(7)">Rec. Date</th>
              <th onclick="sortTable(8)">Age</th>
              <th onclick="sortTable(9)">Stage</th>
              <th style="width:110px" class="no-print"></th>
            </tr>
          </thead>
          <tbody id="discBody">
<?php if(empty($rows)): ?>
            <tr><td colspan="11" style="border:none">
              <div class="empty-state">
                <div class="empty-icon"><svg viewBox="0 0 22 22" fill="none"><path d="M2 7l3 3 6-6" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><rect x="3" y="3" width="16" height="16" rx="2" stroke="currentColor" stroke-width="1.3"/></svg></div>
                <div class="empty-title">No discrepancies found</div>
                <div class="empty-sub">All received quantities match their ASN expectations for this branch.</div>
              </div>
            </td></tr>
<?php else: $sno=1; foreach($rows as $row):
  $is_critical = $row['var_pct'] >= 10;
  $var_cls     = $row['type']==='Shortage' ? 'var-shortage' : ($row['type']==='Over' ? 'var-over' : 'var-exact');
  $var_bg      = $row['type']==='Shortage' ? 'var(--red)'  : ($row['type']==='Over' ? 'var(--amber)'  : 'var(--green)');
  $var_badge_cls= $row['type']==='Shortage'?'b-red':($row['type']==='Over'?'b-amber':'b-green');
  $av_init     = strtoupper(substr($row['prod_desc'],0,2));
  $date_fmt    = $row['rec_date'] ? date('d M Y', strtotime($row['rec_date'])) : '—';
  $age_lbl     = $row['age_days']===0 ? 'Today' : $row['age_days'].'d ago';
  $age_col     = $row['age_days']>30 ? 'var(--red)' : ($row['age_days']>14 ? 'var(--amber)' : 'var(--text3)');
  // Stage dots
  $stages_done = array('checked'=>!empty($row['checked']),'verified'=>!empty($row['verified']),'approved'=>!empty($row['approved']));
  $stage_cls   = $row['stage']==='Approved'?'b-green':($row['stage']==='Verified'?'b-blue':($row['stage']==='Checked'?'b-amber':'b-red'));
  // Next action
  $next_action = '';
  if(!$row['checked'])       $next_action = 'check';
  elseif(!$row['verified'])  $next_action = 'verify';
  elseif(!$row['approved'])  $next_action = 'approve';
?>
            <tr class="<?php echo $is_critical?'critical-row':''; ?>" data-stage="<?php echo $row['stage']; ?>" data-search="<?php echo strtolower(htmlspecialchars($row['prod_name'].' '.$row['prod_desc'].' '.$row['rec_dnno'].' '.$row['batch'])); ?>">
              <td class="sno"><?php echo $sno; ?></td>
              <td class="cell mono" style="white-space:nowrap"><?php echo htmlspecialchars($row['rec_dnno']); ?><br><span style="font-size:9.5px;color:var(--text3)">#<?php echo $row['stockin_id']; ?></span></td>
              <td>
                <div class="item-cell">
                  <div class="item-av"><?php echo htmlspecialchars($av_init); ?></div>
                  <div>
                    <div class="item-name"><?php echo htmlspecialchars($row['prod_name']); ?></div>
                    <div class="item-code"><?php echo htmlspecialchars($row['prod_desc']); ?></div>
                  </div>
                </div>
              </td>
              <td class="cell mono"><?php echo htmlspecialchars($row['batch']); ?></td>
              <td class="cell" style="text-align:right;font-weight:600"><?php echo number_format($row['asn_qty']); ?></td>
              <td class="cell" style="text-align:right;font-weight:600"><?php echo number_format($row['qty']); ?></td>
              <td>
                <div class="var-cell">
                  <div class="var-main <?php echo $var_cls; ?>"><?php echo ($row['variance']>0?'+':'').number_format($row['variance']); ?></div>
                  <div style="display:flex;align-items:center;gap:5px;margin-top:3px">
                    <div style="height:3px;width:48px;background:var(--bg2);border-radius:2px;overflow:hidden"><div style="height:100%;width:<?php echo min(100,$row['var_pct']); ?>%;background:<?php echo $var_bg; ?>;border-radius:2px"></div></div>
                    <span class="var-pct-badge <?php echo $var_badge_cls; ?>"><?php echo $row['var_pct']; ?>%</span>
                  </div>
                  <div style="font-size:10px;color:<?php echo $var_bg; ?>;font-weight:600;margin-top:2px"><?php echo $row['type']; ?></div>
                </div>
              </td>
              <td class="cell" style="font-size:11.5px"><?php echo $date_fmt; ?></td>
              <td class="cell"><span style="font-size:11.5px;font-weight:600;color:<?php echo $age_col; ?>"><?php echo $age_lbl; ?></span></td>
              <td>
                <div class="stage-cell">
                  <div class="stage-track">
                    <div class="stage-dot <?php echo $stages_done['checked']?'sd-done':'sd-next'; ?>" style="<?php echo $stages_done['checked']?'background:var(--amber)':''; ?>"></div>
                    <div class="stage-dot <?php echo $stages_done['verified']?'sd-done':''; ?>" style="<?php echo $stages_done['verified']?'background:var(--blue)':''; ?>"></div>
                    <div class="stage-dot <?php echo $stages_done['approved']?'sd-done':''; ?>" style="<?php echo $stages_done['approved']?'background:var(--green)':''; ?>"></div>
                  </div>
                  <span class="badge <?php echo $stage_cls; ?>"><?php echo $row['stage']; ?></span>
                  <?php if(!empty($row['checked'])): ?><div class="age-badge">Chk: <?php echo date('d M',strtotime($row['checked'])); ?></div><?php endif; ?>
                </div>
              </td>
              <td class="act-cell no-print">
                <?php if($next_action==='check'): ?>
                <button class="ap-btn ap-check" onclick="openModal('check',<?php echo $row['stockin_id']; ?>,'<?php echo htmlspecialchars(addslashes($row['prod_name'])); ?>','<?php echo htmlspecialchars(addslashes($row['rec_dnno'])); ?>','<?php echo $row['variance']; ?>','<?php echo $row['var_pct']; ?>')">
                  <svg viewBox="0 0 12 12" fill="none"><path d="M2 6l2.5 2.5L10 3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>Check
                </button>
                <?php elseif($next_action==='verify'): ?>
                <button class="ap-btn ap-verify" onclick="openModal('verify',<?php echo $row['stockin_id']; ?>,'<?php echo htmlspecialchars(addslashes($row['prod_name'])); ?>','<?php echo htmlspecialchars(addslashes($row['rec_dnno'])); ?>','<?php echo $row['variance']; ?>','<?php echo $row['var_pct']; ?>')">
                  <svg viewBox="0 0 12 12" fill="none"><path d="M2 6l2.5 2.5L10 3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>Verify
                </button>
                <?php elseif($next_action==='approve'): ?>
                <button class="ap-btn ap-approve" onclick="openModal('approve',<?php echo $row['stockin_id']; ?>,'<?php echo htmlspecialchars(addslashes($row['prod_name'])); ?>','<?php echo htmlspecialchars(addslashes($row['rec_dnno'])); ?>','<?php echo $row['variance']; ?>','<?php echo $row['var_pct']; ?>')">
                  <svg viewBox="0 0 12 12" fill="none"><path d="M2 6l2.5 2.5L10 3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>Approve
                </button>
                <?php else: ?>
                <span class="done-badge"><svg viewBox="0 0 12 12" fill="none"><path d="M2 6l2.5 2.5L10 3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>Done</span>
                <?php endif; ?>
              </td>
            </tr>
<?php $sno++; endforeach; endif; ?>
          </tbody>
        </table>
      </div>
      <div class="tbl-foot">
        <div><strong id="visCount"><?php echo $total_rows; ?></strong> records visible &middot; <?php echo $pending_count; ?> pending approval &middot; <?php echo $critical_count; ?> critical</div>
        <div>Branch: <?php echo htmlspecialchars($branch); ?> &middot; <?php echo date('d M Y, H:i'); ?></div>
      </div>
    </div>

  </div><!-- /.body -->
</div><!-- /.content -->
</div><!-- /.shell -->

<!-- Check Modal -->
<div class="mo" id="checkModal" onclick="if(event.target===this)closeModal('checkModal')">
  <div class="mb">
    <div class="mhdr"><div class="mhi mhi-a"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7l3.5 3.5L12 3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg></div><div class="mt-label">Mark as Checked</div><div class="mx" onclick="closeModal('checkModal')"><svg viewBox="0 0 14 14" fill="none"><path d="M2 2l10 10M12 2L2 12" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg></div></div>
    <div class="minfo" id="check_info"></div>
    <form method="POST" action=""><input type="hidden" name="d" id="check_d">
    <div class="mbody"><p id="check_msg">Confirm that you have physically checked this discrepancy.</p><p style="font-size:11.5px;color:var(--text3);margin-top:8px">This will stamp your timestamp as the Inventory Manager check.</p></div>
    <div class="mfoot"><button type="button" class="bcancel" onclick="closeModal('checkModal')">Cancel</button><button type="submit" name="sub" value="1" class="bsave">Confirm Check</button></div>
    </form>
  </div>
</div>

<!-- Verify Modal -->
<div class="mo" id="verifyModal" onclick="if(event.target===this)closeModal('verifyModal')">
  <div class="mb">
    <div class="mhdr"><div class="mhi mhi-b"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7l3.5 3.5L12 3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg></div><div class="mt-label">Verify — Operations Manager</div><div class="mx" onclick="closeModal('verifyModal')"><svg viewBox="0 0 14 14" fill="none"><path d="M2 2l10 10M12 2L2 12" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg></div></div>
    <div class="minfo" id="verify_info"></div>
    <form method="POST" action=""><input type="hidden" name="d" id="verify_d">
    <div class="mbody"><p id="verify_msg">Confirm verification of this discrepancy as Operations Manager.</p><p style="font-size:11.5px;color:var(--text3);margin-top:8px">This will stamp your timestamp as the Operations Manager verification.</p></div>
    <div class="mfoot"><button type="button" class="bcancel" onclick="closeModal('verifyModal')">Cancel</button><button type="submit" name="sub1" value="1" class="bsave">Confirm Verify</button></div>
    </form>
  </div>
</div>

<!-- Approve Modal -->
<div class="mo" id="approveModal" onclick="if(event.target===this)closeModal('approveModal')">
  <div class="mb">
    <div class="mhdr"><div class="mhi mhi-g"><svg viewBox="0 0 14 14" fill="none"><path d="M2 7l3.5 3.5L12 3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg></div><div class="mt-label">Approve — Quality Assurance</div><div class="mx" onclick="closeModal('approveModal')"><svg viewBox="0 0 14 14" fill="none"><path d="M2 2l10 10M12 2L2 12" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg></div></div>
    <div class="minfo" id="approve_info"></div>
    <form method="POST" action=""><input type="hidden" name="d" id="approve_d">
    <div class="mbody"><p id="approve_msg">Confirm final Q.A. approval for this discrepancy.</p><p style="font-size:11.5px;color:var(--text3);margin-top:8px">This is the final approval step. The discrepancy will be marked as fully resolved.</p></div>
    <div class="mfoot"><button type="button" class="bcancel" onclick="closeModal('approveModal')">Cancel</button><button type="submit" name="sub2" value="1" class="bsave">Confirm Approve</button></div>
    </form>
  </div>
</div>

<script>
document.querySelectorAll('.ng-hdr').forEach(function(h){h.addEventListener('click',function(){h.parentElement.classList.toggle('open');});});

var currentStage='';
function setStage(s){
  currentStage=s;
  var btns=['','Pending','Checked','Verified','Approved'];
  btns.forEach(function(b){ var el=document.getElementById('fb-'+(b||'all')); if(el){ el.className='filter-btn'+(s==b?' active-'+(b.toLowerCase()||'all'):''); }});
  applyFilters();
}
function applyFilters(){
  var q=document.getElementById('searchInput').value.toLowerCase();
  var rows=document.querySelectorAll('#discBody tr[data-stage]');
  var vis=0;
  rows.forEach(function(r){
    var matchStage = !currentStage || r.dataset.stage===currentStage;
    var matchQ     = !q || r.dataset.search.includes(q);
    r.style.display = (matchStage&&matchQ)?'':'none';
    if(matchStage&&matchQ) vis++;
  });
  document.getElementById('visCount').textContent=vis;
  document.getElementById('rowCount').textContent=vis+' rows';
}
function openModal(type, sid, name, asn, variance, pct){
  var info='<div class="mi-r"><div class="mi-l">Item</div><div class="mi-v plain">'+name+'</div></div><div class="mi-r"><div class="mi-l">ASN Doc</div><div class="mi-v">'+asn+'</div></div><div class="mi-r"><div class="mi-l">Variance</div><div class="mi-v" style="color:'+( parseInt(variance)>0?'var(--red)':'var(--amber)'  )+'">'+variance+' units</div></div><div class="mi-r"><div class="mi-l">Rate</div><div class="mi-v">'+pct+'%</div></div>';
  var modal = type==='check'?'checkModal':type==='verify'?'verifyModal':'approveModal';
  document.getElementById(type+'_info').innerHTML=info;
  document.getElementById(type+'_d').value=sid;
  document.getElementById(modal).classList.add('open');
  document.body.style.overflow='hidden';
}
function closeModal(id){ document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }

// Sort
var sortDir={};
function sortTable(col){
  var tbody=document.getElementById('discBody');
  var rows=Array.from(tbody.querySelectorAll('tr[data-stage]'));
  var dir=sortDir[col]?-1:1; sortDir[col]=!sortDir[col];
  rows.sort(function(a,b){
    var av=a.cells[col]?a.cells[col].textContent.trim():'';
    var bv=b.cells[col]?b.cells[col].textContent.trim():'';
    var na=parseFloat(av.replace(/[^0-9.\-]/g,'')), nb=parseFloat(bv.replace(/[^0-9.\-]/g,''));
    if(!isNaN(na)&&!isNaN(nb)) return (na-nb)*dir;
    return av.localeCompare(bv)*dir;
  });
  rows.forEach(function(r){ tbody.appendChild(r); });
  document.querySelectorAll('th').forEach(function(th){ th.classList.remove('sorted-asc','sorted-desc'); });
  var th=document.querySelectorAll('th')[col];
  if(th) th.classList.add(dir===1?'sorted-asc':'sorted-desc');
}
</script>
</body>
</html>
