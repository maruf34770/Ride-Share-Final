<?php
header("Access-Control-Allow-Origin: *");
include 'config.php';

$success_data = null;
$error        = '';
$ride_info    = null;
$history      = [];

// ─── GET rider_id and role from URL ─────────────────────────────────────────
$current_rider_id  = isset($_GET['rider_id'])  ? (int)$_GET['rider_id']  : 0;
$current_role      = isset($_GET['role'])       ? sanitize($conn, $_GET['role']) : 'rider';

// ─── AUTO-FETCH the most recent completed & unpaid ride ─────────────────────
$prefill_ride_id = 0;

if ($current_rider_id) {
    // Find the most recent completed ride for this rider that has no paid payment yet
    $autoQ = "SELECT r.ride_id, r.fare, r.pickup_location, r.drop_location,
                     r.status, r.rider_id, r.driver_id
              FROM rides r
              LEFT JOIN payments p ON r.ride_id = p.ride_id AND p.payment_status = 'paid'
              WHERE r.rider_id = $current_rider_id
                AND r.status = 'completed'
                AND p.payment_id IS NULL
              ORDER BY r.created_at DESC
              LIMIT 1";
    $autoRes = $conn->query($autoQ);
    if ($autoRes && $autoRes->num_rows > 0) {
        $ride_info       = $autoRes->fetch_assoc();
        $prefill_ride_id = $ride_info['ride_id'];
    }
}

// ─── HANDLE POST ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ride_id       = isset($_POST['ride_id'])          ? (int)$_POST['ride_id']                 : 0;
    $method        = isset($_POST['method'])           ? sanitize($conn, $_POST['method'])       : '';
    $post_rider_id = isset($_POST['current_rider_id']) ? (int)$_POST['current_rider_id']         : 0;
    $post_role     = isset($_POST['current_role'])     ? sanitize($conn, $_POST['current_role']) : 'rider';

    if (!$ride_id || !$method) {
        $error = "Ride ID and payment method are required.";
    } else {
        $allowed = ['cash', 'wallet', 'card'];
        if (!in_array(strtolower($method), $allowed)) {
            $error = "Invalid payment method selected.";
        } else {
            // Duplicate check
            $dupRes = $conn->query("SELECT payment_id FROM payments WHERE ride_id=$ride_id AND payment_status='paid'");
            if ($dupRes->num_rows > 0) {
                $error = "Payment has already been made for Ride #$ride_id.";
            } else {
                // Get fare
                $fareRes = $conn->query(
                    "SELECT r.fare, r.pickup_location, r.drop_location, r.rider_id, r.driver_id
                     FROM rides r WHERE r.ride_id=$ride_id AND r.status='completed'"
                );
                if (!$fareRes || $fareRes->num_rows === 0) {
                    $error = "Ride #$ride_id not found or not yet completed.";
                } else {
                    $row    = $fareRes->fetch_assoc();
                    $amount = $row['fare'];
                    $sql    = "INSERT INTO payments (ride_id, amount, payment_method, payment_status)
                               VALUES ($ride_id, $amount, '$method', 'paid')";
                    if ($conn->query($sql)) {
                        // ── Redirect straight to rating page ──────────────────
                        $rid = $row['rider_id'];
                        $did = $row['driver_id'];
                        header("Location: submit_rating.php?ride_id=$ride_id&driver_id=$did&rider_id=$rid&role=$post_role");
                        exit();
                    } else {
                        $error = "Database error: " . $conn->error;
                    }
                }
            }
        }
    }
}

// ─── (Payment history removed — moved to ride_history.php) ──────────────────

function methodIcon($m) {
    switch(strtolower($m)) {
        case 'cash':   return '💵';
        case 'wallet': return '👛';
        case 'card':   return '💳';
        default:       return '💰';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment — RideShare</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --bg:        #0a0c10;
    --surface:   #111318;
    --card:      #161b24;
    --border:    #1f2733;
    --accent:    #3b82f6;
    --accent2:   #06b6d4;
    --gold:      #f59e0b;
    --green:     #10b981;
    --red:       #ef4444;
    --text:      #e2e8f0;
    --muted:     #64748b;
    --subtle:    #1e2736;
}

body {
    background: var(--bg);
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    min-height: 100vh;
    background-image:
        radial-gradient(ellipse at 80% 0%, rgba(16,185,129,0.07) 0%, transparent 55%),
        radial-gradient(ellipse at 20% 100%, rgba(59,130,246,0.05) 0%, transparent 55%);
}

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
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.logo span { color: var(--green); }

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
    border-bottom-color: var(--green);
}

.page {
    max-width: 1100px;
    margin: 0 auto;
    padding: 2.5rem 1.5rem 4rem;
}

.two-col {
    display: grid;
    grid-template-columns: 400px 1fr;
    gap: 2rem;
    align-items: start;
}

