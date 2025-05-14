<?php
session_start();
require_once "config/database.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch current Life360 code/link from settings table
$life360_code = '';
$life360_link = '';
$res = $conn->query("SELECT name, value FROM settings WHERE name IN ('life360_code', 'life360_link')");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        if ($row['name'] === 'life360_code') $life360_code = $row['value'];
        if ($row['name'] === 'life360_link') $life360_link = $row['value'];
    }
}

// Handle admin update
$updateMsg = '';
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_code = trim($_POST['life360_code'] ?? '');
    $new_link = trim($_POST['life360_link'] ?? '');
    if ($new_code && $new_link) {
        $stmt = $conn->prepare("INSERT INTO settings (name, value) VALUES ('life360_code', ?), ('life360_link', ?) ON DUPLICATE KEY UPDATE value=VALUES(value)");
        $stmt->bind_param("ss", $new_code, $new_link);
        if ($stmt->execute()) {
            $life360_code = $new_code;
            $life360_link = $new_link;
            $updateMsg = '<div class="alert alert-success">Life360 code/link updated!</div>';
        } else {
            $updateMsg = '<div class="alert alert-danger">Failed to update Life360 code/link.</div>';
        }
    } else {
        $updateMsg = '<div class="alert alert-danger">Both code and link are required.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Our Life360 Circle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .life360-container {
            max-width: 600px;
            margin: 40px auto 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.08);
            padding: 32px 32px 24px 32px;
        }
        .life360-link {
            font-size: 1.1em;
            font-weight: 500;
            color: #198754;
            word-break: break-all;
        }
        .life360-code {
            font-size: 1.3em;
            font-weight: bold;
            letter-spacing: 2px;
            color: #198754;
            background: #e9ecef;
            border-radius: 8px;
            padding: 4px 16px;
            display: inline-block;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="main-content">
    <div class="container">
        <div class="life360-container mt-5">
            <h2 class="mb-3">Join Our Life360 Circle</h2>
            <?php if ($updateMsg) echo $updateMsg; ?>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <form method="POST" class="mb-4">
                <div class="mb-3">
                    <label class="form-label">Life360 Code</label>
                    <input type="text" name="life360_code" class="form-control" value="<?php echo htmlspecialchars($life360_code); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Life360 Invite Link</label>
                    <input type="text" name="life360_link" class="form-control" value="<?php echo htmlspecialchars($life360_link); ?>" required>
                </div>
                <button type="submit" class="btn btn-success">Update Code/Link</button>
            </form>
            <?php endif; ?>
            <p>To join our community's Life360 Circle and share your location for safety and coordination, follow these steps:</p>
            <ol>
                <li>Download the <a href="https://www.life360.com/" target="_blank">Life360 app</a> on your phone.</li>
                <li>Sign up or log in.</li>
                <li><strong>Drivers:</strong> Tap <strong>"Join Circle"</strong> and enter this code:<br>
                    <span class="life360-code"><?php echo htmlspecialchars($life360_code); ?></span>
                </li>
                <li>Or click this link on your phone:<br>
                    <a class="life360-link" href="<?php echo htmlspecialchars($life360_link); ?>" target="_blank"><?php echo htmlspecialchars($life360_link); ?></a>
                </li>
                <li class="mt-2"><strong>Drivers:</strong> Please set your Life360 name to <strong>start with "Driver"</strong> (e.g., <em>Driver Juan Dela Cruz</em>) so users can easily identify you. <strong>After your day of work or when you are done with pickups, please hide your location in the Life360 app for privacy.</strong></li>
                <li class="mt-2"><strong>Users:</strong> If you join, please set your Life360 name to <strong>start with "User"</strong> (e.g., <em>User Maria Santos</em>) and <strong>hide your location</strong> in the Life360 app settings for your privacy.</li>
                <li class="mt-2 text-danger"><strong>Important:</strong> <u>Admins will remove anyone from the circle if their name does not include <strong>"User"</strong> or <strong>"Driver"</strong>.</u> Please follow the naming convention to avoid being removed.</li>
            </ol>
            <div class="alert alert-info mt-4">
                <strong>Note:</strong> By joining, you agree to share your location with the group for safety and coordination purposes. <strong>Users may hide their location in the circle for privacy.</strong> You can leave the circle at any time in the Life360 app. <br>
                <span class="text-danger"><strong>Reminder:</strong> Use the correct naming format (<strong>"User"</strong> or <strong>"Driver"</strong>) or you may be removed by an admin.</span>
            </div>
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