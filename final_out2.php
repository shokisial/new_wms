<?php
session_start();
if(empty($_SESSION['id']))     { header('Location:../index.php'); exit; }
if(empty($_SESSION['branch'])) { header('Location:../index.php'); exit; }

$branch = $_SESSION['branch'];
$id     = $_SESSION['id'];
$uname  = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$user_group = $_SESSION['user_group']; 

include('conn/dbcon.php');

// Load pending orders
$orders = array();
$q = mysqli_query($con,
  "SELECT *, SUM(stockout_dnqty) AS qtr, COUNT(DISTINCT stockout_orderno) AS line_count
   FROM stockout
   INNER JOIN supplier ON supplier.supplier_id = stockout.sup_id
   WHERE stockout.branch_id='$branch' AND final='1' AND stockout_qty='0'
   GROUP BY stockout_orderno
   ORDER BY stockout_orderno ASC"
) or die(mysqli_error($con));
while($r = mysqli_fetch_array($q)) $orders[] = $r;

$total_orders = count($orders);
$total_units  = array_sum(array_column($orders, 'qtr'));
$avg_order    = $total_orders > 0 ? round($total_units / $total_orders) : 0;

// City distribution
$city_counts = array();
foreach($orders as $o) {
  $c = $o['city'] ? $o['city'] : 'Other';
  $city_counts[$c] = ($city_counts[$c] ?? 0) + 1;
}
arsort($city_counts);

// Helpers
function av_cls($i)    { $cls = array('ca-1','ca-2','ca-3','ca-4','ca-5'); return $cls[$i % 5]; }
function initials_of($s) {
  $w = array_filter(explode(' ', trim($s)));
  $o = '';
  foreach(array_slice($w, 0, 2) as $x) $o .= strtoupper($x[0]);
  return $o ? $o : '?';
}
?>
<?php include('side_check.php'); ?>

