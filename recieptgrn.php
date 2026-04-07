<?php session_start();
if(empty($_SESSION['id'])):
  header('Location:../index.php');
endif;
if(empty($_SESSION['branch'])):
  header('Location:../index.php');
endif;
$branch = $_SESSION['branch'];
$id     = $_SESSION['id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sovereign | Goods Receiving Note</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
 
    :root {
      --ink:         #0f1923;
      --ink-mid:     #3a4a5c;
      --ink-light:   #6b7c93;
      --rule:        #c8d4e0;
      --rule-heavy:  #8fa3b8;
      --accent:      #1a3a5c;
      --accent-pale: #e8f0f8;
      --paper:       #ffffff;
      --font-main:   'IBM Plex Sans', sans-serif;
      --font-mono:   'IBM Plex Mono', monospace;
    }
 
    html, body {
      background: #dce4ec;
      font-family: var(--font-main);
      color: var(--ink);
      font-size: 11px;
      line-height: 1.5;
    }
 
    /* ── Page shell ─────────────────────────────── */
    .page {
      width: 210mm;
      min-height: 297mm;
      margin: 16px auto;
      background: var(--paper);
      padding: 10mm 12mm 12mm;
      box-shadow: 0 4px 32px rgba(0,0,0,.18);
    }
 
    /* ── Header ──────────────────────────────────── */
    .doc-header {
      display: grid;
      grid-template-columns: 80px 1fr auto;
      align-items: center;
      gap: 10px;
      border-bottom: 3px solid var(--accent);
      padding-bottom: 8px;
      margin-bottom: 10px;
    }
    .doc-header img {
      width: 72px; height: 72px;
      object-fit: contain;
    }
    .doc-header .company-info h1 {
      font-size: 13px; font-weight: 700;
      color: var(--accent); letter-spacing: .02em;
    }
    .doc-header .company-info p {
      font-size: 9.5px; color: var(--ink-mid); margin-top: 2px;
    }
    .doc-header .doc-id { text-align: right; }
    .doc-header .doc-id .label {
      font-size: 9px; text-transform: uppercase;
      letter-spacing: .08em; color: var(--ink-light);
    }
    .doc-header .doc-id .value {
      font-family: var(--font-mono);
      font-size: 16px; font-weight: 600; color: var(--accent);
    }
    .doc-header .doc-id .supplier {
      font-size: 10px; font-weight: 600;
      color: var(--ink-mid); margin-top: 3px;
    }
 
    /* ── Section title ───────────────────────────── */
    .section-title {
      display: inline-block;
      font-size: 8.5px; font-weight: 700;
      text-transform: uppercase; letter-spacing: .1em;
      color: var(--paper); background: var(--accent);
      padding: 2px 7px; margin-bottom: 6px;
    }
 
    /* ── Info grid ───────────────────────────────── */
    .info-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      border: 1px solid var(--rule-heavy);
      margin-bottom: 10px;
    }
    .info-grid .cell {
      padding: 5px 8px;
      border-right: 1px solid var(--rule);
      border-bottom: 1px solid var(--rule);
    }
    .info-grid .cell:nth-child(3n)       { border-right: none; }
    .info-grid .cell:nth-last-child(-n+3) { border-bottom: none; }
    .info-grid .cell.span2 { grid-column: span 2; }
    .info-grid .cell.span3 { grid-column: span 3; border-right: none; }
    .info-grid .cell .lbl {
      font-size: 8px; text-transform: uppercase;
      letter-spacing: .07em; color: var(--ink-light); margin-bottom: 2px;
    }
    .info-grid .cell .val {
      font-weight: 600; color: var(--ink); font-size: 10.5px;
      border-bottom: 1px dashed var(--rule-heavy);
      min-height: 14px; padding-bottom: 1px;
    }
    .info-grid .cell .val.mono { font-family: var(--font-mono); }
 
    /* ── Items table ─────────────────────────────── */
    .items-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
    }
    .items-table thead tr {
      background: var(--accent); color: var(--paper);
    }
    .items-table thead th {
      padding: 4px 6px;
      font-size: 8.5px; text-transform: uppercase;
      letter-spacing: .07em; font-weight: 600;
      text-align: left; white-space: nowrap;
    }
    .items-table thead th.num,
    .items-table tbody td.num { text-align: center; }
    .items-table tbody tr { border-bottom: 1px solid var(--rule); }
    .items-table tbody tr:nth-child(even) { background: #f4f7fa; }
    .items-table tbody td {
      padding: 4px 6px; font-size: 10px; vertical-align: middle;
    }
    .items-table tfoot tr { background: var(--accent-pale); }
    .items-table tfoot td {
      padding: 4px 6px; font-size: 10px;
      font-weight: 700; color: var(--accent);
    }
    .items-table tfoot td.num { text-align: center; }
    .prod-code {
      font-family: var(--font-mono); font-size: 9px; color: var(--ink-mid);
    }
    .prod-name { font-weight: 600; }
    .uom-badge {
      display: inline-block; font-size: 7.5px;
      background: var(--accent-pale); color: var(--accent);
      border: 1px solid var(--rule); padding: 0 4px;
      border-radius: 2px; margin-left: 3px; font-weight: 600;
    }
    .mono { font-family: var(--font-mono); }
 
    /* ── Datetime bar ────────────────────────────── */
    .datetime-bar {
      display: flex; gap: 24px;
      border: 1px solid var(--rule);
      padding: 6px 10px; margin-bottom: 10px;
    }
    .datetime-bar .dt-item .lbl {
      font-size: 8px; text-transform: uppercase;
      letter-spacing: .07em; color: var(--ink-light);
    }
    .datetime-bar .dt-item .val {
      font-family: var(--font-mono); font-weight: 600; font-size: 11px;
    }
 
    /* ── Signature section ───────────────────────── */
    .sign-section {
      border-top: 2px solid var(--rule-heavy);
      padding-top: 10px; margin-top: 6px;
    }
    .sign-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
      margin-bottom: 14px;
    }
    .sign-row.two { grid-template-columns: repeat(2, 1fr); }
    .sign-block .sign-label {
      font-size: 8.5px; text-transform: uppercase;
      letter-spacing: .07em; color: var(--ink-light); margin-bottom: 18px;
    }
    .sign-block .sign-line {
      border-top: 1px solid var(--ink-mid);
    }
 
    /* ── Print button (screen only) ──────────────── */
    .btn-print {
      display: block; margin: 12px auto;
      background: var(--accent); color: #fff;
      border: none; padding: 8px 24px;
      font-family: var(--font-main); font-size: 12px;
      cursor: pointer; letter-spacing: .05em;
    }
 
    /* ── Print ───────────────────────────────────── */
    @media print {
      @page { margin: 0; size: A4; }
      html, body { background: white; }
      .page { width: 100%; margin: 0; padding: 8mm 10mm; box-shadow: none; }
      .btn-print { display: none !important; }
    }
  </style>
