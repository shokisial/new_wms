<?php
/**
 * Sovereign Warehouse – Stock Vehicle Location Report
 * Fixed: SQL injection, broken HTML structure, undefined variables,
 *        nested tags, export logic placement, input validation.
 */
session_start();

// ── Auth guard (uncomment in production) ─────────────────────────────────────
// if (empty($_SESSION['id']) || $_SESSION['id'] != '11') {
//     header('Location: ../index.php'); exit;
// }
// if (empty($_SESSION['branch'])) {
//     header('Location: ../index.php'); exit;
// }

$branch = $_SESSION['branch'] ?? 'DEFAULT';
$id     = $_SESSION['id']     ?? 0;

date_default_timezone_set('Asia/Karachi');

// ── Database connection ───────────────────────────────────────────────────────
include 'conn/dbcon.php';   // provides $con (mysqli)
include 'DBController.php'; // provides DBController class

// ── Sanitise / parse POST inputs ─────────────────────────────────────────────
$start  = isset($_POST['start'])      ? trim($_POST['start'])      : '';
$end    = isset($_POST['end'])        ? trim($_POST['end'])        : '';
$orno   = isset($_POST['optionlist']) ? trim($_POST['optionlist']) : '';
$choice = isset($_POST['choice']);
$export = isset($_POST['export']);

// Validate and normalise dates
$startDate = $start ? date('Y/m/d', strtotime($start)) : null;
$endDate   = $end   ? date('Y/m/d', strtotime($end))   : null;

