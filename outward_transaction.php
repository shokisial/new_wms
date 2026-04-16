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
<?php include('side_check.php'); ?>

  <!-- ── Main content ── -->
  <div class="main">

    <!-- Breadcrumb -->
    <div class="crumb">
      <a href="new_dash.php">Outbound</a>
      <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Transfer Note
    </div>

    <!-- Page header -->
    <div class="ph">
      <div class="ph-left">
        <div class="title">Customer Delivery Order</div>
        <div class="sub">Review pending outbound lines, then confirm &amp; save as a transfer note</div>
      </div>
      <div class="ph-right">
        <?php if ($act): ?>
        <div style="display:inline-flex;align-items:center;gap:6px;background:#eef7f2;border:1px solid #b6dfc8;border-radius:20px;padding:5px 13px;font-size:11px;font-weight:500;color:#1a6b3a;white-space:nowrap;">
          <span style="width:6px;height:6px;border-radius:50%;background:#1a6b3a;display:inline-block;"></span>
          Active Batch: <strong><?php echo htmlspecialchars($act); ?></strong>
        </div>
        <?php endif; ?>
        <form action="index7.php" target="_blank" method="POST">
          <button type="submit" name="load_excel_data" class="btn btn-ghost">
            <svg viewBox="0 0 13 13" fill="none"><rect x="2" y="1" width="9" height="11" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 4.5h4M4.5 6.5h4M4.5 8.5h2.5" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
            D.C Format
          </button>
        </form>
        <form method="post" action="outward_add.php">
          <button type="submit" name="cash" class="btn btn-navy">
            <svg viewBox="0 0 13 13" fill="none"><path d="M2 6.5l3 3 6-6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Save Transfer Note
          </button>
        </form>
      </div>
    </div>

    <!-- Stats row -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px">
      <div style="background:#fff;border:1px solid #e0ded8;border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px">
        <div style="width:38px;height:38px;border-radius:9px;background:#1a2238;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <svg width="17" height="17" viewBox="0 0 18 18" fill="none"><rect x="2" y="2" width="14" height="14" rx="2" stroke="#fff" stroke-width="1.3"/><path d="M6 9h6M9 6v6" stroke="#fff" stroke-width="1.3" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div style="font-size:10.5px;color:#9a9890;margin-bottom:2px">Pending Lines</div>
          <div style="font-size:20px;font-weight:700;color:#181816;letter-spacing:-.5px;line-height:1"><?php echo $total_rows; ?></div>
          <div style="font-size:10px;color:#9a9890;margin-top:2px">Awaiting confirmation</div>
        </div>
      </div>
      <div style="background:#fff;border:1px solid #e0ded8;border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px">
        <div style="width:38px;height:38px;border-radius:9px;background:#fdf1eb;border:1px solid #f6c9b0;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <svg width="17" height="17" viewBox="0 0 18 18" fill="none"><path d="M15 9H3M9 3l6 6-6 6" stroke="#d95f2b" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div>
          <div style="font-size:10.5px;color:#9a9890;margin-bottom:2px">Total Units</div>
          <div style="font-size:20px;font-weight:700;color:#181816;letter-spacing:-.5px;line-height:1"><?php $tot=0; foreach($rows as $r) $tot+=$r['qty']; echo number_format($tot); ?></div>
          <div style="font-size:10px;color:#9a9890;margin-top:2px">Across all lines</div>
        </div>
      </div>
      <div style="background:#fff;border:1px solid #e0ded8;border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px">
        <div style="width:38px;height:38px;border-radius:9px;background:#eff4ff;border:1px solid #bdd0f8;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <svg width="17" height="17" viewBox="0 0 18 18" fill="none"><path d="M3 5h12M3 9h8M3 13h5" stroke="#1e4fa0" stroke-width="1.4" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div style="font-size:10.5px;color:#9a9890;margin-bottom:2px">Branch</div>
          <div style="font-size:15px;font-weight:700;color:#181816;letter-spacing:-.5px;line-height:1"><?php echo htmlspecialchars($branch); ?></div>
          <div style="font-size:10px;color:#9a9890;margin-top:2px">Active session</div>
        </div>
      </div>
    </div>

    <!-- Import strip (DC File Upload) -->
    <div class="import-strip">
      <div class="import-icon">
        <svg viewBox="0 0 15 15" fill="none"><path d="M8 11V3M5 6l3-3 3 3" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 12h12" stroke="#fff" stroke-width="1.3" stroke-linecap="round"/></svg>
      </div>
      <div class="import-text">
        <strong>Import D.C File</strong>
        <span>Upload an Excel file to bulk-import delivery order lines</span>
      </div>
      <div class="import-actions">
        <form action="excel/outbound_upload.php" method="POST" enctype="multipart/form-data" style="display:flex;align-items:center;gap:8px">
          <label class="btn btn-ghost" style="cursor:pointer;margin:0;font-size:11px;padding:6px 12px">
            <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M2 10.5h9M6.5 2v7M4 5l2.5-3L9 5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span id="fileLabel">Choose File</span>
            <input type="file" name="import_file" id="dcFile" required style="display:none" onchange="updateFileLabel(this)">
          </label>
          <button type="submit" name="save_out_data" class="btn btn-orange" style="font-size:11px;padding:6px 12px">Import</button>
        </form>
      </div>
    </div>

    <!-- Search toolbar -->
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;flex-wrap:wrap">
      <div style="position:relative;flex:1;max-width:280px">
        <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:13px;height:13px;color:#9a9890;pointer-events:none" viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        <input type="text" id="searchInput" placeholder="Search D.C no, product, distributor…" oninput="filterTable(this.value)"
          style="padding:8px 10px 8px 30px;border:1px solid #cccac3;border-radius:8px;font-size:12px;font-family:'Inter',sans-serif;color:#181816;background:#fff;outline:none;width:100%;transition:border .15s">
      </div>
      <div style="font-size:11px;color:#9a9890"><?php echo $total_rows; ?> line<?php echo $total_rows!=1?'s':''; ?> · <?php echo date('d M Y, H:i'); ?></div>
    </div>

    <!-- Records table -->
    <div class="card">
      <div class="card-hdr">
        <div class="card-hdr-title">Outbound Lines</div>
        <div class="card-hdr-note"><?php echo $total_rows; ?> pending · Branch <?php echo htmlspecialchars($branch); ?></div>
      </div>

      <?php if (empty($rows)): ?>
      <div style="padding:52px 20px;text-align:center">
        <div style="width:52px;height:52px;border-radius:14px;background:#ebe9e5;border:1px solid #e0ded8;display:flex;align-items:center;justify-content:center;margin:0 auto 14px">
          <svg width="22" height="22" viewBox="0 0 22 22" fill="none"><path d="M3 17l4-4 4 4 4-4 4 4" stroke="#9a9890" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 5h16" stroke="#9a9890" stroke-width="1.3" stroke-linecap="round"/><path d="M3 9h10" stroke="#9a9890" stroke-width="1.3" stroke-linecap="round"/></svg>
        </div>
        <div style="font-size:14px;font-weight:600;color:#181816;margin-bottom:5px">No pending outbound lines</div>
        <div style="font-size:12px;color:#58574f">Upload a D.C file or add lines to begin processing this delivery order.</div>
      </div>
      <?php else: ?>
      <div class="tbl-wrap">
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
              <th style="width:130px">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row): ?>
            <tr>
              <td class="mono"><?php echo htmlspecialchars($row['serial_no']); ?></td>
              <td class="mono"><?php echo htmlspecialchars($row['prod_id']); ?></td>
              <td style="font-weight:600;padding:11px 14px"><?php echo number_format($row['qty']); ?></td>
              <td style="color:#58574f;padding:11px 14px;font-size:12px"><?php echo htmlspecialchars($row['batch_out']); ?></td>
              <td style="padding:11px 14px;font-size:12px"><?php echo htmlspecialchars($row['dist']); ?></td>
              <td style="color:#58574f;padding:11px 14px;font-size:12px"><?php echo htmlspecialchars($row['city']); ?></td>
              <td style="color:#58574f;padding:11px 14px;font-size:12px"><?php echo htmlspecialchars($row['rem']); ?></td>
              <td style="padding:11px 14px">
                <div style="display:flex;align-items:center;gap:6px">
                  <button class="btn-edit" onclick="openEdit(<?php echo $row['temp_trans_id']; ?>)">
                    <svg viewBox="0 0 12 12" fill="none"><path d="M7.5 2.5l2 2L4 10H2V8l5.5-5.5z" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Edit
                  </button>
                  <button class="del-btn" onclick="openDelete(<?php echo $row['temp_trans_id']; ?>, '<?php echo addslashes(htmlspecialchars($row['prod_id'])); ?>')">
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

      <?php if ($total_rows > 0): ?>
      <div class="card-footer">
        <div class="footer-note">
          <strong><?php echo $total_rows; ?> line<?php echo $total_rows!=1?'s':''; ?></strong> pending confirmation
        </div>
        <div style="font-size:11px;color:#9a9890">Last refreshed: <?php echo date('d M Y, H:i'); ?></div>
      </div>
      <?php endif; ?>
      <?php endif; ?>

    </div>

  </div><!-- /.main -->
