<?php
/**
 * Ojasva Events - Admin Dashboard (PHP + SQLite)
 * Displays leads directly from the 'leads' table in database.sqlite
 */

session_start();

// Security Guard: verify logged-in session
if (!isset($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/db.php';

$message = '';
$messageType = 'success';

// Handle Action: Export CSV (Excel compatible)
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $all = $pdo->query("SELECT * FROM leads ORDER BY id DESC")->fetchAll();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ojasva-leads.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    fputcsv($output, ['ID', 'Client Name', 'Phone', 'Email', 'Event Type', 'Date', 'Venue', 'Guests', 'Package', 'Budget (INR)', 'Details', 'Status', 'Submitted At']);
    foreach ($all as $row) {
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['phone'],
            $row['email'],
            $row['event_type'],
            $row['event_date'],
            $row['venue'],
            $row['guests'],
            $row['package'],
            $row['budget'],
            $row['details'],
            $row['status'],
            $row['created_at']
        ]);
    }
    fclose($output);
    exit;
}

// Handle Action: Delete Lead
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM leads WHERE id = :id");
    $stmt->execute([':id' => $delId]);
    $message = "Lead #$delId successfully deleted.";
}

// Handle Action: Toggle Status
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    $toggleId = (int)$_GET['id'];
    $stmt = $pdo->prepare("
        UPDATE leads 
        SET status = CASE WHEN status = 'Contacted' THEN 'New' ELSE 'Contacted' END 
        WHERE id = :id
    ");
    $stmt->execute([':id' => $toggleId]);
    header('Location: dashboard.php');
    exit;
}

// Handle Action: Manual Lead Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_manual') {
    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $event   = trim($_POST['event'] ?? '');
    $date    = trim($_POST['date'] ?? '');
    $venue   = trim($_POST['venue'] ?? '');
    $guests  = trim($_POST['guests'] ?? '');
    $package = trim($_POST['package'] ?? '');
    $budget  = trim($_POST['budget'] ?? '');
    $details = trim($_POST['details'] ?? '');

    if (!empty($name) && !empty($phone) && !empty($email) && !empty($event)) {
        $stmt = $pdo->prepare("
            INSERT INTO leads (name, phone, email, event_type, event_date, venue, guests, package, budget, details, status)
            VALUES (:name, :phone, :email, :event_type, :event_date, :venue, :guests, :package, :budget, :details, 'New')
        ");
        $stmt->execute([
            ':name'       => $name,
            ':phone'      => $phone,
            ':email'      => $email,
            ':event_type' => $event,
            ':event_date' => $date ?: null,
            ':venue'      => $venue ?: null,
            ':guests'     => $guests ?: null,
            ':package'    => $package ?: null,
            ':budget'     => $budget ?: null,
            ':details'    => $details ?: null,
        ]);
        $message = "New lead for '$name' added to SQLite database.";
    } else {
        $message = "Please fill in all required fields (Name, Phone, Email, Event Type).";
        $messageType = 'error';
    }
}

// Fetch Metrics from SQLite
$totalLeads     = (int)$pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
$weddingLeads   = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE LOWER(event_type) LIKE '%wedding%'")->fetchColumn();
$corporateLeads = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE LOWER(event_type) LIKE '%corporate%'")->fetchColumn();
$privateLeads   = max(0, $totalLeads - $weddingLeads - $corporateLeads);

// Search & Filter
$search = trim($_GET['search'] ?? '');
$filterEvent = trim($_GET['filter_event'] ?? 'ALL');

$sql = "SELECT * FROM leads WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (name LIKE :s OR phone LIKE :s OR email LIKE :s OR venue LIKE :s OR details LIKE :s)";
    $params[':s'] = "%$search%";
}

if ($filterEvent !== 'ALL' && !empty($filterEvent)) {
    $sql .= " AND event_type = :event";
    $params[':event'] = $filterEvent;
}

