<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fare Calculator — RideShare</title>

<!-- Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<!-- Leaflet Map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
:root {
  --bg:      #0a0c10;
  --surface: #13161d;
  --card:    #1a1e28;
  --border:  #252a38;
  --accent:  #f5c518;
  --accent2: #ff6b35;
  --green:   #22c55e;
  --red:     #ef4444;
  --blue:    #3b82f6;
  --text:    #e8eaf0;
  --muted:   #6b7280;
  --font-h:  'Syne', sans-serif;
  --font-b:  'DM Sans', sans-serif;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; }
body {
  background: var(--bg);
  color: var(--text);
  font-family: var(--font-b);
  display: flex; flex-direction: column;
  min-height: 100vh;
  overflow-x: hidden;
}

/* grid bg */
body::before {
  content: '';
  position: fixed; inset: 0;
  background-image:
    linear-gradient(rgba(245,197,24,.025) 1px, transparent 1px),
    linear-gradient(90deg, rgba(245,197,24,.025) 1px, transparent 1px);
  background-size: 48px 48px;
  pointer-events: none; z-index: 0;
}

/* ── TOPBAR ── */
.topbar {
  display: flex; align-items: center; gap: 16px;
  padding: 16px 28px;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  position: relative; z-index: 200;
}
.logo { display:flex; align-items:center; gap:10px; text-decoration:none; }
.logo-icon { width:34px;height:34px;background:var(--accent);border-radius:9px;
  display:flex;align-items:center;justify-content:center;font-size:16px; }
.logo-text { font-family:var(--font-h);font-size:17px;font-weight:800; }
.logo-text span { color:var(--accent); }
.topbar-title { font-family:var(--font-h);font-size:14px;font-weight:700;
  color:var(--muted); margin-left:4px; }
.back-btn {
  margin-left:auto; display:flex;align-items:center;gap:6px;
  padding:8px 16px; border-radius:10px;
  background:var(--card); border:1px solid var(--border);
  color:var(--muted); font-size:13px; font-family:var(--font-h); font-weight:600;
  cursor:pointer; text-decoration:none; transition:all .18s;
}
.back-btn:hover { color:var(--accent); border-color:rgba(245,197,24,.3); }

/* ── LAYOUT ── */
.layout {
  display: grid;
  grid-template-columns: 400px 1fr;
  flex: 1;
  position: relative; z-index: 1;
  min-height: calc(100vh - 65px);
}

/* ── PANEL ── */
.panel {
  background: var(--surface);
  border-right: 1px solid var(--border);
  display: flex; flex-direction: column;
  overflow-y: auto;
  position: relative; z-index: 100;
}
.panel-inner { padding: 28px 24px; flex: 1; }

.panel-title {
  font-family: var(--font-h);
  font-size: 20px; font-weight: 800;
  margin-bottom: 6px;
}
.panel-sub { font-size: 13px; color: var(--muted); margin-bottom: 28px; }

/* ── INPUT FIELDS ── */
.input-group { position: relative; margin-bottom: 14px; }
.input-icon {
  position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
  font-size: 16px; z-index: 2; pointer-events: none;
}
.field-label {
  font-size: 11px; font-weight: 700; color: var(--muted);
  text-transform: uppercase; letter-spacing: .07em;
  margin-bottom: 6px; display: block;
}
.location-input {
  width: 100%;
  background: var(--card);
  border: 1.5px solid var(--border);
  border-radius: 12px;
  color: var(--text);
  font-family: var(--font-b); font-size: 14px;
  padding: 13px 16px 13px 42px;
  outline: none;
  transition: border-color .2s, box-shadow .2s;
}
.location-input:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(245,197,24,.1);
}
.location-input::placeholder { color: var(--muted); }

