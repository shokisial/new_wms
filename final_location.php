<?php
session_start();
if (empty($_SESSION['id']))     { header('Location:../index.php'); exit; }
if (empty($_SESSION['branch'])) { header('Location:../index.php'); exit; }
$branch     = $_SESSION['branch'];
$id         = $_SESSION['id'];
$name       = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$user_group = $_SESSION['user_group'];

include('conn/dbcon.php');

// Active batch
$act = 0;
$qb = mysqli_query($con, "SELECT * FROM batch WHERE batch_status='1'") or die(mysqli_error($con));
while ($rb = mysqli_fetch_array($qb)) { $act = $rb['batch_no']; }

// Get all pending location documents with supplier info
$docs = array();
$qdocs = mysqli_query($con,
    "SELECT stockin.rec_dnno, stockin.truck_no, stockin.shipper_id,
            SUM(stockin.qty) as total_qty,
            SUM(stockin.location) as total_located,
            COUNT(DISTINCT stockin.stockin_id) as line_count,
            supplier.supplier_name,
            gatepass.gatepass_id, gatepass.rptdate
     FROM stockin
     LEFT JOIN supplier ON supplier.supplier_id = stockin.shipper_id
     LEFT JOIN gatepass ON gatepass.gatepass_id = stockin.gatepass_id
     WHERE  stockin.location != stockin.qty
       AND stockin.branch_id='$branch'
     GROUP BY stockin.rec_dnno
     ORDER BY stockin.rec_dnno DESC"
) or die(mysqli_error($con));
while ($r = mysqli_fetch_array($qdocs)) { $docs[] = $r; }

$total_docs      = count($docs);
$total_units     = array_sum(array_column($docs, 'total_qty'));
$total_located   = array_sum(array_column($docs, 'total_located'));
$total_remaining = $total_units - $total_located;
$overall_pct     = $total_units > 0 ? round(($total_located / $total_units) * 100) : 0;

// Avatar colours
$av_cls = array('ca-1','ca-2','ca-3','ca-4','ca-5');
function initials_from($s){ $w=array_filter(explode(' ',trim($s)));$o='';foreach(array_slice($w,0,2) as $x)$o.=strtoupper($x[0]);return $o?$o:'—'; }
?>
<?php include('side_check.php'); ?>

