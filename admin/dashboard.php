<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once "../config/database.php";

// Only allow admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get total users and drivers
$totalUsers = 0;
$totalDrivers = 0;
$totalAdmins = 0;

$result = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['role'] === 'user') $totalUsers = $row['count'];
        if ($row['role'] === 'driver') $totalDrivers = $row['count'];
        if ($row['role'] === 'admin') $totalAdmins = $row['count'];
    }
}

// Get total educational materials and breakdown
$totalMaterials = 0;
$guides = 0;
$presentations = 0;
$videos = 0;
$result = $conn->query("SELECT category, COUNT(*) as count FROM educational_materials GROUP BY category");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $totalMaterials += $row['count'];
        if ($row['category'] === 'guide') $guides = $row['count'];
        if ($row['category'] === 'presentation') $presentations = $row['count'];
        if ($row['category'] === 'video') $videos = $row['count'];
    }
}

// Get all users (role = 'user')
$users = [];
$result = $conn->query("SELECT id, username, nickname, email, created_at FROM users WHERE role = 'user' ORDER BY username");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Get all drivers (role = 'driver')
$drivers = [];
$result = $conn->query("SELECT id, username, nickname, email, created_at FROM users WHERE role = 'driver' ORDER BY username");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $drivers[] = $row;
    }
}

// Get user registrations per month (last 12 months)
$userRegistrations = [];
$driverRegistrations = [];
$months = [];
$sql = "SELECT role, DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM users WHERE role IN ('user','driver') GROUP BY role, month ORDER BY month DESC";
$result = $conn->query($sql);
$userMonthMap = [];
$driverMonthMap = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['role'] === 'user') $userMonthMap[$row['month']] = $row['count'];
        if ($row['role'] === 'driver') $driverMonthMap[$row['month']] = $row['count'];
        $months[] = $row['month'];
    }
    $months = array_unique($months);
    $months = array_reverse($months);
    foreach ($months as $m) {
        $userRegistrations[] = isset($userMonthMap[$m]) ? $userMonthMap[$m] : 0;
        $driverRegistrations[] = isset($driverMonthMap[$m]) ? $driverMonthMap[$m] : 0;
    }
}

// Get all admins
$admins = [];
$result = $conn->query("SELECT id, username, email, created_at FROM users WHERE role = 'admin' ORDER BY username");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}

// Handle admin delete
if (isset($_GET['delete_admin'])) {
    $id = intval($_GET['delete_admin']);
    if ($id != $_SESSION['user_id']) { // Prevent self-delete
        $conn->query("DELETE FROM users WHERE id = $id AND role = 'admin'");
    }
    header("Location: dashboard.php");
    exit;
}

// Handle user/driver delete
if (isset($_GET['delete_user'])) {
    $id = intval($_GET['delete_user']);
    $conn->query("DELETE FROM users WHERE id = $id AND role = 'user'");
    header("Location: dashboard.php");
    exit;
}
if (isset($_GET['delete_driver'])) {
    $id = intval($_GET['delete_driver']);
    $conn->query("DELETE FROM users WHERE id = $id AND role = 'driver'");
    header("Location: dashboard.php");
    exit;
}

