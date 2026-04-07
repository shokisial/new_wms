<?php session_start();
if(empty($_SESSION['id'])):
  header('Location:../index.php');
endif;
if(empty($_SESSION['branch'])):
  header('Location:../index.php');
endif;
$branch = $_SESSION['branch'];
$id = $_SESSION['id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sovereign | Outward Gate Pass</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;600&display=swap" rel="stylesheet">
  <style>
    /* ── Reset & Base ─────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --ink:        #0f1923;
      --ink-mid:    #3a4a5c;
      --ink-light:  #6b7c93;
      --rule:       #c8d4e0;
      --rule-heavy: #8fa3b8;
      --accent:     #1a3a5c;
      --accent-pale:#e8f0f8;
      --paper:      #ffffff;
      --font-main:  'IBM Plex Sans', sans-serif;
      --font-mono:  'IBM Plex Mono', monospace;
    }

    html, body {
      background: #dce4ec;
      font-family: var(--font-main);
      color: var(--ink);
      font-size: 11px;
      line-height: 1.5;
    }

    /* ── Page shell ───────────────────────────────── */
    .page {
      width: 210mm;
      min-height: 297mm;
      margin: 16px auto;
      background: var(--paper);
      padding: 10mm 12mm 12mm;
      box-shadow: 0 4px 32px rgba(0,0,0,.18);
    }

    /* ── Header ───────────────────────────────────── */
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
      width: 72px;
      height: 72px;
      object-fit: contain;
    }
    .doc-header .company-info h1 {
      font-size: 13px;
      font-weight: 700;
      color: var(--accent);
      letter-spacing: .02em;
    }
    .doc-header .company-info p {
      font-size: 9.5px;
      color: var(--ink-mid);
      margin-top: 2px;
    }
    .doc-header .doc-id {
      text-align: right;
    }
    .doc-header .doc-id .label {
      font-size: 9px;
      text-transform: uppercase;
      letter-spacing: .08em;
      color: var(--ink-light);
    }
    .doc-header .doc-id .value {
      font-family: var(--font-mono);
      font-size: 16px;
      font-weight: 600;
      color: var(--accent);
    }

    /* ── Section title ────────────────────────────── */
    .section-title {
      display: inline-block;
      font-size: 8.5px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .1em;
      color: var(--paper);
      background: var(--accent);
      padding: 2px 7px;
      margin-bottom: 6px;
    }

    /* ── Info grid ────────────────────────────────── */
    .info-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 0;
      border: 1px solid var(--rule-heavy);
      margin-bottom: 10px;
    }
    .info-grid .cell {
      padding: 5px 8px;
      border-right: 1px solid var(--rule);
      border-bottom: 1px solid var(--rule);
    }
    .info-grid .cell:nth-child(3n) { border-right: none; }
    .info-grid .cell:nth-last-child(-n+3) { border-bottom: none; }
    .info-grid .cell.span2 {
      grid-column: span 2;
    }
    .info-grid .cell.span3 {
      grid-column: span 3;
      border-right: none;
    }
    .info-grid .cell .lbl {
      font-size: 8px;
      text-transform: uppercase;
      letter-spacing: .07em;
      color: var(--ink-light);
      margin-bottom: 2px;
    }
    .info-grid .cell .val {
      font-weight: 600;
      color: var(--ink);
      font-size: 10.5px;
      border-bottom: 1px dashed var(--rule-heavy);
      min-height: 14px;
      padding-bottom: 1px;
    }
    .info-grid .cell .val.mono {
      font-family: var(--font-mono);
    }

    /* ── Delivery block ───────────────────────────── */
    .delivery-block {
      margin-bottom: 10px;
      border: 1px solid var(--rule-heavy);
    }
    .delivery-header {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      background: var(--accent-pale);
      border-bottom: 1px solid var(--rule-heavy);
      padding: 5px 8px;
      gap: 8px;
    }
    .delivery-header .dh-item .lbl {
      font-size: 8px;
      text-transform: uppercase;
      letter-spacing: .07em;
      color: var(--ink-light);
    }
    .delivery-header .dh-item .val {
      font-weight: 700;
      font-size: 11px;
      color: var(--accent);
    }

    /* ── Items table ──────────────────────────────── */
    .items-table {
      width: 100%;
      border-collapse: collapse;
    }
    .items-table thead tr {
      background: var(--accent);
      color: var(--paper);
    }
    .items-table thead th {
      padding: 4px 7px;
      font-size: 8.5px;
      text-transform: uppercase;
      letter-spacing: .07em;
      font-weight: 600;
      text-align: left;
    }
    .items-table thead th:last-child,
    .items-table tbody td:last-child,
    .items-table tbody td:nth-child(3),
    .items-table tbody td:nth-child(4),
    .items-table thead th:nth-child(3),
    .items-table thead th:nth-child(4) {
      text-align: center;
    }
    .items-table tbody tr {
      border-bottom: 1px solid var(--rule);
    }
    .items-table tbody tr:nth-child(even) {
      background: #f4f7fa;
    }
    .items-table tbody td {
      padding: 4px 7px;
      font-size: 10px;
      vertical-align: middle;
    }
    .items-table tbody td .product-code {
      font-family: var(--font-mono);
      font-size: 9px;
      color: var(--ink-mid);
    }
    .items-table tbody td .product-name {
      font-weight: 600;
    }
    .items-table tbody td .uom-badge {
      display: inline-block;
      font-size: 7.5px;
      background: var(--accent-pale);
      color: var(--accent);
      border: 1px solid var(--rule);
      padding: 0 4px;
      border-radius: 2px;
      margin-left: 3px;
      font-weight: 600;
    }
    .items-table .qty-cell {
      font-family: var(--font-mono);
      font-weight: 600;
      font-size: 10.5px;
    }
    .items-table .qty-sub {
      font-size: 8.5px;
      color: var(--ink-light);
      font-weight: 400;
    }
    .items-table .actual-out {
      font-family: var(--font-mono);
      font-weight: 700;
      color: var(--accent);
    }

    /* ── Footer row ───────────────────────────────── */
    .doc-footer {
      border-top: 2px solid var(--rule-heavy);
      margin-top: 12px;
      padding-top: 10px;
      display: grid;
      grid-template-columns: 1fr 1fr 1fr 1fr;
      gap: 12px;
    }
    .sign-block .sign-label {
      font-size: 8.5px;
      text-transform: uppercase;
      letter-spacing: .07em;
      color: var(--ink-light);
      margin-bottom: 18px;
    }
    .sign-block .sign-line {
      border-top: 1px solid var(--ink-mid);
      margin-top: 4px;
    }

    .datetime-block {
      border: 1px solid var(--rule);
      padding: 6px 8px;
      margin-top: 10px;
      display: flex;
      gap: 20px;
    }
    .datetime-block .dt-item .lbl {
      font-size: 8px;
      text-transform: uppercase;
      letter-spacing: .07em;
      color: var(--ink-light);
    }
    .datetime-block .dt-item .val {
      font-family: var(--font-mono);
      font-weight: 600;
      font-size: 11px;
    }

    /* ── Print ────────────────────────────────────── */
    @media print {
      @page { margin: 0; size: A4; }
      html, body { background: white; }
      .page {
        width: 100%;
        margin: 0;
        padding: 8mm 10mm;
        box-shadow: none;
      }
    }
  </style>