</div><!-- /.layout -->

<!-- ═══════════════════════════════════════
     Additional inline styles for outbound-specific elements
     (edit/delete buttons + modals, not covered by side_check.php)
     ═══════════════════════════════════════ -->
<style>
  .btn-edit{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:6px;background:#eff4ff;border:1px solid #bdd0f8;color:#1e4fa0;font-size:11.5px;font-weight:500;font-family:inherit;cursor:pointer;transition:background .12s;white-space:nowrap}
  .btn-edit:hover{background:#e4edff}
  .btn-edit svg{width:12px;height:12px}

  /* WMS Modal */
  .wms-overlay{position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:1000;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .18s}
  .wms-overlay.open{opacity:1;pointer-events:all}
  .wms-modal{background:#fff;border-radius:12px;border:1px solid #e0ded8;width:100%;max-width:480px;box-shadow:0 8px 40px rgba(0,0,0,.18);transform:translateY(8px);transition:transform .18s;overflow:hidden}
  .wms-overlay.open .wms-modal{transform:translateY(0)}
  .wms-modal-hdr{padding:16px 20px;border-bottom:1px solid #e0ded8;display:flex;align-items:center;justify-content:space-between;background:#fafaf7}
  .wms-modal-title{font-size:14px;font-weight:700;color:#181816}
  .wms-modal-close{width:26px;height:26px;border-radius:6px;background:transparent;border:1px solid #e0ded8;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#9a9890;transition:background .1s,color .1s}
  .wms-modal-close:hover{background:#ebe9e5;color:#181816}
  .wms-modal-close svg{width:12px;height:12px}
  .wms-modal-body{padding:20px}
  .wms-modal-foot{padding:14px 20px;border-top:1px solid #e0ded8;display:flex;justify-content:flex-end;gap:8px;background:#fafaf7}
  .mform-field{margin-bottom:16px}
  .mform-field:last-of-type{margin-bottom:0}
  .mform-label{display:block;font-size:10.5px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:#9a9890;margin-bottom:6px}
  .mform-input{width:100%;padding:9px 12px;border:1px solid #cccac3;border-radius:7px;font-size:12.5px;font-family:'JetBrains Mono',monospace;color:#181816;background:#fff;outline:none;transition:border .15s,box-shadow .15s}
  .mform-input:focus{border-color:#93aac8;box-shadow:0 0 0 3px rgba(30,79,160,.08)}

  /* Delete confirm */
  .del-confirm-body{padding:20px;text-align:center}
  .del-icon{width:46px;height:46px;border-radius:12px;background:#fef2f2;border:1px solid #fecaca;display:flex;align-items:center;justify-content:center;margin:0 auto 12px}
  .del-icon svg{width:20px;height:20px;color:#b91c1c}
  .del-msg{font-size:13px;color:#58574f;margin-top:4px}
  .del-msg strong{color:#181816}
  .btn-danger-modal{display:inline-flex;align-items:center;gap:5px;padding:7px 18px;border-radius:6px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;font-size:12.5px;font-weight:500;font-family:inherit;cursor:pointer;transition:background .12s}
  .btn-danger-modal:hover{background:#fde8e8}
  .btn-danger-modal svg{width:12px;height:12px}
  .btn-save-modal{display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:7px;background:#1a2238;border:1px solid #1a2238;color:#fff;font-size:12px;font-weight:600;font-family:inherit;cursor:pointer;transition:background .12s}
  .btn-save-modal:hover{background:#1e2a42}
  .btn-save-modal svg{width:13px;height:13px}
  .btn-cancel-modal{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:7px;background:transparent;border:1px solid #cccac3;color:#58574f;font-size:12px;font-weight:500;font-family:inherit;cursor:pointer;transition:border .12s,color .12s}
  .btn-cancel-modal:hover{border-color:#e0ded8;color:#181816}
</style>

<!-- ═══════════════════════════════════════
     EDIT MODAL — built from PHP data, shown via JS
     ═══════════════════════════════════════ -->
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
      <input type="hidden" name="id"  value="<?php echo $row['temp_trans_id']; ?>">
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
        <button type="button" class="btn-cancel-modal" onclick="closeModal(this.closest('.wms-overlay'))">Cancel</button>
        <button type="submit" class="btn-save-modal">
          <svg viewBox="0 0 13 13" fill="none"><path d="M2 6.5l3 3 6-6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Save Changes
        </button>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>

<!-- ═══════════════════════════════════════
     DELETE MODAL (single, updated via JS)
     ═══════════════════════════════════════ -->
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
        <div style="font-size:14px;font-weight:700;color:#181816;margin-bottom:6px">Remove this line?</div>
        <div class="del-msg">Product <strong id="del-prod-name"></strong> will be removed from this delivery order. This cannot be undone.</div>
      </div>
      <div class="wms-modal-foot">
        <button type="button" class="btn-cancel-modal" onclick="closeModal(document.getElementById('delete-modal'))">Cancel</button>
        <button type="submit" class="btn-danger-modal">
          <svg viewBox="0 0 12 12" fill="none"><path d="M2 3h8M5 3V2h2v1M10 3l-.7 7H2.7L2 3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Yes, Remove
        </button>
      </div>
    </form>
  </div>
</div>

<script>
/* Sidebar accordion — matches side_check.php nav-grp-hdr pattern */
document.querySelectorAll('.nav-grp-hdr').forEach(function(hdr) {
  hdr.addEventListener('click', function() {
    hdr.parentElement.classList.toggle('open');
  });
});

/* Table search filter */
function filterTable(val) {
  val = val.toLowerCase();
  document.querySelectorAll('#outTable tbody tr').forEach(function(r) {
    r.style.display = r.textContent.toLowerCase().includes(val) ? '' : 'none';
  });
}

/* File input label update */
function updateFileLabel(input) {
  var label = document.getElementById('fileLabel');
  label.textContent = input.files.length ? input.files[0].name : 'Choose File';
}

/* Modal helpers */
function openModal(overlay)  { overlay.classList.add('open'); }
function closeModal(overlay) { overlay.classList.remove('open'); }
function closeOnBackdrop(e, overlay) { if (e.target === overlay) closeModal(overlay); }

function openEdit(id) {
  openModal(document.getElementById('edit-modal-' + id));
}

function openDelete(id, prodName) {
  document.getElementById('del-id-input').value = id;
  document.getElementById('del-prod-name').textContent = prodName;
  openModal(document.getElementById('delete-modal'));
}

/* ESC key closes any open modal */
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.wms-overlay.open').forEach(function(o) {
      o.classList.remove('open');
    });
  }
});
</script>