/* ── AUTOCOMPLETE DROPDOWN ── */
.autocomplete-list {
  position: absolute; left: 0; right: 0; top: calc(100% + 4px);
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 12px;
  overflow: hidden;
  z-index: 9999;
  box-shadow: 0 16px 40px rgba(0,0,0,.5);
  display: none;
  max-height: 200px; overflow-y: auto;
}
.autocomplete-list.open { display: block; animation: fadeDown .15s ease; }
@keyframes fadeDown { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:translateY(0)} }
.ac-item {
  padding: 11px 16px;
  font-size: 13px; color: var(--text);
  cursor: pointer; border-bottom: 1px solid rgba(37,42,56,.5);
  display: flex; align-items: flex-start; gap: 10px;
  transition: background .12s;
}
.ac-item:last-child { border-bottom: none; }
.ac-item:hover { background: rgba(245,197,24,.07); }
.ac-icon { font-size: 14px; margin-top: 1px; flex-shrink: 0; color: var(--muted); }
.ac-name { font-weight: 500; font-size: 13px; line-height: 1.3; }
.ac-addr { font-size: 11px; color: var(--muted); margin-top: 2px; }

/* ── SWAP BUTTON ── */
.swap-btn {
  display: flex; align-items: center; justify-content: center;
  width: 36px; height: 36px; border-radius: 10px;
  background: var(--card); border: 1px solid var(--border);
  color: var(--accent); font-size: 18px;
  cursor: pointer; margin: -4px auto 10px;
  transition: all .18s;
}
.swap-btn:hover { background: rgba(245,197,24,.1); transform: rotate(180deg); }

/* ── VEHICLE SELECTOR ── */
.vehicle-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; margin-bottom: 20px; }
.vehicle-opt {
  background: var(--card); border: 1.5px solid var(--border);
  border-radius: 12px; padding: 14px 10px;
  text-align: center; cursor: pointer;
  transition: all .18s;
}
.vehicle-opt:hover { border-color: rgba(245,197,24,.3); }
.vehicle-opt.selected { background: rgba(245,197,24,.1); border-color: var(--accent); }
.v-emoji { font-size: 26px; margin-bottom: 6px; }
.v-name { font-family: var(--font-h); font-size: 12px; font-weight: 700; color: var(--text); }
.v-rate { font-size: 11px; color: var(--muted); margin-top: 2px; }