</head>
<body onload="window.print();">
 
<?php
if (isset($_POST['sub'])) {
  include('conn/dbcon.php');
  include('createbarcode.php');
 
  $queryb = mysqli_query($con, "SELECT * FROM branch WHERE branch_id='$branch'") or die(mysqli_error());
  $rowb       = mysqli_fetch_array($queryb);
  $branch_add = $rowb['branch_name'];
}
 
$rcdoc = $_POST['grn_no']; 
$g_no  = $_POST['g_no'];
 //echo 'check = ' . $g_no . 'Branch = ' . $branch;
// Supplier & document number
$query10 = mysqli_query($con,
  "SELECT * FROM stockin
   INNER JOIN supplier ON supplier.supplier_id = stockin.shipper_id
   WHERE stockin.gatepass_id='$g_no'"
) or die(mysqli_error($con));
while ($row10 = mysqli_fetch_array($query10)) {
  $ts  = $row10['supplier_name'];
  $dcn = $row10['rec_dnno'];
}
 
// Items
$qtr = 0; $qtrp = 0; $qtrl = 0;
$veh = $cnc = $indat = $outdat = $veh_temp = $p_temp = $dr = $mobile = $tr = $gtpass = $type = $blt = $seal = '';
 
$query = mysqli_query($con,
  "SELECT * FROM stockin
   INNER JOIN gatepass ON gatepass.gatepass_id = stockin.gatepass_id
   WHERE stockin.gatepass_id='$g_no'
     AND stockin.branch_id='$branch'
   ORDER BY stockin_id ASC"
) or die(mysqli_error($con));
 
