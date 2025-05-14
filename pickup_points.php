<?php
session_start();
require_once "config/database.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
// Fetch all pickup points
$points = [];
$result = $conn->query("SELECT * FROM pickup_points ORDER BY id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $points[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pickup Points - EcoMap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        #pickupMap { height: 500px; width: 100%; border-radius: 10px; margin-bottom: 24px; }
        .leaflet-container { z-index: 1; }
        .see-more-badge {
            display: inline-block;
            background: #f1f1f1;
            color: #333;
            border-radius: 12px;
            padding: 2px 10px;
            font-size: 0.95em;
            margin-left: 6px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
            font-variant: small-caps;
            text-transform: lowercase;
        }
        .see-more-badge:hover {
            background: #e0e0e0;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container my-5">
    <h2 class="mb-4">Pickup Points</h2>
    <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" id="showInactive" />
        <label class="form-check-label" for="showInactive">Show inactive pickup points</label>
    </div>
    <div id="pickupMap"></div>
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="pickupPointsTable">
            <thead class="table-success">
                <tr>
                    <th>Name</th>
                    <th>Address/Description</th>
                    <th>Latitude</th>
                    <th>Longitude</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="pickupPointsTbody">
                <!-- Table rows will be rendered by JS -->
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var points = <?php echo json_encode($points); ?>;
var map = L.map('pickupMap').setView([13.7575, 121.0583], 13);
// Restrict map to Batangas City bounds
var southWest = L.latLng(13.7300, 121.0300);
var northEast = L.latLng(13.8300, 121.1000);
var batangasBounds = L.latLngBounds(southWest, northEast);
map.setMaxBounds(batangasBounds);
map.setMinZoom(12);
map.setMaxZoom(17);
map.on('drag', function() {
    map.panInsideBounds(batangasBounds, { animate: false });
});
map.fitBounds(batangasBounds);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap contributors'
}).addTo(map);
// Custom marker icons
var blueIcon = new L.Icon({
    iconUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-icon.png',
    shadowUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});
var grayIcon = new L.Icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-grey.png',
    shadowUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});
var mapMarkers = [];
function renderPoints(showInactive) {
    // Remove old markers
    mapMarkers.forEach(function(m) { map.removeLayer(m); });
    mapMarkers = [];
    // Render table
    var tbody = document.getElementById('pickupPointsTbody');
    tbody.innerHTML = '';
    points.forEach(function(pt) {
        var isActive = (pt.status && pt.status.toLowerCase() === 'active');
        if (!isActive && !showInactive) return;
        // Table row
        var tr = document.createElement('tr');
        if (!isActive) tr.style.color = '#888';
        tr.innerHTML = `
            <td>${pt.name ? pt.name.replace(/</g, '&lt;') : ''}</td>
            <td style="max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                ${pt.address && pt.address.length > 30 ?
                    `<span title="${pt.address.replace(/"/g, '&quot;')}">${pt.address.substring(0, 30)}...</span> <span class='see-more-badge' style='font-variant:small-caps;text-transform:lowercase;background:#f1f1f1;color:#333;border-radius:12px;padding:2px 10px;font-size:0.95em;margin-left:6px;cursor:pointer;'>see more</span>`
                    : (pt.address ? pt.address.replace(/</g, '&lt;') : '')}
            </td>
            <td>${pt.latitude}</td>
            <td>${pt.longitude}</td>
            <td>${pt.status ? pt.status.charAt(0).toUpperCase() + pt.status.slice(1) : 'Active'}</td>
        `;
        tbody.appendChild(tr);
        // Map marker
        var marker = L.marker([pt.latitude, pt.longitude], { icon: isActive ? blueIcon : grayIcon })
            .addTo(map)
            .bindPopup('<strong>' + pt.name + '</strong><br>' + (pt.address || ''));
        mapMarkers.push(marker);
    });
    // See more modal logic
    document.querySelectorAll('.see-more-badge').forEach(function(link) {
        link.addEventListener('click', function() {
            var desc = this.parentElement.querySelector('span[title]')?.getAttribute('title') || '';
            document.getElementById('descModalBody').textContent = desc;
            var modal = new bootstrap.Modal(document.getElementById('descModal'));
            modal.show();
        });
    });
}
document.getElementById('showInactive').addEventListener('change', function() {
    renderPoints(this.checked);
});
// Initial render (active only)
renderPoints(false);
</script>
<!-- Modal for full description -->
<div class="modal fade" id="descModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Full Description</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="descModalBody"></div>
    </div>
  </div>
</div>
<footer class="bg-dark text-light py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5>EcoMap</h5>
                <p>Making waste management smarter and more efficient.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <h5>Contact Us</h5>
                <p>Email: info@ecomap.com<br>Phone: (123) 456-7890</p>
            </div>
        </div>
    </div>
</footer>
</body>
</html> 