<style>
  /* ── Location page-specific styles ── */

  /* Batch pill */
  .batch-pill { display: inline-flex; align-items: center; gap: 6px; background: var(--green-bg); border: 1px solid var(--green-border); border-radius: 20px; padding: 5px 13px; font-size: 11px; font-weight: 500; color: var(--green); white-space: nowrap; flex-shrink: 0; }
  .bp-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--green); flex-shrink: 0; }

  /* Stats row */
  .stats-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 16px; }
  .sc { background: var(--white); border: 1px solid var(--border); border-radius: 10px; padding: 14px 16px; display: flex; align-items: center; gap: 12px; }
  .si { width: 38px; height: 38px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .si svg { width: 17px; height: 17px; }
  .si-navy   { background: var(--navy); color: #fff; }
  .si-orange { background: var(--orange-muted); color: var(--orange); }
  .si-green  { background: var(--green-bg); color: var(--green); }
  .si-amber  { background: var(--amber-bg); color: var(--amber); }
  .stat-lbl { font-size: 10.5px; color: var(--text3); margin-bottom: 2px; }
  .stat-val { font-size: 20px; font-weight: 700; color: var(--text1); letter-spacing: -.5px; line-height: 1; }
  .stat-sub { font-size: 10px; color: var(--text3); margin-top: 2px; }

  /* Overall progress card */
  .prog-card  { background: var(--white); border: 1px solid var(--border); border-radius: 10px; padding: 14px 18px; margin-bottom: 18px; display: flex; align-items: center; gap: 20px; }
  .prog-left  { flex: 1; }
  .prog-hdr   { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
  .prog-title { font-size: 12px; font-weight: 600; color: var(--text1); }
  .prog-pct   { font-size: 13px; font-weight: 700; color: var(--orange); }
  .prog-bg    { height: 8px; background: var(--bg2); border-radius: 4px; overflow: hidden; }
  .prog-fill  { height: 100%; border-radius: 4px; background: var(--orange); }
  .prog-leg   { display: flex; gap: 18px; margin-top: 8px; }
  .pl         { font-size: 11px; color: var(--text3); display: flex; align-items: center; gap: 5px; }
  .pd         { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
  .prog-right { flex-shrink: 0; text-align: center; }
  .prog-circle{ width: 64px; height: 64px; border-radius: 50%; background: conic-gradient(var(--orange) <?php echo $overall_pct; ?>%, var(--bg2) 0); display: flex; align-items: center; justify-content: center; }
  .prog-inner { width: 48px; height: 48px; border-radius: 50%; background: var(--white); display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; color: var(--text1); }

  /* Search / filter bar */
  .filter-bar      { display: flex; align-items: center; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; }
  .filter-bar-left { display: flex; align-items: center; gap: 8px; flex: 1; }
  .sw              { position: relative; }
  .sw svg          { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 13px; height: 13px; color: var(--text3); pointer-events: none; }
  .sw input        { padding: 8px 10px 8px 30px; border: 1px solid var(--border2); border-radius: 8px; font-size: 12px; font-family: 'Inter', sans-serif; color: var(--text1); background: var(--white); outline: none; width: 220px; transition: border .15s; }
  .sw input:focus  { border-color: #93aac8; }
  .view-toggle     { display: flex; background: var(--bg2); border: 1px solid var(--border); border-radius: 8px; padding: 3px; gap: 2px; }
  .vt-btn          { padding: 5px 10px; border-radius: 6px; font-size: 12px; cursor: pointer; color: var(--text2); border: none; background: none; font-family: 'Inter', sans-serif; display: flex; align-items: center; gap: 5px; transition: all .12s; }
  .vt-btn svg      { width: 13px; height: 13px; }
  .vt-btn.active   { background: var(--white); color: var(--text1); font-weight: 500; box-shadow: 0 1px 3px rgba(0,0,0,.06); }

  /* Card grid */
  .doc-grid        { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 14px; }
  .doc-card        { background: var(--white); border: 1px solid var(--border); border-radius: 10px; overflow: hidden; transition: box-shadow .15s, border-color .15s; }
  .doc-card:hover  { border-color: #93aac8; box-shadow: 0 4px 16px rgba(0,0,0,.08); }
  .doc-card-hdr    { padding: 14px 16px 0; display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
  .doc-av          { width: 36px; height: 36px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0; }
  .ca-1 { background: #e8f0fe; color: #1e4fa0; }
  .ca-2 { background: #fce8e6; color: #c0392b; }
  .ca-3 { background: #e6f4ea; color: #1a6b3a; }
  .ca-4 { background: #fef3e2; color: #92580a; }
  .ca-5 { background: #f3e8ff; color: #6b21a8; }
  .doc-card-meta   { flex: 1; }
  .doc-num         { font-family: 'JetBrains Mono', monospace; font-size: 13px; font-weight: 700; color: var(--text1); margin-bottom: 2px; }
  .doc-customer    { font-size: 11.5px; color: var(--text2); font-weight: 500; }

  .doc-status-badge         { display: inline-flex; align-items: center; gap: 4px; padding: 2px 9px; border-radius: 20px; font-size: 10.5px; font-weight: 500; }
  .doc-status-badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; }
  .b-amber         { background: var(--amber-bg); color: var(--amber); border: 1px solid var(--amber-border); }
  .b-amber::before { background: var(--amber); }
  .b-partial         { background: #eff4ff; color: #1e4fa0; border: 1px solid #bdd0f8; }
  .b-partial::before { background: #1e4fa0; }

  .doc-card-body   { padding: 12px 16px; }
  .doc-info-row    { display: flex; align-items: center; gap: 6px; font-size: 11.5px; color: var(--text2); margin-bottom: 6px; }
  .doc-info-row svg{ width: 12px; height: 12px; color: var(--text3); flex-shrink: 0; }
  .doc-info-row strong { color: var(--text1); font-weight: 500; font-family: 'JetBrains Mono', monospace; font-size: 11px; }
  .doc-qty-row     { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
  .doc-qty-item    { flex: 1; text-align: center; background: var(--bg); border: 1px solid var(--border); border-radius: 7px; padding: 7px 6px; }
  .doc-qty-label   { font-size: 9px; font-weight: 600; color: var(--text3); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 2px; }
  .doc-qty-val     { font-size: 15px; font-weight: 700; color: var(--text1); line-height: 1; }
  .doc-qty-val.orange { color: var(--orange); }
  .doc-qty-val.green  { color: var(--green); }
  .doc-card-prog   { padding: 0 16px 14px; }
  .prog-row        { display: flex; align-items: center; justify-content: space-between; margin-bottom: 5px; }
  .prog-label      { font-size: 10.5px; color: var(--text3); }
  .prog-val        { font-size: 10.5px; font-weight: 600; color: var(--orange); }
  .mini-bar-bg     { height: 5px; background: var(--bg2); border-radius: 3px; overflow: hidden; }
  .mini-bar-fill   { height: 100%; border-radius: 3px; background: var(--orange); }
  .doc-card-foot   { padding: 10px 16px; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
  .doc-gp          { font-size: 10.5px; color: var(--text3); font-family: 'JetBrains Mono', monospace; }
  .doc-btn         { display: inline-flex; align-items: center; gap: 5px; padding: 5px 13px; border-radius: 6px; background: var(--navy); color: #fff; font-size: 11.5px; font-weight: 500; cursor: pointer; border: none; font-family: 'Inter', sans-serif; transition: background .12s; }
  .doc-btn:hover   { background: var(--navy-mid); }
  .doc-btn svg     { width: 12px; height: 12px; }

  /* List view */
  .doc-list        { display: none; }
  .card            { background: var(--white); border: 1px solid var(--border); border-radius: 10px; overflow: hidden; }
  .card-hdr        { padding: 12px 16px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
  .card-hdr-title  { font-size: 13px; font-weight: 600; color: var(--text1); }
  .card-hdr-note   { font-size: 11px; color: var(--text3); }
  table            { width: 100%; border-collapse: collapse; }
  th               { font-size: 9.5px; font-weight: 600; color: var(--text3); text-transform: uppercase; letter-spacing: .06em; padding: 9px 14px; border-bottom: 1px solid var(--border); text-align: left; white-space: nowrap; background: #fafaf7; }
  td               { padding: 0; border-bottom: 1px solid #f0efe8; vertical-align: middle; }
  tbody tr:last-child td { border-bottom: none; }
  tbody tr:hover td      { background: #f8f7f4; }

  .doc-cell        { display: flex; align-items: center; gap: 10px; padding: 12px 14px; }
  .doc-icon        { width: 30px; height: 30px; border-radius: 7px; background: var(--bg2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all .12s; }
  .doc-icon svg    { width: 13px; height: 13px; color: var(--text2); }
  tbody tr:hover .doc-icon     { background: var(--orange-muted); border-color: var(--orange-border); }
  tbody tr:hover .doc-icon svg { color: var(--orange); }
  .doc-no          { font-family: 'JetBrains Mono', monospace; font-size: 12px; font-weight: 600; color: var(--text1); }
  .cust-cell       { display: flex; align-items: center; gap: 8px; padding: 12px 14px; }
  .cav             { width: 28px; height: 28px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; flex-shrink: 0; }
  .cname           { font-size: 12px; font-weight: 500; color: var(--text1); }
  .cell            { padding: 12px 14px; font-size: 12px; color: var(--text1); }
  .mono            { font-family: 'JetBrains Mono', monospace; font-size: 11px; color: var(--text2); }
  .qty-prog        { padding: 12px 14px; }
  .qprog-vals      { display: flex; align-items: baseline; gap: 4px; margin-bottom: 4px; }
  .qprog-main      { font-size: 13px; font-weight: 700; color: var(--text1); }
  .qprog-total     { font-size: 11px; color: var(--text3); }
  .qbar-bg         { height: 4px; background: var(--bg2); border-radius: 2px; width: 80px; overflow: hidden; }
  .qbar-fill       { height: 100%; border-radius: 2px; background: var(--orange); }
  .act-cell        { padding: 12px 14px; opacity: 0; transition: opacity .12s; }
  tbody tr:hover .act-cell { opacity: 1; }
  .tbl-footer      { padding: 10px 14px; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; background: #fafaf7; }
  .tbl-footer-note { font-size: 11px; color: var(--text3); }
  .tbl-footer-note strong { color: var(--text2); }

  .empty-state     { padding: 52px 20px; text-align: center; }
  .empty-icon      { width: 52px; height: 52px; border-radius: 14px; background: var(--bg2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; }
  .empty-icon svg  { width: 22px; height: 22px; color: var(--text3); }
  .empty-title     { font-size: 14px; font-weight: 600; color: var(--text1); margin-bottom: 5px; }
  .empty-sub       { font-size: 12px; color: var(--text2); }

  /* View toggle logic */
  body.list-view .doc-grid { display: none; }
  body.list-view .doc-list { display: block; }
</style>

  <!-- ── Main content ── -->
  <div class="main">

    <div class="crumb">
      <a href="#">Inbound</a>
      <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Location
    </div>

    <div class="ph">
      <div class="ph-left">
        <div class="title">Location Assignment</div>
        <div class="sub">Select a document to begin assigning warehouse locations to received stock</div>
      </div>
      <?php if ($act): ?>
      <div class="batch-pill"><div class="bp-dot"></div>Active Batch: <strong><?php echo htmlspecialchars($act); ?></strong></div>
      <?php endif; ?>
    </div>

    <?php if ($total_docs > 0): ?>
    <!-- Stats -->
    <div class="stats-row">
      <div class="sc"><div class="si si-navy"><svg viewBox="0 0 18 18" fill="none"><rect x="2" y="2" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 9h6M9 6v6" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg></div><div><div class="stat-lbl">Pending Documents</div><div class="stat-val"><?php echo $total_docs; ?></div><div class="stat-sub">Awaiting location</div></div></div>
      <div class="sc"><div class="si si-orange"><svg viewBox="0 0 18 18" fill="none"><path d="M9 2v10M5 8l4 4 4-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 14h14" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg></div><div><div class="stat-lbl">Total Units</div><div class="stat-val"><?php echo number_format($total_units); ?></div><div class="stat-sub">Across all docs</div></div></div>
      <div class="sc"><div class="si si-green"><svg viewBox="0 0 18 18" fill="none"><path d="M3 9l4 4 8-8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></div><div><div class="stat-lbl">Located</div><div class="stat-val"><?php echo number_format($total_located); ?></div><div class="stat-sub"><?php echo $overall_pct; ?>% done</div></div></div>
      <div class="sc"><div class="si si-amber"><svg viewBox="0 0 18 18" fill="none"><circle cx="9" cy="9" r="7" stroke="currentColor" stroke-width="1.3"/><path d="M9 5v4l3 3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg></div><div><div class="stat-lbl">Remaining</div><div class="stat-val"><?php echo number_format($total_remaining); ?></div><div class="stat-sub">Units to locate</div></div></div>
    </div>

    <!-- Overall progress -->
    <div class="prog-card">
      <div class="prog-left">
        <div class="prog-hdr">
          <div class="prog-title">Overall putaway progress — <?php echo $total_docs; ?> document<?php echo $total_docs!=1?'s':''; ?></div>
          <div class="prog-pct"><?php echo $overall_pct; ?>%</div>
        </div>
        <div class="prog-bg"><div class="prog-fill" style="width:<?php echo $overall_pct; ?>%"></div></div>
        <div class="prog-leg">
          <span class="pl"><span class="pd" style="background:var(--orange)"></span><?php echo number_format($total_located); ?> located</span>
          <span class="pl"><span class="pd" style="background:var(--bg2);border:1px solid var(--border2)"></span><?php echo number_format($total_remaining); ?> remaining</span>
          <span class="pl"><span class="pd" style="background:var(--text3)"></span><?php echo number_format($total_units); ?> total</span>
        </div>
      </div>
      <div class="prog-right">
        <div class="prog-circle"><div class="prog-inner"><?php echo $overall_pct; ?>%</div></div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Filter bar + view toggle -->
    <div class="filter-bar">
      <div class="filter-bar-left">
        <div class="sw">
          <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
          <input type="text" id="searchInput" placeholder="Search document, customer, vehicle…" oninput="filterDocs(this.value)">
        </div>
      </div>
      <div class="view-toggle">
        <button class="vt-btn active" id="btnGrid" onclick="setView('grid')">
          <svg viewBox="0 0 13 13" fill="none"><rect x="1" y="1" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="7" y="1" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="1" y="7" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="7" y="7" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/></svg>
          Cards
        </button>
        <button class="vt-btn" id="btnList" onclick="setView('list')">
          <svg viewBox="0 0 13 13" fill="none"><path d="M1 3.5h11M1 6.5h11M1 9.5h11" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
          List
        </button>
      </div>
    </div>

    <?php if (empty($docs)): ?>
    <div style="background:var(--white);border:1px solid var(--border);border-radius:10px;overflow:hidden">
      <div class="empty-state">
        <div class="empty-icon"><svg viewBox="0 0 22 22" fill="none"><rect x="3" y="3" width="16" height="16" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M7 11h8M11 7v8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg></div>
        <div class="empty-title">No documents pending location</div>
        <div class="empty-sub">All received stock has been assigned locations, or no items have been received yet.</div>
      </div>
    </div>
    <?php else: ?>

    <!-- CARD VIEW -->
    <div class="doc-grid" id="docGrid">
      <?php foreach ($docs as $i => $doc):
        $av      = $av_cls[$i % 5];
        $pct     = $doc['total_qty'] > 0 ? round(($doc['total_located'] / $doc['total_qty']) * 100) : 0;
        $rem     = $doc['total_qty'] - $doc['total_located'];
        $bs      = $pct == 0 ? 'b-amber' : 'b-partial';
        $bl      = $pct == 0 ? 'Unlocated' : 'In Progress';
        $cust    = $doc['supplier_name'] ?: 'Unknown Customer';
        $cust_ini= initials_from($cust);
        $target  = ($doc['shipper_id'] == 55) ? 'final_location_ok1.php' : 'final_location_ok.php';
        $truck   = $doc['truck_no']    ?: '—';
        $gp      = $doc['gatepass_id'] ?: '—';
        $indate  = $doc['rptdate'] ? date('d M Y', strtotime($doc['rptdate'])) : '—';
      ?>
      <div class="doc-card" data-search="<?php echo strtolower(htmlspecialchars($doc['rec_dnno'] . ' ' . $cust . ' ' . $truck)); ?>">
        <div class="doc-card-hdr">
          <div class="doc-av <?php echo $av; ?>"><?php echo htmlspecialchars($cust_ini); ?></div>
          <div class="doc-card-meta">
            <div class="doc-num"><?php echo htmlspecialchars($doc['rec_dnno']); ?></div>
            <div class="doc-customer"><?php echo htmlspecialchars($cust); ?></div>
          </div>
          <span class="doc-status-badge <?php echo $bs; ?>"><?php echo $bl; ?></span>
        </div>

        <div class="doc-card-body">
          <div class="doc-info-row">
            <svg viewBox="0 0 12 12" fill="none"><rect x="1" y="4" width="10" height="7" rx="1.5" stroke="currentColor" stroke-width="1.1"/><path d="M8 4V3a3 3 0 0 0-4 2.8" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
            Vehicle: <strong><?php echo htmlspecialchars($truck); ?></strong>
          </div>
          <div class="doc-info-row">
            <svg viewBox="0 0 12 12" fill="none"><rect x="2" y="1" width="8" height="10" rx="1.5" stroke="currentColor" stroke-width="1.1"/><path d="M4 4h4M4 6.5h4M4 9h2" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
            Gate Pass: <strong><?php echo htmlspecialchars($gp); ?></strong>
          </div>
          <div class="doc-info-row">
            <svg viewBox="0 0 12 12" fill="none"><circle cx="6" cy="6" r="5" stroke="currentColor" stroke-width="1.1"/><path d="M6 3.5v3l2 1.5" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
            Received: <strong><?php echo $indate; ?></strong>
          </div>

          <div class="doc-qty-row">
            <div class="doc-qty-item">
              <div class="doc-qty-label">Total Qty</div>
              <div class="doc-qty-val"><?php echo number_format($doc['total_qty']); ?></div>
            </div>
            <div class="doc-qty-item">
              <div class="doc-qty-label">Located</div>
              <div class="doc-qty-val green"><?php echo number_format($doc['total_located']); ?></div>
            </div>
            <div class="doc-qty-item">
              <div class="doc-qty-label">Remaining</div>
              <div class="doc-qty-val orange"><?php echo number_format($rem); ?></div>
            </div>
            <div class="doc-qty-item">
              <div class="doc-qty-label">Lines</div>
              <div class="doc-qty-val"><?php echo $doc['line_count']; ?></div>
            </div>
          </div>
        </div>

        <div class="doc-card-prog">
          <div class="prog-row">
            <div class="prog-label">Location progress</div>
            <div class="prog-val"><?php echo $pct; ?>%</div>
          </div>
          <div class="mini-bar-bg"><div class="mini-bar-fill" style="width:<?php echo $pct; ?>%"></div></div>
        </div>

        <div class="doc-card-foot">
          <div class="doc-gp">Lines: <?php echo $doc['line_count']; ?></div>
          <form method="POST" action="<?php echo $target; ?>" style="display:inline">
            <button type="submit" name="sub" value="<?php echo htmlspecialchars($doc['rec_dnno']); ?>" class="doc-btn">
              <svg viewBox="0 0 12 12" fill="none"><path d="M2 6h8M6 2l4 4-4 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
              Assign Locations
            </button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- LIST VIEW -->
    <div class="doc-list" id="docList">
      <div class="card">
        <div class="card-hdr">
          <div class="card-hdr-title">Pending documents</div>
          <div class="card-hdr-note"><?php echo $total_docs; ?> document<?php echo $total_docs!=1?'s':''; ?> · <?php echo number_format($total_remaining); ?> units unassigned</div>
        </div>
        <div style="overflow-x:auto">
          <table id="docTable">
            <thead>
              <tr>
                <th>Document No.</th>
                <th>Customer</th>
                <th>Gate Pass</th>
                <th>Vehicle</th>
                <th>Total Qty</th>
                <th>Location Progress</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($docs as $i => $doc):
                $av      = $av_cls[$i % 5];
                $pct     = $doc['total_qty'] > 0 ? round(($doc['total_located'] / $doc['total_qty']) * 100) : 0;
                $rem     = $doc['total_qty'] - $doc['total_located'];
                $bs      = $pct == 0 ? 'b-amber' : 'b-partial';
                $bl      = $pct == 0 ? 'Unlocated' : 'In Progress';
                $cust    = $doc['supplier_name'] ?: 'Unknown Customer';
                $cust_ini= initials_from($cust);
                $target  = ($doc['shipper_id'] == 55) ? 'final_location_ok1.php' : 'final_location_ok.php';
              ?>
              <tr>
                <td>
                  <div class="doc-cell">
                    <div class="doc-icon"><svg viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 5h5M4.5 7h5M4.5 9h3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg></div>
                    <div class="doc-no"><?php echo htmlspecialchars($doc['rec_dnno']); ?></div>
                  </div>
                </td>
                <td>
                  <div class="cust-cell">
                    <div class="cav <?php echo $av; ?>"><?php echo htmlspecialchars($cust_ini); ?></div>
                    <div class="cname"><?php echo htmlspecialchars($cust); ?></div>
                  </div>
                </td>
                <td class="cell mono"><?php echo htmlspecialchars($doc['gatepass_id'] ?: '—'); ?></td>
                <td class="cell mono"><?php echo htmlspecialchars($doc['truck_no'] ?: '—'); ?></td>
                <td class="cell" style="font-weight:600"><?php echo number_format($doc['total_qty']); ?></td>
                <td>
                  <div class="qty-prog">
                    <div class="qprog-vals">
                      <span class="qprog-main"><?php echo number_format($doc['total_located']); ?></span>
                      <span class="qprog-total">/ <?php echo number_format($doc['total_qty']); ?></span>
                    </div>
                    <div class="qbar-bg"><div class="qbar-fill" style="width:<?php echo $pct; ?>%"></div></div>
                  </div>
                </td>
                <td class="cell"><span class="doc-status-badge <?php echo $bs; ?>"><?php echo $bl; ?></span></td>
                <td class="act-cell">
                  <form method="POST" action="<?php echo $target; ?>" style="display:inline">
                    <button type="submit" name="sub" value="<?php echo htmlspecialchars($doc['rec_dnno']); ?>" class="doc-btn">
                      <svg viewBox="0 0 12 12" fill="none"><path d="M2 6h8M6 2l4 4-4 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                      Open
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="tbl-footer">
          <div class="tbl-footer-note"><strong><?php echo $total_docs; ?> document<?php echo $total_docs!=1?'s':''; ?></strong> · <?php echo number_format($total_remaining); ?> units still to locate</div>
          <div class="tbl-footer-note">Last refreshed: <?php echo date('d M Y, H:i'); ?></div>
        </div>
      </div>
    </div>

    <?php endif; ?>

  </div><!-- /.main -->
</div><!-- /.layout -->

<script>
document.querySelectorAll('.nav-grp-hdr').forEach(function(h) {
  h.addEventListener('click', function() { h.parentElement.classList.toggle('open'); });
});

function setView(v) {
  document.getElementById('btnGrid').classList.toggle('active', v === 'grid');
  document.getElementById('btnList').classList.toggle('active', v === 'list');
  document.body.classList.toggle('list-view', v === 'list');
}

function filterDocs(val) {
  val = val.toLowerCase();
  document.querySelectorAll('.doc-card').forEach(function(c) {
    c.style.display = c.dataset.search.includes(val) ? '' : 'none';
  });
  document.querySelectorAll('#docTable tbody tr').forEach(function(r) {
    r.style.display = r.textContent.toLowerCase().includes(val) ? '' : 'none';
  });
}
</script>

</body>
</html>