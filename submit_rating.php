<?php
header("Access-Control-Allow-Origin: *");
include 'config.php';

$success_data = null;
$error        = '';

// Pre-fill from GET
$pf_ride_id   = isset($_GET['ride_id'])   ? (int)$_GET['ride_id']   : 0;
$pf_driver_id = isset($_GET['driver_id']) ? (int)$_GET['driver_id'] : 0;
$pf_rider_id  = isset($_GET['rider_id'])  ? (int)$_GET['rider_id']  : 0;

// ─── HANDLE POST ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ride_id   = isset($_POST['ride_id'])   ? (int)$_POST['ride_id']                 : 0;
    $rider_id  = isset($_POST['rider_id'])  ? (int)$_POST['rider_id']                : 0;
    $driver_id = isset($_POST['driver_id']) ? (int)$_POST['driver_id']               : 0;
    $rating    = isset($_POST['rating'])    ? (int)$_POST['rating']                  : 0;
    $feedback  = isset($_POST['feedback'])  ? sanitize($conn, $_POST['feedback'])     : '';

    if (!$ride_id || !$rider_id || !$driver_id) {
        $error = "Ride ID, Rider ID, and Driver ID are all required.";
    } elseif ($rating < 1 || $rating > 5) {
        $error = "Please select a star rating between 1 and 5.";
    } else {
        // Check ride completed
        $check = $conn->query("SELECT status FROM rides WHERE ride_id=$ride_id");
        if (!$check || $check->num_rows === 0) {
            $error = "Ride #$ride_id was not found.";
        } else {
            $rideRow = $check->fetch_assoc();
            if ($rideRow['status'] !== 'completed') {
                $error = "You can only rate a completed ride. Current status: " . htmlspecialchars($rideRow['status']);
            } else {
                // Duplicate check
                $dup = $conn->query("SELECT rating_id FROM ratings WHERE ride_id=$ride_id AND rider_id=$rider_id");
                if ($dup->num_rows > 0) {
                    $error = "You have already submitted a rating for Ride #$ride_id.";
                } else {
                    $sql = "INSERT INTO ratings (ride_id, rider_id, driver_id, rating, feedback)
                            VALUES ($ride_id, $rider_id, $driver_id, $rating, '$feedback')";
                    if ($conn->query($sql)) {
                        // Get updated driver stats
                        $avgSql = "SELECT 
                                    COUNT(rating_id)  AS total_ratings,
                                    ROUND(AVG(rating), 2) AS avg_rating,
                                    SUM(CASE WHEN rating=5 THEN 1 ELSE 0 END) AS five_star,
                                    SUM(CASE WHEN rating=4 THEN 1 ELSE 0 END) AS four_star,
                                    SUM(CASE WHEN rating=3 THEN 1 ELSE 0 END) AS three_star,
                                    SUM(CASE WHEN rating=2 THEN 1 ELSE 0 END) AS two_star,
                                    SUM(CASE WHEN rating=1 THEN 1 ELSE 0 END) AS one_star
                                   FROM ratings WHERE driver_id=$driver_id";
                        $avgRes = $conn->query($avgSql);
                        $avgRow = $avgRes->fetch_assoc();
                        $success_data = [
                            "ride_id"    => $ride_id,
                            "driver_id"  => $driver_id,
                            "your_stars" => $rating,
                            "feedback"   => $feedback,
                            "stats"      => $avgRow,
                        ];
                    } else {
                        $error = "Database error: " . $conn->error;
                    }
                }
            }
        }
    }
}

// ─── LOAD DRIVER RATING STATS ─────
$view_driver_id = isset($_GET['view_driver']) ? (int)$_GET['view_driver'] : 0;
$driver_stats   = null;

$stats_driver_id = $success_data ? $success_data['driver_id'] : $view_driver_id;
if ($stats_driver_id) {
    $statSql = "SELECT 
                    COUNT(rating_id) AS total_ratings,
                    ROUND(AVG(rating),2) AS avg_rating,
                    SUM(CASE WHEN rating=5 THEN 1 ELSE 0 END) AS five_star,
                    SUM(CASE WHEN rating=4 THEN 1 ELSE 0 END) AS four_star,
                    SUM(CASE WHEN rating=3 THEN 1 ELSE 0 END) AS three_star,
                    SUM(CASE WHEN rating=2 THEN 1 ELSE 0 END) AS two_star,
                    SUM(CASE WHEN rating=1 THEN 1 ELSE 0 END) AS one_star
                FROM ratings WHERE driver_id=$stats_driver_id";
    $statRes = $conn->query($statSql);
    $driver_stats = $statRes->fetch_assoc();
}

