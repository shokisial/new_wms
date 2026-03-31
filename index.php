<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sovereign WMS — Sign In</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --navy:       #1a2238;
      --navy-mid:   #1e2a42;
      --navy-deep:  #111827;
      --navy-light: #253350;
      --orange:     #d95f2b;
      --orange-lt:  #f4722e;
      --white:      #ffffff;
      --bg:         #eef0f5;
      --border:     #dde1ec;
      --input-bg:   #f8f9fc;
      --text1:      #0e1628;
      --text2:      #5a6380;
      --text3:      #9aa0b4;
      --error-bg:   #fef2f2;
      --error:      #b91c1c;
      --error-bd:   #fecaca;
    }

    html, body {
      height: 100%;
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }

    /* ── Shell ── */
    .shell {
      width: 100%;
      max-width: 980px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 24px 80px rgba(14,22,40,.18), 0 4px 16px rgba(14,22,40,.08);
      min-height: 540px;
    }

    /* ── Left panel ── */
    .left {
      background: var(--navy);
      padding: 48px 44px;
      display: flex;
      flex-direction: column;
      position: relative;
      overflow: hidden;
    }

    /* Decorative circles */
    .left::before {
      content: '';
      position: absolute;
      width: 340px; height: 340px;
      border-radius: 50%;
      border: 1.5px solid rgba(255,255,255,.05);
      top: -90px; right: -90px;
      pointer-events: none;
    }
    .left::after {
      content: '';
      position: absolute;
      width: 200px; height: 200px;
      border-radius: 50%;
      border: 1.5px solid rgba(255,255,255,.04);
      bottom: -50px; left: -60px;
      pointer-events: none;
    }
    .deco-circle {
      position: absolute;
      width: 130px; height: 130px;
      border-radius: 50%;
      border: 1.5px solid rgba(255,255,255,.04);
      bottom: 90px; right: 50px;
      pointer-events: none;
    }
    /* Orange accent corner blob */
    .deco-blob {
      position: absolute;
      width: 180px; height: 180px;
      background: var(--orange);
      opacity: .07;
      border-radius: 50%;
      bottom: -60px; right: -60px;
      pointer-events: none;
    }

    .logo-box {
      display: flex;
      align-items: center;
      gap: 13px;
      background: rgba(255,255,255,.07);
      border: 1px solid rgba(255,255,255,.10);
      border-radius: 14px;
      padding: 13px 18px;
      width: fit-content;
      margin-bottom: auto;
      position: relative;
      z-index: 1;
    }
    .logo-text { font-size: 17px; font-weight: 700; color: #fff; letter-spacing: -.2px; }
    .logo-sub  { font-size: 9px; font-weight: 400; color: rgba(255,255,255,.4); letter-spacing: .14em; text-transform: uppercase; margin-top: 2px; }

    .left-body { padding: 8px 0 0; position: relative; z-index: 1; }
    .left-eyebrow {
      font-size: 10px; font-weight: 600;
      color: rgba(255,255,255,.3);
      letter-spacing: .14em; text-transform: uppercase;
      margin-bottom: 14px;
      display: flex; align-items: center; gap: 8px;
    }
    .left-eyebrow::before {
      content: '';
      display: inline-block;
      width: 18px; height: 1.5px;
      background: var(--orange);
      border-radius: 2px;
    }
    .left-headline {
      font-size: 31px; font-weight: 700;
      color: #fff; line-height: 1.18;
      letter-spacing: -.6px;
      margin-bottom: 14px;
    }
    .left-headline span { color: var(--orange-lt); }
    .left-desc { font-size: 13px; color: rgba(255,255,255,.4); line-height: 1.65; max-width: 270px; }

    .left-footer {
      margin-top: auto;
      padding-top: 28px;
      display: flex; align-items: center; gap: 10px;
      border-top: 1px solid rgba(255,255,255,.06);
      position: relative; z-index: 1;
    }
    .left-footer-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--orange); flex-shrink: 0; }
    .left-footer-text { font-size: 11px; color: rgba(255,255,255,.25); line-height: 1.5; }

    /* ── Right panel ── */
    .right {
      background: var(--white);
      padding: 52px 48px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .form-eyebrow {
      font-size: 10px; font-weight: 600;
      color: var(--orange); letter-spacing: .14em;
      text-transform: uppercase; margin-bottom: 8px;
    }
    .form-title { font-size: 26px; font-weight: 700; color: var(--text1); letter-spacing: -.5px; margin-bottom: 5px; }
    .form-sub   { font-size: 13px; color: var(--text2); margin-bottom: 30px; line-height: 1.55; }

    /* Error alert */
    .alert-error {
      display: flex; align-items: flex-start; gap: 10px;
      background: var(--error-bg);
      border: 1px solid var(--error-bd);
      border-radius: 9px;
      padding: 11px 14px;
      margin-bottom: 20px;
      font-size: 12.5px; color: var(--error);
      line-height: 1.5;
    }
    .alert-error svg { width: 15px; height: 15px; flex-shrink: 0; margin-top: 1px; }

    /* Fields */
    .field { margin-bottom: 16px; }
    .field-row {
      display: flex; align-items: center;
      justify-content: space-between;
      margin-bottom: 7px;
    }
    .field-label {
      font-size: 10.5px; font-weight: 600;
      color: var(--text2); letter-spacing: .06em;
      text-transform: uppercase;
    }
    .field-forgot {
      font-size: 11.5px; color: var(--orange);
      font-weight: 500; cursor: pointer;
      text-decoration: none; background: none; border: none;
      font-family: inherit;
    }
    .field-forgot:hover { text-decoration: underline; }

    .input-wrap { position: relative; }
    .input-wrap input,
    .input-wrap select {
      width: 100%;
      padding: 11px 14px;
      background: var(--input-bg);
      border: 1.5px solid var(--border);
      border-radius: 10px;
      font-size: 13.5px;
      font-family: 'Inter', sans-serif;
      color: var(--text1);
      outline: none;
      transition: border .15s, background .15s;
      appearance: none;
      -webkit-appearance: none;
    }
    .input-wrap input::placeholder { color: var(--text3); }
    .input-wrap input:focus,
    .input-wrap select:focus {
      border-color: #9aaad0;
      background: #fff;
    }
    .input-wrap .eye {
      position: absolute; right: 13px; top: 50%;
      transform: translateY(-50%);
      cursor: pointer; color: var(--text3);
      background: none; border: none; padding: 0;
      display: flex; align-items: center;
    }
    .input-wrap .eye:hover { color: var(--text2); }
    /* Custom select arrow */
    .input-wrap.has-select::after {
      content: '';
      position: absolute; right: 14px; top: 50%;
      transform: translateY(-50%);
      width: 0; height: 0;
      border-left: 4px solid transparent;
      border-right: 4px solid transparent;
      border-top: 5px solid var(--text3);
      pointer-events: none;
    }
    .input-wrap select { padding-right: 36px; cursor: pointer; }

    /* Submit button */
    .sign-btn {
      width: 100%;
      padding: 14px;
      background: var(--navy);
      color: #fff;
      font-size: 14px; font-weight: 600;
      font-family: 'Inter', sans-serif;
      border: none; border-radius: 10px;
      cursor: pointer; letter-spacing: .01em;
      transition: background .15s;
      display: flex; align-items: center; justify-content: center; gap: 8px;
      margin-top: 6px; margin-bottom: 22px;
    }
    .sign-btn:hover { background: var(--navy-mid); }
    .sign-btn svg { width: 16px; height: 16px; }

    /* Divider */
    .divider {
      display: flex; align-items: center; gap: 10px;
      margin-bottom: 16px;
    }
    .divider-line { flex: 1; height: 1px; background: var(--border); }
    .divider-text { font-size: 11px; color: var(--text3); white-space: nowrap; }

    /* SSO button */
    .sso-btn {
      width: 100%;
      padding: 11px;
      background: #fff; color: var(--text2);
      font-size: 13px; font-weight: 500;
      font-family: 'Inter', sans-serif;
      border: 1.5px solid var(--border);
      border-radius: 10px; cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 9px;
      transition: all .15s;
    }
    .sso-btn:hover { border-color: #9aaad0; color: var(--text1); background: var(--input-bg); }

    /* Footer note */
    .form-footer {
      margin-top: 20px; text-align: center;
      font-size: 11.5px; color: var(--text3);
    }
    .form-footer a { color: var(--orange); font-weight: 500; text-decoration: none; }
    .form-footer a:hover { text-decoration: underline; }

    /* ── Responsive ── */
    @media (max-width: 680px) {
      .shell { grid-template-columns: 1fr; min-height: auto; border-radius: 16px; }
      .left  { padding: 36px 28px; min-height: 220px; }
      .left-headline { font-size: 24px; }
      .left-body .left-desc { display: none; }
      .left-footer { display: none; }
      .right { padding: 36px 28px; }
    }
  </style>
</head>
<body>

<div class="shell">

  <!-- ── Left panel ── -->
  <div class="left">
    <div class="deco-circle"></div>
    <div class="deco-blob"></div>

    <div class="logo-box">
      <!-- Sovereign diamond logo mark -->
      <svg width="28" height="33" viewBox="0 0 28 34" fill="none">
        <rect x="4" y="1" width="16" height="16" rx="1.5" transform="rotate(45 4 1)"
              stroke="#d95f2b" stroke-width="2.2" fill="none"/>
        <rect x="8" y="13" width="16" height="16" rx="1.5" transform="rotate(45 8 13)"
              stroke="rgba(255,255,255,0.85)" stroke-width="2.2" fill="none"/>
      </svg>
      <div>
        <div class="logo-text">Sovereign</div>
        <div class="logo-sub">Warehousing &amp; Distribution</div>
      </div>
    </div>

    <div class="left-body">
      <div class="left-eyebrow">Secure Access Portal</div>
      <div class="left-headline">
        Your warehouse,<br>
        <span>fully in control.</span>
      </div>
      <div class="left-desc">
        Sign in with your assigned credentials to access the Sovereign WMS dashboard and manage your operations.
      </div>
    </div>

    <div class="left-footer">
      <div class="left-footer-dot"></div>
      <div class="left-footer-text">
        Protected by enterprise-grade security<br>All sessions are monitored and logged
      </div>
    </div>
  </div>

  <!-- ── Right panel ── -->
  <div class="right">
    <div class="form-eyebrow">Sign in</div>
    <div class="form-title">Welcome back</div>
    <div class="form-sub">Select your branch and enter your credentials to continue.</div>

    <?php
    /* ── DB connection ── */
    include('conn/dbcon.php');

    /* ── Show error if login failed ── */
    // if (isset($_GET['error'])) {
    //   $msg = match($_GET['error']) {
    //     'invalid' => 'Incorrect username or password. Please try again.',
    //     'locked'  => 'This account has been locked. Contact your administrator.',
    //     default   => 'Something went wrong. Please try again.',
    //   };
    //   echo '
    //   <div class="alert-error">
    //     <svg viewBox="0 0 15 15" fill="none">
    //       <circle cx="7.5" cy="7.5" r="6.5" stroke="currentColor" stroke-width="1.3"/>
    //       <path d="M7.5 4.5v3.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
    //       <circle cx="7.5" cy="10.5" r=".7" fill="currentColor"/>
    //     </svg>
    //     ' . htmlspecialchars($msg) . '
    //   </div>';
  //  }
    ?>

    <form action="login.php" method="POST" autocomplete="off">

      <!-- Branch -->
      <div class="field">
        <div class="field-row">
          <span class="field-label">Branch</span>
        </div>
        <div class="input-wrap has-select">
          <select name="branch" required>
            <?php
            $q = mysqli_query($con, "SELECT * FROM branch ORDER BY branch_name ASC") or die(mysqli_error($con));
            while ($row = mysqli_fetch_array($q)) {
              $sel = (isset($_POST['branch']) && $_POST['branch'] == $row['branch_id']) ? 'selected' : '';
              echo '<option value="' . htmlspecialchars($row['branch_id']) . '" ' . $sel . '>'
                   . htmlspecialchars($row['branch_name']) . '</option>';
            }
            ?>
          </select>
        </div>
      </div>

      <!-- Username -->
      <div class="field">
        <div class="field-row">
          <span class="field-label">Username</span>
        </div>
        <div class="input-wrap">
          <input
            type="text"
            name="username"
            placeholder="Enter your username"
            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
            autocomplete="username"
            required>
        </div>
      </div>

      <!-- Password -->
      <div class="field">
        <div class="field-row">
          <span class="field-label">Password</span>
          <button type="button" class="field-forgot" onclick="alert('Please contact your system administrator to reset your password.')">
            
          </button>
        </div>
        <div class="input-wrap">
          <input
            type="password"
            name="password"
            id="pwd"
            placeholder="Enter your password"
            autocomplete="current-password"
            required>
          <button type="button" class="eye" onclick="togglePwd()" aria-label="Show/hide password">
            <svg id="eye-icon" width="17" height="17" viewBox="0 0 17 17" fill="none">
              <ellipse cx="8.5" cy="8.5" rx="7.5" ry="5.5" stroke="currentColor" stroke-width="1.2"/>
              <circle cx="8.5" cy="8.5" r="2.2" stroke="currentColor" stroke-width="1.2"/>
            </svg>
          </button>
        </div>
      </div>

      <button type="submit" name="login" class="sign-btn">
        Sign In
        <svg viewBox="0 0 16 16" fill="none">
          <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>

    </form>
<?php /*
    <div class="divider">
      <div class="divider-line"></div>
      <div class="divider-text">or continue with</div>
      <div class="divider-line"></div>
    </div>

    <button class="sso-btn" type="button" onclick="alert('SSO not configured. Contact your administrator.')">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none">
        <rect x="0.5" y="0.5" width="6" height="6" rx="1" fill="#4285F4"/>
        <rect x="8.5" y="0.5" width="6" height="6" rx="1" fill="#34A853"/>
        <rect x="0.5" y="8.5" width="6" height="6" rx="1" fill="#FBBC05"/>
        <rect x="8.5" y="8.5" width="6" height="6" rx="1" fill="#EA4335"/>
      </svg>
      Sign in with Google Workspace
    </button>

    <div class="form-footer">
      Having trouble? <a href="mailto:admin@sovereign.com">Contact your system admin</a>
    </div>
  </div>

</div>

<script>
function togglePwd() {
  var p = document.getElementById('pwd');
  var icon = document.getElementById('eye-icon');
  if (p.type === 'password') {
    p.type = 'text';
    icon.innerHTML = '<ellipse cx="8.5" cy="8.5" rx="7.5" ry="5.5" stroke="currentColor" stroke-width="1.2"/><line x1="2" y1="2" x2="15" y2="15" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>';
  } else {
    p.type = 'password';
    icon.innerHTML = '<ellipse cx="8.5" cy="8.5" rx="7.5" ry="5.5" stroke="currentColor" stroke-width="1.2"/><circle cx="8.5" cy="8.5" r="2.2" stroke="currentColor" stroke-width="1.2"/>';
  }
}
</script>
*/?>
</body>
</html>