$items = [];
while ($row = mysqli_fetch_array($query)) {
  $pds = $row['prod_id'];
  $query11 = mysqli_query($con,
    "SELECT * FROM product WHERE prod_desc='$pds' AND branch_id='$branch'"
  ) or die(mysqli_error($con));
  $row11 = mysqli_fetch_array($query11);
 
  $veh      = $row['truck_no'];
  $cnc      = $row['cnic'];
  $indat    = $row['indate'];
  $outdat   = $row['outdate'];
  $veh_temp = $row['veh_temp'];
  $p_temp   = $row['item_temp'];
  $dr       = $row['driver'];
  $mobile   = $row['mobile'];
  $tr       = $row['transporter'];
  $gtpass   = $row['gatepass_id'];
  $type     = $row['veh_size'];
  $blt      = $row['bilty'];
  $seal     = $row['seal'];
 
  $qtr  += $row['qty'];
  $qtrp += $row['pack_rec'];
  $qtrl += $row['loose_rec'];
 
  $items[] = ['row' => $row, 'prod' => $row11];
}
 
$date_in  = date_create("$indat");
$date_out = date_create("$outdat");
?>
 
<div class="page">
 
  <!-- ── Header ────────────────────────────────────────── -->
  <div class="doc-header">
    <img src="taqlogo.png" alt="Logo">
    <div class="company-info">
      <h1>Sovereign Warehousing &amp; Distribution &mdash; <?php echo htmlspecialchars($branch_add); ?></h1>
      <p>Goods Receiving Note &nbsp;&bull;&nbsp; SWD/SRSM/016/QF-04</p>
    </div>
    <div class="doc-id">
      <div class="label">GRN No.</div>
      <div class="value"><?php echo htmlspecialchars($g_no); ?></div>
      <div class="supplier"><?php echo htmlspecialchars($ts); ?></div>
    </div>
  </div>
 
  <!-- ── Warehouse / Doc No ────────────────────────────── -->
  <div class="section-title">Document Info</div>
  <div class="info-grid">
    <div class="cell span2">
      <div class="lbl">Warehouse</div>
      <div class="val">Sovereign Warehousing &amp; Distribution &mdash; <?php echo htmlspecialchars($branch_add); ?></div>
    </div>
    <div class="cell">
      <div class="lbl">Document No.</div>
      <div class="val mono"><?php echo htmlspecialchars($dcn); ?></div>
    </div>
  </div>
 
  <!-- ── Items Table ───────────────────────────────────── -->
  <div class="section-title">Received Items</div>
  <table class="items-table">
    <thead>
      <tr>
        <th>Item Code</th>
        <th>Description</th>
        <th>Batch</th>
        <th>MFG</th>
        <th>Expiry</th>
        <th class="num">Qty</th>
        <th class="num">Carton</th>
        <th class="num">Loose</th>
        <th class="num">Damage</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it):
        $r  = $it['row'];
        $p  = $it['prod'];
      ?>
      <tr>
        <td><span class="prod-code"><?php echo htmlspecialchars($p['prod_desc']); ?></span></td>
        <td>
          <span class="prod-name"><?php echo htmlspecialchars($p['prod_name']); ?></span>
          <span class="uom-badge"><?php echo htmlspecialchars($r['uom']); ?></span>
        </td>
        <td class="mono"><?php echo htmlspecialchars($r['batch']); ?></td>
        <td class="mono"><?php echo htmlspecialchars($r['mfg']); ?></td>
        <td class="mono"><?php echo htmlspecialchars($r['expiry']); ?></td>
        <td class="num mono"><?php echo $r['qty']; ?></td>
        <td class="num mono"><?php echo $r['pack_rec']; ?></td>
        <td class="num mono"><?php echo round($r['loose_rec']); ?></td>
        <td class="num mono"><?php echo htmlspecialchars($r['cond_qty']); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="5" style="text-align:right; padding-right:10px;">Total Received</td>
        <td class="num"><?php echo $qtr; ?></td>
        <td class="num"><?php echo $qtrp; ?></td>
        <td class="num"><?php echo round($qtrl); ?></td>
        <td class="num">0</td>
      </tr>
    </tfoot>
  </table>
 
  <!-- ── Vehicle & Driver Info ─────────────────────────── -->
  <div class="section-title">Vehicle &amp; Driver Details</div>
  <div class="info-grid">
    <div class="cell">
      <div class="lbl">Vehicle No.</div>
      <div class="val mono"><?php echo htmlspecialchars($veh); ?></div>
    </div>
    <div class="cell">
      <div class="lbl">Vehicle Type</div>
      <div class="val"><?php echo htmlspecialchars($type); ?></div>
    </div>
    <div class="cell">
      <div class="lbl">CNIC #</div>
      <div class="val mono"><?php echo htmlspecialchars($cnc); ?></div>
    </div>
 
    <div class="cell">
      <div class="lbl">Driver</div>
      <div class="val"><?php echo htmlspecialchars($dr); ?></div>
    </div>
    <div class="cell">
      <div class="lbl">Transporter</div>
      <div class="val"><?php echo htmlspecialchars($tr); ?></div>
    </div>
    <div class="cell">
      <div class="lbl">Cell No.</div>
      <div class="val mono"><?php echo htmlspecialchars($mobile); ?></div>
    </div>
 
    <div class="cell">
      <div class="lbl">Seal No.</div>
      <div class="val mono"><?php echo htmlspecialchars($seal); ?></div>
    </div>
    <div class="cell">
      <div class="lbl">Vehicle Temperature</div>
      <div class="val"><?php echo htmlspecialchars($veh_temp); ?></div>
    </div>
    <div class="cell">
      <div class="lbl">Product Temperature</div>
      <div class="val"><?php echo htmlspecialchars($p_temp); ?></div>
    </div>
  </div>
 
  <!-- ── Date / Time ───────────────────────────────────── -->
  <div class="datetime-bar">
    <div class="dt-item">
      <div class="lbl">Date &amp; Time In</div>
      <div class="val"><?php echo date_format($date_in,  "d-m-Y &nbsp; H:i:s"); ?></div>
    </div>
    <div class="dt-item">
      <div class="lbl">Date &amp; Time Out</div>
      <div class="val"><?php echo date_format($date_out, "d-m-Y &nbsp; H:i:s"); ?></div>
    </div>
  </div>
 
  <!-- ── Signatures ────────────────────────────────────── -->
  <div class="sign-section">
    <div class="sign-row">
      <div class="sign-block">
        <div class="sign-label">Received By</div>
        <div class="sign-line"></div>
      </div>
      <div class="sign-block">
        <div class="sign-label">Signature</div>
        <div class="sign-line"></div>
      </div>
      <div class="sign-block">
        <div class="sign-label">Checked By</div>
        <div class="sign-line"></div>
      </div>
      <div class="sign-block">
        <div class="sign-label">Signature</div>
        <div class="sign-line"></div>
      </div>
    </div>
    <div class="sign-row two">
      <div class="sign-block">
        <div class="sign-label">Driver Signature / Thumb</div>
        <div class="sign-line"></div>
      </div>
      <div class="sign-block">
        <div class="sign-label">Inventory</div>
        <div class="sign-line"></div>
      </div>
    </div>
    <div class="sign-row two">
      <div class="sign-block">
        <div class="sign-label">(V.F) Manager Inventory</div>
        <div class="sign-line"></div>
      </div>
      <div class="sign-block">
        <div class="sign-label">Authorized By</div>
        <div class="sign-line"></div>
      </div>
    </div>
  </div>
 
</div><!-- /.page -->
 
<button class="btn-print" onclick="window.print()">&#128438; Print</button>
 
<p style="page-break-after: always;">&nbsp;</p>
<?php include('ack.php'); ?>
 
</body>
</html>
 