// Add pickup points stats
$pickup_total = $pickup_active = $pickup_inactive = 0;
$pickup_res = $conn->query("SELECT status, COUNT(*) as cnt FROM pickup_points GROUP BY status");
if ($pickup_res) {
    while ($row = $pickup_res->fetch_assoc()) {
        if ($row['status'] === 'active') $pickup_active = $row['cnt'];
        if ($row['status'] === 'inactive') $pickup_inactive = $row['cnt'];
        $pickup_total += $row['cnt'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EcoMap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
    .nav-tabs .nav-link {
        font-weight: 600;
        font-size: 1.1rem;
        color: #198754;
        border-radius: 12px 12px 0 0;
        margin-right: 8px;
        letter-spacing: 1px;
        transition: background 0.2s, color 0.2s;
    }
    .nav-tabs .nav-link.active {
        background: #198754;
        color: #fff !important;
        border-color: #198754 #198754 #fff;
        box-shadow: 0 2px 8px rgba(25,135,84,0.08);
    }
    .nav-tabs .nav-link:hover {
        background: #e9f7ef;
        color: #145c32;
    }
    .btn-group .btn {
        border-color: #dee2e6 !important;
        color: #198754;
        background: #fff;
        font-weight: 600;
        transition: background 0.2s, color 0.2s;
    }
    .btn-group .btn.active, .btn-group .btn:active {
        background: #198754 !important;
        color: #fff !important;
        border-color: #198754 !important;
        font-weight: bold;
    }
    .btn-group .btn:focus, .btn-group .btn:active {
        box-shadow: none !important;
        outline: none !important;
    }
    .btn-group .btn:not(.active):hover {
        background: #e9f7ef;
        color: #145c32;
        border-color: #198754 !important;
    }
    body {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        margin: 0;
        padding: 0;
    }

    .container {
        flex: 1 0 auto;
    }

    footer {
        flex-shrink: 0;
        margin-top: auto;
        background: var(--deep-green);
        color: white;
        text-align: center;
        padding: 1rem 0;
    }

    /* Keep your existing styles */
    .user-table tbody tr:hover {
        background-color: rgba(0,0,0,0.02);
    }

    .custom-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        background: rgba(40, 167, 69, 0.95);
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 12px;
        transform: translateX(150%);
        transition: transform 0.3s ease-in-out;
        font-family: 'Poppins', sans-serif;
    }

    .custom-notification.show {
        transform: translateX(0);
    }

    .custom-notification i {
        font-size: 20px;
    }

    .custom-notification .close-btn {
        margin-left: 10px;
        cursor: pointer;
        opacity: 0.8;
        transition: opacity 0.2s;
    }

    .custom-notification .close-btn:hover {
        opacity: 1;
    }

    .custom-confirm {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: #2d2d2d;
        padding: 20px 25px;
        border-radius: 8px;
        z-index: 10000;
        text-align: center;
        min-width: 320px;
        color: white;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .custom-confirm .message {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 15px;
    }

    .custom-confirm .message i {
        font-size: 20px;
    }

    .custom-confirm .btn-group {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }

    .custom-confirm .btn {
        padding: 6px 20px;
        border-radius: 4px;
        font-size: 14px;
    }

    .custom-confirm .btn-yes {
        background: white;
        color: #2d2d2d;
    }

    .custom-confirm .btn-no {
        background: #4a4a4a;
        color: white;
        border: none;
    }

    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
    }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>
    <!-- Notification Container -->
    <div id="notificationContainer"></div>

    <!-- Confirmation Dialog Container -->
    <div id="confirmContainer"></div>

    <div class="container my-5">
        <h2 class="mb-4">Admin Dashboard</h2>
        <div class="row g-4 mb-4">
            <!-- Users Card -->
            <div class="col-md-3 d-flex">
                <div class="card text-center p-3 flex-fill h-100">
                    <h5 class="mb-2">Community Users</h5>
                    <div class="display-5 fw-bold text-success"><?php echo $totalUsers; ?></div>
                </div>
            </div>
            <!-- Drivers Card -->
            <div class="col-md-3 d-flex">
                <div class="card text-center p-3 flex-fill h-100">
                    <h5 class="mb-2">Drivers</h5>
                    <div class="display-5 fw-bold text-primary"><?php echo $totalDrivers; ?></div>
                </div>
            </div>
            <!-- Educational Materials Card -->
            <div class="col-md-3 d-flex">
                <div class="card text-center p-3 flex-fill h-100">
                    <h5 class="mb-2">Educational Materials</h5>
                    <div class="display-5 fw-bold text-warning"><?php echo $totalMaterials; ?></div>
                    <div class="small mt-2">
                        <span class="badge bg-success">Guides: <?php echo $guides; ?></span>
                        <span class="badge bg-primary ms-2">Presentations: <?php echo $presentations; ?></span>
                        <span class="badge bg-warning text-dark ms-2">Videos: <?php echo $videos; ?></span>
                    </div>
                </div>
            </div>
            <!-- Pickup Points Card -->
            <div class="col-md-3 d-flex">
                <div class="card text-center p-3 flex-fill h-100">
                    <h5 class="mb-2">Pickup Points</h5>
                    <div class="display-5 fw-bold text-info"><?php echo $pickup_total; ?></div>
                    <div class="small mt-2">
                        <span class="badge bg-success">Active: <?php echo $pickup_active; ?></span>
                        <span class="badge bg-secondary ms-2">Inactive: <?php echo $pickup_inactive; ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-5">
            <div class="col-md-7 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title mb-0">Registrations (Last 12 Months)</h5>
                            <div class="btn-group" role="group" aria-label="Chart Toggle">
                                <button type="button" class="btn btn-outline-success active" id="toggleUserChart">Community Users</button>
                                <button type="button" class="btn btn-outline-primary" id="toggleDriverChart">Drivers</button>
                            </div>
                        </div>
                        <canvas id="registrationsChart" style="width:100%;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-5 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Materials by Category</h5>
                        <canvas id="materialsPieChart" style="width:100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="mb-3">
            <input type="text" id="userSearch" class="form-control" placeholder="Search users, drivers, or admins by username or email...">
        </div>
        <ul class="nav nav-tabs mb-3" id="userTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="true">Community Users</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="drivers-tab" data-bs-toggle="tab" data-bs-target="#drivers" type="button" role="tab" aria-controls="drivers" aria-selected="false">Drivers</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="admins-tab" data-bs-toggle="tab" data-bs-target="#admins" type="button" role="tab" aria-controls="admins" aria-selected="false">Admins</button>
            </li>
        </ul>
        <div class="tab-content" id="userTabsContent">
            <div class="tab-pane fade show active" id="users" role="tabpanel" aria-labelledby="users-tab">
                <div class="table-responsive mb-5">
                    <table class="table table-bordered table-striped user-table">
                        <thead class="table-success">
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Nickname</th>
                                <th>Email</th>
                                <th>Joined</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td>
                                    <?php
                                    if (isset($user['nickname']) && $user['nickname'] !== null && $user['nickname'] !== '') {
                                        echo htmlspecialchars($user['nickname']);
                                    } else {
                                        echo '<span class="text-muted">No nickname set</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo $user['created_at']; ?></td>
                                <td>
                                    <a href="?delete_user=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user?');">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="drivers" role="tabpanel" aria-labelledby="drivers-tab">
                <div class="table-responsive mb-5">
                    <table class="table table-bordered table-striped user-table">
                        <thead class="table-success">
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Nickname</th>
                                <th>Email</th>
                                <th>Joined</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($drivers as $driver): ?>
                            <tr>
                                <td><?php echo $driver['id']; ?></td>
                                <td><?php echo htmlspecialchars($driver['username']); ?></td>
                                <td>
                                    <?php
                                    if (isset($driver['nickname']) && $driver['nickname'] !== null && $driver['nickname'] !== '') {
                                        echo htmlspecialchars($driver['nickname']);
                                    } else {
                                        echo '<span class="text-muted">No nickname set</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($driver['email']); ?></td>
                                <td><?php echo $driver['created_at']; ?></td>
                                <td>
                                    <a href="?delete_driver=<?php echo $driver['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this driver?');">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="admins" role="tabpanel" aria-labelledby="admins-tab">
                <div class="table-responsive mb-5">
                    <table class="table table-bordered table-striped user-table">
                        <thead class="table-success">
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Joined</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?php echo $admin['id']; ?></td>
                                <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                <td><?php echo $admin['created_at']; ?></td>
                                <td>
                                    <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                        <a href="?delete_admin=<?php echo $admin['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this admin?');">Delete</a>
                                    <?php else: ?>
                                        <span class="text-muted">(You)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <footer>
        <div class="container text-center">
            EcoMap Admin Dashboard © <?php echo date('Y'); ?>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-doughnutlabel@1.0.3/dist/chartjs-plugin-doughnutlabel.min.js"></script>
    <script>
    document.getElementById('userSearch').addEventListener('input', function() {
        const search = this.value.toLowerCase();
        document.querySelectorAll('.user-table tbody tr').forEach(row => {
            const username = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
            const email = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
            if (username.includes(search) || email.includes(search)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    // Chart data from PHP
    const months = <?php echo json_encode($months); ?>;
    const userRegistrations = <?php echo json_encode($userRegistrations); ?>;
    const driverRegistrations = <?php echo json_encode($driverRegistrations); ?>;

    // Registration Line Chart (switchable)
    const regCtx = document.getElementById('registrationsChart').getContext('2d');
    // Create gradient for line
    const lineGradient = regCtx.createLinearGradient(0, 0, 0, 300);
    lineGradient.addColorStop(0, '#27ae60');
    lineGradient.addColorStop(1, '#5dade2');
    let regChart = new Chart(regCtx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Community Users',
                data: userRegistrations,
                borderColor: lineGradient,
                backgroundColor: 'rgba(39,174,96,0.10)',
                fill: true,
                tension: 0.4,
                pointRadius: 6,
                pointBackgroundColor: '#27ae60',
                pointBorderColor: '#fff',
                pointHoverRadius: 9,
                pointHoverBackgroundColor: '#5dade2',
                borderWidth: 4,
                shadowOffsetX: 0,
                shadowOffsetY: 4,
                shadowBlur: 10,
                shadowColor: 'rgba(39,174,96,0.15)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 2,
            plugins: {
                legend: { display: false },
            },
            scales: {
                x: { title: { display: true, text: 'Month' } },
                y: {
                    title: { display: true, text: 'Registrations' },
                    beginAtZero: true,
                    min: 0,
                    precision: 0,
                    ticks: {
                        stepSize: 1,
                        callback: function(value) { return Number.isInteger(value) ? value : null; }
                    }
                }
            }
        },
        plugins: [{
            beforeDraw: chart => {
                const ctx = chart.ctx;
                ctx.save();
                ctx.shadowColor = 'rgba(39,174,96,0.10)';
                ctx.shadowBlur = 10;
                ctx.shadowOffsetX = 0;
                ctx.shadowOffsetY = 4;
            },
            afterDraw: chart => {
                const ctx = chart.ctx;
                ctx.restore();
            }
        }]
    });
    // Toggle chart data
    const toggleUserBtn = document.getElementById('toggleUserChart');
    const toggleDriverBtn = document.getElementById('toggleDriverChart');
    toggleUserBtn.addEventListener('click', function() {
        toggleUserBtn.classList.add('active');
        toggleDriverBtn.classList.remove('active');
        regChart.data.datasets[0].label = 'Community Users';
        regChart.data.datasets[0].data = userRegistrations;
        regChart.data.datasets[0].borderColor = lineGradient;
        regChart.data.datasets[0].pointBackgroundColor = '#27ae60';
        regChart.update();
    });
    toggleDriverBtn.addEventListener('click', function() {
        toggleDriverBtn.classList.add('active');
        toggleUserBtn.classList.remove('active');
        regChart.data.datasets[0].label = 'Drivers';
        regChart.data.datasets[0].data = driverRegistrations;
        regChart.data.datasets[0].borderColor = '#0d6efd';
        regChart.data.datasets[0].pointBackgroundColor = '#0d6efd';
        regChart.update();
    });
    // Materials Doughnut Chart with Gradient and Center Label
    const materialsPieCtx = document.getElementById('materialsPieChart').getContext('2d');
    // Gradients for slices
    const grad1 = materialsPieCtx.createLinearGradient(0, 0, 0, 200);
    grad1.addColorStop(0, '#27ae60'); grad1.addColorStop(1, '#145a32');
    const grad2 = materialsPieCtx.createLinearGradient(0, 0, 0, 200);
    grad2.addColorStop(0, '#5dade2'); grad2.addColorStop(1, '#154360');
    const grad3 = materialsPieCtx.createLinearGradient(0, 0, 0, 200);
    grad3.addColorStop(0, '#f7ca18'); grad3.addColorStop(1, '#b7950b');
    const materialsPieChart = new Chart(materialsPieCtx, {
        type: 'doughnut',
        data: {
            labels: ['Guides', 'Presentations', 'Videos'],
            datasets: [{
                data: [<?php echo $guides; ?>, <?php echo $presentations; ?>, <?php echo $videos; ?>],
                backgroundColor: [grad1, grad2, grad3],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 1.1,
            cutout: '70%',
            plugins: {
                legend: { position: 'bottom' },
                doughnutlabel: {
                    labels: [
                        {
                            text: '<?php echo $totalMaterials; ?>',
                            font: { size: '28' },
                            color: '#27ae60'
                        },
                        {
                            text: 'Total',
                            font: { size: '16' },
                            color: '#888'
                        }
                    ]
                }
            }
        }
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Check if Bootstrap is loaded
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap JS is not loaded! Tabs will not work.');
    }
    // Ensure Bootstrap tabs work
    document.addEventListener('DOMContentLoaded', function() {
        var triggerTabList = [].slice.call(document.querySelectorAll('#userTabs button'));
        triggerTabList.forEach(function (triggerEl) {
            triggerEl.addEventListener('click', function (event) {
                event.preventDefault();
                var tabTrigger = new bootstrap.Tab(triggerEl);
                tabTrigger.show();
            });
        });
    });
    </script>
    <script>
    // Add Pick up points nav link if not present
    document.addEventListener('DOMContentLoaded', function() {
        var nav = document.querySelector('.navbar-nav.me-auto');
        if (nav && !nav.innerHTML.includes('Pick up points')) {
            var li = document.createElement('li');
            li.className = 'nav-item';
            li.innerHTML = '<a class="nav-link" href="pickup_points.php">Pick up points</a>';
            nav.appendChild(li);
        }
    });
    </script>
    <script>
    function showNotification(message, type = 'success') {
        const container = document.getElementById('notificationContainer');
        const notification = document.createElement('div');
        notification.className = `custom-notification ${type === 'success' ? 'bg-success' : 'bg-danger'}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
            <span class="close-btn">&times;</span>
        `;
        container.appendChild(notification);
        
        // Show notification
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Add close button functionality
        notification.querySelector('.close-btn').addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        });
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }

    function showConfirmDialog(message, onConfirm) {
        const container = document.getElementById('confirmContainer');
        
        // Create overlay
        const overlay = document.createElement('div');
        overlay.className = 'overlay';
        
        // Create confirmation dialog
        const dialog = document.createElement('div');
        dialog.className = 'custom-confirm';
        dialog.innerHTML = `
            <div class="message">
                <i class="fas fa-question-circle"></i>
                <span>${message}</span>
            </div>
            <div class="btn-group">
                <button class="btn btn-no" id="noBtn">No</button>
                <button class="btn btn-yes" id="yesBtn">Yes</button>
            </div>
        `;
        
        container.appendChild(overlay);
        container.appendChild(dialog);
        
        // Handle button clicks
        document.getElementById('noBtn').onclick = () => {
            overlay.remove();
            dialog.remove();
        };
        
        document.getElementById('yesBtn').onclick = () => {
            overlay.remove();
            dialog.remove();
            onConfirm();
        };
    }

    // Update delete confirmation handlers
    document.querySelectorAll('a[href*="delete_user"]').forEach(link => {
        link.onclick = function(e) {
            e.preventDefault();
            showConfirmDialog('Are you sure you want to delete this user?', () => {
                fetch(this.href)
                    .then(response => {
                        showNotification('User deleted successfully');
                        setTimeout(() => location.reload(), 1000);
                    })
                    .catch(error => showNotification('Error deleting user', 'error'));
            });
        };
    });

    document.querySelectorAll('a[href*="delete_driver"]').forEach(link => {
        link.onclick = function(e) {
            e.preventDefault();
            showConfirmDialog('Are you sure you want to delete this driver?', () => {
                fetch(this.href)
                    .then(response => {
                        showNotification('Driver deleted successfully');
                        setTimeout(() => location.reload(), 1000);
                    })
                    .catch(error => showNotification('Error deleting driver', 'error'));
            });
        };
    });

    document.querySelectorAll('a[href*="delete_admin"]').forEach(link => {
        link.onclick = function(e) {
            e.preventDefault();
            showConfirmDialog('Are you sure you want to delete this admin?', () => {
                fetch(this.href)
                    .then(response => {
                        showNotification('Admin deleted successfully');
                        setTimeout(() => location.reload(), 1000);
                    })
                    .catch(error => showNotification('Error deleting admin', 'error'));
            });
        };
    });
    </script>
</body>
</html> 