@media (max-width: 860px) {
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
    padding: 1.3rem 1.5rem;
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

.panel-icon.green { background: rgba(16,185,129,0.15); }
.panel-icon.blue  { background: rgba(59,130,246,0.15); }

.panel-body { padding: 1.5rem; }

/* ── RIDE PREVIEW ── */
.ride-preview {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 1rem 1.2rem;
    margin-bottom: 1.2rem;
}

.rp-label {
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 0.8rem;
}

.rp-route {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 1rem;
}

.rp-loc {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.88rem;
}

.dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.dot.from { background: var(--green); }
.dot.to   { background: var(--red); }
.conn-line { width: 1px; height: 12px; background: var(--border); margin-left: 4.5px; }

.rp-fare {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 0.8rem;
    border-top: 1px solid var(--border);
}

.rp-fare-label { font-size: 0.8rem; color: var(--muted); }
.rp-fare-amt {
    font-family: 'Syne', sans-serif;
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--green);
}

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
    margin-bottom: 0.45rem;
}

.form-group input {
    width: 100%;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 9px;
    color: var(--text);
    padding: 0.7rem 1rem;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.92rem;
    outline: none;
    transition: border-color 0.2s;
}

.form-group input:focus { border-color: var(--green); }

/* ── PAYMENT METHOD SELECTOR ── */
.method-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.7rem;
    margin-bottom: 1.3rem;
}

.method-option { display: none; }

.method-option + label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 0.9rem 0.5rem;
    border-radius: 10px;
    border: 2px solid var(--border);
    background: var(--card);
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}

