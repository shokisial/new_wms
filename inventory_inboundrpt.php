<?php
session_start();
if (empty($_SESSION['id']))     { header('Location:../index.php'); exit; }
if (empty($_SESSION['branch'])) { header('Location:../index.php'); exit; }

$branch = $_SESSION['branch'];
include('conn/dbcon.php');

$stdt = $_POST['stdt'] ?? '';
$endt = $_POST['endt'] ?? '';

// ── Branch ────────────────────────────────────────────────────────────────────
$branchRow   = mysqli_fetch_assoc(mysqli_query($con, "SELECT branch_name FROM branch WHERE branch_id='$branch'"));
$branch_name = $branchRow ? $branchRow['branch_name'] : $branch;

// ── Date labels ───────────────────────────────────────────────────────────────
$stdt_fmt = $stdt ? date('d M Y', strtotime(str_replace('/', '-', $stdt))) : '—';
$endt_fmt = $endt ? date('d M Y', strtotime(str_replace('/', '-', $endt))) : '—';

// ── Query ─────────────────────────────────────────────────────────────────────
$rows = [];
if ($stdt && $endt) {
  $q = mysqli_query($con,
    "SELECT stockin.*, product.prod_name, product.prod_desc AS p_code
     FROM stockin
     INNER JOIN product ON product.prod_desc = stockin.prod_id
     WHERE stockin.date >= '$stdt' AND stockin.date <= '$endt'
       AND stockin.qty > 0
       AND stockin.branch_id = '$branch'
     ORDER BY stockin.date ASC"
  ) or die(mysqli_error($con));
  while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
}

