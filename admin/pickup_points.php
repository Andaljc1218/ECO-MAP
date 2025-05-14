<?php
session_start();
require_once "../config/database.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Handle form submission for adding/editing pickup points
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $address = $_POST['address'] ?? '';
    $schedule = $_POST['schedule'] ?? '';
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $status = $_POST['status'] ?? 'active';
    
    if (isset($_POST['pickup_id']) && !empty($_POST['pickup_id'])) {
        // Update existing pickup point
        $id = $_POST['pickup_id'];
        $stmt = $conn->prepare("UPDATE pickup_points SET name = ?, address = ?, latitude = ?, longitude = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssddsi", $name, $address, $latitude, $longitude, $status, $id);
    } else {
        // Add new pickup point
        $stmt = $conn->prepare("INSERT INTO pickup_points (name, address, schedule, latitude, longitude, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdds", $name, $address, $schedule, $latitude, $longitude, $status);
    }
    
    if ($stmt->execute()) {
        header("Location: pickup_points.php?success=1");
        exit;
    } else {
        $error = "Error saving pickup point: " . $conn->error;
    }
}

// Handle deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM pickup_points WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: pickup_points.php?success=2");
        exit;
    } else {
        $error = "Error deleting pickup point: " . $conn->error;
    }
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
    <title>Manage Pickup Points - EcoMap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="../assets/css/style.css">
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
<?php include '../navbar.php'; ?>
<div class="container my-5">
    <h2 class="mb-4">Manage Pickup Points</h2>
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            if ($_GET['success'] == 1) {
                echo "Pickup point saved successfully!";
            } elseif ($_GET['success'] == 2) {
                echo "Pickup point deleted successfully!";
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <div id="pickupMap"></div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Pickup Points List</h5>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#pickupModal" id="addPickupBtn">Add Pickup Point</button>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-success">
                <tr>
                    <th>Name</th>
                    <th>Address/Description</th>
                    <th>Latitude</th>
                    <th>Longitude</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($points as $pt): ?>
                <tr>
                    <td><?php echo htmlspecialchars($pt['name']); ?></td>
                    <td style="max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        <?php if(strlen($pt['address']) > 30): ?>
                            <span title="<?php echo htmlspecialchars($pt['address']); ?>">
                                <?php echo htmlspecialchars(mb_strimwidth($pt['address'], 0, 30, '...')); ?>
                            </span>
                            <a href="#" class="see-more-badge" data-bs-toggle="modal" data-bs-target="#descModal" data-desc="<?php echo htmlspecialchars($pt['address']); ?>">See more</a>
                        <?php else: ?>
                            <?php echo htmlspecialchars($pt['address']); ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $pt['latitude']; ?></td>
                    <td><?php echo $pt['longitude']; ?></td>
                    <td><?php echo ucfirst($pt['status'] ?? 'active'); ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm edit-btn" 
                            data-id="<?php echo $pt['id']; ?>"
                            data-name="<?php echo htmlspecialchars($pt['name'], ENT_QUOTES); ?>"
                            data-address="<?php echo htmlspecialchars($pt['address'] ?? '', ENT_QUOTES); ?>"
                            data-latitude="<?php echo $pt['latitude']; ?>"
                            data-longitude="<?php echo $pt['longitude']; ?>"
                            data-status="<?php echo $pt['status'] ?? 'active'; ?>"
                        >Edit</button>
                        <a href="?delete=<?php echo $pt['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this pickup point?');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- Modal for Add/Edit Pickup Point -->
<div class="modal fade" id="pickupModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="pickupForm">
        <div class="modal-header">
          <h5 class="modal-title" id="pickupModalTitle">Add Pickup Point</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="pickup_id" id="pickup_id">
          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" class="form-control" name="name" id="pickup_name" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Address/Description</label>
            <textarea class="form-control" name="address" id="pickup_address" rows="3" style="resize:vertical;" placeholder="Enter address or description"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Location (click on the map or enter coordinates)</label>
            <div id="modalPickupMap" style="height: 220px; width: 100%; border-radius: 8px; margin-bottom: 12px;"></div>
          </div>
          <div class="mb-3 row">
            <div class="col-6">
              <label class="form-label">Latitude</label>
              <input type="number" step="any" class="form-control" name="latitude" id="pickup_latitude" required>
            </div>
            <div class="col-6">
              <label class="form-label">Longitude</label>
              <input type="number" step="any" class="form-control" name="longitude" id="pickup_longitude" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status" id="pickup_status">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="mb-2">
            <small>Click on the map to set coordinates, or edit the fields directly.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Map setup for main map
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
// Add markers for all points
var points = <?php echo json_encode($points); ?>;
points.forEach(function(pt) {
    if(pt.latitude && pt.longitude) {
        var isActive = (pt.status && pt.status.toLowerCase() === 'active');
        var marker = L.marker([pt.latitude, pt.longitude], { icon: isActive ? blueIcon : grayIcon })
            .addTo(map)
            .bindPopup('<strong>' + pt.name + '</strong><br>' + (pt.address || ''));
    }
});
// --- Modal map logic ---
var modalMap, modalMarker;
function initModalMap(lat, lng) {
    if (modalMap) {
        modalMap.remove();
    }
    modalMap = L.map('modalPickupMap', {
        center: [lat, lng],
        zoom: 15,
        zoomControl: false,
        attributionControl: false
    });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19
    }).addTo(modalMap);
    modalMarker = L.marker([lat, lng], {draggable: true}).addTo(modalMap);
    modalMarker.on('dragend', function(e) {
        var pos = modalMarker.getLatLng();
        document.getElementById('pickup_latitude').value = pos.lat.toFixed(6);
        document.getElementById('pickup_longitude').value = pos.lng.toFixed(6);
    });
    modalMap.on('click', function(e) {
        modalMarker.setLatLng(e.latlng);
        document.getElementById('pickup_latitude').value = e.latlng.lat.toFixed(6);
        document.getElementById('pickup_longitude').value = e.latlng.lng.toFixed(6);
    });
}
function updateModalMarker() {
    var lat = parseFloat(document.getElementById('pickup_latitude').value);
    var lng = parseFloat(document.getElementById('pickup_longitude').value);
    if (!isNaN(lat) && !isNaN(lng) && modalMarker) {
        modalMarker.setLatLng([lat, lng]);
        modalMap.setView([lat, lng]);
    }
}
// Modal logic for add/edit
var pickupModal = new bootstrap.Modal(document.getElementById('pickupModal'));
document.getElementById('addPickupBtn').onclick = function() {
    document.getElementById('pickupForm').reset();
    document.getElementById('pickup_id').value = '';
    document.getElementById('pickupModalTitle').textContent = 'Add Pickup Point';
    setTimeout(function() {
        initModalMap(13.7575, 121.0583);
        document.getElementById('pickup_latitude').value = 13.7575;
        document.getElementById('pickup_longitude').value = 121.0583;
    }, 300);
};
document.querySelectorAll('.edit-btn').forEach(function(btn) {
    btn.onclick = function() {
        document.getElementById('pickup_id').value = btn.getAttribute('data-id');
        document.getElementById('pickup_name').value = btn.getAttribute('data-name');
        document.getElementById('pickup_address').value = btn.getAttribute('data-address');
        document.getElementById('pickup_latitude').value = btn.getAttribute('data-latitude');
        document.getElementById('pickup_longitude').value = btn.getAttribute('data-longitude');
        document.getElementById('pickup_status').value = btn.getAttribute('data-status');
        document.getElementById('pickupModalTitle').textContent = 'Edit Pickup Point';
        setTimeout(function() {
            var lat = parseFloat(btn.getAttribute('data-latitude')) || 13.7575;
            var lng = parseFloat(btn.getAttribute('data-longitude')) || 121.0583;
            initModalMap(lat, lng);
        }, 300);
        pickupModal.show();
    };
});
document.getElementById('pickup_latitude').addEventListener('input', updateModalMarker);
document.getElementById('pickup_longitude').addEventListener('input', updateModalMarker);
// When modal is hidden, destroy the map to avoid issues
var modalEl = document.getElementById('pickupModal');
modalEl.addEventListener('hidden.bs.modal', function () {
    if (modalMap) {
        modalMap.remove();
        modalMap = null;
        modalMarker = null;
    }
});
document.querySelectorAll('.see-more-badge').forEach(function(link) {
    link.addEventListener('click', function() {
        document.getElementById('descModalBody').textContent = this.getAttribute('data-desc');
    });
});
</script>
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