// Recent feedbacks for the driver
$recent_feedbacks = [];
if ($stats_driver_id) {
    $fbSql = "SELECT rt.rating, rt.feedback, rt.created_at,
                     rt.rider_id
              FROM ratings rt
              WHERE rt.driver_id=$stats_driver_id
                AND rt.feedback != ''
              ORDER BY rt.created_at DESC LIMIT 5";
    $fbRes = $conn->query($fbSql);
    while ($fbRow = $fbRes->fetch_assoc()) $recent_feedbacks[] = $fbRow;
}

function barWidth($count, $total) {
    if (!$total) return 0;
    return round(($count / $total) * 100);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rate Your Driver — RideShare</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --bg:      #0a0c10;
    --surface: #111318;
    --card:    #161b24;
    --border:  #1f2733;
    --accent:  #3b82f6;
    --gold:    #f59e0b;
    --green:   #10b981;
    --red:     #ef4444;
    --text:    #e2e8f0;
    --muted:   #64748b;
}

body {
    background: var(--bg);
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    min-height: 100vh;
    background-image:
        radial-gradient(ellipse at 50% 0%, rgba(245,158,11,0.06) 0%, transparent 55%),
        radial-gradient(ellipse at 80% 100%, rgba(59,130,246,0.05) 0%, transparent 55%);
}