/* ── ROUTE INFO ── */
.route-info {
  background: var(--card); border: 1px solid var(--border);
  border-radius: 14px; padding: 16px 18px;
  margin-bottom: 16px;
  display: none; animation: fadeUp .3s ease;
}
.route-info.show { display: flex; align-items: center; gap: 16px; }
@keyframes fadeUp { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
.route-stat { flex: 1; text-align: center; }
.rs-value { font-family: var(--font-h); font-size: 22px; font-weight: 800; color: var(--text); }
.rs-label { font-size: 11px; color: var(--muted); margin-top: 2px; text-transform: uppercase; letter-spacing: .06em; }
.rs-divider { width: 1px; height: 40px; background: var(--border); }

/* ── FARE CARD ── */
.fare-card {
  background: linear-gradient(135deg, rgba(245,197,24,.12), rgba(245,197,24,.04));
  border: 1.5px solid rgba(245,197,24,.25);
  border-radius: 16px; padding: 20px 22px;
  margin-bottom: 20px;
  display: none;
}
.fare-card.show { display: block; animation: fadeUp .3s ease; }
.fare-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.fare-title { font-family: var(--font-h); font-size: 13px; font-weight: 700; color: var(--muted); }
.fare-total { font-family: var(--font-h); font-size: 36px; font-weight: 800; color: var(--accent); }
.fare-rows { display: flex; flex-direction: column; gap: 7px; }
.fare-row { display: flex; justify-content: space-between; font-size: 13px; }
.fare-row .f-label { color: var(--muted); }
.fare-row .f-val { font-weight: 600; }
.fare-row.total { border-top: 1px solid rgba(245,197,24,.2); padding-top: 8px; margin-top: 4px; }
.fare-row.total .f-label { color: var(--text); font-weight: 700; }
.fare-row.total .f-val { color: var(--accent); font-size: 15px; }

/* ── BUTTONS ── */
.btn {
  width: 100%; padding: 14px; border-radius: 12px;
  font-family: var(--font-h); font-size: 14px; font-weight: 700;
  border: none; cursor: pointer; letter-spacing: .04em;
  transition: all .18s ease; display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-primary { background: var(--accent); color: #0a0c10; margin-bottom: 10px; }
.btn-primary:hover { background: #e6b800; box-shadow: 0 8px 24px rgba(245,197,24,.3); }
.btn-primary:disabled { opacity:.4; cursor:not-allowed; box-shadow:none; }
.btn-secondary { background: var(--card); color: var(--text); border: 1px solid var(--border); }
.btn-secondary:hover { border-color: var(--accent); color: var(--accent); }

/* ── LOADING SPINNER ── */
.spinner {
  width: 18px; height: 18px;
  border: 2px solid rgba(10,12,16,.3);
  border-top-color: #0a0c10;
  border-radius: 50%;
  animation: spin .6s linear infinite;
  display: none;
}
@keyframes spin { to { transform: rotate(360deg); } }
.btn-primary.loading .btn-label { display: none; }
.btn-primary.loading .spinner { display: block; }

/* ── MAP ── */
#map {
  width: 100%; height: 100%;
  background: #0d1117;
  min-height: 400px;
}
.leaflet-container { background: #0d1117 !important; }

/* ── STATUS MSG ── */
.status-msg {
  font-size: 13px; color: var(--muted);
  text-align: center; padding: 10px 0;
  display: none;
}
.status-msg.show { display: block; }
.status-msg.error { color: var(--red); }

/* ── SCROLLBAR ── */
::-webkit-scrollbar { width: 5px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

/* ── RESPONSIVE ── */
@media (max-width: 900px) {
  .layout { grid-template-columns: 1fr; grid-template-rows: auto 320px; }
  .panel { border-right: none; border-bottom: 1px solid var(--border); }
}
</style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
  <a class="logo" href="dashboard.php">
    <div class="logo-icon">🚗</div>
    <div class="logo-text">Ride<span>Share</span></div>
  </a>
  <span class="topbar-title">/ Fare Calculator</span>
  <a class="back-btn" href="dashboard.php">← Back to Dashboard</a>
</div>

<div class="layout">

  <!-- ── LEFT PANEL ─────────────────────────────── -->
  <div class="panel">
    <div class="panel-inner">
      <div class="panel-title">Calculate Fare</div>
      <div class="panel-sub">Type your locations — suggestions appear automatically</div>

      <!-- Pickup -->
      <label class="field-label">📍 Pickup Location</label>
      <div class="input-group">
        <span class="input-icon">🔵</span>
        <input class="location-input" type="text" id="pickup-input" placeholder="Search pickup location…" autocomplete="off">
        <div class="autocomplete-list" id="pickup-list"></div>
      </div>

      <!-- Swap -->
      <button class="swap-btn" id="swap-btn" title="Swap locations">⇅</button>

      <!-- Dropoff -->
      <label class="field-label">🏁 Drop-off Location</label>
      <div class="input-group">
        <span class="input-icon">🔴</span>
        <input class="location-input" type="text" id="dropoff-input" placeholder="Search destination…" autocomplete="off">
        <div class="autocomplete-list" id="dropoff-list"></div>
      </div>

      <!-- Vehicle -->
      <label class="field-label" style="margin-bottom:10px">Choose Vehicle</label>
      <div class="vehicle-row">
        <div class="vehicle-opt selected" data-type="car" onclick="selectVehicle(this)">
          <div class="v-emoji">🚗</div>
          <div class="v-name">Car</div>
          <div class="v-rate">৳50 + ৳15/km</div>
        </div>
        <div class="vehicle-opt" data-type="bike" onclick="selectVehicle(this)">
          <div class="v-emoji">🏍️</div>
          <div class="v-name">Bike</div>
          <div class="v-rate">৳30 + ৳8/km</div>
        </div>
        <div class="vehicle-opt" data-type="cng" onclick="selectVehicle(this)">
          <div class="v-emoji">🛺</div>
          <div class="v-name">CNG</div>
          <div class="v-rate">৳40 + ৳12/km</div>
        </div>
      </div>

      <!-- Calculate btn -->
      <button class="btn btn-primary" id="calc-btn" onclick="calculateRoute()" disabled>
        <span class="btn-label">🗺️ Calculate Route & Fare</span>
        <div class="spinner"></div>
      </button>

      <!-- Status msg -->
      <div class="status-msg" id="status-msg"></div>

      <!-- Route info -->
      <div class="route-info" id="route-info">
        <div class="route-stat">
          <div class="rs-value" id="ri-distance">—</div>
          <div class="rs-label">Distance</div>
        </div>
        <div class="rs-divider"></div>
        <div class="route-stat">
          <div class="rs-value" id="ri-duration">—</div>
          <div class="rs-label">Est. Time</div>
        </div>
        <div class="rs-divider"></div>
        <div class="route-stat">
          <div class="rs-value" id="ri-vehicle">🚗</div>
          <div class="rs-label">Vehicle</div>
        </div>
      </div>

      <!-- Fare breakdown -->
      <div class="fare-card" id="fare-card">
        <div class="fare-top">
          <div class="fare-title">ESTIMATED FARE</div>
        </div>
        <div class="fare-total" id="fare-total">৳ —</div>
        <br>
        <div class="fare-rows">
          <div class="fare-row"><span class="f-label">Base Fare</span><span class="f-val" id="f-base">—</span></div>
          <div class="fare-row"><span class="f-label">Distance Charge</span><span class="f-val" id="f-dist-charge">—</span></div>
          <div class="fare-row total"><span class="f-label">Total</span><span class="f-val" id="f-total-row">—</span></div>
        </div>
      </div>

      <!-- Use in ride request -->
      <button class="btn btn-primary" id="use-btn" style="display:none;margin-top:4px" onclick="useInRequest()">
        🚗 Use This Route — Request a Ride
      </button>

    </div>
  </div>

  <!-- ── MAP ───────────────────────────────────── -->
  <div id="map"></div>
</div>

<script>
// ── State ──────────────────────────────────────────────
const state = {
  pickup:  { text: '', lat: null, lon: null },
  dropoff: { text: '', lat: null, lon: null },
  vehicle: 'car',
  routeLayer: null,
  pickupMarker: null,
  dropoffMarker: null,
  distance: 0,
  duration: 0
};

const rates = {
  car:  { base: 50, km: 15, emoji: '🚗' },
  bike: { base: 30, km: 8,  emoji: '🏍️' },
  cng:  { base: 40, km: 12, emoji: '🛺' }
};

// ── Map init ────────────────────────────────────────────
const map = L.map('map', {
  center: [23.8103, 90.4125], // Dhaka
  zoom: 12,
  zoomControl: true
});

// Dark tile from CartoDB
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
  attribution: '© OpenStreetMap contributors © CARTO',
  subdomains: 'abcd', maxZoom: 19
}).addTo(map);

// Custom markers
function makeIcon(color) {
  return L.divIcon({
    html: `<div style="
      width:16px;height:16px;border-radius:50%;
      background:${color};
      border:3px solid #0a0c10;
      box-shadow:0 0 10px ${color}88;
    "></div>`,
    className: '', iconSize: [16, 16], iconAnchor: [8, 8]
  });
}
const pickupIcon  = makeIcon('#f5c518');
const dropoffIcon = makeIcon('#ef4444');

// ── Autocomplete ────────────────────────────────────────
let debounceTimer = {};

function setupAutocomplete(inputId, listId, which) {
  const input = document.getElementById(inputId);
  const list  = document.getElementById(listId);

  input.addEventListener('input', () => {
    clearTimeout(debounceTimer[which]);
    const q = input.value.trim();
    if (q.length < 3) { list.classList.remove('open'); return; }
    debounceTimer[which] = setTimeout(() => searchPlaces(q, list, which), 350);
  });

  input.addEventListener('blur', () => {
    setTimeout(() => list.classList.remove('open'), 200);
  });
}

async function searchPlaces(q, list, which) {
  try {
    // Bias to Dhaka area
    const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(q)}&format=json&limit=5&countrycodes=bd&accept-language=en`;
    const res = await fetch(url, { headers: { 'Accept-Language': 'en' } });
    const data = await res.json();

    list.innerHTML = '';
    if (!data.length) {
      list.innerHTML = '<div class="ac-item"><span class="ac-icon">🔍</span><div><div class="ac-name" style="color:var(--muted)">No results found</div></div></div>';
      list.classList.add('open');
      return;
    }

    data.forEach(place => {
      const name = place.display_name.split(',')[0];
      const addr = place.display_name.split(',').slice(1, 3).join(',').trim();
      const item = document.createElement('div');
      item.className = 'ac-item';
      item.innerHTML = `<span class="ac-icon">📍</span><div><div class="ac-name">${name}</div><div class="ac-addr">${addr}</div></div>`;
      item.addEventListener('mousedown', () => {
        selectPlace(which, place.display_name.split(',').slice(0,2).join(',').trim(), parseFloat(place.lat), parseFloat(place.lon));
        document.getElementById(which === 'pickup' ? 'pickup-input' : 'dropoff-input').value =
          place.display_name.split(',').slice(0,2).join(',').trim();
        list.classList.remove('open');
      });
      list.appendChild(item);
    });
    list.classList.add('open');
  } catch(e) {
    console.error('Geocode error', e);
  }
}

function selectPlace(which, name, lat, lon) {
  state[which] = { text: name, lat, lon };

  // Update / add marker
  if (which === 'pickup') {
    if (state.pickupMarker) map.removeLayer(state.pickupMarker);
    state.pickupMarker = L.marker([lat, lon], { icon: pickupIcon })
      .addTo(map).bindPopup(`<b>Pickup:</b> ${name}`);
  } else {
    if (state.dropoffMarker) map.removeLayer(state.dropoffMarker);
    state.dropoffMarker = L.marker([lat, lon], { icon: dropoffIcon })
      .addTo(map).bindPopup(`<b>Drop-off:</b> ${name}`);
  }

  // Fly to location
  map.flyTo([lat, lon], 13, { duration: 1 });

  // If both selected, fit bounds
  if (state.pickup.lat && state.dropoff.lat) {
    map.flyToBounds([
      [state.pickup.lat, state.pickup.lon],
      [state.dropoff.lat, state.dropoff.lon]
    ], { padding: [60, 60], duration: 1.2 });
    document.getElementById('calc-btn').disabled = false;
  }
}

// ── Swap ───────────────────────────────────────────────
document.getElementById('swap-btn').onclick = () => {
  const pi = document.getElementById('pickup-input');
  const di = document.getElementById('dropoff-input');
  [pi.value, di.value] = [di.value, pi.value];
  [state.pickup, state.dropoff] = [state.dropoff, state.pickup];

  if (state.pickup.lat)  {
    if (state.pickupMarker) map.removeLayer(state.pickupMarker);
    state.pickupMarker = L.marker([state.pickup.lat, state.pickup.lon], { icon: pickupIcon })
      .addTo(map);
  }
  if (state.dropoff.lat) {
    if (state.dropoffMarker) map.removeLayer(state.dropoffMarker);
    state.dropoffMarker = L.marker([state.dropoff.lat, state.dropoff.lon], { icon: dropoffIcon })
      .addTo(map);
  }
};

// ── Vehicle select ──────────────────────────────────────
function selectVehicle(el) {
  document.querySelectorAll('.vehicle-opt').forEach(v => v.classList.remove('selected'));
  el.classList.add('selected');
  state.vehicle = el.dataset.type;
  if (state.distance > 0) renderFare(state.distance);
}

// ── Calculate Route ─────────────────────────────────────
async function calculateRoute() {
  if (!state.pickup.lat || !state.dropoff.lat) return;

  const btn = document.getElementById('calc-btn');
  btn.classList.add('loading');
  btn.disabled = true;
  showStatus('Calculating route…', '');

  // Remove old route
  if (state.routeLayer) { map.removeLayer(state.routeLayer); state.routeLayer = null; }

  try {
    // Use OSRM (completely free, no key needed)
    const url = `https://router.project-osrm.org/route/v1/driving/` +
      `${state.pickup.lon},${state.pickup.lat};${state.dropoff.lon},${state.dropoff.lat}` +
      `?overview=full&geometries=geojson`;

    const res = await fetch(url);
    const data = await res.json();

    if (data.code !== 'Ok' || !data.routes.length) {
      showStatus('Could not find a route between these locations.', 'error');
      btn.classList.remove('loading'); btn.disabled = false;
      return;
    }

    const route = data.routes[0];
    const distKm   = (route.distance / 1000).toFixed(2);
    const durMins  = Math.ceil(route.duration / 60);
    state.distance = parseFloat(distKm);
    state.duration = durMins;

    // Draw route
    state.routeLayer = L.geoJSON(route.geometry, {
      style: { color: '#f5c518', weight: 4, opacity: .85, dashArray: null }
    }).addTo(map);
    map.fitBounds(state.routeLayer.getBounds(), { padding: [60, 60] });

    // Update UI
    document.getElementById('ri-distance').textContent = distKm + ' km';
    document.getElementById('ri-duration').textContent = durMins + ' min';
    document.getElementById('ri-vehicle').textContent = rates[state.vehicle].emoji;
    document.getElementById('route-info').classList.add('show');

    renderFare(state.distance);
    showStatus('', '');

    document.getElementById('use-btn').style.display = 'flex';

  } catch(e) {
    showStatus('Network error. Check your connection.', 'error');
  } finally {
    btn.classList.remove('loading');
    btn.disabled = false;
  }
}

// ── Render Fare ─────────────────────────────────────────
function renderFare(distKm) {
  const r    = rates[state.vehicle];
  const dist_charge = (distKm * r.km).toFixed(2);
  const total       = (r.base + parseFloat(dist_charge)).toFixed(2);

  document.getElementById('fare-total').textContent    = '৳' + total;
  document.getElementById('f-base').textContent        = '৳' + r.base;
  document.getElementById('f-dist-charge').textContent = `৳${dist_charge} (${distKm}km × ৳${r.km})`;
  document.getElementById('f-total-row').textContent   = '৳' + total;
  document.getElementById('fare-card').classList.add('show');
}

// ── Status message ──────────────────────────────────────
function showStatus(msg, type) {
  const el = document.getElementById('status-msg');
  el.textContent = msg;
  el.className = 'status-msg' + (msg ? ' show' : '') + (type === 'error' ? ' error' : '');
}

// ── Use in ride request ──────────────────────────────────
function useInRequest() {
  const params = new URLSearchParams({
    pickup:   state.pickup.text,
    dropoff:  state.dropoff.text,
    distance: state.distance,
    vehicle:  state.vehicle
  });
  window.location.href = 'dashboard.php?tab=request&' + params.toString();
}

// ── Init autocomplete ───────────────────────────────────
setupAutocomplete('pickup-input',  'pickup-list',  'pickup');
setupAutocomplete('dropoff-input', 'dropoff-list', 'dropoff');
</script>
</body>
</html>