$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leadsList = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#29172a">
<title>Enquiry Dashboard | Ojasva Events</title>
<link rel="icon" href="assets/ojasva-monogram.png" type="image/png" sizes="256x256">
<link rel="stylesheet" href="assets/site.css">
<style>
.dash-body {
  background: #f7f2ed;
  color: var(--ink);
  min-height: 100vh;
}
.dash-header {
  background: var(--plum);
  color: #fff8f4;
  padding: 16px 0;
  border-bottom: 1px solid #4a284c;
}
.dash-header-wrap {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}
.dash-brand {
  display: inline-flex;
  align-items: center;
  gap: 12px;
}
.dash-brand img {
  width: 44px;
  height: 44px;
}
.dash-user {
  display: flex;
  align-items: center;
  gap: 14px;
  font-size: 0.84rem;
}
.dash-user code {
  color: var(--gold);
  background: #391c3b;
  padding: 4px 8px;
  border-radius: 4px;
}
.dash-content {
  padding: 34px 0 60px;
}
.metrics-row {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 18px;
  margin-bottom: 26px;
}
.metric-card {
  background: #fffaf6;
  border: 1px solid var(--border);
  padding: 22px;
}
.metric-card span {
  display: block;
  font-size: 0.72rem;
  letter-spacing: 0.12em;
  font-weight: 700;
  text-transform: uppercase;
  color: #7b4f5d;
  margin-bottom: 6px;
}
.metric-card strong {
  display: block;
  font-family: var(--serif);
  font-size: 2.6rem;
  line-height: 1;
  color: var(--plum);
}
.metric-card small {
  display: block;
  color: var(--muted);
  font-size: 0.78rem;
  margin-top: 6px;
}
.dash-panel {
  background: #fffaf6;
  border: 1px solid var(--border);
  padding: 24px;
}
.alert-banner {
  padding: 12px 16px;
  margin-bottom: 20px;
  border-left: 3px solid #8e4f62;
  font-size: 0.88rem;
  background: #f4eae4;
}
.alert-error {
  border-left-color: #c81e1e;
  background: #fde8e8;
  color: #9b1c1c;
}
.controls-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 20px;
}
.search-form {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  flex: 1;
  min-width: 280px;
}
.search-form input, .search-form select {
  padding: 8px 12px;
  border: 1px solid #cbbebc;
  background: white;
  font-size: 0.88rem;
}
.action-buttons {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.table-container {
  overflow-x: auto;
  border: 1px solid var(--border);
  background: white;
}
table.leads-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 0.88rem;
}
table.leads-table th {
  background: #2b172c;
  color: #faede6;
  font-size: 0.72rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  padding: 12px 14px;
  font-weight: 650;
  white-space: nowrap;
}
table.leads-table td {
  padding: 12px 14px;
  border-bottom: 1px solid #ebdcd5;
  vertical-align: top;
}
table.leads-table tr:hover td {
  background: #fdf6f0;
}
.status-pill {
  display: inline-block;
  padding: 3px 9px;
  border-radius: 12px;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  text-decoration: none;
}
.status-new {
  background: #ffe3e3;
  color: #9c1c1c;
  border: 1px solid #f8b4b4;
}
.status-contacted {
  background: #def7ec;
  color: #03543f;
  border: 1px solid #84e1bc;
}
.btn-action {
  background: none;
  border: 1px solid #d6c5be;
  padding: 5px 9px;
  cursor: pointer;
  font-size: 0.76rem;
  border-radius: 4px;
  color: var(--ink);
  display: inline-block;
}
.btn-action:hover {
  background: var(--plum);
  color: white;
}
.btn-delete {
  color: #9c1c1c;
}
.btn-delete:hover {
  background: #9c1c1c;
  color: white;
  border-color: #9c1c1c;
}
.drawer-form {
  background: #f3eae3;
  border: 1px solid var(--border);
  padding: 20px;
  margin-bottom: 24px;
}
.drawer-form h3 {
  font-size: 1.25rem;
  margin: 0 0 12px;
}
.drawer-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 12px;
}
.drawer-grid label {
  font-size: 0.8rem;
  font-weight: 700;
}
.drawer-grid input, .drawer-grid select, .drawer-form textarea {
  display: block;
  width: 100%;
  padding: 8px;
  border: 1px solid #c9b9b4;
  background: white;
  margin-top: 4px;
  font-size: 0.88rem;
}
.details-dialog {
  width: min(650px, calc(100% - 30px));
  background: #fffaf6;
  border: 1px solid var(--border);
  padding: 26px;
  color: var(--ink);
  box-shadow: 0 20px 45px rgba(25, 12, 27, 0.4);
}
.details-dialog::backdrop {
  background: rgba(22, 10, 24, 0.65);
}
.details-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  background: white;
  padding: 14px;
  border: 1px solid var(--border);
  margin: 16px 0;
  font-size: 0.88rem;
}
.details-msg {
  background: white;
  padding: 14px;
  border: 1px solid var(--border);
  font-size: 0.9rem;
  white-space: pre-wrap;
  max-height: 220px;
  overflow-y: auto;
  line-height: 1.6;
}
@media (max-width: 900px) {
  .metrics-row { grid-template-columns: 1fr 1fr; }
  .drawer-grid { grid-template-columns: 1fr; }
}
@media (max-width: 500px) {
  .metrics-row { grid-template-columns: 1fr; }
}
</style>
</head>
<body class="dash-body">

