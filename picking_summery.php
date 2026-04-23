<?php
session_start();
if (empty($_SESSION['id']))     { header('Location:../index.php'); exit; }
if (empty($_SESSION['branch'])) { header('Location:../index.php'); exit; }

$branch = $_SESSION['branch'];
$id     = $_SESSION['id'];
$name   = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$user_group = $_SESSION['user_group']; 

include('conn/dbcon.php');

// Fetch picking summary grouped by picking_no + date
$pickings = array();
$qp = mysqli_query($con,
  "SELECT picking_no, dat, COUNT(*) as item_count
   FROM location_temp
   WHERE picked_user='0' AND branch_id='$branch'
   GROUP BY picking_no, dat
   ORDER BY picking_no DESC"
) or die(mysqli_error($con));
while ($r = mysqli_fetch_array($qp)) { $pickings[] = $r; }

$total_pickings = count($pickings);
$total_items    = array_sum(array_column($pickings, 'item_count'));

// Helper: relative date
function relativeDate($datestr) {
  if (!$datestr) return '—';
  $ts   = strtotime($datestr);
  if (!$ts) return $datestr;
  $diff = floor((time() - $ts) / 86400);
  if ($diff == 0) return 'Today';
  if ($diff == 1) return '1 day ago';
  return $diff . ' days ago';
}
?>
<?php include('side_check.php'); ?>