</head>
<body onload="window.print();">

<?php
include('conn/dbcon.php');
include('createbarcode.php');

$queryb = mysqli_query($con, "SELECT * FROM branch WHERE branch_id='$branch'") or die(mysqli_error());
$rowb = mysqli_fetch_array($queryb);
$branch_add = $rowb['branch_name'];

$rcdoc = $_POST['gdn_no'];

$query = mysqli_query($con, "SELECT * FROM gatepass_out WHERE gatepass_id='$rcdoc'") or die(mysqli_error($con));
while ($row = mysqli_fetch_array($query)) {
  $dndn    = $row['dn_no'];
  $veh     = $row['vehicle_no'];
  $cnc     = $row['cnic'];
  $remarks = $row['remarks'];
  $bilty   = $row['bilty'];
  $dr      = $row['driver'];
  $mobile  = $row['mobile'];
  $tr      = $row['trns_name'];
  $gtpass  = $row['gatepass_id'];
  $seal    = $row['seal_no'];
  $indt    = $row['indate'];
  $outdt   = $row['outdate'];
  $gps     = $row['out_seq'];
  $veh_temp  = $row['veh_temp'];
  $item_temp = $row['item_temp'];
}

$dat0  = date_create("$indt");
$date  = date_create("$indt");
$date1 = date_create("$outdt");
?>