<header class="dash-header">
  <div class="wrap dash-header-wrap">
    <div class="dash-brand">
      <img src="assets/ojasva-monogram.png" alt="Ojasva Events">
      <div>
        <strong style="font-family:var(--serif);font-size:1.3rem;letter-spacing:0.08em;display:block;line-height:1">OJASVA EVENTS</strong>
        <span style="font-size:0.65rem;letter-spacing:0.2em;color:var(--gold);text-transform:uppercase">SQLite Lead Management Dashboard</span>
      </div>
    </div>

    <div class="dash-user">
      <a href="index.html" class="button outline" style="padding:7px 14px;min-height:36px;font-size:0.78rem">Home</a>
      <span>Logged in: <code><?= htmlspecialchars($_SESSION['admin_user']) ?></code></span>
      <a href="logout.php" class="button secondary" style="padding:7px 14px;min-height:36px;font-size:0.78rem">Logout</a>
    </div>
  </div>
</header>

<main class="dash-content">
  <div class="wrap">
    
    <!-- METRICS CARDS FROM SQLITE -->
    <div class="metrics-row">
      <div class="metric-card">
        <span>Total Enquiries</span>
        <strong><?= $totalLeads ?></strong>
        <small>Table: <code>leads</code> (database.sqlite)</small>
      </div>
      <div class="metric-card">
        <span>Weddings</span>
        <strong><?= $weddingLeads ?></strong>
        <small>Ceremonies &amp; Receptions</small>
      </div>
      <div class="metric-card">
        <span>Corporate</span>
        <strong><?= $corporateLeads ?></strong>
        <small>Conferences &amp; Offsites</small>
      </div>
      <div class="metric-card">
        <span>Celebrations</span>
        <strong><?= $privateLeads ?></strong>
        <small>Social &amp; Private Parties</small>
      </div>
    </div>

    <!-- MAIN PANEL -->
    <div class="dash-panel">
      
      <?php if (!empty($message)): ?>
        <div class="alert-banner <?= $messageType === 'error' ? 'alert-error' : '' ?>">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>

      <div class="controls-bar">
        <!-- Search & Filter Form -->
        <form method="GET" action="dashboard.php" class="search-form">
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, phone, email, venue...">
          <select name="filter_event">
            <option value="ALL">All Event Types</option>
            <option value="Wedding" <?= $filterEvent === 'Wedding' ? 'selected' : '' ?>>Weddings</option>
            <option value="Corporate event" <?= $filterEvent === 'Corporate event' ? 'selected' : '' ?>>Corporate events</option>
            <option value="Private celebration" <?= $filterEvent === 'Private celebration' ? 'selected' : '' ?>>Private celebrations</option>
            <option value="School or college event" <?= $filterEvent === 'School or college event' ? 'selected' : '' ?>>School / College</option>
            <option value="Festival or public event" <?= $filterEvent === 'Festival or public event' ? 'selected' : '' ?>>Festivals / Public</option>
            <option value="Other" <?= $filterEvent === 'Other' ? 'selected' : '' ?>>Other</option>
          </select>
          <button type="submit" class="button secondary" style="padding:8px 14px;min-height:38px;font-size:0.82rem">Filter</button>
          <?php if (!empty($search) || $filterEvent !== 'ALL'): ?>
            <a href="dashboard.php" class="button secondary" style="padding:8px 12px;min-height:38px;font-size:0.82rem">Reset</a>
          <?php endif; ?>
        </form>

        <div class="action-buttons">
          <button type="button" id="toggle-manual-btn" class="button secondary" style="padding:9px 14px;min-height:38px;font-size:0.82rem">
            + Add Enquiry Manually
          </button>
          <a href="dashboard.php?action=export_csv" class="button primary" style="padding:9px 14px;min-height:38px;font-size:0.82rem">
            Export CSV / Excel ↓
          </a>
        </div>
      </div>

      <!-- MANUAL ENTRY FORM DRAWER -->
      <div id="manual-drawer" class="drawer-form" hidden>
        <h3>Record Walk-in / Direct Call Lead</h3>
        <form method="POST" action="dashboard.php">
          <input type="hidden" name="action" value="add_manual">
          <div class="drawer-grid">
            <label>Client Name *<input name="name" required placeholder="Enter Client Name"></label>
            <label>Phone Number *<input type="tel" name="phone" required placeholder="Enter Phone Number"></label>
            <label>Email Address *<input type="email" name="email" required placeholder="Enter Email Address"></label>
            <label>Event Type *
              <select name="event" required>
                <option>Wedding</option>
                <option>Corporate event</option>
                <option>Private celebration</option>
                <option>School or college event</option>
                <option>Festival or public event</option>
                <option>Other</option>
              </select>
            </label>
            <label>Target Date<input type="date" name="date"></label>
            <label>City or Venue<input name="venue" placeholder="Enter City or Venue"></label>
            <label>Estimated Guests<input type="number" name="guests" placeholder="Enter Estimated Guests"></label>
            <label>Package
              <select name="package">
                <option value="">Custom</option>
                <option>Silver</option>
                <option>Gold</option>
                <option>Platinum</option>
              </select>
            </label>
            <label>Budget in ₹<input type="number" name="budget" placeholder="Enter Budget in ₹"></label>
          </div>
          <label style="display:block;margin-bottom:12px;font-size:0.8rem;font-weight:700">
            Notes / Event Details
            <textarea name="details" rows="2" placeholder="Specific requirements, venue discussions, catering..."></textarea>
          </label>
          <div style="display:flex;gap:8px;justify-content:flex-end">
            <button type="button" id="cancel-manual-btn" class="button secondary" style="padding:8px 16px;min-height:38px;font-size:0.82rem">Cancel</button>
            <button type="submit" class="button primary" style="padding:8px 18px;min-height:38px;font-size:0.82rem">Save to SQLite</button>
          </div>
        </form>
      </div>

      <!-- LEADS TABLE -->
      <div class="table-container">
        <table class="leads-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Client Name</th>
              <th>Contact</th>
              <th>Event Type</th>
              <th>Date &amp; Venue</th>
              <th>Guests / Pkg</th>
              <th>Budget (₹)</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($leadsList)): ?>
              <tr>
                <td colspan="9" style="text-align:center;padding:36px;color:var(--muted)">
                  No inquiries found matching your query.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($leadsList as $lead): ?>
                <?php 
                  $isContacted = ($lead['status'] === 'Contacted');
                  $statusClass = $isContacted ? 'status-contacted' : 'status-new';
                  $statusLabel = $isContacted ? '✓ Contacted' : '● New';
                  $dateFormatted = !empty($lead['created_at']) ? date('d M Y', strtotime($lead['created_at'])) : 'Recent';
                  $leadJson = htmlspecialchars(json_encode($lead), ENT_QUOTES, 'UTF-8');
                ?>
                <tr>
                  <td style="color:var(--muted);font-size:0.78rem">#<?= htmlspecialchars($lead['id']) ?></td>
                  <td>
                    <strong style="color:var(--plum);display:block"><?= htmlspecialchars($lead['name']) ?></strong>
                    <small style="color:var(--muted)"><?= $dateFormatted ?></small>
                  </td>
                  <td>
                    <a href="tel:<?= htmlspecialchars($lead['phone']) ?>" style="display:block;font-weight:600"><?= htmlspecialchars($lead['phone']) ?></a>
                    <a href="mailto:<?= htmlspecialchars($lead['email']) ?>" style="color:var(--muted);font-size:0.8rem"><?= htmlspecialchars($lead['email']) ?></a>
                  </td>
                  <td><strong><?= htmlspecialchars($lead['event_type']) ?></strong></td>
                  <td>
                    <div><?= !empty($lead['event_date']) ? htmlspecialchars($lead['event_date']) : 'Date TBD' ?></div>
                    <small style="color:var(--muted)"><?= !empty($lead['venue']) ? htmlspecialchars($lead['venue']) : 'No venue specified' ?></small>
                  </td>
                  <td>
                    <div><?= !empty($lead['guests']) ? htmlspecialchars($lead['guests']) . ' guests' : '—' ?></div>
                    <small style="color:#7b4f5d"><?= !empty($lead['package']) ? htmlspecialchars($lead['package']) : 'Custom' ?></small>
                  </td>
                  <td>
                    <?= !empty($lead['budget']) ? '₹ ' . number_format((float)$lead['budget']) : '—' ?>
                  </td>
                  <td>
                    <a href="dashboard.php?action=toggle_status&id=<?= $lead['id'] ?>" class="status-pill <?= $statusClass ?>" title="Click to toggle status">
                      <?= $statusLabel ?>
                    </a>
                  </td>
                  <td>
                    <div style="display:flex;gap:4px">
                      <button type="button" class="btn-action" onclick='viewDetails(<?= $leadJson ?>)'>View</button>
                      <a href="dashboard.php?action=delete&id=<?= $lead['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete lead #<?= $lead['name'] ?> from SQLite database?');">✕</a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>

  </div>
