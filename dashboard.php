<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
include 'config.php';

$session_user_id = $_SESSION['user_id'];
$session_role    = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RideShare — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:#0a0c10;--surface:#13161d;--card:#1a1e28;--border:#252a38;
    --accent:#f5c518;--accent2:#ff6b35;--green:#22c55e;--red:#ef4444;
    --blue:#3b82f6;--text:#e8eaf0;--muted:#6b7280;
    --font-h:'Syne',sans-serif;--font-b:'DM Sans',sans-serif;
  }
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
  html{scroll-behavior:smooth;}
  body{background:var(--bg);color:var(--text);font-family:var(--font-b);font-size:15px;min-height:100vh;overflow-x:hidden;}
  body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(245,197,24,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(245,197,24,.03) 1px,transparent 1px);background-size:48px 48px;pointer-events:none;z-index:0;}
  .sidebar{position:fixed;top:0;left:0;width:240px;height:100vh;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;z-index:100;padding:0 0 24px;}
  .sidebar-logo{padding:28px 24px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;}
  .logo-icon{width:36px;height:36px;background:var(--accent);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;}
  .logo-text{font-family:var(--font-h);font-size:18px;font-weight:800;color:var(--text);}
  .logo-text span{color:var(--accent);}
  .nav{flex:1;padding:16px 12px;display:flex;flex-direction:column;gap:4px;}
  .nav-item{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;font-size:14px;font-weight:500;color:var(--muted);cursor:pointer;border:none;background:none;width:100%;text-align:left;transition:all .18s ease;text-decoration:none;}
  .nav-item:hover{background:var(--card);color:var(--text);}
  .nav-item.active{background:rgba(245,197,24,.12);color:var(--accent);}
  .nav-item .icon{font-size:18px;width:22px;text-align:center;}
  .nav-label{font-family:var(--font-h);font-weight:600;font-size:13px;}
  .sidebar-footer{padding:16px 24px 0;border-top:1px solid var(--border);}
  .status-badge{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--muted);}
  .dot{width:8px;height:8px;border-radius:50%;background:var(--green);box-shadow:0 0 8px var(--green);animation:pulse 2s infinite;}
  @keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
  .main{margin-left:240px;min-height:100vh;position:relative;z-index:1;}
  .topbar{position:sticky;top:0;z-index:50;display:flex;align-items:center;justify-content:space-between;padding:18px 40px;background:rgba(10,12,16,.85);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);}
  .page-title{font-family:var(--font-h);font-size:22px;font-weight:700;}
  .topbar-right{display:flex;align-items:center;gap:16px;}
  .time-display{font-size:13px;color:var(--muted);font-weight:300;}
  .user-badge{font-size:13px;color:var(--muted);}
  .user-badge span{color:var(--accent);font-weight:600;}
  .content{padding:32px 40px;}
  .tab-panel{display:none;}
  .tab-panel.active{display:block;animation:fadeUp .3s ease;}
  @keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
  .stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:32px;}
  .stat-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:22px;position:relative;overflow:hidden;transition:transform .2s,border-color .2s;}
  .stat-card:hover{transform:translateY(-3px);border-color:rgba(245,197,24,.3);}
  .stat-label{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;font-weight:500;margin-bottom:10px;}
  .stat-value{font-family:var(--font-h);font-size:32px;font-weight:800;color:var(--text);line-height:1;margin-bottom:6px;}
  .stat-sub{font-size:12px;color:var(--muted);}
  .stat-icon{position:absolute;top:18px;right:18px;font-size:22px;opacity:.6;}
  .section-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;}
  .section-title{font-family:var(--font-h);font-size:17px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:10px;}
  .section-title::before{content:'';display:block;width:4px;height:20px;background:var(--accent);border-radius:4px;}
  .form-card{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:32px;max-width:680px;}
  .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
  .form-group{display:flex;flex-direction:column;gap:8px;}
  .form-group.full{grid-column:1/-1;}
  .form-label{font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;}
  .form-input,.form-select{background:var(--surface);border:1.5px solid var(--border);border-radius:12px;color:var(--text);font-family:var(--font-b);font-size:14px;padding:12px 16px;outline:none;transition:border-color .2s,box-shadow .2s;width:100%;}
  .form-input:focus,.form-select:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(245,197,24,.12);}
  .form-select option{background:var(--surface);}
  .fare-preview{background:rgba(245,197,24,.06);border:1.5px solid rgba(245,197,24,.2);border-radius:14px;padding:18px 22px;display:flex;align-items:center;justify-content:space-between;margin-top:4px;}
  .fare-label{font-size:13px;color:var(--muted);}
  .fare-amount{font-family:var(--font-h);font-size:28px;font-weight:800;color:var(--accent);}
  .fare-breakdown{font-size:12px;color:var(--muted);margin-top:2px;}
  .btn{display:inline-flex;align-items:center;gap:8px;padding:13px 28px;border-radius:12px;font-family:var(--font-h);font-size:14px;font-weight:700;border:none;cursor:pointer;transition:all .18s ease;letter-spacing:.03em;}
  .btn-primary{background:var(--accent);color:#0a0c10;}
  .btn-primary:hover{background:#e6b800;transform:translateY(-1px);box-shadow:0 8px 24px rgba(245,197,24,.3);}
  .btn-secondary{background:var(--surface);color:var(--text);border:1.5px solid var(--border);}
  .btn-secondary:hover{border-color:var(--accent);color:var(--accent);}
  .btn-danger{background:rgba(239,68,68,.12);color:var(--red);border:1.5px solid rgba(239,68,68,.25);}
  .btn-danger:hover{background:rgba(239,68,68,.2);}
  .pipeline{display:flex;align-items:center;gap:0;margin:28px 0;}
  .pipe-step{flex:1;text-align:center;padding:18px 12px;background:var(--surface);border-top:1px solid var(--border);border-bottom:1px solid var(--border);position:relative;transition:all .25s ease;}
  .pipe-step:first-child{border-left:1px solid var(--border);border-radius:14px 0 0 14px;}
  .pipe-step:last-child{border-right:1px solid var(--border);border-radius:0 14px 14px 0;}
  .pipe-step.active{background:rgba(245,197,24,.1);border-color:rgba(245,197,24,.4);z-index:1;}
  .pipe-step.done{background:rgba(34,197,94,.06);border-color:rgba(34,197,94,.2);}
  .pipe-step.cancelled{background:rgba(239,68,68,.06);border-color:rgba(239,68,68,.2);}
  .pipe-num{width:30px;height:30px;border-radius:50%;background:var(--border);color:var(--muted);font-family:var(--font-h);font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;transition:all .25s;}
  .pipe-step.active .pipe-num{background:var(--accent);color:#0a0c10;}
  .pipe-step.done .pipe-num{background:var(--green);color:#fff;}
  .pipe-step.cancelled .pipe-num{background:var(--red);color:#fff;}
  .pipe-label{font-size:12px;font-weight:600;font-family:var(--font-h);color:var(--muted);}
  .pipe-step.active .pipe-label{color:var(--accent);}
  .pipe-step.done .pipe-label{color:var(--green);}
  .pipe-step.cancelled .pipe-label{color:var(--red);}
  .table-wrap{background:var(--card);border:1px solid var(--border);border-radius:20px;overflow:hidden;}
  table{width:100%;border-collapse:collapse;}
  thead th{background:var(--surface);padding:14px 18px;font-family:var(--font-h);font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;text-align:left;border-bottom:1px solid var(--border);}
  tbody tr{border-bottom:1px solid rgba(37,42,56,.6);transition:background .15s;}
  tbody tr:last-child{border-bottom:none;}
  tbody tr:hover{background:rgba(245,197,24,.03);}
  tbody td{padding:14px 18px;font-size:13.5px;color:var(--text);vertical-align:middle;}
  .td-id{font-family:var(--font-h);font-size:12px;color:var(--muted);}
  .td-name{font-weight:500;}
  .td-fare{font-family:var(--font-h);font-weight:700;color:var(--accent);}
  .pill{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;font-family:var(--font-h);text-transform:uppercase;letter-spacing:.06em;}
  .pill::before{content:'';width:6px;height:6px;border-radius:50%;}
  .pill-requested{background:rgba(59,130,246,.12);color:var(--blue);}
  .pill-requested::before{background:var(--blue);}
  .pill-accepted{background:rgba(245,197,24,.12);color:var(--accent);}
  .pill-accepted::before{background:var(--accent);box-shadow:0 0 6px var(--accent);animation:pulse 1.5s infinite;}
  .pill-ongoing{background:rgba(255,107,53,.12);color:var(--accent2);}
  .pill-ongoing::before{background:var(--accent2);box-shadow:0 0 6px var(--accent2);animation:pulse 1.5s infinite;}
  .pill-completed{background:rgba(34,197,94,.12);color:var(--green);}
  .pill-completed::before{background:var(--green);}
  .pill-cancelled{background:rgba(239,68,68,.12);color:var(--red);}
  .pill-cancelled::before{background:var(--red);}
  #toast{position:fixed;bottom:32px;right:32px;background:var(--card);border:1px solid var(--border);border-radius:14px;padding:14px 20px;font-size:14px;font-weight:500;box-shadow:0 16px 40px rgba(0,0,0,.5);transform:translateY(80px);opacity:0;transition:all .3s cubic-bezier(.34,1.56,.64,1);z-index:1000;min-width:260px;display:flex;align-items:center;gap:10px;}
  #toast.show{transform:translateY(0);opacity:1;}
  #toast.success{border-color:rgba(34,197,94,.4);}
  #toast.error{border-color:rgba(239,68,68,.4);}
  .driver-info-box{background:rgba(34,197,94,.06);border:1.5px solid rgba(34,197,94,.2);border-radius:14px;padding:20px 24px;display:none;margin-top:20px;gap:16px;align-items:center;}
  .driver-info-box.show{display:flex;animation:fadeUp .3s ease;}
  .driver-avatar{width:50px;height:50px;border-radius:14px;background:rgba(34,197,94,.15);display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;}
  .driver-details h4{font-family:var(--font-h);font-size:15px;font-weight:700;margin-bottom:4px;}
  .driver-details p{font-size:12px;color:var(--muted);}
  .empty-row td{text-align:center;padding:48px !important;}
  .empty-msg{color:var(--muted);font-size:14px;}
  .empty-msg span{display:block;font-size:36px;margin-bottom:12px;}
  ::-webkit-scrollbar{width:6px;}
  ::-webkit-scrollbar-track{background:transparent;}
  ::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px;}
  /* External links section */
  .ext-links{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:24px;}
  .ext-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:20px;text-decoration:none;color:var(--text);transition:all .2s;display:flex;flex-direction:column;gap:8px;}
  .ext-card:hover{border-color:var(--accent);transform:translateY(-2px);}
  .ext-card .ec-icon{font-size:28px;}
  .ext-card .ec-title{font-family:var(--font-h);font-weight:700;font-size:14px;}
  .ext-card .ec-sub{font-size:12px;color:var(--muted);}
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">🚗</div>
    <div class="logo-text">Ride<span>Share</span></div>
  </div>
  <nav class="nav">
    <button class="nav-item active" onclick="switchTab('dashboard',this)">
      <span class="icon">📊</span><span class="nav-label">Dashboard</span>
    </button>
    <button class="nav-item" onclick="switchTab('request',this)">
      <span class="icon">📍</span><span class="nav-label">Request Ride</span>
    </button>
    <button class="nav-item" onclick="switchTab('assign',this)">
      <span class="icon">🔗</span><span class="nav-label">Assign Driver</span>
    </button>
    <button class="nav-item" onclick="switchTab('status',this)">
      <span class="icon">🔄</span><span class="nav-label">Update Status</span>
    </button>
    <button class="nav-item" onclick="switchTab('fare',this)">
      <span class="icon">💰</span><span class="nav-label">Fare Calculator</span>
    </button>
    <button class="nav-item" onclick="switchTab('rides',this)">
      <span class="icon">📋</span><span class="nav-label">All Rides</span>
    </button>
    <hr style="border-color:var(--border);margin:8px 0;">
    <!-- T3 pages -->
    <a class="nav-item" href="rider_dashboard.php?user_id=<?= $session_user_id ?>&role=<?= $session_role ?>">
      <span class="icon">🙋</span><span class="nav-label">My Dashboard</span>
    </a>
    <a class="nav-item" href="make_payment.php?rider_id=<?= $session_user_id ?>&role=<?= $session_role ?>">
      <span class="icon">💳</span><span class="nav-label">Payment</span>
    </a>
    <a class="nav-item" href="submit_rating.php">
      <span class="icon">⭐</span><span class="nav-label">Rate Driver</span>
    </a>
    <a class="nav-item" href="logout.php">
      <span class="icon">🚪</span><span class="nav-label">Logout</span>
    </a>
  </nav>
  <div class="sidebar-footer">
    <div class="status-badge"><div class="dot"></div> System Online</div>
  </div>
</aside>

<main class="main">
  <div class="topbar">
    <div class="page-title" id="page-title">Dashboard Overview</div>
    <div class="topbar-right">
      <div class="user-badge">Logged in as <span><?= htmlspecialchars($_SESSION['name']) ?></span> (<?= $_SESSION['role'] ?>)</div>
      <div class="time-display" id="clock"></div>
    </div>
  </div>

  <div class="content">

    <!-- DASHBOARD TAB -->
    <div class="tab-panel active" id="tab-dashboard">
      <?php
        $total    = $conn->query("SELECT COUNT(*) c FROM rides")->fetch_assoc()['c'] ?? 0;
        $ongoing  = $conn->query("SELECT COUNT(*) c FROM rides WHERE status='Ongoing'")->fetch_assoc()['c'] ?? 0;
        $complete = $conn->query("SELECT COUNT(*) c FROM rides WHERE status='completed'")->fetch_assoc()['c'] ?? 0;
        $revenue_row = $conn->query("SELECT SUM(fare) s FROM rides WHERE status='completed'");
        $revenue  = $revenue_row ? ($revenue_row->fetch_assoc()['s'] ?? 0) : 0;
        $drivers  = $conn->query("SELECT COUNT(*) c FROM driver WHERE availability_status='online'")->fetch_assoc()['c'] ?? 0;
      ?>
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-label">Total Rides</div>
          <div class="stat-value"><?= $total ?></div>
          <div class="stat-sub">All time</div>
          <div class="stat-icon">🚘</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Ongoing</div>
          <div class="stat-value" style="color:var(--accent2)"><?= $ongoing ?></div>
          <div class="stat-sub">Active now</div>
          <div class="stat-icon">⚡</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Completed</div>
          <div class="stat-value" style="color:var(--green)"><?= $complete ?></div>
          <div class="stat-sub">Successful trips</div>
          <div class="stat-icon">✅</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Total Revenue</div>
          <div class="stat-value" style="color:var(--accent)">৳<?= number_format($revenue,0) ?></div>
          <div class="stat-sub"><?= $drivers ?> drivers online</div>
          <div class="stat-icon">💰</div>
        </div>
      </div>

      <div class="section-head"><div class="section-title">Recent Activity</div></div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>#ID</th><th>Rider</th><th>Driver</th><th>Pickup</th><th>Drop-off</th><th>Fare</th><th>Status</th></tr>
          </thead>
          <tbody>
          <?php
            $res = $conn->query("
              SELECT r.ride_id, u.name AS rider_name, r.driver_id,
                     r.pickup_location, r.drop_location, r.fare, r.status
              FROM rides r
              JOIN users u ON r.rider_id = u.id
              LEFT JOIN driver d ON r.driver_id = d.driver_id
              ORDER BY r.ride_id DESC LIMIT 10
            ");
            if ($res && $res->num_rows > 0):
              while ($row = $res->fetch_assoc()):
                $pill = strtolower($row['status']);
          ?>
            <tr>
              <td class="td-id">#<?= $row['ride_id'] ?></td>
              <td class="td-name"><?= htmlspecialchars($row['rider_name']) ?></td>
              <td><?= $row['driver_id'] ? '🧑 D-'.$row['driver_id'] : '<span style="color:var(--muted)">—</span>' ?></td>
              <td>📍 <?= htmlspecialchars($row['pickup_location']) ?></td>
              <td>🏁 <?= htmlspecialchars($row['drop_location']) ?></td>
              <td class="td-fare">৳<?= number_format($row['fare'],2) ?></td>
              <td><span class="pill pill-<?= $pill ?>"><?= $row['status'] ?></span></td>
            </tr>
          <?php endwhile; else: ?>
            <tr class="empty-row"><td colspan="7"><div class="empty-msg"><span>🚗</span>No rides yet</div></td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- REQUEST RIDE TAB -->
    <div class="tab-panel" id="tab-request">
      <div class="section-head"><div class="section-title">New Ride Request</div></div>
      <div id="req-no-route">
        <div class="form-card" style="max-width:560px;text-align:center;padding:40px 32px">
          <div style="font-size:52px;margin-bottom:16px">🗺️</div>
          <div style="font-family:var(--font-h);font-size:18px;font-weight:800;margin-bottom:10px">Calculate Your Route First</div>
          <div style="font-size:14px;color:var(--muted);margin-bottom:28px;line-height:1.7">Use the Fare Calculator to pick your route, then click "Use This Route".</div>
          <a href="fare_calculator.php" class="btn btn-primary" style="display:inline-flex;width:auto;text-decoration:none;padding:13px 32px">🗺️ Go to Fare Calculator</a>
        </div>
      </div>
      <div id="req-has-route" style="display:none">
        <div class="form-card" style="max-width:600px">
          <div style="background:rgba(34,197,94,.06);border:1.5px solid rgba(34,197,94,.2);border-radius:14px;padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;gap:14px">
            <div style="font-size:26px">✅</div>
            <div style="flex:1">
              <div style="font-family:var(--font-h);font-size:13px;font-weight:700;color:var(--green);margin-bottom:3px">Route loaded from Fare Calculator</div>
              <div style="font-size:12px;color:var(--muted)" id="req-route-summary">Loading…</div>
            </div>
            <a href="fare_calculator.php" style="font-size:12px;color:var(--accent);font-family:var(--font-h);font-weight:700;text-decoration:none;white-space:nowrap">✏️ Change</a>
          </div>
          <div class="form-grid" style="margin-bottom:20px">
            <div class="form-group">
              <label class="form-label">📍 Pickup</label>
              <div style="background:var(--surface);border:1.5px solid var(--border);border-radius:12px;padding:12px 16px;font-size:13.5px;display:flex;align-items:center;gap:8px;min-height:46px">
                <span style="color:var(--accent);flex-shrink:0">●</span><span id="req-pickup-display">—</span>
              </div>
              <input type="hidden" id="req_pickup">
            </div>
            <div class="form-group">
              <label class="form-label">🏁 Drop-off</label>
              <div style="background:var(--surface);border:1.5px solid var(--border);border-radius:12px;padding:12px 16px;font-size:13.5px;display:flex;align-items:center;gap:8px;min-height:46px">
                <span style="color:#ef4444;flex-shrink:0">●</span><span id="req-dropoff-display">—</span>
              </div>
              <input type="hidden" id="req_dropoff">
            </div>
            <div class="form-group">
              <label class="form-label">📏 Distance</label>
              <div style="background:var(--surface);border:1.5px solid var(--border);border-radius:12px;padding:12px 16px;font-size:15px;font-family:var(--font-h);font-weight:800">
                <span id="req-dist-display">—</span> <span style="color:var(--muted);font-size:12px;font-weight:400">km</span>
              </div>
              <input type="hidden" id="req_dist">
            </div>
            <div class="form-group">
              <label class="form-label">🚗 Vehicle</label>
              <div style="background:var(--surface);border:1.5px solid var(--border);border-radius:12px;padding:12px 16px;font-size:14px;font-family:var(--font-h);font-weight:700;color:var(--accent)">
                <span id="req-vehicle-display">—</span>
              </div>
              <input type="hidden" id="req_vehicle">
            </div>
          </div>
          <div style="border-top:1px solid var(--border);margin-bottom:20px"></div>
          <div class="form-group" style="margin-bottom:20px;max-width:300px">
            <label class="form-label">👤 Passenger ID (your user ID)</label>
            <input class="form-input" type="text" id="req_pid" value="<?= $session_user_id ?>" placeholder="Your user ID">
          </div>
          <div class="fare-preview" id="fare-preview" style="margin-bottom:24px">
            <div>
              <div class="fare-label">Estimated Fare</div>
              <div class="fare-breakdown" id="fare-breakdown">From fare calculator</div>
            </div>
            <div class="fare-amount" id="fare-display">৳ —</div>
          </div>
          <div style="display:flex;gap:12px;flex-wrap:wrap">
            <button class="btn btn-primary" style="width:auto;padding:13px 32px" onclick="requestRide()">📍 Confirm &amp; Request Ride</button>
            <a href="fare_calculator.php" class="btn btn-secondary" style="width:auto;padding:13px 24px;text-decoration:none">🔄 Recalculate</a>
          </div>
        </div>
      </div>
    </div>

    <!-- ASSIGN DRIVER TAB -->
    <div class="tab-panel" id="tab-assign">
      <div class="section-head"><div class="section-title">Assign Driver to Ride</div></div>
      <div class="form-card">
        <div class="form-group" style="margin-bottom:20px">
          <label class="form-label">Ride ID</label>
          <input class="form-input" type="text" id="assign_rid" placeholder="Enter Ride ID" style="max-width:300px">
        </div>
        <button class="btn btn-primary" onclick="assignDriver()">🔗 Find &amp; Assign Driver</button>
        <div class="driver-info-box" id="driver-info">
          <div class="driver-avatar">🧑‍✈️</div>
          <div class="driver-details">
            <h4 id="d-name">Driver Name</h4>
            <p id="d-vehicle">Vehicle: — | License: —</p>
          </div>
          <span class="pill pill-accepted" style="margin-left:auto">Assigned ✓</span>
        </div>
      </div>
    </div>

    <!-- UPDATE STATUS TAB -->
    <div class="tab-panel" id="tab-status">
      <div class="section-head"><div class="section-title">Update Ride Status</div></div>
      <div class="pipeline">
        <div class="pipe-step" id="ps-requested"><div class="pipe-num">1</div><div class="pipe-label">Requested</div></div>
        <div class="pipe-step" id="ps-accepted"><div class="pipe-num">2</div><div class="pipe-label">Accepted</div></div>
        <div class="pipe-step" id="ps-ongoing"><div class="pipe-num">3</div><div class="pipe-label">Ongoing</div></div>
        <div class="pipe-step" id="ps-completed"><div class="pipe-num">4</div><div class="pipe-label">Completed</div></div>
      </div>
      <div class="form-card">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Ride ID</label>
            <input class="form-input" type="text" id="upd_rid" placeholder="Ride ID">
          </div>
          <div class="form-group">
            <label class="form-label">New Status</label>
            <select class="form-select" id="upd_status" onchange="highlightPipeline()">
              <option value="Accepted">✅ Accepted</option>
              <option value="Ongoing">⚡ Ongoing</option>
              <option value="completed">🏁 Completed</option>
              <option value="cancelled">❌ Cancelled</option>
            </select>
          </div>
        </div>
        <br>
        <div style="display:flex;gap:12px">
          <button class="btn btn-primary" onclick="updateStatus()">🔄 Update Status</button>
          <button class="btn btn-danger" onclick="document.getElementById('upd_status').value='cancelled';highlightPipeline();updateStatus();">❌ Cancel Ride</button>
        </div>
      </div>
    </div>

    <!-- FARE CALCULATOR TAB -->
    <div class="tab-panel" id="tab-fare">
      <div class="section-head"><div class="section-title">Fare Calculator</div></div>
      <div class="form-card" style="max-width:560px">
        <div style="text-align:center;padding:16px 0 24px">
          <div style="font-size:56px;margin-bottom:16px">🗺️</div>
          <div style="font-family:var(--font-h);font-size:20px;font-weight:800;margin-bottom:10px">Map-Based Fare Calculator</div>
          <div style="font-size:14px;color:var(--muted);margin-bottom:28px;line-height:1.6">Search real locations, see the route on a live map, and get an instant fare estimate.</div>
          <a href="fare_calculator.php" class="btn btn-primary" style="display:inline-flex;width:auto;text-decoration:none;padding:14px 36px">🗺️ Open Fare Calculator</a>
        </div>
        <div style="border-top:1px solid var(--border);padding-top:20px;margin-top:4px">
          <div style="font-family:var(--font-h);font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px">Rate Card</div>
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:16px;text-align:center">
              <div style="font-size:28px;margin-bottom:6px">🚗</div>
              <div style="font-family:var(--font-h);font-size:13px;font-weight:700">Car</div>
              <div style="font-size:11px;color:var(--muted);margin-top:4px">৳50 base</div>
              <div style="font-size:11px;color:var(--accent);font-weight:600">+ ৳15/km</div>
            </div>
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:16px;text-align:center">
              <div style="font-size:28px;margin-bottom:6px">🏍️</div>
              <div style="font-family:var(--font-h);font-size:13px;font-weight:700">Bike</div>
              <div style="font-size:11px;color:var(--muted);margin-top:4px">৳30 base</div>
              <div style="font-size:11px;color:var(--accent);font-weight:600">+ ৳8/km</div>
            </div>
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:16px;text-align:center">
              <div style="font-size:28px;margin-bottom:6px">🛺</div>
              <div style="font-family:var(--font-h);font-size:13px;font-weight:700">CNG</div>
              <div style="font-size:11px;color:var(--muted);margin-top:4px">৳40 base</div>
              <div style="font-size:11px;color:var(--accent);font-weight:600">+ ৳12/km</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ALL RIDES TAB -->
    <div class="tab-panel" id="tab-rides">
      <div class="section-head">
        <div class="section-title">All Rides</div>
        <select class="form-select" id="filter-status" onchange="filterTable()" style="width:160px;padding:8px 14px">
          <option value="">All Statuses</option>
          <option>Requested</option><option>Accepted</option><option>Ongoing</option>
          <option value="completed">Completed</option><option value="cancelled">Cancelled</option>
        </select>
      </div>
      <div class="table-wrap">
        <table id="rides-table">
          <thead>
            <tr><th>#ID</th><th>Rider</th><th>Driver</th><th>Pickup</th><th>Drop-off</th><th>Dist.</th><th>Fare</th><th>Status</th></tr>
          </thead>
          <tbody>
          <?php
            $res2 = $conn->query("
              SELECT r.ride_id, u.name AS rider_name, r.driver_id,
                     r.pickup_location, r.drop_location, r.distance, r.fare, r.status
              FROM rides r
              JOIN users u ON r.rider_id = u.id
              LEFT JOIN driver d ON r.driver_id = d.driver_id
              ORDER BY r.ride_id DESC
            ");
            if ($res2 && $res2->num_rows > 0):
              while ($row = $res2->fetch_assoc()):
                $pill = strtolower($row['status']);
          ?>
            <tr data-status="<?= $row['status'] ?>">
              <td class="td-id">#<?= $row['ride_id'] ?></td>
              <td class="td-name"><?= htmlspecialchars($row['rider_name']) ?></td>
              <td><?= $row['driver_id'] ? '🧑 D-'.$row['driver_id'] : '<span style="color:var(--muted)">—</span>' ?></td>
              <td>📍 <?= htmlspecialchars($row['pickup_location']) ?></td>
              <td>🏁 <?= htmlspecialchars($row['drop_location']) ?></td>
              <td><?= $row['distance'] ?> km</td>
              <td class="td-fare">৳<?= number_format($row['fare'],2) ?></td>
              <td><span class="pill pill-<?= $pill ?>"><?= $row['status'] ?></span></td>
            </tr>
          <?php endwhile; else: ?>
            <tr class="empty-row"><td colspan="8"><div class="empty-msg"><span>🚗</span>No rides found</div></td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<div id="toast"><span class="toast-icon" id="toast-icon">✅</span><span id="toast-msg">Done</span></div>

<script>
function tick(){const n=new Date();document.getElementById('clock').textContent=n.toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',second:'2-digit'});}
tick();setInterval(tick,1000);

const titles={dashboard:'Dashboard Overview',request:'Request a Ride',assign:'Assign Driver',status:'Update Status',fare:'Fare Calculator',rides:'All Rides'};
function switchTab(name,el){
  document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(b=>b.classList.remove('active'));
  document.getElementById('tab-'+name).classList.add('active');
  el.classList.add('active');
  document.getElementById('page-title').textContent=titles[name];
}

let toastTimer;
function toast(msg,type='success'){
  const t=document.getElementById('toast');
  document.getElementById('toast-msg').textContent=msg;
  document.getElementById('toast-icon').textContent=type==='success'?'✅':'❌';
  t.className='show '+type;
  clearTimeout(toastTimer);
  toastTimer=setTimeout(()=>t.className='',3500);
}

const rates={car:{base:50,km:15},bike:{base:30,km:8},cng:{base:40,km:12}};
const vehicleEmoji={car:'🚗 Car',bike:'🏍️ Bike',cng:'🛺 CNG'};
function computeFare(dist,vehicle){const r=rates[vehicle]||rates.car;return{total:r.base+dist*r.km,base:r.base,km:r.km};}

function loadRouteFromURL(){
  const params=new URLSearchParams(window.location.search);
  const tab=params.get('tab'),pickup=params.get('pickup'),dropoff=params.get('dropoff'),distance=params.get('distance'),vehicle=params.get('vehicle')||'car';
  if(tab==='request'){const nb=document.querySelector('.nav-item[onclick*="request"]');if(nb)switchTab('request',nb);}
  if(pickup&&dropoff&&distance){
    document.getElementById('req_pickup').value=pickup;
    document.getElementById('req_dropoff').value=dropoff;
    document.getElementById('req_dist').value=distance;
    document.getElementById('req_vehicle').value=vehicle;
    document.getElementById('req-pickup-display').textContent=pickup;
    document.getElementById('req-dropoff-display').textContent=dropoff;
    document.getElementById('req-dist-display').textContent=parseFloat(distance).toFixed(2);
    document.getElementById('req-vehicle-display').textContent=vehicleEmoji[vehicle]||vehicle;
    document.getElementById('req-route-summary').textContent=pickup+' → '+dropoff+' ('+parseFloat(distance).toFixed(2)+' km)';
    const f=computeFare(parseFloat(distance),vehicle);
    document.getElementById('fare-display').textContent='৳'+f.total.toFixed(2);
    document.getElementById('fare-breakdown').textContent='Base ৳'+f.base+' + '+parseFloat(distance).toFixed(2)+'km × ৳'+f.km+'/km';
    document.getElementById('req-no-route').style.display='none';
    document.getElementById('req-has-route').style.display='block';
  }
}

async function requestRide(){
  const pid=document.getElementById('req_pid').value.trim();
  const pickup=document.getElementById('req_pickup').value.trim();
  const drop=document.getElementById('req_dropoff').value.trim();
  const dist=document.getElementById('req_dist').value;
  const veh=document.getElementById('req_vehicle').value;
  if(!pid){toast('Please enter your Passenger ID','error');return;}
  if(!pickup||!drop||!dist){toast('Route data missing — use Fare Calculator first','error');return;}
  const fd=new FormData();
  fd.append('passenger_id',pid);fd.append('pickup_location',pickup);
  fd.append('dropoff_location',drop);fd.append('distance',dist);fd.append('vehicle_type',veh);
  try{
    const r=await fetch('request_ride.php',{method:'POST',body:fd});
    const d=await r.json();
    if(d.success){toast('Ride #'+d.ride_id+' requested! Fare: ৳'+d.fare,'success');document.getElementById('req-no-route').style.display='block';document.getElementById('req-has-route').style.display='none';history.replaceState(null,'','dashboard.php');}
    else toast(d.message,'error');
  }catch(e){toast('Request failed','error');}
}

async function assignDriver(){
  const rid=document.getElementById('assign_rid').value.trim();
  if(!rid){toast('Enter a Ride ID','error');return;}
  const fd=new FormData();fd.append('ride_id',rid);
  try{
    const r=await fetch('assign_driver.php',{method:'POST',body:fd});
    const d=await r.json();
    if(d.success){
      document.getElementById('d-name').textContent=d.driver_name;
      document.getElementById('d-vehicle').textContent='Vehicle: '+(d.vehicle_type||'—')+'  |  License: '+(d.license||'—');
      document.getElementById('driver-info').classList.add('show');
      toast('Driver '+d.driver_name+' assigned to Ride #'+rid,'success');
    }else{toast(d.message,'error');document.getElementById('driver-info').classList.remove('show');}
  }catch(e){toast('Request failed','error');}
}

function highlightPipeline(){
  const val=document.getElementById('upd_status').value;
  const order=['requested','accepted','ongoing','completed'];
  const cancelled=val==='cancelled';
  order.forEach((s,i)=>{
    const el=document.getElementById('ps-'+s);el.className='pipe-step';
    if(cancelled){el.classList.add('cancelled');return;}
    const target=val.toLowerCase();const ti=order.indexOf(target);
    if(i<ti)el.classList.add('done');else if(i===ti)el.classList.add('active');
  });
}
highlightPipeline();

async function updateStatus(){
  const rid=document.getElementById('upd_rid').value.trim();
  const st=document.getElementById('upd_status').value;
  if(!rid){toast('Enter a Ride ID','error');return;}
  const fd=new FormData();fd.append('ride_id',rid);fd.append('status',st);
  try{
    const r=await fetch('update_status.php',{method:'POST',body:fd});
    const d=await r.json();
    if(d.success){toast(d.message,'success');highlightPipeline();}else toast(d.message,'error');
  }catch(e){toast('Request failed','error');}
}

function filterTable(){
  const val=document.getElementById('filter-status').value;
  document.querySelectorAll('#rides-table tbody tr').forEach(tr=>{
    if(!val||tr.dataset.status===val)tr.style.display='';else tr.style.display='none';
  });
}

window.addEventListener('DOMContentLoaded',loadRouteFromURL);
</script>
</body>
</html>