// ── Export handler (runs before any HTML output) ──────────────────────────────
if ($export && $orno !== '') {
    $ornoSafe = mysqli_real_escape_string($con, $orno);
    $branchSafe = mysqli_real_escape_string($con, $branch);

    $db_handle    = new DBController();
    $exportQuery  = mysqli_query($con,
        "SELECT si.rec_dnno, p.prod_desc, p.prod_name, si.batch, si.expiry,
                lc.mfg_dat, lc.loc_qty, lc.out_blc, lc.stock_location
         FROM stockin si
         INNER JOIN product p ON p.prod_desc = si.prod_id
         INNER JOIN location_control lc ON lc.st_id = si.stockin_id
         WHERE si.rec_dnno = '$ornoSafe'
           AND si.branch_id = '$branchSafe'"
    ) or die(mysqli_error($con));

    $filename = "Warehouse_Location_Report_{$orno}.xls";
    header('Content-Type: application/vnd.ms-excel');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header('Cache-Control: max-age=0');

    $headers = ['Code','SKU','Batch','MFG Date','Expiry','Qty','Balance QTY','Location'];
    echo implode("\t", $headers) . "\n";

    while ($row = mysqli_fetch_assoc($exportQuery)) {
        echo implode("\t", [
            $row['prod_desc'],
            $row['prod_name'],
            $row['batch'],
            $row['mfg_dat'],
            $row['expiry'],
            $row['loc_qty'],
            $row['out_blc'],
            $row['stock_location'],
        ]) . "\n";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Warehouse Location Report | <?php include '../dist/includes/title.php'; ?></title>

    <!-- Bootstrap & AdminLTE -->
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../plugins/datatables/dataTables.bootstrap.css">
    <link rel="stylesheet" href="../dist/css/AdminLTE.min.css">
    <link rel="stylesheet" href="../dist/css/skins/_all-skins.min.css">

    <style>
        /* ── Base ─────────────────────────────────────────────────── */
        body {
            font-family: Arial, sans-serif;
            font-size: 0.92em;
            color: #212121;
            background: #f5f5f5;
        }

        /* ── Filter card ──────────────────────────────────────────── */
        .filter-card {
            background: #fff;
            border-radius: 6px;
            padding: 18px 22px;
            margin-bottom: 18px;
            box-shadow: 0 1px 4px rgba(0,0,0,.1);
        }
        .filter-card label {
            font-weight: 600;
            margin-bottom: 4px;
            display: block;
        }
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
        }
        .filter-group { display: flex; flex-direction: column; }

        /* ── Report header ────────────────────────────────────────── */
        .report-header {
            background: #2c3e50;
            color: #fff;
            padding: 14px 20px;
            border-radius: 6px 6px 0 0;
            margin-bottom: 0;
        }
        .report-header h4 { margin: 0; font-size: 1.05em; }
        .report-header small { opacity: .75; }

        /* ── Table ────────────────────────────────────────────────── */
        .report-table-wrap {
            background: #fff;
            border-radius: 0 0 6px 6px;
            box-shadow: 0 1px 4px rgba(0,0,0,.1);
            overflow-x: auto;
        }
        table#reportTab {
            border-collapse: collapse;
            width: 100%;
            min-width: 750px;
        }
        table#reportTab th {
            background: #e8ecef;
            border-bottom: 2px solid #bbb;
            padding: 9px 13px;
            text-align: left;
            white-space: nowrap;
        }
        table#reportTab td {
            border-bottom: 1px solid #e0e0e0;
            padding: 7px 13px;
            vertical-align: middle;
        }
        table#reportTab tbody tr:hover { background: #f9f9f9; }
        table#reportTab tfoot tr td {
            font-weight: 700;
            background: #f0f4f8;
            border-top: 2px solid #bbb;
        }

        /* ── Action bar ───────────────────────────────────────────── */
        .action-bar {
            display: flex;
            gap: 10px;
            padding: 12px 16px;
            background: #fff;
            border-top: 1px solid #e0e0e0;
            border-radius: 0 0 6px 6px;
        }

        /* ── Badges ───────────────────────────────────────────────── */
        .badge-dn {
            display: inline-block;
            background: #2980b9;
            color: #fff;
            border-radius: 4px;
            padding: 2px 8px;
            font-size: .85em;
            font-weight: 700;
        }

        /* ── Print ────────────────────────────────────────────────── */
        @media print {
            .no-print, .filter-card, .action-bar { display: none !important; }
            body { background: #fff; }
            .report-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }

        /* ── Empty state ──────────────────────────────────────────── */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #888;
        }
        .empty-state .icon { font-size: 3em; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="container-fluid" style="max-width:1100px; margin:30px auto; padding:0 16px;">

    <!-- ══ STEP 1 – Date range filter ════════════════════════════════════════ -->
    <div class="filter-card no-print">
        <h5 style="margin:0 0 14px;font-weight:700;">
            📦 Sovereign Warehouse – Stock Vehicle Location Report
        </h5>
        <form method="POST" action="">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="start">From Date</label>
                    <input type="date" id="start" name="start" class="form-control"
                           value="<?php echo htmlspecialchars($start); ?>" required>
                </div>
                <div class="filter-group">
                    <label for="end">To Date</label>
                    <input type="date" id="end" name="end" class="form-control"
                           value="<?php echo htmlspecialchars($end); ?>" required>
                </div>
                <div class="filter-group" style="justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary">
                        🔍 Load Vehicles
                    </button>
                </div>
            </div>
        </form>
    </div>

    <?php if ($startDate && $endDate): ?>

    <!-- ══ STEP 2 – Vehicle (DN) selector ════════════════════════════════════ -->
    <div class="filter-card no-print">
        <form method="POST" action="">
            <!-- Carry dates forward -->
            <input type="hidden" name="start" value="<?php echo htmlspecialchars($start); ?>">
            <input type="hidden" name="end"   value="<?php echo htmlspecialchars($end); ?>">

            <div class="filter-row">
                <div class="filter-group" style="min-width:240px;">
                    <label for="optionlist">Select Vehicle / DN No.</label>
                    <?php
                    $startSafe  = mysqli_real_escape_string($con, $startDate);
                    $endSafe    = mysqli_real_escape_string($con, $endDate);
                    $branchSafe = mysqli_real_escape_string($con, $branch);

                    $vehicleQuery = mysqli_query($con,
                        "SELECT rec_dnno, COUNT(*) AS line_count
                         FROM stockin
                         WHERE `date` >= '$startSafe'
                           AND `date` <= '$endSafe'
                           AND branch_id = '$branchSafe'
                         GROUP BY rec_dnno
                         ORDER BY rec_dnno"
                    ) or die(mysqli_error($con));
                    ?>
                    <select id="optionlist" name="optionlist" class="form-control" required>
                        <option value="">-- Select Vehicle --</option>
                        <?php while ($vrow = mysqli_fetch_assoc($vehicleQuery)): ?>
                            <option value="<?php echo htmlspecialchars($vrow['rec_dnno']); ?>"
                                <?php echo ($orno === $vrow['rec_dnno']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($vrow['rec_dnno']); ?>
                                (<?php echo (int)$vrow['line_count']; ?> lines)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-group" style="justify-content:flex-end;">
                    <button type="submit" name="choice" class="btn btn-success">
                        📋 View Report
                    </button>
                </div>
            </div>
        </form>
    </div>

    <?php endif; // end date filter ?>

    <!-- ══ STEP 3 – Report output ════════════════════════════════════════════ -->
    <?php if ($choice && $orno !== ''): ?>

    <?php
    $ornoSafe   = mysqli_real_escape_string($con, $orno);
    $branchSafe = mysqli_real_escape_string($con, $branch);

    // Fetch all stock-in rows for this DN + branch
    $mainQuery = mysqli_query($con,
        "SELECT si.*, p.prod_desc, p.prod_name
         FROM stockin si
         INNER JOIN product p ON p.prod_desc = si.prod_id
         WHERE si.rec_dnno = '$ornoSafe'
           AND si.branch_id = '$branchSafe'
         ORDER BY si.stockin_id"
    ) or die(mysqli_error($con));

    $db_handle   = new DBController();
    $grandQty    = 0;
    $grandBalance= 0;
    $allRows     = []; // collect for table output

    while ($row1 = mysqli_fetch_assoc($mainQuery)) {
        $stno   = $row1['stockin_id'];
        $pid    = htmlspecialchars($row1['prod_desc']);
        $pname  = htmlspecialchars($row1['prod_name']);
        $batch  = htmlspecialchars($row1['batch']);
        $expiry = htmlspecialchars($row1['expiry']);

        $locationRows = $db_handle->runQuery(
            "SELECT * FROM location_control WHERE st_id = " . (int)$stno
        );

        if (!empty($locationRows)) {
            foreach ($locationRows as $loc) {
                $qty = (float)$loc['loc_qty'];
                $blc = (float)$loc['out_blc'];
                $grandQty     += $qty;
                $grandBalance += $blc;

                $allRows[] = [
                    'pid'      => $pid,
                    'pname'    => $pname,
                    'batch'    => $batch,
                    'mfg'      => htmlspecialchars($loc['mfg_dat'] ?? ''),
                    'expiry'   => $expiry,
                    'qty'      => $qty,
                    'balance'  => $blc,
                    'location' => htmlspecialchars($loc['stock_location'] ?? ''),
                ];
            }
        }
    }
    ?>

    <!-- Report header bar -->
    <div class="report-header">
        <h4>
            Sovereign Warehouse – Location Report &nbsp;
            <span class="badge-dn"><?php echo htmlspecialchars($orno); ?></span>
        </h4>
        <small>
            Generated: <?php echo date('d M Y, H:i'); ?> &nbsp;|&nbsp;
            Date range: <?php echo htmlspecialchars($start); ?> – <?php echo htmlspecialchars($end); ?> &nbsp;|&nbsp;
            Total lines: <?php echo count($allRows); ?>
        </small>
    </div>

    <div class="report-table-wrap">
        <?php if (empty($allRows)): ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <p>No location records found for DN <strong><?php echo htmlspecialchars($orno); ?></strong>.</p>
            </div>
        <?php else: ?>
        <table id="reportTab">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Code</th>
                    <th>SKU / Description</th>
                    <th>Batch</th>
                    <th>MFG Date</th>
                    <th>Expiry</th>
                    <th>Quantity</th>
                    <th>Balance QTY</th>
                    <th>Location</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allRows as $i => $r): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td><?php echo $r['pid']; ?></td>
                    <td><?php echo $r['pname']; ?></td>
                    <td><?php echo $r['batch']; ?></td>
                    <td><?php echo $r['mfg']; ?></td>
                    <td><?php echo $r['expiry']; ?></td>
                    <td><?php echo number_format($r['qty']); ?></td>
                    <td><?php echo number_format($r['balance']); ?></td>
                    <td><?php echo $r['location']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" style="text-align:right;">Total Packages:</td>
                    <td><?php echo number_format($grandQty); ?></td>
                    <td><?php echo number_format($grandBalance); ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>

    <!-- Action bar -->
    <div class="action-bar no-print">
        <button onclick="window.print()" class="btn btn-default">
            🖨️ Print
        </button>

        <!-- Export form – carries all required context -->
        <form method="POST" action="" style="display:inline;">
            <input type="hidden" name="start"      value="<?php echo htmlspecialchars($start); ?>">
            <input type="hidden" name="end"        value="<?php echo htmlspecialchars($end); ?>">
            <input type="hidden" name="optionlist" value="<?php echo htmlspecialchars($orno); ?>">
            <input type="hidden" name="choice"     value="1">
            <button type="submit" name="export" id="btnExport"
                    class="btn btn-success" <?php echo empty($allRows) ? 'disabled' : ''; ?>>
                📥 Export to Excel
            </button>
        </form>
    </div>

    <?php endif; // end report output ?>

</div><!-- /container -->

<!-- Bootstrap JS (optional, for any Bootstrap components) -->
<script src="../plugins/jQuery/jquery-2.2.3.min.js"></script>
<script src="../bootstrap/js/bootstrap.min.js"></script>

</body>
</html>