.method-option + label .m-icon { font-size: 1.6rem; }
.method-option + label .m-name {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.method-option:checked + label {
    border-color: var(--green);
    background: rgba(16,185,129,0.1);
    color: var(--green);
}

.method-option:checked + label .m-name { color: var(--green); }

.method-option + label:hover {
    border-color: rgba(16,185,129,0.4);
    background: rgba(16,185,129,0.05);
}

/* ── SUBMIT BTN ── */
.pay-btn {
    width: 100%;
    background: var(--green);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 0.85rem;
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.2s, transform 0.1s;
    letter-spacing: 0.03em;
}
.pay-btn:hover  { background: #059669; transform: translateY(-1px); }
.pay-btn:active { transform: translateY(0); }

/* ── ALERTS ── */
.alert {
    border-radius: 10px;
    padding: 1rem 1.2rem;
    margin-bottom: 1.2rem;
    font-size: 0.9rem;
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
}
.alert.success {
    background: rgba(16,185,129,0.08);
    border: 1px solid rgba(16,185,129,0.25);
    color: var(--green);
}
.alert.error {
    background: rgba(239,68,68,0.08);
    border: 1px solid rgba(239,68,68,0.25);
    color: var(--red);
}

/* ── SUCCESS RECEIPT ── */
.receipt {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    margin-top: 1rem;
}
.receipt-header {
    background: linear-gradient(135deg, #064e3b, #065f46);
    padding: 1.2rem 1.4rem;
    text-align: center;
}
.receipt-header .tick {
    font-size: 2.5rem;
    margin-bottom: 0.4rem;
}
.receipt-header h3 {
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 1.1rem;
    color: #a7f3d0;
}
.receipt-header p { font-size: 0.8rem; color: rgba(167,243,208,0.7); margin-top: 0.2rem; }

.receipt-body { padding: 1.2rem; }

.receipt-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.55rem 0;
    border-bottom: 1px solid rgba(31,39,51,0.5);
    font-size: 0.87rem;
}
.receipt-row:last-child { border-bottom: none; }
.receipt-row .rr-label { color: var(--muted); }
.receipt-row .rr-val   { font-weight: 600; }
.receipt-row.highlight .rr-val {
    font-family: 'Syne', sans-serif;
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--green);
}

/* ── HISTORY TABLE ── */
.page-title {
    font-family: 'Syne', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 0.3rem;
    letter-spacing: -1px;
}
.page-title span { color: var(--green); }
.page-sub { color: var(--muted); font-size: 0.92rem; margin-bottom: 2rem; }

.hist-wrapper {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    overflow: hidden;
    margin-top: 2.5rem;
}

.hist-head {
    padding: 1.1rem 1.4rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.hist-head h3 { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 0.95rem; }
.badge {
    background: rgba(16,185,129,0.1);
    color: var(--green);
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.2rem 0.65rem;
    border-radius: 999px;
    border: 1px solid rgba(16,185,129,0.2);
}

.hist-table { width: 100%; border-collapse: collapse; }
.hist-table th {
    background: var(--card);
    padding: 0.75rem 1rem;
    text-align: left;
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    border-bottom: 1px solid var(--border);
}
.hist-table td {
    padding: 0.9rem 1rem;
    font-size: 0.86rem;
    border-bottom: 1px solid rgba(31,39,51,0.6);
    vertical-align: middle;
}
.hist-table tr:last-child td { border-bottom: none; }
.hist-table tbody tr:hover { background: rgba(16,185,129,0.03); }

.paid-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 0.2rem 0.55rem;
    border-radius: 6px;
    font-size: 0.75rem; font-weight: 700;
    background: rgba(16,185,129,0.1);
    color: var(--green);
    border: 1px solid rgba(16,185,129,0.2);
}

/* filter for history */
.hist-filter {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1rem 1.2rem;
    display: flex;
    gap: 0.8rem;
    align-items: flex-end;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}
.hist-filter input {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    padding: 0.6rem 0.9rem;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.9rem;
    outline: none;
    flex: 1;
    min-width: 140px;
}
.hist-filter input:focus { border-color: var(--green); }
.hist-filter .hf-label {
    font-size: 0.7rem; font-weight: 700; color: var(--muted);
    text-transform: uppercase; letter-spacing: 0.07em;
    display: block; margin-bottom: 4px;
}
.hist-btn {
    background: var(--green);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 0.65rem 1.2rem;
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: 0.88rem;
    cursor: pointer;
    white-space: nowrap;
    align-self: flex-end;
    transition: background 0.2s;
}
.hist-btn:hover { background: #059669; }
</style>
</head>
<body>

<!-- HEADER -->
<header class="header">
    <div class="logo">🚗 <span>Ride</span>Share</div>
    <nav>
        <ul class="nav-links">
            <li><a href="dashboard.php?user_id=<?= $current_rider_id ?>&role=<?= $current_role ?>">Dashboard</a></li>
            <li><a href="ride_history.php?user_id=<?= $current_rider_id ?>&role=<?= $current_role ?>">History</a></li>
            <li><a href="make_payment.php?rider_id=<?= $current_rider_id ?>&role=<?= $current_role ?>" class="active">Payment</a></li>
        </ul>
    </nav>
</header>

<div class="page">

    <h1 class="page-title">Make a <span>Payment</span></h1>
    <p class="page-sub">Settle your fare quickly and securely.</p>

    <div class="two-col">

        <!-- ─── LEFT: PAYMENT FORM ─── -->
        <div>
            <div class="panel">
                <div class="panel-head">
                    <div class="panel-icon green">💳</div>
                    <h2>Payment Details</h2>
                </div>
                <div class="panel-body">

                    <?php if ($error): ?>
                        <div class="alert error">⚠ <?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <?php if ($ride_info): ?>

                        <form method="POST" action="">
                            <!-- Hidden fields: ride_id, rider_id, role auto-passed -->
                            <input type="hidden" name="ride_id"          value="<?= $prefill_ride_id ?>">
                            <input type="hidden" name="current_rider_id" value="<?= $current_rider_id ?>">
                            <input type="hidden" name="current_role"     value="<?= htmlspecialchars($current_role) ?>">

                            <!-- Auto-loaded ride preview -->
                            <div class="ride-preview">
                                <div class="rp-label">Your Latest Ride · #<?= $ride_info['ride_id'] ?></div>
                                <div class="rp-route">
                                    <div class="rp-loc">
                                        <span class="dot from"></span>
                                        <?= htmlspecialchars($ride_info['pickup_location']) ?>
                                    </div>
                                    <div class="conn-line"></div>
                                    <div class="rp-loc">
                                        <span class="dot to"></span>
                                        <?= htmlspecialchars($ride_info['drop_location']) ?>
                                    </div>
                                </div>
                                <div class="rp-fare">
                                    <span class="rp-fare-label">Total Fare</span>
                                    <span class="rp-fare-amt">৳<?= number_format($ride_info['fare'], 2) ?></span>
                                </div>
                            </div>

                            <!-- Payment Method -->
                            <div class="form-group">
                                <label>Payment Method</label>
                            </div>
                            <div class="method-grid">
                                <div>
                                    <input type="radio" name="method" value="cash" id="m_cash" class="method-option" checked>
                                    <label for="m_cash">
                                        <span class="m-icon">💵</span>
                                        <span class="m-name">Cash</span>
                                    </label>
                                </div>
                                <div>
                                    <input type="radio" name="method" value="wallet" id="m_wallet" class="method-option">
                                    <label for="m_wallet">
                                        <span class="m-icon">👛</span>
                                        <span class="m-name">Wallet</span>
                                    </label>
                                </div>
                                <div>
                                    <input type="radio" name="method" value="card" id="m_card" class="method-option">
                                    <label for="m_card">
                                        <span class="m-icon">💳</span>
                                        <span class="m-name">Card</span>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="pay-btn">💳 Confirm Payment</button>
                        </form>

                    <?php else: ?>
                        <!-- No unpaid completed ride found -->
                        <div style="text-align:center;padding:2.5rem 1rem;">
                            <div style="font-size:2.5rem;margin-bottom:0.8rem;">✅</div>
                            <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:1.05rem;margin-bottom:0.4rem;">No Pending Payments</div>
                            <div style="color:var(--muted);font-size:0.88rem;margin-bottom:1.5rem;">You have no completed rides awaiting payment.</div>
                            <a href="ride_history.php?user_id=<?= $current_rider_id ?>&role=<?= $current_role ?>"
                               style="display:inline-block;background:var(--green);color:#fff;text-decoration:none;padding:0.65rem 1.4rem;border-radius:9px;font-family:'Syne',sans-serif;font-weight:700;font-size:0.9rem;">
                               📋 View Ride History
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div><!-- /two-col -->

</div><!-- /page -->
</body>
</html>