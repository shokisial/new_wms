
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sovereign WMS — A.S.N</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --navy:          #1a2238;
      --navy-mid:      #1e2a42;
      --navy-light:    #253350;
      --orange:        #d95f2b;
      --orange-light:  #f4722e;
      --orange-muted:  #fdf1eb;
      --orange-border: #f6c9b0;
      --bg:            #f6f5f3;
      --bg2:           #eeede9;
      --white:         #ffffff;
      --border:        #e2e0db;
      --border2:       #d0cec8;
      --text1:         #1a1a18;
      --text2:         #5c5b57;
      --text3:         #9e9c96;
      --green:         #1a6b3a;
      --green-bg:      #eef7f2;
      --green-border:  #b6dfc8;
      --red:           #b91c1c;
      --red-bg:        #fef2f2;
      --red-border:    #fecaca;
      --amber:         #92580a;
      --amber-bg:      #fffbeb;
      --amber-border:  #fcd88a;
      --sidebar-w:     220px;
      --topbar-h:      52px;
    }

    html, body {
      height: 100%;
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      color: var(--text1);
      font-size: 13px;
    }

    /* ── Topbar ── */
    .topbar {
      position: fixed; top: 0; left: 0; right: 0;
      height: var(--topbar-h);
      background: var(--navy);
      display: flex; align-items: center;
      padding: 0 18px; gap: 10px;
      z-index: 100;
      border-bottom: 2px solid var(--orange);
    }
    .logo-mark { display: flex; align-items: center; width: 30px; height: 30px; flex-shrink: 0; }
    .brand { display: flex; flex-direction: column; line-height: 1; }
    .brand .b1 { font-size: 14px; font-weight: 600; color: #fff; letter-spacing: -.2px; }
    .brand .b2 { font-size: 9px; color: #8a9ab8; letter-spacing: .12em; text-transform: uppercase; margin-top: 1px; }
    .topbar-right { margin-left: auto; display: flex; align-items: center; gap: 14px; }
    .branch-pill {
      background: var(--navy-light); border: 1px solid #304060;
      border-radius: 6px; padding: 4px 10px;
      display: flex; align-items: center; gap: 7px;
      font-size: 11px; color: #8a9ab8;
    }
    .branch-pill strong { color: #fff; font-weight: 500; }
    .avatar {
      width: 30px; height: 30px; border-radius: 50%;
      background: var(--orange);
      display: flex; align-items: center; justify-content: center;
      font-size: 11px; font-weight: 600; color: #fff; flex-shrink: 0;
    }

    /* ── Layout ── */
    .layout {
      display: flex;
      padding-top: var(--topbar-h);
      min-height: 100vh;
    }

    /* ── Sidebar ── */
    .sidebar {
      position: fixed;
      top: var(--topbar-h); left: 0; bottom: 0;
      width: var(--sidebar-w);
      background: var(--navy);
      overflow-y: auto;
      padding: 6px 0 24px;
      border-right: 1px solid #253350;
      z-index: 90;
    }
    .sidebar::-webkit-scrollbar { width: 3px; }
    .sidebar::-webkit-scrollbar-thumb { background: #2e3d5a; border-radius: 3px; }

    .nav-sect { padding: 14px 14px 5px; font-size: 9.5px; font-weight: 600; color: #364d70; letter-spacing: .1em; text-transform: uppercase; }
    .nav-item {
      display: flex; align-items: center; gap: 9px;
      padding: 7px 14px; cursor: pointer;
      color: #7a8ba8; font-size: 12px;
      text-decoration: none; position: relative;
      transition: background .12s, color .12s;
    }
    .nav-item:hover { background: #1e2a42; color: #c8d3e8; }
    .nav-item.active { color: #fff; }
    .nav-item.active::after {
      content: ''; position: absolute;
      right: 0; top: 6px; bottom: 6px;
      width: 2.5px; background: var(--orange);
      border-radius: 2px 0 0 2px;
    }
    .nav-item svg { width: 14px; height: 14px; flex-shrink: 0; opacity: .55; }
    .nav-item:hover svg, .nav-item.active svg { opacity: 1; }

    .nav-grp-hdr {
      display: flex; align-items: center; gap: 9px;
      padding: 7px 14px; cursor: pointer;
      color: #7a8ba8; font-size: 12px;
      transition: background .12s, color .12s;
    }
    .nav-grp-hdr:hover { background: #1e2a42; color: #c8d3e8; }
    .nav-grp-hdr svg.ic { width: 14px; height: 14px; flex-shrink: 0; opacity: .55; }
    .nav-grp-hdr:hover svg.ic, .nav-grp.open .nav-grp-hdr svg.ic { opacity: 1; }
    .nav-grp-hdr svg.ch { margin-left: auto; width: 11px; height: 11px; opacity: .4; transition: transform .18s; }
    .nav-grp.open .nav-grp-hdr { color: #c8d3e8; }
    .nav-grp.open .nav-grp-hdr svg.ch { transform: rotate(90deg); opacity: .7; }
    .nav-sub-list { display: none; padding: 2px 0; }
    .nav-grp.open .nav-sub-list { display: block; }
    .nav-sub {
      display: block; padding: 5.5px 14px 5.5px 36px;
      cursor: pointer; color: #5c6e8a; font-size: 11.5px;
      text-decoration: none;
      transition: color .12s, background .12s;
    }
    .nav-sub:hover { color: #c8d3e8; background: #1c2640; }
    .nav-sub.active { color: var(--orange-light); font-weight: 500; }
    .nav-sep { height: 1px; background: #253350; margin: 8px 14px; }

    /* ── Main content ── */
    .main {
      margin-left: var(--sidebar-w);
      flex: 1;
      padding: 22px 26px 40px;
      min-height: calc(100vh - var(--topbar-h));
    }

    /* ── Page components ── */
    .crumb { font-size: 11px; color: var(--text3); display: flex; align-items: center; gap: 5px; margin-bottom: 10px; }
    .crumb a { color: var(--text2); text-decoration: none; }
    .crumb a:hover { color: var(--text1); }

    .ph { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 18px; }
    .ph-left .title { font-size: 18px; font-weight: 600; color: var(--text1); letter-spacing: -.4px; }
    .ph-left .sub { font-size: 12px; color: var(--text2); margin-top: 3px; }
    .ph-right { display: flex; gap: 8px; align-items: center; }

    .btn {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 15px; border-radius: 7px;
      font-size: 12px; font-weight: 500;
      cursor: pointer; border: none;
      font-family: 'Inter', sans-serif;
      white-space: nowrap; transition: all .13s;
    }
    .btn svg { width: 13px; height: 13px; flex-shrink: 0; }
    .btn-navy   { background: var(--navy);   color: #fff; }
    .btn-navy:hover { background: var(--navy-mid); }
    .btn-orange { background: var(--orange); color: #fff; }
    .btn-orange:hover { background: var(--orange-light); }
    .btn-ghost  { background: var(--white);  color: var(--text2); border: 1px solid var(--border2); }
    .btn-ghost:hover { background: var(--bg2); color: var(--text1); }

    .import-strip {
      display: flex; align-items: center; gap: 12px;
      padding: 11px 16px;
      background: var(--orange-muted);
      border: 1px solid var(--orange-border);
      border-radius: 9px; margin-bottom: 18px;
    }
    .import-icon {
      width: 32px; height: 32px; border-radius: 7px;
      background: var(--orange);
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .import-icon svg { width: 15px; height: 15px; }
    .import-text strong { display: block; font-size: 12px; font-weight: 600; color: var(--orange); margin-bottom: 1px; }
    .import-text span { font-size: 11px; color: #9a5535; }
    .import-actions { margin-left: auto; display: flex; gap: 8px; align-items: center; }

    .card { background: var(--white); border: 1px solid var(--border); border-radius: 10px; overflow: hidden; margin-bottom: 16px; }
    .card-hdr { padding: 12px 16px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .card-hdr-title { font-size: 13px; font-weight: 600; color: var(--text1); }
    .card-hdr-note  { font-size: 11px; color: var(--text3); }
    .card-body { padding: 16px; }
    .card-footer { padding: 12px 16px; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .footer-note { font-size: 11px; color: var(--text3); }
    .footer-note strong { color: var(--text2); }

    .cust-row { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; padding-bottom: 16px; border-bottom: 1px solid var(--border); }
    .field-label { font-size: 10.5px; font-weight: 600; color: var(--text3); text-transform: uppercase; letter-spacing: .06em; white-space: nowrap; }

    .sel-wrap { position: relative; flex: 1; max-width: 300px; }
    .sel-wrap::after {
      content: ''; position: absolute; right: 11px; top: 50%;
      transform: translateY(-50%); pointer-events: none;
      width: 0; height: 0;
      border-left: 4px solid transparent; border-right: 4px solid transparent;
      border-top: 5px solid var(--text3);
    }
    .sel-wrap select {
      width: 100%; padding: 8px 32px 8px 10px;
      border: 1.5px solid var(--border2); border-radius: 8px;
      font-size: 12px; font-family: 'Inter', sans-serif;
      color: var(--text1); background: var(--white);
      outline: none; -webkit-appearance: none; appearance: none; cursor: pointer;
    }
    .sel-wrap select:focus { border-color: #9aafcf; }

    .form-grid {
      display: grid;
      grid-template-columns: minmax(0,2.5fr) minmax(0,1.2fr) minmax(0,1fr) minmax(0,1fr) 90px;
      gap: 14px; align-items: end;
    }
    .ff { display: flex; flex-direction: column; gap: 5px; }
    .ff label { font-size: 10.5px; font-weight: 600; color: var(--text3); text-transform: uppercase; letter-spacing: .06em; }
    .ff input, .ff select {
      padding: 8px 10px;
      border: 1.5px solid var(--border2); border-radius: 8px;
      font-size: 12px; font-family: 'Inter', sans-serif;
      color: var(--text1); background: var(--white);
      outline: none; width: 100%;
      -webkit-appearance: none; appearance: none;
    }
    .ff input:focus, .ff select:focus { border-color: #9aafcf; }
    .ff input::-webkit-input-placeholder { color: var(--text3); }
    .ff input::-moz-placeholder { color: var(--text3); }
    .add-btn {
      width: 100%; height: 34px;
      background: var(--navy); color: #fff;
      border: none; border-radius: 7px;
      font-size: 12px; font-weight: 500;
      cursor: pointer; font-family: 'Inter', sans-serif;
      transition: background .13s;
    }
    .add-btn:hover { background: var(--navy-mid); }

    .tbl-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th {
      font-size: 10.5px; font-weight: 600; color: var(--text3);
      text-transform: uppercase; letter-spacing: .05em;
      padding: 8px 14px; border-bottom: 1px solid var(--border);
      text-align: left; white-space: nowrap;
    }
    td { padding: 10px 14px; border-bottom: 1px solid #f2f0ec; font-size: 12px; color: var(--text1); vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: #fbfaf8; }
    .mono { font-family: 'JetBrains Mono', monospace; font-size: 11px; color: var(--text2); }
    .badge { display: inline-flex; align-items: center; padding: 2px 9px; border-radius: 20px; font-size: 11px; font-weight: 500; }
    .badge-amber { background: var(--amber-bg); color: var(--amber); border: 1px solid var(--amber-border); }
    .badge-green { background: var(--green-bg); color: var(--green); border: 1px solid var(--green-border); }
    .del-btn {
      background: var(--red-bg); color: var(--red);
      border: 1px solid var(--red-border);
      padding: 3px 10px; border-radius: 5px;
      font-size: 11px; font-weight: 500;
      cursor: pointer; font-family: 'Inter', sans-serif;
    }
    .empty-row { text-align: center; padding: 32px 14px; color: var(--text3); font-size: 12px; }
  </style>
</head>
<body>

<!-- ── Topbar ── -->
<div class="topbar">
  <svg class="logo-mark" viewBox="0 0 30 36" fill="none">
    <rect x="5" y="1" width="16" height="16" rx="1.5" transform="rotate(45 5 1)" stroke="#d95f2b" stroke-width="2.4" fill="none"/>
    <rect x="9" y="13" width="16" height="16" rx="1.5" transform="rotate(45 9 13)" stroke="#ffffff" stroke-width="2.4" fill="none"/>
  </svg>
  <div class="brand">
    <span class="b1">Sovereign</span>
    <span class="b2">Warehousing &amp; Distribution</span>
  </div>
  <div class="topbar-right">
    <div class="branch-pill">
      <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><rect x="1" y="4" width="10" height="7" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4 4V3a2 2 0 1 1 4 0v1" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
      Branch: <strong><?php echo htmlspecialchars($branch); ?></strong>
    </div>
    <div class="avatar">
      <?php
        $parts = explode(' ', trim($name));
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $p) $initials .= strtoupper($p[0]);
        echo htmlspecialchars($initials);
      ?>
    </div>
  </div>
</div>

<div class="layout">

  <!-- ── Sidebar ── -->
  <aside class="sidebar">

    <div class="nav-sect">Main</div>
    <a href="new_dash.php" class="nav-item">
      <svg viewBox="0 0 14 14" fill="none"><rect x="1" y="1" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="8" y="1" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="1" y="8" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="8" y="8" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.2"/></svg>
      Dashboard
    </a>

    <div class="nav-sect">Operations</div>

    <div class="nav-grp open">
      <div class="nav-grp-hdr">
        <svg class="ic" viewBox="0 0 14 14" fill="none"><path d="M1 7h12M7 3l4 4-4 4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Inbound
        <svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="nav-sub-list">
        <a href="inward_transaction.php" class="nav-sub active">A.S.N</a>
        <a href="gatepass.php"           class="nav-sub">Gate Pass</a>
        <a href="final_barcode.php"      class="nav-sub">Receive</a>
        <a href="final_location.php"     class="nav-sub">Location</a>
        <a href="index_stkveh.php"       class="nav-sub">Location List</a>
      </div>
    </div>

    <div class="nav-grp">
      <div class="nav-grp-hdr">
        <svg class="ic" viewBox="0 0 14 14" fill="none"><path d="M13 7H1M7 11l-4-4 4-4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Outbound
        <svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="nav-sub-list">
        <a href="outward_transaction.php" class="nav-sub">Transfer Note</a>
        <a href="final_out2.php"          class="nav-sub">Order Preparation</a>
        <a href="picking_summery.php"     class="nav-sub">Picking Summary</a>
        <a href="seg_list.php"            class="nav-sub">Segregation List</a>
        <a href="gatepass_out.php"        class="nav-sub">Gate Pass</a>
      </div>
    </div>

    <div class="nav-grp">
      <div class="nav-grp-hdr">
        <svg class="ic" viewBox="0 0 14 14" fill="none"><path d="M2 5h8a3 3 0 0 1 0 6H6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 3L2 5l2 2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Return
        <svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="nav-sub-list">
        <a href="final_barcode_return.php" class="nav-sub">Return Stock</a>
        <a href="gatepass_newreturn.php"   class="nav-sub">Return Gate Pass</a>
      </div>
    </div>
<?php /*
    <div class="nav-sep"></div>
    <div class="nav-sect">Warehouse</div>

    <div class="nav-grp">
      <div class="nav-grp-hdr">
        <svg class="ic" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="2" stroke="currentColor" stroke-width="1.2"/><path d="M7 1v1.5M7 11.5V13M1 7h1.5M11.5 7H13M2.93 2.93l1.06 1.06M10.01 10.01l1.06 1.06M2.93 11.07l1.06-1.06M10.01 3.99l1.06-1.06" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        Configuration
        <svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="nav-sub-list">
        <a href="product.php"        class="nav-sub">Items</a>
        <a href="dealer.php"         class="nav-sub">Adjustment</a>
        <a href="dealer_pre.php"     class="nav-sub">Pre-Adjustment</a>
        <a href="stockcount_int.php" class="nav-sub">Stock Check</a>
        <a href="supplier.php"       class="nav-sub">Customer</a>
        <a href="zone.php"           class="nav-sub">Zone</a>
      </div>
    </div>

    <div class="nav-grp">
      <div class="nav-grp-hdr">
        <svg class="ic" viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 5h5M4.5 7.5h5M4.5 10h3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        Reports
        <svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="nav-sub-list">
        <a href="index_stock.php"       class="nav-sub">Location Wise Stock</a>
        <a href="inbound_report.php"    class="nav-sub">Inbound Report</a>
        <a href="outbound_report.php"   class="nav-sub">Outbound Report</a>
        <a href="index_stockbatch.php"  class="nav-sub">Batch Wise Report</a>
        <a href="index_stockall.php"    class="nav-sub">All Stock Report</a>
        <a href="discrepancy.php"       class="nav-sub">Discrepancy Report</a>
        <a href="expire.php"            class="nav-sub">Expiry Report</a>
        <a href="expire_intimation.php" class="nav-sub">90 Days Expiry</a>
        <a href="index_ledger.php"      class="nav-sub">Customer Ledger</a>
      </div>
    </div>

    <div class="nav-grp">
      <div class="nav-grp-hdr">
        <svg class="ic" viewBox="0 0 14 14" fill="none"><path d="M1 11L4.5 7l3 2.5L11 4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Performance
        <svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="nav-sub-list">
        <a href="rec_time.php"          class="nav-sub">Receive Accuracy</a>
        <a href="out_timeold.php"       class="nav-sub">Outbound Accuracy</a>
        <a href="picking_time.php"      class="nav-sub">Picking Time</a>
        <a href="adj_rpt.php"           class="nav-sub">Adjustment Report</a>
        <a href="quality_checkrpt.php"  class="nav-sub">QMS</a>
        <a href="stockcount_report.php" class="nav-sub">Stock Count Report</a>
      </div>
    </div>

    <div class="nav-sep"></div>
    <div class="nav-sect">Quality</div>

    <div class="nav-grp">
      <div class="nav-grp-hdr">
        <svg class="ic" viewBox="0 0 14 14" fill="none"><path d="M2 7l3.5 3.5L12 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Quality Check
        <svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="nav-sub-list">
        <a href="qualitycheck.php"      class="nav-sub">Daily QMS</a>
        <a href="qualitycheck_week.php" class="nav-sub">Weekly QMS</a>
      </div>
    </div>

    <div class="nav-grp">
      <div class="nav-grp-hdr">
        <svg class="ic" viewBox="0 0 14 14" fill="none"><rect x="2" y="4" width="10" height="9" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M5 4V3a2 2 0 0 1 4 0v1" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        Stock Maintenance
        <svg class="ch" viewBox="0 0 10 10" fill="none"><path d="M3 2l4 3-4 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="nav-sub-list">
        <a href="stockcount_int.php" class="nav-sub">Count Start</a>
        <a href="stock_count.php"    class="nav-sub">Stock Count</a>
      </div>
    </div>
*/ ?>
    <div class="nav-sep"></div>
    <a href="logout.php" class="nav-item" style="color:#5c6e8a; margin-top:4px">
      <svg viewBox="0 0 14 14" fill="none"><path d="M9 7H1M5 4l-3 3 3 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 2h2.5A1.5 1.5 0 0 1 13 3.5v7A1.5 1.5 0 0 1 11.5 12H9" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
      Logout
    </a>

  </aside>