// ── Totals ────────────────────────────────────────────────────────────────────
$grand  = array_sum(array_column($rows, 'qty'));
$pkr    = array_sum(array_column($rows, 'pack'));
$grandl = array_sum(array_column($rows, 'loose'));
$total_rows = count($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sovereign WMS — Inbound Report Print</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --navy:#1a2238; --navy-mid:#1e2a42;
      --orange:#d95f2b; --orange-muted:#fdf1eb; --orange-border:#f6c9b0;
      --bg:#f6f5f3; --bg2:#eeede9; --white:#ffffff;
      --border:#e2e0db; --border2:#d0cec8;
      --text1:#1a1a18; --text2:#5c5b57; --text3:#9e9c96;
      --green:#1a6b3a; --green-bg:#eef7f2; --green-bd:#b6dfc8;
      --red:#b91c1c; --red-bg:#fef2f2; --red-bd:#fecaca;
      --amber:#92580a; --amber-bg:#fffbeb; --amber-bd:#fcd88a;
      --blue:#1e4fa0; --blue-bg:#eff4ff; --blue-bd:#bdd0f8;
    }

    html, body {
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      color: var(--text1);
      font-size: 12px;
    }

    /* ── Screen wrapper ── */
    .page-wrap {
      max-width: 1200px;
      margin: 0 auto;
      padding: 24px 28px 48px;
    }

    /* ── Screen toolbar ── */
    .screen-bar {
      background: var(--navy);
      border-bottom: 2px solid var(--orange);
      padding: 10px 28px;
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 0;
    }
    .screen-bar-brand { display: flex; align-items: center; gap: 10px; }
    .screen-bar .b1 { font-size: 13px; font-weight: 600; color: #fff; }
    .screen-bar .b2 { font-size: 9px; color: #8a9ab8; letter-spacing: .12em; text-transform: uppercase; }
    .screen-bar-actions { margin-left: auto; display: flex; gap: 8px; }
    .btn-print { display: inline-flex; align-items: center; gap: 6px; padding: 7px 16px; border-radius: 7px; font-size: 12px; font-weight: 500; cursor: pointer; border: none; font-family: 'Inter', sans-serif; background: var(--orange); color: #fff; transition: all .13s; }
    .btn-print:hover { background: var(--orange-lt, #f4722e); }
    .btn-print svg { width: 13px; height: 13px; }
    .btn-back { display: inline-flex; align-items: center; gap: 6px; padding: 7px 16px; border-radius: 7px; font-size: 12px; font-weight: 500; cursor: pointer; border: 1px solid #304060; font-family: 'Inter', sans-serif; background: transparent; color: #8a9ab8; transition: all .13s; text-decoration: none; }
    .btn-back:hover { background: #253350; color: #fff; }
    .btn-back svg { width: 13px; height: 13px; }

    /* ── Report container ── */
    .report {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: 12px;
      overflow: hidden;
      margin-top: 22px;
    }

    /* ── Report header ── */
    .report-hdr {
      background: var(--navy);
      padding: 22px 28px 20px;
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 20px;
      flex-wrap: wrap;
    }
    .report-hdr-logo { display: flex; align-items: center; gap: 12px; }
    .logo-mark { width: 34px; height: 34px; flex-shrink: 0; }
    .rh-brand .rh-b1 { font-size: 16px; font-weight: 700; color: #fff; letter-spacing: -.3px; }
    .rh-brand .rh-b2 { font-size: 9.5px; color: #8a9ab8; letter-spacing: .1em; text-transform: uppercase; margin-top: 2px; }
    .report-hdr-meta { text-align: right; }
    .rh-title { font-size: 13px; font-weight: 600; color: var(--orange); text-transform: uppercase; letter-spacing: .08em; }
    .rh-branch { font-size: 14px; font-weight: 600; color: #fff; margin-top: 4px; }
    .rh-range { font-size: 11px; color: #8a9ab8; margin-top: 3px; }
    .rh-generated { font-size: 10px; color: #5c6e8a; margin-top: 6px; }

    /* ── Summary pills ── */
    .summary-strip {
      background: #f8f7f5;
      border-bottom: 1px solid var(--border);
      padding: 14px 28px;
      display: flex;
      gap: 28px;
      flex-wrap: wrap;
    }
    .sum-item { display: flex; flex-direction: column; gap: 2px; }
    .sum-label { font-size: 9.5px; font-weight: 600; color: var(--text3); text-transform: uppercase; letter-spacing: .07em; }
    .sum-value { font-size: 18px; font-weight: 700; color: var(--text1); letter-spacing: -.4px; line-height: 1; }
    .sum-sep { width: 1px; background: var(--border); align-self: stretch; }

    /* ── Table ── */
    .tbl-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    thead tr { background: #fafaf8; }
    th {
      font-size: 9.5px;
      font-weight: 600;
      color: var(--text3);
      text-transform: uppercase;
      letter-spacing: .07em;
      padding: 9px 14px;
      border-bottom: 2px solid var(--border);
      text-align: left;
      white-space: nowrap;
    }
    td { padding: 0; border-bottom: 1px solid #f0ede8; vertical-align: middle; }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:nth-child(even) td { background: #fdfcfb; }
    .cell { padding: 9px 14px; font-size: 11.5px; color: var(--text1); }

    /* Article cell */
    .art-cell { padding: 9px 14px; }
    .art-name { font-size: 11.5px; font-weight: 500; color: var(--text1); }
    .art-code { font-family: 'JetBrains Mono', monospace; font-size: 10px; color: var(--text3); margin-top: 1px; }

    .mono { font-family: 'JetBrains Mono', monospace; font-size: 10.5px; font-weight: 500; }
    .qty-val { font-size: 13px; font-weight: 700; color: var(--text1); }

    /* Date */
    .date-cell { font-size: 11px; color: var(--text2); white-space: nowrap; }

    /* MFG / Expiry */
    .exp-cell { font-size: 11px; color: var(--text2); white-space: nowrap; }

    /* Shelf life diff badge */
    .diff-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      border-radius: 5px;
      padding: 2px 8px;
      font-size: 10.5px;
      font-weight: 600;
      white-space: nowrap;
    }
    .diff-ok   { background: var(--green-bg); border: 1px solid var(--green-bd); color: var(--green); }
    .diff-warn { background: var(--amber-bg); border: 1px solid var(--amber-bd); color: var(--amber); }
    .diff-crit { background: var(--red-bg);   border: 1px solid var(--red-bd);   color: var(--red); }

    /* GP tag */
    .gp-tag { font-family: 'JetBrains Mono', monospace; font-size: 10.5px; font-weight: 600; color: var(--blue); }

    /* Totals row */
    .totals-row td { background: var(--navy) !important; }
    .totals-row .cell { color: #fff; font-weight: 600; }
    .totals-row .qty-val { color: #fff; }

    /* ── Report footer ── */
    .report-footer {
      padding: 16px 28px;
      border-top: 1px solid var(--border);
      background: #fafaf8;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 10px;
    }
    .rf-note { font-size: 10.5px; color: var(--text3); }
    .rf-note strong { color: var(--text2); }

    /* Sig strip */
    .sig-strip {
      margin-top: 20px;
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 20px 28px;
      display: flex;
      gap: 60px;
      flex-wrap: wrap;
    }
    .sig-block { display: flex; flex-direction: column; gap: 28px; }
    .sig-label { font-size: 10.5px; font-weight: 600; color: var(--text2); text-transform: uppercase; letter-spacing: .06em; }
    .sig-line  { width: 200px; height: 1px; background: var(--border2); }

    /* Empty state */
    .empty-state { padding: 52px 20px; text-align: center; }
    .empty-icon { width: 52px; height: 52px; border-radius: 14px; background: var(--bg2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; }
    .empty-icon svg { width: 22px; height: 22px; color: var(--text3); }
    .empty-title { font-size: 14px; font-weight: 600; color: var(--text1); margin-bottom: 5px; }
    .empty-sub   { font-size: 12px; color: var(--text2); }

    /* ── Print styles ── */
    @media print {
      html, body { background: #fff; font-size: 10.5px; }
      .screen-bar, .sig-strip, .no-print { display: none !important; }
      .page-wrap { padding: 0; max-width: 100%; }
      .report { margin-top: 0; border: none; border-radius: 0; }
      .report-hdr { padding: 14px 18px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      .summary-strip { -webkit-print-color-adjust: exact; print-color-adjust: exact; padding: 10px 18px; }
      th { font-size: 8.5px; padding: 6px 10px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      .cell, .art-cell { padding: 6px 10px; font-size: 10px; }
      .art-name { font-size: 10px; }
      .art-code { font-size: 9px; }
      .mono { font-size: 9.5px; }
      .diff-badge { font-size: 9px; padding: 1px 6px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      .totals-row td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      tbody tr:nth-child(even) td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      .report-footer { padding: 10px 18px; }
      @page { margin: 10mm 8mm; size: A4 landscape; }
    }
  </style>
</head>
<body>

<!-- Screen toolbar (hidden on print) -->
<div class="screen-bar no-print">
  <div class="screen-bar-brand">
    <svg class="logo-mark" viewBox="0 0 30 36" fill="none">
      <rect x="5" y="1" width="16" height="16" rx="1.5" transform="rotate(45 5 1)" stroke="#d95f2b" stroke-width="2.4" fill="none"/>
      <rect x="9" y="13" width="16" height="16" rx="1.5" transform="rotate(45 9 13)" stroke="#ffffff" stroke-width="2.4" fill="none"/>
    </svg>
    <div>
      <div class="b1">Sovereign WMS</div>
      <div class="b2">Print Preview</div>
    </div>
  </div>
  <div class="screen-bar-actions">
    <a href="inbound_report.php" class="btn-back">
      <svg viewBox="0 0 13 13" fill="none"><path d="M8 2L3 6.5 8 11" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Back
    </a>
    <button onclick="window.print()" class="btn-print">
      <svg viewBox="0 0 13 13" fill="none"><rect x="2" y="4" width="9" height="6" rx="1" stroke="currentColor" stroke-width="1.2"/><path d="M4 4V2.5h5V4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/><path d="M4 10v-2h5v2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
      Print / Save PDF
    </button>
  </div>
</div>

<div class="page-wrap">
  <div class="report">

    <!-- Report Header -->
    <div class="report-hdr">
      <div class="report-hdr-logo">
        <svg class="logo-mark" viewBox="0 0 34 40" fill="none">
          <rect x="6" y="1" width="18" height="18" rx="1.5" transform="rotate(45 6 1)" stroke="#d95f2b" stroke-width="2.6" fill="none"/>
          <rect x="10" y="15" width="18" height="18" rx="1.5" transform="rotate(45 10 15)" stroke="#ffffff" stroke-width="2.6" fill="none"/>
        </svg>
        <div class="rh-brand">
          <div class="rh-b1">Sovereign</div>
          <div class="rh-b2">Warehousing &amp; Distribution</div>
        </div>
      </div>
      <div class="report-hdr-meta">
        <div class="rh-title">Inbound Report</div>
        <div class="rh-branch"><?php echo htmlspecialchars($branch_name); ?></div>
        <div class="rh-range">
          <?php echo $stdt_fmt; ?> &nbsp;→&nbsp; <?php echo $endt_fmt; ?>
        </div>
        <div class="rh-generated">Generated: <?php echo date('d M Y, H:i'); ?></div>
      </div>
    </div>

    <!-- Summary Strip -->
    <div class="summary-strip">
      <div class="sum-item">
        <div class="sum-label">Total Lines</div>
        <div class="sum-value"><?php echo number_format($total_rows); ?></div>
      </div>
      <div class="sum-sep"></div>
      <div class="sum-item">
        <div class="sum-label">Total Qty</div>
        <div class="sum-value"><?php echo number_format($grand); ?></div>
      </div>
      <div class="sum-sep"></div>
      <div class="sum-item">
        <div class="sum-label">Total Cartons</div>
        <div class="sum-value"><?php echo number_format($pkr); ?></div>
      </div>
      <div class="sum-sep"></div>
      <div class="sum-item">
        <div class="sum-label">Loose Units</div>
        <div class="sum-value"><?php echo number_format($grandl); ?></div>
      </div>
      <div class="sum-sep"></div>
      <div class="sum-item">
        <div class="sum-label">Date Range</div>
        <div class="sum-value" style="font-size:13px;letter-spacing:0"><?php echo $stdt_fmt; ?> → <?php echo $endt_fmt; ?></div>
      </div>
    </div>

    <!-- Table -->
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Document No.</th>
            <th>Gate Pass #</th>
            <th>Batch</th>
            <th>Article</th>
            <th>Qty</th>
            <th>Cartons</th>
            <th>Loose</th>
            <th>Inbound Date</th>
            <th>M.F.G</th>
            <th>Expiry</th>
            <th>Shelf Life (days)</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
          <tr><td colspan="12" style="border:none">
            <div class="empty-state">
              <div class="empty-icon">
                <svg viewBox="0 0 22 22" fill="none"><rect x="3" y="5" width="16" height="13" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M7 5V4a4 4 0 0 1 8 0v1" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
              </div>
              <div class="empty-title">No inbound records found</div>
              <div class="empty-sub">No data available for the selected date range.</div>
            </div>
          </td></tr>
          <?php else: ?>
          <?php $sno = 1; foreach ($rows as $row):
            // Shelf life calc
            $diff_days = null;
            $diff_class = 'diff-ok';
            try {
              if (!empty($row['mfg']) && !empty($row['expiry']) && $row['mfg'] !== '0000-00-00' && $row['expiry'] !== '0000-00-00') {
                $mfg_dt    = new DateTime($row['mfg']);
                $exp_dt    = new DateTime($row['expiry']);
                $diff_days = (int)$mfg_dt->diff($exp_dt)->format('%a');
                if ($diff_days < 90)       $diff_class = 'diff-crit';
                elseif ($diff_days < 180)  $diff_class = 'diff-warn';
              }
            } catch (Exception $e) {}
          ?>
          <tr>
            <!-- # -->
            <td><div class="cell" style="color:var(--text3)"><?php echo $sno; ?></div></td>

            <!-- Doc No -->
            <td><div class="cell mono"><?php echo htmlspecialchars(trim($row['rec_dnno'] . ' ' . $row['grn_no'])); ?></div></td>

            <!-- Gate Pass -->
            <td><div class="cell"><span class="gp-tag"><?php echo htmlspecialchars($row['gatepass_id'] ?: '—'); ?></span></div></td>

            <!-- Batch -->
            <td><div class="cell mono"><?php echo htmlspecialchars($row['batch'] ?: '—'); ?></div></td>

            <!-- Article -->
            <td>
              <div class="art-cell">
                <div class="art-name"><?php echo htmlspecialchars($row['prod_name']); ?></div>
                <div class="art-code"><?php echo htmlspecialchars($row['prod_id']); ?></div>
              </div>
            </td>

            <!-- Qty -->
            <td><div class="cell"><span class="qty-val"><?php echo number_format($row['qty']); ?></span></div></td>

            <!-- Cartons -->
            <td><div class="cell"><?php echo number_format($row['pack']); ?></div></td>

            <!-- Loose -->
            <td><div class="cell"><?php echo number_format($row['loose']); ?></div></td>

            <!-- Inbound Date -->
            <td><div class="cell date-cell"><?php echo $row['date'] ? date('d M Y', strtotime($row['date'])) : '—'; ?></div></td>

            <!-- MFG -->
            <td><div class="cell exp-cell"><?php echo $row['mfg'] && $row['mfg'] !== '0000-00-00' ? date('d M Y', strtotime($row['mfg'])) : '—'; ?></div></td>

            <!-- Expiry -->
            <td><div class="cell exp-cell"><?php echo $row['expiry'] && $row['expiry'] !== '0000-00-00' ? date('d M Y', strtotime($row['expiry'])) : '—'; ?></div></td>

            <!-- Shelf Life -->
            <td>
              <div class="cell">
                <?php if ($diff_days !== null): ?>
                <span class="diff-badge <?php echo $diff_class; ?>">
                  <?php echo number_format($diff_days); ?> d
                </span>
                <?php else: ?>—<?php endif; ?>
              </div>
            </td>
          </tr>
          <?php $sno++; endforeach; ?>

          <!-- Totals Row -->
          <tr class="totals-row">
            <td colspan="5"><div class="cell">Total</div></td>
            <td><div class="cell"><span class="qty-val"><?php echo number_format($grand); ?></span></div></td>
            <td><div class="cell"><span class="qty-val"><?php echo number_format($pkr); ?></span></div></td>
            <td><div class="cell"><span class="qty-val"><?php echo number_format($grandl); ?></span></div></td>
            <td colspan="4"></td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Report Footer -->
    <div class="report-footer">
      <div class="rf-note"><strong><?php echo $total_rows; ?> line<?php echo $total_rows != 1 ? 's' : ''; ?></strong> &nbsp;·&nbsp; <?php echo number_format($grand); ?> units &nbsp;·&nbsp; <?php echo number_format($pkr); ?> cartons &nbsp;·&nbsp; <?php echo number_format($grandl); ?> loose</div>
      <div class="rf-note">Printed: <?php echo date('d M Y, H:i'); ?> &nbsp;·&nbsp; <?php echo htmlspecialchars($branch_name); ?></div>
    </div>

  </div><!-- /.report -->

  <!-- Signature strip (screen only, also prints) -->
  <?php if (!empty($rows)): ?>
  <div class="sig-strip">
    <div class="sig-block">
      <div class="sig-label">Prepared by</div>
      <div class="sig-line"></div>
    </div>
    <div class="sig-block">
      <div class="sig-label">Verified by</div>
      <div class="sig-line"></div>
    </div>
    <div class="sig-block">
      <div class="sig-label">Approved by</div>
      <div class="sig-line"></div>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /.page-wrap -->

<script>
  // Auto-print when opened as a new tab from the report
  window.addEventListener('load', function() {
    // Only auto-print if opened via form POST (not a direct refresh)
    if (document.referrer && document.referrer.includes('inbound_report')) {
      window.print();
    }
  });
</script>
</body>
</html>