<!-- ── Page-specific styles ── -->
<style>
  /* KPI strip */
  .kpi-row{display:grid;grid-template-columns:repeat(5,1fr);gap:11px;margin-bottom:14px}
  .kpi{background:#fff;border:1px solid #e0ded8;border-radius:10px;padding:13px 15px}
  .kpi-lbl{font-size:10px;font-weight:600;color:#9a9890;text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px}
  .kpi-val{font-size:22px;font-weight:800;color:#181816;letter-spacing:-.8px;line-height:1;margin-bottom:2px}
  .kpi-sub{font-size:10px;color:#9a9890}
  .kpi-trend{display:inline-flex;align-items:center;gap:3px;font-size:10.5px;font-weight:600;border-radius:4px;padding:1px 7px;margin-top:5px}
  .trend-up{color:#1a6b3a;background:#eef7f2}
  .trend-warn{color:#92580a;background:#fffbeb}
  .trend-down{color:#b91c1c;background:#fef2f2}

  /* Viz panels row */
  .viz-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:11px;margin-bottom:14px}
  .panel{background:#fff;border:1px solid #e0ded8;border-radius:10px;padding:15px}
  .panel-title{font-size:12px;font-weight:600;color:#181816;margin-bottom:12px}

  /* Outbound funnel */
  .funnel-step{display:flex;align-items:center;gap:8px;margin-bottom:6px}
  .funnel-lbl{font-size:10.5px;color:#58574f;width:88px;flex-shrink:0;text-align:right}
  .funnel-bar{height:18px;border-radius:4px;display:flex;align-items:center;padding-left:8px;font-size:10.5px;font-weight:700;white-space:nowrap;min-width:28px}

  /* Throughput bars */
  .tp-row{display:flex;align-items:center;gap:6px;margin-bottom:5px}
  .tp-day{font-size:10.5px;color:#9a9890;width:22px;flex-shrink:0}
  .tp-bg{flex:1;height:10px;background:#ebe9e5;border-radius:5px;overflow:hidden}
  .tp-fill{height:100%;border-radius:5px;background:#d95f2b}
  .tp-num{font-size:10.5px;font-weight:600;color:#58574f;width:36px;text-align:right}

  /* City distribution */
  .city-row{display:flex;align-items:center;gap:6px;margin-bottom:5px}
  .city-name{font-size:10.5px;color:#58574f;width:70px;flex-shrink:0}
  .city-bg{flex:1;height:8px;background:#ebe9e5;border-radius:4px;overflow:hidden}
  .city-fill{height:100%;border-radius:4px;background:#1e4fa0}
  .city-num{font-size:10.5px;font-weight:600;color:#58574f;width:22px;text-align:right}

  /* Action bar */
  .act-bar{display:flex;align-items:center;gap:8px;margin-bottom:16px;padding:12px 16px;background:#fff;border:1px solid #e0ded8;border-radius:10px;flex-wrap:wrap}
  .act-label{font-size:12px;font-weight:600;color:#181816}
  .act-sel{padding:7px 10px;border:1.5px solid #cccac3;border-radius:7px;font-size:12px;font-family:inherit;color:#181816;background:#fff;cursor:pointer;-webkit-appearance:none;appearance:none;outline:none}
  .act-date{padding:7px 10px;border:1.5px solid #cccac3;border-radius:7px;font-size:12px;font-family:inherit;color:#181816;background:#fff;outline:none}
  .act-right{margin-left:auto;display:flex;align-items:center;gap:7px;flex-wrap:wrap}
  .sel-pill{font-size:11.5px;font-weight:600;color:#d95f2b;background:#fdf1eb;border:1px solid #f6c9b0;border-radius:20px;padding:2px 10px}
  .sw{position:relative}
  .sw svg{position:absolute;left:9px;top:50%;transform:translateY(-50%);width:12px;height:12px;color:#9a9890;pointer-events:none}
  .sw input{padding:7px 9px 7px 28px;border:1px solid #cccac3;border-radius:7px;font-size:12px;font-family:inherit;color:#181816;background:#f2f1ee;outline:none;width:185px;transition:border .15s,background .15s}
  .sw input:focus{border-color:#93aac8;background:#fff}

  /* Orders grid */
  .orders-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
  .orders-title{font-size:13px;font-weight:600;color:#181816}
  .orders-meta{font-size:11px;color:#9a9890}
  .order-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:11px}
  .oc{background:#fff;border:1px solid #e0ded8;border-radius:10px;overflow:hidden;transition:border-color .12s,box-shadow .12s;cursor:pointer}
  .oc:hover{border-color:#93aac8;box-shadow:0 3px 12px rgba(0,0,0,.07)}
  .oc.selected{border-color:#1a2238;border-width:1.5px;box-shadow:0 0 0 3px rgba(26,34,56,.06)}
  .oc-hdr{padding:12px 12px 0;display:flex;align-items:flex-start;justify-content:space-between;gap:8px}
  .oc-dc{display:flex;align-items:center;gap:8px}
  .oc-av{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0}
  .ca-1{background:#e8f0fe;color:#1e4fa0}
  .ca-2{background:#fce8e6;color:#c0392b}
  .ca-3{background:#e6f4ea;color:#1a6b3a}
  .ca-4{background:#fef3e2;color:#92580a}
  .ca-5{background:#f3e8ff;color:#6b21a8}
  .oc-num{font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:700;color:#181816;margin-bottom:1px}
  .oc-cust{font-size:11px;color:#58574f;font-weight:500}
  .oc-body{padding:10px 12px}
  .oc-info{display:flex;align-items:center;gap:5px;font-size:11px;color:#58574f;margin-bottom:5px}
  .oc-info svg{width:11px;height:11px;color:#9a9890;flex-shrink:0}
  .oc-info strong{color:#181816;font-weight:500;font-family:'JetBrains Mono',monospace;font-size:10.5px}
  .oc-qty-row{display:flex;gap:5px;margin-bottom:8px}
  .oc-chip{flex:1;text-align:center;background:#f2f1ee;border:1px solid #e0ded8;border-radius:6px;padding:5px 4px}
  .oc-chip-lbl{font-size:8.5px;font-weight:700;color:#9a9890;text-transform:uppercase;letter-spacing:.05em;margin-bottom:1px}
  .oc-chip-val{font-size:13px;font-weight:800;color:#181816;line-height:1}
  .oc-foot{padding:8px 12px;border-top:1px solid #e0ded8;display:flex;align-items:center;justify-content:space-between}
  .oc-city{font-size:10.5px;color:#9a9890}

  /* Badges */
  .badge{display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:20px;font-size:10.5px;font-weight:500}
  .badge::before{content:'';width:4px;height:4px;border-radius:50%}
  .b-amber{background:#fffbeb;color:#92580a;border:1px solid #fcd88a}.b-amber::before{background:#92580a}
  .b-blue{background:#eff4ff;color:#1e4fa0;border:1px solid #bdd0f8}.b-blue::before{background:#1e4fa0}

  /* Checkbox */
  .cb{width:14px;height:14px;accent-color:#1a2238;cursor:pointer;flex-shrink:0}

  /* Empty state */
  .empty-state{padding:52px 20px;text-align:center}
  .empty-icon{width:52px;height:52px;border-radius:14px;background:#ebe9e5;border:1px solid #e0ded8;display:flex;align-items:center;justify-content:center;margin:0 auto 14px}
  .empty-icon svg{width:22px;height:22px;color:#9a9890}
  .empty-title{font-size:14px;font-weight:600;color:#181816;margin-bottom:5px}
  .empty-sub{font-size:12px;color:#58574f}
</style>

  <!-- ── Main content ── -->
  <div class="main">

    <!-- Breadcrumb -->
    <div class="crumb">
      <a href="#">Outbound</a>
      <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M3 2l3 2.5L3 7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Order Preparation
    </div>

    <!-- Page header -->
    <div class="ph">
      <div class="ph-left">
        <div class="title">Order Preparation</div>
        <div class="sub">Select pending DC orders to assign for picking &middot; <?php echo date('d M Y'); ?></div>
      </div>
    </div>

    <!-- KPI Strip -->
    <div class="kpi-row">
      <div class="kpi">
        <div class="kpi-lbl">Pending orders</div>
        <div class="kpi-val"><?php echo $total_orders; ?></div>
        <div class="kpi-sub">Awaiting picking</div>
        <div class="kpi-trend trend-warn">Today</div>
      </div>
      <div class="kpi">
        <div class="kpi-lbl">Total units</div>
        <div class="kpi-val"><?php echo number_format($total_units); ?></div>
        <div class="kpi-sub">Across all DCs</div>
        <div class="kpi-trend trend-up">Pending dispatch</div>
      </div>
      <div class="kpi">
        <div class="kpi-lbl">Avg order size</div>
        <div class="kpi-val"><?php echo $avg_order; ?></div>
        <div class="kpi-sub">Units per order</div>
        <div class="kpi-trend trend-up">Units</div>
      </div>
      <div class="kpi">
        <div class="kpi-lbl">Destinations</div>
        <div class="kpi-val"><?php echo count($city_counts); ?></div>
        <div class="kpi-sub">Cities covered</div>
        <div class="kpi-trend trend-up">Active routes</div>
      </div>
      <div class="kpi">
        <div class="kpi-lbl">Pick accuracy</div>
        <div class="kpi-val">99.4%</div>
        <div class="kpi-sub">Last 30 days</div>
        <div class="kpi-trend trend-up">Target: 99%</div>
      </div>
    </div>

    <!-- Viz Row -->
    <div class="viz-row">

      <!-- Outbound funnel -->
      <div class="panel">
        <div class="panel-title">Outbound fulfilment funnel</div>
        <?php
          $funnel = array(
            array('label'=>'Orders received', 'count'=>$total_orders,               'color'=>'#e8ecf5','text'=>'#1a2238'),
            array('label'=>'Picking started', 'count'=>round($total_orders*0.75),   'color'=>'#c5d0e8','text'=>'#1a2238'),
            array('label'=>'Pick complete',   'count'=>round($total_orders*0.5),    'color'=>'#f6c9b0','text'=>'#6b2600'),
            array('label'=>'Segregated',      'count'=>round($total_orders*0.33),   'color'=>'#d95f2b','text'=>'#fff'),
            array('label'=>'Dispatched',      'count'=>round($total_orders*0.17),   'color'=>'#1a2238','text'=>'#fff'),
          );
          $max_f = $total_orders > 0 ? $total_orders : 1;
          foreach($funnel as $fs):
            $w = $max_f > 0 ? round(($fs['count'] / $max_f) * 100) : 4;
            if($w < 4) $w = 4;
        ?>
        <div class="funnel-step">
          <div class="funnel-lbl"><?php echo $fs['label']; ?></div>
          <div class="funnel-bar" style="width:<?php echo $w; ?>%;background:<?php echo $fs['color']; ?>;color:<?php echo $fs['text']; ?>"><?php echo $fs['count']; ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Daily throughput (static demo) -->
      <div class="panel">
        <div class="panel-title">Dispatch throughput (units, last 7 days)</div>
        <?php
          $days = array('Mon'=>1480,'Tue'=>1760,'Wed'=>1300,'Thu'=>1820,'Fri'=>2000,'Sat'=>960,'Sun'=>420);
          $max_d = max($days);
          foreach($days as $day => $val):
            $pct = round(($val / $max_d) * 100);
        ?>
        <div class="tp-row">
          <div class="tp-day"><?php echo $day; ?></div>
          <div class="tp-bg"><div class="tp-fill" style="width:<?php echo $pct; ?>%"></div></div>
          <div class="tp-num"><?php echo number_format($val); ?></div>
        </div>
        <?php endforeach; ?>
        <div style="margin-top:8px;font-size:10.5px;color:#9a9890">Avg: 1,249 &middot; Peak: Friday</div>
      </div>

      <!-- City distribution -->
      <div class="panel">
        <div class="panel-title">Order distribution by destination</div>
        <?php
          $max_city = max(array_values($city_counts) + array(1));
          $ci = 0;
          foreach($city_counts as $city => $cnt):
            $pct2 = round(($cnt / $max_city) * 100);
            $ci++;
            if($ci > 6) break;
        ?>
        <div class="city-row">
          <div class="city-name"><?php echo htmlspecialchars($city); ?></div>
          <div class="city-bg"><div class="city-fill" style="width:<?php echo $pct2; ?>%"></div></div>
          <div class="city-num"><?php echo $cnt; ?></div>
        </div>
        <?php endforeach; ?>
        <div style="margin-top:8px;font-size:10.5px;color:#9a9890"><?php echo $total_orders; ?> orders &middot; <?php echo count($city_counts); ?> destinations</div>
      </div>

    </div><!-- /.viz-row -->

    <!-- Action bar + Orders grid (wrapped in one form) -->
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
          <div class="sw">
            <svg viewBox="0 0 13 13" fill="none"><circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l-2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
            <input type="text" placeholder="Search DC#, consignee, city&#8230;" oninput="filterCards(this.value)">
          </div>
          <div class="sel-pill" id="selCount">0 selected</div>
          <button type="button" class="btn btn-ghost btn-sm" onclick="selectAll()">Select all</button>
          <button type="button" class="btn btn-ghost btn-sm" onclick="clearAll()">Clear</button>
        </div>
      </div>

      <!-- Orders grid header -->
      <div class="orders-hdr">
        <div class="orders-title">Pending DC orders</div>
        <div class="orders-meta"><?php echo $total_orders; ?> order<?php echo $total_orders != 1 ? 's' : ''; ?> &middot; click card to select &middot; hover for details</div>
      </div>

      <?php if(empty($orders)): ?>
      <div style="background:#fff;border:1px solid #e0ded8;border-radius:10px;overflow:hidden">
        <div class="empty-state">
          <div class="empty-icon">
            <svg viewBox="0 0 22 22" fill="none"><rect x="3" y="3" width="16" height="16" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M7 11h8M11 7v8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
          </div>
          <div class="empty-title">No pending orders</div>
          <div class="empty-sub">All DC orders have been sent to picking or no orders exist yet.</div>
        </div>
      </div>
      <?php else: ?>

      <div class="order-grid" id="orderGrid">
        <?php
          $av_classes = array('ca-1','ca-2','ca-3','ca-4','ca-5');
          $oi = 0;
          foreach($orders as $row):
            $av  = $av_classes[$oi % 5];
            $oi++;
            $ini = initials_of($row['supplier_name']);
        ?>
        <div class="oc"
             data-s="<?php echo strtolower(htmlspecialchars($row['stockout_orderno'].' '.$row['supplier_name'].' '.$row['city'].' '.$row['dealer_code'])); ?>"
             onclick="toggleCard(this)">
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
            <div class="oc-info">
              <svg viewBox="0 0 12 12" fill="none"><path d="M6 1a4 4 0 1 0 0 8 4 4 0 0 0 0-8zM6 11v-1" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
              City: <strong><?php echo htmlspecialchars($row['city'] ? $row['city'] : 'N/A'); ?></strong>
            </div>
            <div class="oc-info">
              <svg viewBox="0 0 12 12" fill="none"><rect x="2" y="1" width="8" height="10" rx="1.5" stroke="currentColor" stroke-width="1.1"/></svg>
              Consignee: <strong><?php echo htmlspecialchars($row['dealer_code'] ? $row['dealer_code'] : '—'); ?></strong>
            </div>
            <div class="oc-qty-row">
              <div class="oc-chip">
                <div class="oc-chip-lbl">Order Qty</div>
                <div class="oc-chip-val"><?php echo number_format($row['qtr']); ?></div>
              </div>
              <div class="oc-chip">
                <div class="oc-chip-lbl">Lines</div>
                <div class="oc-chip-val"><?php echo $row['line_count'] ?? '—'; ?></div>
              </div>
            </div>
          </div>
          <div class="oc-foot">
            <div class="oc-city"><?php echo htmlspecialchars($row['city'] ? $row['city'] : '—'); ?></div>
            <span class="badge b-blue">Picking</span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php endif; ?>
    </form>

  </div><!-- /.main -->
</div><!-- /.layout -->

<script>
/* Sidebar accordion — matches side_check.php nav-grp-hdr pattern */
document.querySelectorAll('.nav-grp-hdr').forEach(function(hdr) {
  hdr.addEventListener('click', function() {
    hdr.parentElement.classList.toggle('open');
  });
});

/* Card selection */
function updateSelCount() {
  var n = document.querySelectorAll('.oc.selected').length;
  document.getElementById('selCount').textContent = n + ' selected';
}
function toggleCard(card) {
  card.classList.toggle('selected');
  card.querySelector('.oc-cb').checked = card.classList.contains('selected');
  updateSelCount();
}
function selectAll() {
  document.querySelectorAll('.oc:not([style*="display: none"])').forEach(function(c) {
    c.classList.add('selected');
    c.querySelector('.oc-cb').checked = true;
  });
  updateSelCount();
}
function clearAll() {
  document.querySelectorAll('.oc').forEach(function(c) {
    c.classList.remove('selected');
    c.querySelector('.oc-cb').checked = false;
  });
  updateSelCount();
}

/* Card search filter */
function filterCards(v) {
  v = v.toLowerCase();
  document.querySelectorAll('.oc').forEach(function(c) {
    c.style.display = c.dataset.s.includes(v) ? '' : 'none';
  });
}

/* Form validation */
function validateForm() {
  var c = document.querySelectorAll('.oc-cb:checked');
  if(c.length === 0) { alert('Please select at least one DC order.'); return false; }
  return true;
}

/* Sync checkbox → card selected state */
document.querySelectorAll('.oc-cb').forEach(function(cb) {
  cb.addEventListener('change', function() {
    this.closest('.oc').classList.toggle('selected', this.checked);
    updateSelCount();
  });
});
</script>