/* ── HEADER ── */
.header {
    background: linear-gradient(135deg, #0f1621 0%, #111827 100%);
    border-bottom: 1px solid var(--border);
    padding: 0 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 68px;
    position: sticky;
    top: 0;
    z-index: 100;
}
.logo {
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 1.4rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.logo span { color: var(--gold); }
.nav-links { display: flex; gap: 1.5rem; list-style: none; }
.nav-links a {
    color: var(--muted);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    transition: color 0.2s;
    padding: 0.3rem 0;
    border-bottom: 2px solid transparent;
}
.nav-links a:hover, .nav-links a.active {
    color: var(--text);
    border-bottom-color: var(--gold);
}

/* ── PAGE ── */
.page {
    max-width: 1050px;
    margin: 0 auto;
    padding: 2.5rem 1.5rem 4rem;
}

.page-title {
    font-family: 'Syne', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 0.3rem;
    letter-spacing: -1px;
}
.page-title span { color: var(--gold); }
.page-sub { color: var(--muted); font-size: 0.92rem; margin-bottom: 2rem; }

.two-col {
    display: grid;
    grid-template-columns: 420px 1fr;
    gap: 2rem;
    align-items: start;
}
@media (max-width: 880px) {
    .two-col { grid-template-columns: 1fr; }
}

/* ── PANEL ── */
.panel {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
}
.panel-head {
    padding: 1.2rem 1.5rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 0.6rem;
}
.panel-head h2 {
    font-family: 'Syne', sans-serif;
    font-size: 1rem;
    font-weight: 700;
}
.panel-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.panel-icon.gold { background: rgba(245,158,11,0.15); }
.panel-icon.blue { background: rgba(59,130,246,0.15); }
.panel-body { padding: 1.5rem; }

/* ── FORM ── */
.form-group {
    margin-bottom: 1.1rem;
}
.form-group label {
    display: block;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 0.4rem;
}
.form-group input,
.form-group textarea {
    width: 100%;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 9px;
    color: var(--text);
    padding: 0.7rem 1rem;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.9rem;
    outline: none;
    transition: border-color 0.2s;
}
.form-group input:focus,
.form-group textarea:focus { border-color: var(--gold); }
.form-group textarea { resize: vertical; min-height: 90px; }

/* ── STAR RATING WIDGET ── */
.star-widget {
    display: flex;
    gap: 0.5rem;
    flex-direction: row-reverse;
    justify-content: flex-end;
    margin-bottom: 1.3rem;
}

.star-widget input { display: none; }

.star-widget label {
    font-size: 2.4rem;
    color: var(--border);
    cursor: pointer;
    transition: color 0.15s, transform 0.15s;
    line-height: 1;
}

/* Hover & checked styles (RTL trick with row-reverse) */
.star-widget label:hover,
.star-widget label:hover ~ label,
.star-widget input:checked ~ label {
    color: var(--gold);
    transform: scale(1.15);
}

.star-widget label:hover { transform: scale(1.2); }

.star-hint {
    font-size: 0.78rem;
    color: var(--muted);
    text-align: center;
    margin-bottom: 1rem;
    margin-top: -0.8rem;
    min-height: 1.2em;
    transition: all 0.2s;
}

/* ── SUBMIT BTN ── */
.rate-btn {
    width: 100%;
    background: linear-gradient(135deg, var(--gold), #d97706);
    color: #111;
    border: none;
    border-radius: 10px;
    padding: 0.85rem;
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 1rem;
    cursor: pointer;
    transition: opacity 0.2s, transform 0.1s;
    letter-spacing: 0.02em;
}
.rate-btn:hover  { opacity: 0.9; transform: translateY(-1px); }
.rate-btn:active { transform: translateY(0); }

/* ── ALERTS ── */
.alert {
    border-radius: 10px;
    padding: 1rem 1.2rem;
    margin-bottom: 1.2rem;
    font-size: 0.9rem;
}
.alert.success {
    background: rgba(245,158,11,0.08);
    border: 1px solid rgba(245,158,11,0.25);
    color: var(--gold);
}
.alert.error {
    background: rgba(239,68,68,0.08);
    border: 1px solid rgba(239,68,68,0.25);
    color: var(--red);
}

/* ── SUCCESS CARD ── */
.success-card {
    background: var(--card);
    border: 1px solid rgba(245,158,11,0.2);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    margin-top: 0.5rem;
}
.success-stars { font-size: 2rem; margin-bottom: 0.6rem; letter-spacing: 4px; }
.success-title {
    font-family: 'Syne', sans-serif;
    font-size: 1.1rem;
    font-weight: 800;
    margin-bottom: 0.3rem;
    color: var(--gold);
}
.success-sub { font-size: 0.85rem; color: var(--muted); }
.success-fb {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 0.8rem 1rem;
    margin-top: 1rem;
    font-size: 0.88rem;
    text-align: left;
    color: var(--muted);
    font-style: italic;
}

/* ── DRIVER STATS PANEL ── */
.avg-big {
    display: flex;
    align-items: flex-end;
    gap: 0.7rem;
    margin-bottom: 1rem;
}
.avg-num {
    font-family: 'Syne', sans-serif;
    font-size: 3.5rem;
    font-weight: 800;
    color: var(--gold);
    line-height: 1;
}
.avg-meta { padding-bottom: 0.4rem; }
.avg-stars { font-size: 1.2rem; letter-spacing: 2px; }
.avg-stars .s { color: var(--gold); }
.avg-stars .e { color: var(--border); }
.avg-total { font-size: 0.78rem; color: var(--muted); margin-top: 2px; }

/* ── BAR CHART ── */
.bar-list { display: flex; flex-direction: column; gap: 0.55rem; margin-bottom: 1.5rem; }
.bar-row { display: flex; align-items: center; gap: 0.7rem; }
.bar-label { font-size: 0.8rem; color: var(--muted); width: 24px; text-align: right; flex-shrink: 0; }
.bar-track { flex: 1; background: var(--card); border-radius: 999px; height: 8px; overflow: hidden; }
.bar-fill { height: 100%; border-radius: 999px; background: var(--gold); transition: width 0.8s ease; }
.bar-fill.low { background: var(--red); }
.bar-fill.mid { background: #f97316; }
.bar-count { font-size: 0.75rem; color: var(--muted); width: 20px; flex-shrink: 0; }

/* ── FEEDBACK LIST ── */
.fb-section-title {
    font-family: 'Syne', sans-serif;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--muted);
    margin-bottom: 0.7rem;
}
.fb-list { display: flex; flex-direction: column; gap: 0.8rem; }
.fb-item {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 0.9rem 1rem;
}
.fb-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.4rem;
}
.fb-rider { font-size: 0.75rem; color: var(--muted); }
.fb-stars-sm { font-size: 0.9rem; letter-spacing: 1px; }
.fb-stars-sm .s { color: var(--gold); }
.fb-stars-sm .e { color: var(--border); }
.fb-text { font-size: 0.86rem; color: var(--text); font-style: italic; }
.fb-date { font-size: 0.72rem; color: var(--muted); margin-top: 0.3rem; }

/* ── VIEW DRIVER FORM ── */
.lookup-bar {
    display: flex;
    gap: 0.7rem;
    align-items: flex-end;
    margin-bottom: 1.5rem;
}
.lookup-bar input {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    padding: 0.6rem 0.9rem;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.9rem;
    outline: none;
    flex: 1;
}
.lookup-bar input:focus { border-color: var(--gold); }
.lookup-btn {
    background: var(--gold);
    color: #111;
    border: none;
    border-radius: 8px;
    padding: 0.65rem 1.1rem;
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: 0.88rem;
    cursor: pointer;
    white-space: nowrap;
    transition: opacity 0.2s;
}
.lookup-btn:hover { opacity: 0.85; }

.divider { height: 1px; background: var(--border); margin: 1.2rem 0; }
</style>
</head>
<body>

<!-- HEADER -->
<header class="header">
    <div class="logo">🚗 <span>Ride</span>Share</div>
    <nav>
        <ul class="nav-links">
            <li><a href="ride_history.php">History</a></li>
            <li><a href="make_payment.php">Payment</a></li>
            <li><a href="submit_rating.php" class="active">Rating</a></li>
        </ul>
    </nav>
</header>

<div class="page">

    <h1 class="page-title">Rate Your <span>Driver</span></h1>
    <p class="page-sub">Share your experience and help others find great drivers.</p>

    <div class="two-col">

        <!-- ─── LEFT: RATING FORM ─── -->
        <div>
            <div class="panel">
                <div class="panel-head">
                    <div class="panel-icon gold">⭐</div>
                    <h2>Submit Rating</h2>
                </div>
                <div class="panel-body">

                    <?php if ($error): ?>
                        <div class="alert error">⚠ <?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <?php if ($success_data): ?>
                        <div class="alert success">✓ Your rating was submitted successfully!</div>
                        <div class="success-card">
                            <div class="success-stars">
                                <?= str_repeat('★', $success_data['your_stars']) . str_repeat('☆', 5 - $success_data['your_stars']) ?>
                            </div>
                            <div class="success-title">
                                <?php
                                $labels = [1=>'Needs Improvement',2=>'Fair',3=>'Good',4=>'Great!',5=>'Excellent!'];
                                echo $labels[$success_data['your_stars']] ?? '';
                                ?>
                            </div>
                            <div class="success-sub">
                                You rated Driver #<?= $success_data['driver_id'] ?> for Ride #<?= $success_data['ride_id'] ?>
                            </div>
                            <?php if ($success_data['feedback']): ?>
                                <div class="success-fb">"<?= htmlspecialchars($success_data['feedback']) ?>"</div>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top:1.2rem;">
                            <a href="ride_history.php"
                               style="display:block;text-align:center;color:var(--accent);text-decoration:none;font-size:0.9rem;font-weight:600;">
                               ← Back to Ride History
                            </a>
                        </div>

                    <?php else: ?>
                        <form method="POST" action="">

                            <!-- IDs row -->
                            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.7rem;">
                                <div class="form-group">
                                    <label>Ride ID</label>
                                    <input type="number" name="ride_id" placeholder="e.g. 5"
                                           value="<?= $pf_ride_id ?: '' ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Rider ID</label>
                                    <input type="number" name="rider_id" placeholder="e.g. 1"
                                           value="<?= $pf_rider_id ?: '' ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Driver ID</label>
                                    <input type="number" name="driver_id" placeholder="e.g. 2"
                                           value="<?= $pf_driver_id ?: '' ?>" required>
                                </div>
                            </div>

                            <!-- Star rating -->
                            <div class="form-group">
                                <label>Your Rating</label>
                            </div>

                            <div class="star-widget" id="starWidget">
                                <?php for ($s = 5; $s >= 1; $s--): ?>
                                    <input type="radio" name="rating" id="star<?= $s ?>" value="<?= $s ?>">
                                    <label for="star<?= $s ?>">★</label>
                                <?php endfor; ?>
                            </div>
                            <div class="star-hint" id="starHint">Click to rate</div>

                            <!-- Feedback -->
                            <div class="form-group">
                                <label>Feedback <span style="font-size:0.7rem;color:var(--muted);font-weight:400;">(optional)</span></label>
                                <textarea name="feedback" placeholder="How was your experience? Any comments for the driver..."></textarea>
                            </div>

                            <button type="submit" class="rate-btn">⭐ Submit Rating</button>
                        </form>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- ─── RIGHT: DRIVER STATS ─── -->
        <div>
            <div class="panel">
                <div class="panel-head">
                    <div class="panel-icon blue">📊</div>
                    <h2>Driver Rating Stats</h2>
                </div>
                <div class="panel-body">

                    <!-- Lookup form -->
                    <form method="GET" action="">
                        <?php if ($pf_ride_id):   ?><input type="hidden" name="ride_id"   value="<?= $pf_ride_id ?>">  <?php endif; ?>
                        <?php if ($pf_driver_id): ?><input type="hidden" name="driver_id" value="<?= $pf_driver_id ?>"><?php endif; ?>
                        <?php if ($pf_rider_id):  ?><input type="hidden" name="rider_id"  value="<?= $pf_rider_id ?>"> <?php endif; ?>
                        <div class="lookup-bar">
                            <input type="number" name="view_driver"
                                   placeholder="Driver ID to view stats"
                                   value="<?= $stats_driver_id ?: '' ?>">
                            <button type="submit" class="lookup-btn">🔍 Load</button>
                        </div>
                    </form>

                    <?php if ($driver_stats && $stats_driver_id): ?>

                        <?php
                        $avg   = round((float)$driver_stats['avg_rating'], 1);
                        $total = (int)$driver_stats['total_ratings'];
                        $full  = floor($avg);
                        $half  = ($avg - $full) >= 0.5 ? 1 : 0;
                        $empty = 5 - $full - $half;
                        ?>

                        <!-- Big Average -->
                        <div class="avg-big">
                            <div class="avg-num"><?= $avg ?: '—' ?></div>
                            <div class="avg-meta">
                                <div class="avg-stars">
                                    <?= str_repeat('<span class="s">★</span>', $full) ?>
                                    <?= $half ? '<span class="s" style="opacity:0.5;">★</span>' : '' ?>
                                    <?= str_repeat('<span class="e">☆</span>', $empty) ?>
                                </div>
                                <div class="avg-total">
                                    <?= $total ?> rating<?= $total !== 1 ? 's' : '' ?> · Driver #<?= $stats_driver_id ?>
                                </div>
                            </div>
                        </div>

                        <!-- Bar breakdown -->
                        <div class="bar-list">
                            <?php
                            $bars = [
                                5 => ['count' => (int)$driver_stats['five_star'],  'class' => ''],
                                4 => ['count' => (int)$driver_stats['four_star'],  'class' => ''],
                                3 => ['count' => (int)$driver_stats['three_star'], 'class' => 'mid'],
                                2 => ['count' => (int)$driver_stats['two_star'],   'class' => 'mid'],
                                1 => ['count' => (int)$driver_stats['one_star'],   'class' => 'low'],
                            ];
                            foreach ($bars as $star => $b):
                                $w = barWidth($b['count'], $total);
                            ?>
                            <div class="bar-row">
                                <span class="bar-label"><?= $star ?>★</span>
                                <div class="bar-track">
                                    <div class="bar-fill <?= $b['class'] ?>" style="width:<?= $w ?>%"></div>
                                </div>
                                <span class="bar-count"><?= $b['count'] ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (count($recent_feedbacks) > 0): ?>
                            <div class="divider"></div>
                            <div class="fb-section-title">Recent Feedback</div>
                            <div class="fb-list">
                                <?php foreach ($recent_feedbacks as $fb): ?>
                                <div class="fb-item">
                                    <div class="fb-header">
                                        <span class="fb-rider">Rider #<?= $fb['rider_id'] ?></span>
                                        <span class="fb-stars-sm">
                                            <?= str_repeat('<span class="s">★</span>', $fb['rating']) ?>
                                            <?= str_repeat('<span class="e">☆</span>', 5 - $fb['rating']) ?>
                                        </span>
                                    </div>
                                    <div class="fb-text">"<?= htmlspecialchars($fb['feedback']) ?>"</div>
                                    <div class="fb-date"><?= date('d M Y, h:i A', strtotime($fb['created_at'])) ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($total > 0): ?>
                            <div class="divider"></div>
                            <div style="text-align:center;padding:1rem;color:var(--muted);font-size:0.85rem;">
                                No written feedback yet.
                            </div>
                        <?php endif; ?>

                    <?php elseif ($stats_driver_id): ?>
                        <div style="text-align:center;padding:2rem;color:var(--muted);">
                            <div style="font-size:2.5rem;margin-bottom:0.5rem;">⭐</div>
                            <div style="font-weight:600;">No ratings yet</div>
                            <div style="font-size:0.82rem;margin-top:0.3rem;">Driver #<?= $stats_driver_id ?> has no ratings.</div>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center;padding:2.5rem;color:var(--muted);">
                            <div style="font-size:2.5rem;margin-bottom:0.5rem;">🔎</div>
                            <div style="font-size:0.88rem;">Enter a Driver ID above to see their rating breakdown.</div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

    </div><!-- /two-col -->
</div><!-- /page -->

<script>
// Dynamic star hint labels
const hints = {
    1: '😞 Poor — Needs Improvement',
    2: '😐 Fair',
    3: '🙂 Good',
    4: '😊 Great!',
    5: '🤩 Excellent!',
};

const radios = document.querySelectorAll('.star-widget input[type=radio]');
const hint   = document.getElementById('starHint');

radios.forEach(r => {
    r.addEventListener('change', () => {
        if (hints[r.value]) {
            hint.textContent = hints[r.value];
            hint.style.color = r.value >= 4 ? 'var(--gold)' : (r.value <= 2 ? 'var(--red)' : 'var(--muted)');
        }
    });
});
</script>
</body>
</html>