</main>

<!-- DETAILS DIALOG -->
<dialog id="lead-dialog" class="details-dialog">
  <div style="display:flex;justify-content:space-between;align-items:baseline">
    <span style="font-size:0.7rem;letter-spacing:0.12em;font-weight:700;color:#7b4f5d;text-transform:uppercase">Enquiry Brief</span>
    <form method="dialog"><button class="btn-action">Close ✕</button></form>
  </div>
  <h2 id="modal-name" style="font-family:var(--serif);font-size:1.8rem;margin:8px 0 2px;color:var(--plum)">Client Name</h2>
  <div style="font-size:0.78rem;color:var(--muted)" id="modal-time">Received: --</div>

  <div class="details-grid">
    <div><strong>Phone:</strong> <span id="modal-phone">--</span></div>
    <div><strong>Email:</strong> <span id="modal-email">--</span></div>
    <div><strong>Event:</strong> <span id="modal-event">--</span></div>
    <div><strong>Date:</strong> <span id="modal-date">--</span></div>
    <div><strong>Venue:</strong> <span id="modal-venue">--</span></div>
    <div><strong>Guests:</strong> <span id="modal-guests">--</span></div>
    <div><strong>Package:</strong> <span id="modal-package">--</span></div>
    <div><strong>Budget:</strong> <span id="modal-budget">--</span></div>
  </div>

  <label style="font-size:0.78rem;font-weight:700;text-transform:uppercase;color:var(--muted);display:block;margin-bottom:4px">Client Notes &amp; Ideas:</label>
  <div class="details-msg" id="modal-msg">No details provided.</div>

  <div style="display:flex;justify-content:flex-end;margin-top:20px;gap:8px">
    <form method="dialog"><button class="button secondary" style="padding:8px 16px;min-height:36px;font-size:0.8rem">Close</button></form>
  </div>