<div class="page">

  <!-- ── Header ──────────────────────────────────────────── -->
  <div class="doc-header">
    <img src="taqlogo.png" alt="Logo">
    <div class="company-info">
      <h1>Sovereign Warehousing and Distribution &mdash; <?php echo htmlspecialchars($branch_add); ?></h1>
      <p>SWD/WH/DS/023/QF-03 &nbsp;&bull;&nbsp; Outward Gate Pass</p>
    </div>
    <div class="doc-id">
      <div class="label">Gate Pass #</div>
      <div class="value"><?php echo htmlspecialchars($rcdoc); ?></div>
    </div>
  </div>

  <!-- ── Transport Info ──────────────────────────────────── -->
  <div class="section-title">Transport Details</div>
  <div class="info-grid">

    <div class="cell span2">
      <div class="lbl">Transporter</div>
      <div class="val"><?php echo htmlspecialchars($tr); ?></div>
    </div>
    <div class="cell">
      <div class="lbl">Bilty Number</div>
      <div class="val mono"><?php echo htmlspecialchars($bilty); ?></div>
    </div>

    <div class="cell">
      <div class="lbl">Vehicle Number</div>
      <div class="val mono"><?php echo htmlspecialchars($veh); ?></div>
    </div>
    <div class="cell">
      <div class="lbl">Manual G.Pass No</div>
      <div class="val mono"><?php echo htmlspecialchars($gps); ?></div>
    </div>
    <div class="cell">
      <div class="lbl">Seal #</div>
      <div class="val mono"><?php echo htmlspecialchars($seal); ?></div>
    </div>

    <div class="cell">
      <div class="lbl">Driver</div>
      <div class="val"><?php echo htmlspecialchars($dr); ?></div>
    </div>
    <div class="cell">
      <div class="lbl">CNIC #</div>
      <div class="val mono"><?php echo htmlspecialchars($cnc); ?></div>
    </div>
    <div class="cell">
      <div class="lbl">Cell #</div>
      <div class="val mono"><?php echo htmlspecialchars($mobile); ?></div>
    </div>

    <div class="cell">
      <div class="lbl">Vehicle Temp</div>
      <div class="val"><?php echo htmlspecialchars($veh_temp); ?></div>
    </div>
    <div class="cell">
      <div class="lbl">Product Temp</div>
      <div class="val"><?php echo htmlspecialchars($item_temp); ?></div>
    </div>
    <div class="cell">
      <div class="lbl">Carton / Bag</div>
      <div class="val">&nbsp;</div>
    </div>

  </div>

  <!-- ── Date / Time Bar ─────────────────────────────────── -->
  <div class="datetime-block">
    <div class="dt-item">
      <div class="lbl">Date</div>
      <div class="val"><?php echo date_format($dat0, "d/m/Y"); ?></div>
    </div>
    <div class="dt-item">
      <div class="lbl">In Time</div>
      <div class="val"><?php echo date_format($date, "H:i"); ?></div>
    </div>
    <div class="dt-item">
      <div class="lbl">Out Time</div>
      <div class="val"><?php echo date_format($date1, "H:i"); ?></div>
    </div>
  </div>

  <br>

  <!-- ── Delivery Challan Items ──────────────────────────── -->
  <div class="section-title">Delivery Challans</div>

  <?php
  $query1 = mysqli_query($con,
    "SELECT * FROM stockout
     WHERE gatepass_id='$rcdoc'
       AND stockout_qty > 0
       AND branch_id='$branch'
     GROUP BY stockout_orderno
     ORDER BY city"
  ) or die(mysqli_error($con));

  while ($row1 = mysqli_fetch_array($query1)):
    $d_id    = $row1['dealer_code'];
    $orderno = $row1['stockout_orderno'];

    $query12 = mysqli_query($con,
      "SELECT *, SUM(stockout_qty) AS qtv, SUM(pack_deliver) AS pkdel
       FROM stockout
       WHERE gatepass_id='$rcdoc'
         AND stockout_orderno='$orderno'
         AND branch_id='$branch'
       ORDER BY stockout_orderno"
    ) or die(mysqli_error($con));
    $row12 = mysqli_fetch_array($query12);
  ?>

  <div class="delivery-block">
    <div class="delivery-header">
      <div class="dh-item">
        <div class="lbl">D.C No</div>
        <div class="val"><?php echo htmlspecialchars($row12['stockout_orderno']); ?></div>
      </div>
      <div class="dh-item">
        <div class="lbl">Distributor</div>
        <div class="val"><?php echo htmlspecialchars($row12['dealer_code']); ?></div>
      </div>
      <div class="dh-item">
        <div class="lbl">City</div>
        <div class="val"><?php echo htmlspecialchars($row12['city']); ?></div>
      </div>
    </div>

    <table class="items-table">
      <thead>
        <tr>
          <th>Item</th>
          <th>Batch No</th>
          <th>Qty &nbsp;/&nbsp; Pack &nbsp;/&nbsp; Loose</th>
          <th>Return</th>
          <th>Actual Out</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $query2 = mysqli_query($con,
          "SELECT * FROM stockout
           WHERE stockout_qty > 0
             AND gatepass_id='$rcdoc'
             AND stockout_orderno='$orderno'
             AND branch_id='$branch'"
        ) or die(mysqli_error($con));

        while ($row2 = mysqli_fetch_array($query2)):
          $prds = $row2['product_id'];
          $query3 = mysqli_query($con,
            "SELECT * FROM product WHERE prod_desc='$prds' AND branch_id='$branch'"
          ) or die(mysqli_error($con));
          $row3 = mysqli_fetch_array($query3);
          $actual_out = $row2['stockout_qty'] - $row2['return_qty'];
        ?>
        <tr>
          <td>
            <span class="product-code"><?php echo htmlspecialchars($row3['prod_desc']); ?></span>
            <span class="product-name"> <?php echo htmlspecialchars($row3['prod_name']); ?></span>
            <span class="uom-badge"><?php echo htmlspecialchars($row3['uom']); ?></span>
          </td>
          <td class="mono"><?php echo htmlspecialchars($row2['batch']); ?></td>
          <td class="qty-cell" style="text-align:center">
            <?php echo $row2['stockout_qty']; ?>
            <span class="qty-sub">&nbsp;/&nbsp;<?php echo $row2['pack_deliver']; ?>&nbsp;/&nbsp;<?php echo $row2['stockout_loosedn']; ?></span>
          </td>
          <td style="text-align:center"><?php echo $row2['return_qty']; ?></td>
          <td class="actual-out" style="text-align:center"><?php echo $actual_out; ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <?php endwhile; ?>

  <!-- ── Signatures ──────────────────────────────────────── -->
  <div class="doc-footer">
    <div class="sign-block">
      <div class="sign-label">Driver Signature</div>
      <div class="sign-line"></div>
    </div>
    <div class="sign-block">
      <div class="sign-label">Supervisor</div>
      <div class="sign-line"></div>
    </div>
    <div class="sign-block">
      <div class="sign-label">Authorized By</div>
      <div class="sign-line"></div>
    </div>
    <div class="sign-block">
      <div class="sign-label">Security Guard</div>
      <div class="sign-line"></div>
    </div>
  </div>

</div><!-- /.page -->

<script src="../../plugins/jQuery/jQuery-2.1.3.min.js"></script>
<script src="../../bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
