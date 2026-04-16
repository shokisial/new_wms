<?php
session_start();
if (empty($_SESSION['id']))     { header('Location:../index.php'); exit; }
if (empty($_SESSION['branch'])) { header('Location:../index.php'); exit; }

$branch = $_SESSION['branch'];
$id     = $_SESSION['id'];
$name   = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';

$ssd = isset($_POST['optionlist']) ? $_POST['optionlist'] : '';
$user_group = $_SESSION['user_group']; 

include('conn/dbcon.php');
?>
<?php include('side_check.php');  ?>

  <!-- ── Main content ── -->
  <div class="main">

    <!-- Breadcrumb -->
    <div class="crumb">
      <a href="#">Inbound</a>
      <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Advance Shipping Note
    </div>

    <!-- Page header -->
    <div class="ph">
      <div class="ph-left">
        <div class="title">Advance Shipping Note (A.S.N)</div>
        <div class="sub">Create inbound shipment records before stock arrives at the warehouse</div>
      </div>
      <div class="ph-right">
        <form action="index5.php" target="_blank" method="POST">
          <button type="submit" name="load_excel_data" class="btn btn-ghost">
            <svg viewBox="0 0 13 13" fill="none"><path d="M6.5 1v8M3 5.5l3.5 3.5 3.5-3.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M1 10v1.5A1.5 1.5 0 0 0 2.5 13h8a1.5 1.5 0 0 0 1.5-1.5V10" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
            ASN Format
          </button>
        </form>
        <form action="inward_add.php" method="POST">
          <input type="hidden" name="rec_dnno">
          <button type="submit" name="cash" class="btn btn-navy">
            <svg viewBox="0 0 13 13" fill="none"><path d="M1.5 9.5v1A1.5 1.5 0 0 0 3 12h7a1.5 1.5 0 0 0 1.5-1.5v-1M9 4.5L6.5 7 4 4.5M6.5 7V1" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Save A.S.N
          </button>
        </form>
      </div>
    </div>

    <!-- Import strip -->
    <div class="import-strip">
      <div class="import-icon">
        <svg viewBox="0 0 15 15" fill="none"><path d="M7.5 1v9M4 6l3.5 4L11 6" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M1 11v1.5A1.5 1.5 0 0 0 2.5 14h10a1.5 1.5 0 0 0 1.5-1.5V11" stroke="#fff" stroke-width="1.3" stroke-linecap="round"/></svg>
      </div>
      <div class="import-text">
        <strong>Bulk Import via Excel</strong>
        <span>Upload your ASN spreadsheet to add multiple records at once</span>
      </div>
      <div class="import-actions">
        <form action="excel/lp_upload.php" method="POST" enctype="multipart/form-data" style="display:flex;align-items:center;gap:8px">
          <label class="btn btn-ghost" style="cursor:pointer;margin:0;font-size:11px;padding:6px 12px">
            <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M6.5 9V1M3 4.5l3.5-3.5L10 4.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M1 10v1.5A1.5 1.5 0 0 0 2.5 13h8a1.5 1.5 0 0 0 1.5-1.5V10" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
            Choose File
            <input type="file" name="import_file" required style="display:none">
          </label>
          <button type="submit" name="save_lp_data" class="btn btn-orange" style="font-size:11px;padding:6px 12px">Import</button>
        </form>
      </div>
    </div>

    <!-- Entry card -->
    <div class="card">
      <div class="card-hdr">
        <div class="card-hdr-title">New entry</div>
        <div class="card-hdr-note">Select customer first, then fill product details</div>
      </div>
      <div class="card-body">

        <!-- Customer selector -->
        <div class="cust-row">
          <span class="field-label">Customer</span>
          <form action="" method="POST" style="flex:1;max-width:300px">
            <div class="sel-wrap">
              <select name="optionlist" onchange="this.form.submit()">
                  <option value="">Select Customer</option>
                <?php
                $q9 = mysqli_query($con, "SELECT * FROM supplier WHERE branch_id='$branch' ORDER BY supplier_name") or die(mysqli_error($con));
                while ($r9 = mysqli_fetch_array($q9)) {
                  $sel = ($ssd == $r9['supplier_id']) ? 'selected' : '';
                  echo '<option value="' . $r9['supplier_id'] . '" ' . $sel . '>' . htmlspecialchars($r9['supplier_name']) . '</option>';
                }
                ?>
              </select>
            </div>
          </form>
        </div>

        <!-- Entry form -->
        <form method="post" action="inward_transactionadd.php">
          <div class="form-grid">
            <div class="ff">
              <label>S.K.U / Product</label>
              <select name="prod_name" required>
                  <?php
                if ($ssd) {
                  $q2 = mysqli_query($con, "SELECT * FROM product WHERE supplier_id='$ssd' ORDER BY prod_name") or die(mysqli_error($con));
                  while ($r2 = mysqli_fetch_array($q2)) { ?>
                    <option value="<?php echo $r2['prod_desc']; ?>" ><?php echo $r2['prod_name']; ?> </option>;
                <?php  }
                }
                ?>
                  
              </select>
             
            </div>
            <input type="hidden" name="sup" value="<?php echo $ssd; ?>">
            <div class="ff"> 
              <label>Batch No.</label>
              <input type="text" name="batch" placeholder="e.g. B240501" required>  
            </div>
            <div class="ff">
              <label>ASN No.</label>
              <input type="text" name="asn" placeholder="ABC123">
            </div>
            <div class="ff">
              <label>Quantity</label>
              <input type="number" name="qty" placeholder="0" min="0" required>
            </div>
            <div class="ff">
              <label style="opacity:0">–</label>
              <button type="submit" class="add-btn">+ Add</button>
            </div>
          </div>
          <div id="result"></div>
        </form>

      </div>
    </div>

    <!-- Records table -->
    <div class="card">
      <div class="card-hdr">
        <div class="card-hdr-title">Pending ASN records</div>
        <div style="display:flex;gap:8px">
          <form action="inbound_report.php" method="POST" target="_blank">
            <input type="hidden" name="rec_dnno">
            <button type="submit" name="cash" class="btn btn-ghost" style="font-size:11px;padding:5px 12px">
              <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><rect x="1" y="1" width="10" height="10" rx="2" stroke="currentColor" stroke-width="1.1"/><path d="M3.5 4.5h5M3.5 6.5h5M3.5 8.5h3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
              Detail
            </button>
          </form>
          <form action="asnview.php" method="POST" target="_blank">
            <button type="submit" name="cash" class="btn btn-ghost" style="font-size:11px;padding:5px 12px">
              <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M8.5 1.5l2 2-6 6H2.5v-2l6-6z" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
              ASN Edit
            </button>
          </form>
        </div>
      </div>

      <div class="tbl-wrap">
        <table>
          <thead>
            <tr>
              <th>ASN #</th>
              <th>Product ID</th>
              <th>Batch</th>
              <th>Qty</th>
              <th>Serial / Print</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $query = mysqli_query($con, "SELECT * FROM temp_trans WHERE branch_id='$branch'") or die(mysqli_error($con));
            $count = mysqli_num_rows($query);
            if ($count == 0) {
              echo '<tr><td colspan="6" class="empty-row">No pending records. Add an entry above to get started.</td></tr>';
            }
            while ($row = mysqli_fetch_array($query)) {
            ?>
            <tr>
              <td class="mono"><?php echo htmlspecialchars($row['po_no']); ?></td>
              <td><?php echo htmlspecialchars($row['prod_id']); ?></td>
              <td class="mono"><?php echo htmlspecialchars($row['batch']); ?></td>
              <td style="font-weight:600"><?php echo htmlspecialchars($row['qty']); ?></td>
              <td class="mono" style="color:var(--text3)"><?php echo htmlspecialchars($row['serial_no']); ?></td>
              <td>
                <form action="temptrns_del.php" method="POST">
                  <input type="hidden" name="gid" value="<?php echo $row['temp_trans_id']; ?>">
                  <button type="submit" name="sub" class="del-btn">Delete</button>
                </form>
              </td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>

      <?php if ($count > 0) { ?>
      <div class="card-footer">
        <div class="footer-note">
          <strong><?php echo $count; ?> record<?php echo $count != 1 ? 's' : ''; ?></strong> pending
        </div>
        <form action="inward_add.php" method="POST">
          <input type="hidden" name="rec_dnno">
          <button type="submit" name="cash" class="btn btn-orange">
            <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M1.5 9.5v1A1.5 1.5 0 0 0 3 12h7a1.5 1.5 0 0 0 1.5-1.5v-1M9 4.5L6.5 7 4 4.5M6.5 7V1" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Save All A.S.N
          </button>
        </form>
      </div>
      <?php } ?>

    </div>

  </div><!-- /.main -->
</div><!-- /.layout -->

<script>
document.querySelectorAll('.nav-grp-hdr').forEach(function(hdr) {
  hdr.addEventListener('click', function() {
    hdr.parentElement.classList.toggle('open');
  });
});
</script>

</body>
</html>