</dialog>

<script>
// Manual Drawer Toggle
const drawer = document.getElementById('manual-drawer');
document.getElementById('toggle-manual-btn').addEventListener('click', () => {
  drawer.hidden = !drawer.hidden;
});
document.getElementById('cancel-manual-btn').addEventListener('click', () => {
  drawer.hidden = true;
});

// View Details Modal
function viewDetails(lead) {
  document.getElementById('modal-name').textContent = lead.name || 'Anonymous';
  document.getElementById('modal-time').textContent = 'Created: ' + (lead.created_at || 'Recent');
  document.getElementById('modal-phone').textContent = lead.phone || '—';
  document.getElementById('modal-email').textContent = lead.email || '—';
  document.getElementById('modal-event').textContent = lead.event_type || '—';
  document.getElementById('modal-date').textContent = lead.event_date || 'Date TBD';
  document.getElementById('modal-venue').textContent = lead.venue || 'No venue';
  document.getElementById('modal-guests').textContent = lead.guests ? lead.guests + ' guests' : '—';
  document.getElementById('modal-package').textContent = lead.package || 'Custom';
  document.getElementById('modal-budget').textContent = lead.budget ? '₹ ' + Number(lead.budget).toLocaleString('en-IN') : '—';
  document.getElementById('modal-msg').textContent = lead.details || 'No additional details written.';

  document.getElementById('lead-dialog').showModal();
}
</script>

<footer class="dash-footer" style="background:#221323;color:#e8dbe0;padding:24px 0;margin-top:60px;border-top:1px solid rgba(255,255,255,.08);font-size:0.82rem;">
  <div class="wrap" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
    <p style="margin:0">© Ojasva Events Admin Portal</p>
    <p style="margin:0;color:var(--gold);font-weight:600">Developed by Digi Ritual</p>
  </div>
</footer>

</body>
</html>

