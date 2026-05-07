<?php

header("Access-Control-Allow-Origin: *");
include 'config.php';

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$role    = isset($_GET['role'])    ? sanitize($conn, $_GET['role']) : 'rider';
$rated   = isset($_GET['rated'])   ? (int)$_GET['rated'] : 0;

$stats  = [];
$recent = [];

if ($user_id) {
    $where = ($role === 'driver') ? "r.driver_id = $user_id" : "r.rider_id = $user_id";

    $statSql = "SELECT
                    COUNT(r.ride_id)                              AS total_rides,
                    SUM(CASE WHEN r.status='completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN r.status='cancelled' THEN 1 ELSE 0 END) AS cancelled,
                    COALESCE(SUM(r.fare), 0)                      AS total_fare,
                    COALESCE(SUM(p.amount), 0)                    AS total_paid,
                    ROUND(COALESCE(AVG(rt.rating), 0), 1)         AS avg_rating
                FROM rides r
                LEFT JOIN payments p  ON r.ride_id   = p.ride_id  AND p.payment_status = 'paid'
                LEFT JOIN ratings  rt ON r.driver_id = rt.driver_id
                WHERE $where";
    $statRes = $conn->query($statSql);
    $stats   = $statRes ? $statRes->fetch_assoc() : [];

    $pendSql = "SELECT COUNT(*) AS pending
                FROM rides r
                LEFT JOIN payments p ON r.ride_id = p.ride_id AND p.payment_status = 'paid'
                WHERE $where AND r.status = 'completed' AND p.payment_id IS NULL";
    $pendRes = $conn->query($pendSql);
    $pendRow = $pendRes ? $pendRes->fetch_assoc() : ['pending' => 0];
    $pending_payments = (int)$pendRow['pending'];

    $recSql = "SELECT r.ride_id, r.pickup_location, r.drop_location, r.fare,
                      r.status, r.created_at, r.driver_id, r.rider_id,
                      p.payment_status
               FROM rides r
               LEFT JOIN payments p ON r.ride_id = p.ride_id AND p.payment_status = 'paid'
               WHERE $where
               ORDER BY r.created_at DESC LIMIT 3";
    $recRes = $conn->query($recSql);
    while ($row = $recRes->fetch_assoc()) $recent[] = $row;
}

function statusColor($s) {
    switch(strtolower($s)) {
        case 'completed': return '#10b981';
        case 'ongoing':   return '#3b82f6';
        case 'accepted':  return '#06b6d4';
        case 'cancelled': return '#ef4444';
        default:          return '#f59e0b';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Dashboard — RideShare</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{--bg:#0a0c10;--surface:#111318;--card:#161b24;--border:#1f2733;--accent:#3b82f6;--accent2:#06b6d4;--gold:#f59e0b;--green:#10b981;--red:#ef4444;--text:#e2e8f0;--muted:#64748b;}
body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;min-height:100vh;background-image:radial-gradient(ellipse at 70% 0%,rgba(59,130,246,0.07) 0%,transparent 55%),radial-gradient(ellipse at 20% 100%,rgba(16,185,129,0.05) 0%,transparent 55%);}
.header{background:linear-gradient(135deg,#0f1621 0%,#111827 100%);border-bottom:1px solid var(--border);padding:0 2rem;display:flex;align-items:center;justify-content:space-between;height:68px;position:sticky;top:0;z-index:100;backdrop-filter:blur(12px);}
.logo{font-family:'Syne',sans-serif;font-weight:800;font-size:1.4rem;display:flex;align-items:center;gap:0.5rem;}
.logo span{color:var(--accent);}
.nav-links{display:flex;gap:1.5rem;list-style:none;}
.nav-links a{color:var(--muted);text-decoration:none;font-size:0.9rem;font-weight:500;padding:0.3rem 0;border-bottom:2px solid transparent;transition:color 0.2s;}
.nav-links a:hover,.nav-links a.active{color:var(--text);border-bottom-color:var(--accent);}
.page{max-width:1000px;margin:0 auto;padding:2.5rem 1.5rem 4rem;}
.page-title{font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;letter-spacing:-1px;margin-bottom:0.3rem;}
.page-title span{color:var(--accent);}
.page-sub{color:var(--muted);font-size:0.95rem;margin-bottom:2rem;}
.toast{background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:10px;padding:0.85rem 1.2rem;color:var(--green);font-size:0.9rem;font-weight:500;margin-bottom:1.8rem;display:flex;align-items:center;gap:0.6rem;}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:2rem;}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:1.2rem 1.3rem;position:relative;overflow:hidden;}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:14px 14px 0 0;}
.stat-card.blue::before{background:var(--accent);}
.stat-card.green::before{background:var(--green);}
.stat-card.gold::before{background:var(--gold);}
.stat-card.cyan::before{background:var(--accent2);}
.stat-label{font-size:0.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.5rem;}
.stat-value{font-family:'Syne',sans-serif;font-size:1.9rem;font-weight:800;line-height:1;}
.stat-card.blue .stat-value{color:var(--accent);}
.stat-card.green .stat-value{color:var(--green);}
.stat-card.gold .stat-value{color:var(--gold);}
.stat-card.cyan .stat-value{color:var(--accent2);}
.main-grid{display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start;}
.panel{background:var(--surface);border:1px solid var(--border);border-radius:16px;overflow:hidden;}
.panel-head{padding:1.2rem 1.5rem;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:0.6rem;}
.panel-head h2{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;}
.panel-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;}
.panel-icon.blue{background:rgba(59,130,246,0.15);}
.panel-icon.green{background:rgba(16,185,129,0.15);}
.panel-body{padding:1.4rem 1.5rem;}
.ride-item{display:flex;align-items:center;justify-content:space-between;padding:0.9rem 0;border-bottom:1px solid var(--border);gap:1rem;}
.ride-item:last-child{border-bottom:none;padding-bottom:0;}
.route-info{flex:1;min-width:0;}
.route-from,.route-to{display:flex;align-items:center;gap:6px;font-size:0.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.route-from{color:var(--green);margin-bottom:2px;}
.route-to{color:var(--red);}
.rdot{font-size:0.55rem;}
.ride-right{text-align:right;flex-shrink:0;}
.ride-fare{font-family:'Syne',sans-serif;font-weight:700;font-size:0.95rem;color:var(--green);}
.ride-status-dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:4px;flex-shrink:0;}
.ride-status-label{font-size:0.75rem;color:var(--muted);}
.view-all-link{display:block;text-align:center;margin-top:1.2rem;color:var(--accent);font-size:0.88rem;font-weight:600;text-decoration:none;padding:0.55rem;border:1px solid rgba(59,130,246,0.25);border-radius:8px;transition:background 0.2s;}
.view-all-link:hover{background:rgba(59,130,246,0.07);}
.actions-list{display:flex;flex-direction:column;gap:0.75rem;}
.action-card{display:flex;align-items:center;gap:1rem;padding:1rem 1.1rem;border-radius:12px;background:var(--card);border:1px solid var(--border);text-decoration:none;color:var(--text);transition:all 0.2s;}
.action-card:hover{border-color:var(--accent);background:rgba(59,130,246,0.05);}
.action-card.green-hover:hover{border-color:var(--green);background:rgba(16,185,129,0.05);}
.ac-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;}
.ac-icon.blue{background:rgba(59,130,246,0.15);}
.ac-icon.green{background:rgba(16,185,129,0.15);}
.ac-text{flex:1;}
.ac-title{font-family:'Syne',sans-serif;font-weight:700;font-size:0.92rem;margin-bottom:1px;}
.ac-sub{font-size:0.77rem;color:var(--muted);}
.ac-arrow{color:var(--muted);font-size:1rem;}
.badge{background:var(--red);color:#fff;border-radius:999px;padding:1px 7px;font-size:0.7rem;font-weight:700;margin-left:4px;}
</style>
</head>
<body>
<header class="header">
  <div class="logo">🚗 <span>Ride</span>Share</div>
  <nav>
    <ul class="nav-links">
      <li><a href="rider_dashboard.php?user_id=<?= $user_id ?>&role=<?= $role ?>" class="active">My Dashboard</a></li>
      <li><a href="ride_history.php?user_id=<?= $user_id ?>&role=<?= $role ?>">History</a></li>
      <li><a href="make_payment.php?rider_id=<?= $user_id ?>&role=<?= $role ?>">Payment</a></li>
      <li><a href="dashboard.php">Ops Dashboard</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </nav>
</header>
<div class="page">
  <h1 class="page-title">Your <span>Dashboard</span></h1>
  <p class="page-sub"><?= $user_id ? 'Welcome back, '.ucfirst($role).' #'.$user_id : 'Overview of your rides, payments, and ratings.' ?></p>
  <?php if ($rated): ?><div class="toast">⭐ Rating submitted successfully — thanks for your feedback!</div><?php endif; ?>
  <?php if ($user_id): ?>
  <div class="stats-grid">
    <div class="stat-card blue"><div class="stat-label">Total Rides</div><div class="stat-value"><?= (int)($stats['total_rides']??0) ?></div></div>
    <div class="stat-card green"><div class="stat-label">Completed</div><div class="stat-value"><?= (int)($stats['completed']??0) ?></div></div>
    <div class="stat-card gold"><div class="stat-label"><?= $role==='driver'?'Total Earned':'Total Spent' ?></div><div class="stat-value" style="font-size:1.4rem">৳<?= number_format((float)($stats['total_fare']??0),0) ?></div></div>
    <div class="stat-card cyan"><div class="stat-label">Avg Rating</div><div class="stat-value"><?= number_format((float)($stats['avg_rating']??0),1) ?> <small style="font-size:0.9rem;color:var(--muted)">/ 5</small></div></div>
  </div>
  <div class="main-grid">
    <div class="panel">
      <div class="panel-head"><div class="panel-icon blue">🚕</div><h2>Recent Trips</h2></div>
      <div class="panel-body">
        <?php if (!count($recent)): ?>
          <div style="text-align:center;padding:2rem;color:var(--muted)"><div style="font-size:2rem;margin-bottom:0.5rem">🛣️</div><div style="font-size:0.88rem">No rides yet.</div></div>
        <?php else: ?>
          <?php foreach ($recent as $r): ?>
          <div class="ride-item">
            <div class="route-info">
              <div class="route-from"><span class="rdot">🟢</span><?= htmlspecialchars(mb_strimwidth($r['pickup_location'],0,28,'…')) ?></div>
              <div class="route-to"><span class="rdot">🔴</span><?= htmlspecialchars(mb_strimwidth($r['drop_location'],0,28,'…')) ?></div>
            </div>
            <div class="ride-right">
              <div class="ride-fare">৳<?= number_format($r['fare'],2) ?></div>
              <div class="ride-status-label" style="margin-top:3px">
                <span class="ride-status-dot" style="background:<?= statusColor($r['status']) ?>"></span>
                <?= htmlspecialchars(ucfirst($r['status'])) ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
          <a href="ride_history.php?user_id=<?= $user_id ?>&role=<?= $role ?>" class="view-all-link">View All Rides →</a>
        <?php endif; ?>
      </div>
    </div>
    <div>
      <div class="panel">
        <div class="panel-head"><div class="panel-icon green">⚡</div><h2>Quick Actions</h2></div>
        <div class="panel-body">
          <div class="actions-list">
            <a href="ride_history.php?user_id=<?= $user_id ?>&role=<?= $role ?>" class="action-card">
              <div class="ac-icon blue">📋</div>
              <div class="ac-text"><div class="ac-title">Ride History</div><div class="ac-sub"><?= ucfirst($role) ?> ride records</div></div>
              <span class="ac-arrow">›</span>
            </a>
            <?php if ($role==='rider'): ?>
            <a href="make_payment.php?rider_id=<?= $user_id ?>&role=<?= $role ?>" class="action-card green-hover">
              <div class="ac-icon green">💳</div>
              <div class="ac-text">
                <div class="ac-title">Make Payment<?php if($pending_payments>0): ?><span class="badge"><?= $pending_payments ?></span><?php endif; ?></div>
                <div class="ac-sub"><?= $pending_payments>0 ? "$pending_payments ride(s) awaiting payment" : "No pending payments" ?></div>
              </div>
              <span class="ac-arrow">›</span>
            </a>
            <?php endif; ?>
            <a href="dashboard.php" class="action-card">
              <div class="ac-icon blue">📊</div>
              <div class="ac-text"><div class="ac-title">Ops Dashboard</div><div class="ac-sub">Request rides, manage fleet</div></div>
              <span class="ac-arrow">›</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php else: ?>
  <div style="text-align:center;padding:4rem 2rem;color:var(--muted)">
    <div style="font-size:3rem;margin-bottom:0.8rem">🚗</div>
    <h2 style="font-family:'Syne',sans-serif;color:var(--text);margin-bottom:0.4rem">No User Loaded</h2>
    <p>Please <a href="index.php" style="color:var(--accent)">log in</a> first.</p>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