<!-- ── Page-specific styles ── -->
<style>
  /* Stats row */
  .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
  .stat-card { background: #fff; border: 1px solid #e0ded8; border-radius: 10px; padding: 14px 16px; display: flex; align-items: center; gap: 12px; }
  .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .stat-icon svg { width: 18px; height: 18px; }
  .si-navy   { background: #1a2238; color: #fff; }
  .si-orange { background: #fdf1eb; color: #d95f2b; }
  .si-green  { background: #eef7f2; color: #1a6b3a; }
  .stat-label { font-size: 10.5px; color: #9a9890; margin-bottom: 2px; }
  .stat-value { font-size: 20px; font-weight: 700; color: #181816; letter-spacing: -.5px; line-height: 1; }
  .stat-sub { font-size: 10px; color: #9a9890; margin-top: 2px; }

  /* Toolbar inside card header */
  .toolbar { display: flex; align-items: center; gap: 8px; }
  .filter-btn { padding: 6px 12px; border: 1px solid #cccac3; border-radius: 7px; font-size: 11.5px; font-family: inherit; color: #58574f; background: #fff; cursor: pointer; display: flex; align-items: center; gap: 5px; transition: all .12s; }
  .filter-btn:hover { background: #ebe9e5; color: #181816; }
  .filter-btn.active { background: #1a2238; color: #fff; border-color: #1a2238; }
  .search-wrap { position: relative; }
  .search-wrap svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 13px; height: 13px; color: #9a9890; pointer-events: none; }
  .search-wrap input { padding: 7px 10px 7px 30px; border: 1px solid #cccac3; border-radius: 7px; font-size: 12px; font-family: inherit; color: #181816; background: #f2f1ee; outline: none; width: 210px; transition: border .15s, background .15s; }
  .search-wrap input:focus { border-color: #9aafcf; background: #fff; }

  /* Picking no cell */
  .pick-cell { display: flex; align-items: center; gap: 10px; padding: 13px 14px; }
  .pick-icon { width: 32px; height: 32px; border-radius: 7px; background: #ebe9e5; border: 1px solid #e0ded8; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all .13s; }
  .pick-icon svg { width: 14px; height: 14px; color: #58574f; }
  tbody tr:hover .pick-icon { background: #fdf1eb; border-color: #f6c9b0; }
  tbody tr:hover .pick-icon svg { color: #d95f2b; }
  .pick-num { font-family: 'JetBrains Mono', monospace; font-size: 12px; font-weight: 600; color: #181816; }
  .pick-label { font-size: 10.5px; color: #9a9890; margin-top: 1px; }

  /* Date cell */
  .date-cell { padding: 13px 14px; }
  .date-val { font-size: 12px; font-weight: 500; color: #181816; }
  .date-rel { font-size: 10.5px; color: #9a9890; margin-top: 2px; }

  /* Items cell */
  .items-cell { padding: 13px 14px; }
  .items-main { font-size: 14px; font-weight: 700; color: #181816; line-height: 1; }
  .items-sub { font-size: 10.5px; color: #9a9890; margin-top: 3px; }

  /* Badge */
  .badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
  .badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; }
  .b-amber { background: #fffbeb; color: #92580a; border: 1px solid #fcd88a; }
  .b-amber::before { background: #92580a; }

  /* Row action button */
  .row-action { opacity: 0; padding: 13px 14px; transition: opacity .13s; }
  tbody tr:hover .row-action { opacity: 1; }
  .act-btn { display: inline-flex; align-items: center; gap: 5px; padding: 6px 13px; border-radius: 6px; font-size: 11.5px; font-weight: 500; cursor: pointer; border: none; font-family: inherit; transition: all .13s; white-space: nowrap; background: #1a2238; color: #fff; text-decoration: none; }
  .act-btn:hover { background: #1e2a42; }
  .act-btn svg { width: 12px; height: 12px; }

  /* Table footer */
  .tbl-footer { padding: 10px 14px; border-top: 1px solid #e0ded8; display: flex; align-items: center; justify-content: space-between; background: #fafaf8; }
  .tbl-footer-note { font-size: 11px; color: #9a9890; }
  .tbl-footer-note strong { color: #58574f; }

  /* Empty state */
  .empty-state { padding: 52px 20px; text-align: center; }
  .empty-icon { width: 52px; height: 52px; border-radius: 14px; background: #ebe9e5; border: 1px solid #e0ded8; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; }
  .empty-icon svg { width: 22px; height: 22px; color: #9a9890; }
  .empty-title { font-size: 14px; font-weight: 600; color: #181816; margin-bottom: 5px; }
  .empty-sub { font-size: 12px; color: #58574f; }
</style>

  <!-- ── Main content ── -->
  <div class="main">

    <!-- Breadcrumb -->
    <div class="crumb">
      <a href="#">Outbound</a>
      <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Picking Summary
    </div>

    <!-- Page header -->
    <div class="ph">
      <div class="ph-left">
        <div class="title">Picking Summary</div>
        <div class="sub">Click a picking number to view and process its details — Unit: <?php echo htmlspecialchars($branch); ?></div>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon si-navy">
          <svg viewBox="0 0 18 18" fill="none"><rect x="2" y="4" width="14" height="11" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 4V3a3 3 0 0 1 6 0v1" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Open Pick Lists</div>
          <div class="stat-value"><?php echo $total_pickings; ?></div>
          <div class="stat-sub">Awaiting processing</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-orange">
          <svg viewBox="0 0 18 18" fill="none"><rect x="3" y="2" width="12" height="14" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M6 6h6M6 9h6M6 12h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Total Line Items</div>
          <div class="stat-value"><?php echo number_format($total_items); ?></div>
          <div class="stat-sub">Across <?php echo $total_pickings; ?> pick lists</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-green">
          <svg viewBox="0 0 18 18" fill="none"><path d="M9 3a6 6 0 1 0 0 12A6 6 0 0 0 9 3z" stroke="currentColor" stroke-width="1.3"/><path d="M6.5 9l2 2 3-3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div>
          <div class="stat-label">Status</div>
          <div class="stat-value" style="font-size:15px">Pending</div>
          <div class="stat-sub">Not yet picked</div>
        </div>
      </div>
    </div>

    <!-- Table Card -->
    <div class="card">
      <div class="card-hdr">
        <div class="card-hdr-title">Pending pick lists</div>
        <div class="toolbar">
          <button class="filter-btn active" onclick="setFilter('all', this)">All</button>
          <button class="filter-btn" onclick="setFilter('today', this)">Today</button>
          <div class="search-wrap">
            <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
            <input type="text" id="si" placeholder="Search picking no or date…" oninput="filterRows(this.value)">
          </div>
        </div>
      </div>

      <div class="tbl-wrap">
        <table id="pt">
          <thead>
            <tr>
              <th>Picking No.</th>
              <th>Date</th>
              <th>Items</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($pickings)): ?>
            <tr><td colspan="5" style="border:none">
              <div class="empty-state">
                <div class="empty-icon">
                  <svg viewBox="0 0 22 22" fill="none"><rect x="3" y="5" width="16" height="13" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M7 5V4a4 4 0 0 1 8 0v1" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                </div>
                <div class="empty-title">No pending pick lists</div>
                <div class="empty-sub">All picking orders have been processed for this branch.</div>
              </div>
            </td></tr>
            <?php else: ?>
              <?php foreach ($pickings as $row):
                $rel      = relativeDate($row['dat']);
                $date_fmt = $row['dat'] ? date('d M Y', strtotime($row['dat'])) : $row['dat'];
              ?>
              <tr>
                <!-- Picking No -->
                <td>
                  <div class="pick-cell">
                    <div class="pick-icon">
                      <svg viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 5h5M4.5 7h5M4.5 9h3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
                    </div>
                    <div>
                      <div class="pick-num"><?php echo htmlspecialchars($row['picking_no']); ?></div>
                      <div class="pick-label">Pick List</div>
                    </div>
                  </div>
                </td>

                <!-- Date -->
                <td>
                  <div class="date-cell">
                    <div class="date-val"><?php echo htmlspecialchars($date_fmt ?: '—'); ?></div>
                    <div class="date-rel"><?php echo $rel; ?></div>
                  </div>
                </td>

                <!-- Items -->
                <td>
                  <div class="items-cell">
                    <div class="items-main"><?php echo number_format($row['item_count']); ?></div>
                    <div class="items-sub">Line items</div>
                  </div>
                </td>

                <!-- Status -->
                <td style="padding:13px 14px">
                  <span class="badge b-amber">Pending</span>
                </td>

                <!-- Action -->
                <td class="row-action">
                  <form method="POST" action="summeryno2.php" style="display:inline">
                    <input type="hidden" name="dt" value="<?php echo htmlspecialchars($row['dat']); ?>">
                    <button type="submit" name="sub" value="<?php echo htmlspecialchars($row['picking_no']); ?>" class="act-btn">
                      <svg viewBox="0 0 12 12" fill="none"><path d="M2 6h8M6 2l4 4-4 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                      Open
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="tbl-footer">
        <div class="tbl-footer-note">
          <strong><?php echo $total_pickings; ?> pick list<?php echo $total_pickings != 1 ? 's' : ''; ?></strong>
          · <?php echo number_format($total_items); ?> total line items
        </div>
        <div class="tbl-footer-note">Last refreshed: <?php echo date('d M Y, H:i'); ?></div>
      </div>
    </div>

  </div><!-- /.main -->
</div><!-- /.layout -->

<script>
/* Sidebar accordion — matches side_check.php nav-grp-hdr pattern */
document.querySelectorAll('.nav-grp-hdr').forEach(function(h) {
  h.addEventListener('click', function() { h.parentElement.classList.toggle('open'); });
});

/* Table search filter */
function filterRows(v) {
  v = v.toLowerCase();
  document.querySelectorAll('#pt tbody tr').forEach(function(r) {
    r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none';
  });
}

/* Today / All filter buttons */
function setFilter(type, btn) {
  document.querySelectorAll('.filter-btn').forEach(function(b) { b.classList.remove('active'); });
  btn.classList.add('active');
  document.querySelectorAll('#pt tbody tr').forEach(function(r) {
    if (type === 'all')        { r.style.display = ''; }
    else if (type === 'today') { r.style.display = r.textContent.includes('Today') ? '' : 'none'; }
  });
}
</script>