<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = $error = '';

// Fetch user info
$stmt = $conn->prepare("SELECT username, email, role, created_at, nickname, profile_pic, gender FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $email, $role, $created_at, $nickname, $profile_pic, $gender);
$stmt->fetch();
$stmt->close();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_nickname = trim($_POST['nickname']);
    if ($new_username && $new_email) {
        // Check for unique username/email
        $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->bind_param("ssi", $new_username, $new_email, $user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Username or email already taken.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, email=?, nickname=? WHERE id=?");
            $stmt->bind_param("sssi", $new_username, $new_email, $new_nickname, $user_id);
            if ($stmt->execute()) {
                $success = "Profile updated successfully.";
                $_SESSION['username'] = $new_username;
                $_SESSION['nickname'] = $new_nickname;
                $username = $new_username;
                $email = $new_email;
                $nickname = $new_nickname;
            } else {
                $error = "Failed to update profile.";
            }
        }
        $stmt->close();
    } else {
        $error = "Username and email cannot be empty.";
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    if ($new !== $confirm) {
        $error = "New passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($hashed);
        $stmt->fetch();
        $stmt->close();
        if (!password_verify($current, $hashed)) {
            $error = "Current password is incorrect.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $new_hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt->bind_param("si", $new_hashed, $user_id);
            if ($stmt->execute()) {
                $success = "Password changed successfully.";
            } else {
                $error = "Failed to change password.";
            }
            $stmt->close();
        }
    }
}

// Handle profile picture upload
if (isset($_POST['upload_pic']) && isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array(strtolower($ext), $allowed)) {
        $newname = 'user_' . $user_id . '_' . time() . '.' . $ext;
        $target = 'uploads/profile_pics/' . $newname;
        if (!is_dir('uploads/profile_pics')) mkdir('uploads/profile_pics', 0777, true);
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target)) {
            $stmt = $conn->prepare("UPDATE users SET profile_pic=? WHERE id=?");
            $stmt->bind_param("si", $target, $user_id);
            if ($stmt->execute()) {
                $success = "Profile picture updated.";
                $profile_pic = $target;
            } else {
                $error = "Failed to update profile picture.";
            }
            $stmt->close();
        } else {
            $error = "Failed to upload image.";
        }
    } else {
        $error = "Invalid file type. Only JPG, PNG, GIF allowed.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - EcoMap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="main-content" style="margin-bottom: 24px;">
    <div class="container d-flex justify-content-center align-items-start" style="min-height:80vh;">
        <div class="w-100" style="max-width:700px; margin-top:40px;">
            <h2 class="mb-4 text-center">My Profile</h2>
            <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
            <div class="card shadow rounded-4" style="padding: 0 24px; margin-bottom: 32px;">
                <div class="row g-0 align-items-center">
                    <div class="col-12 col-md-4 text-center py-4">
                        <?php
                        $default_avatar = 'assets/img/default-other.png';
                        if ($gender === 'male') $default_avatar = 'assets/img/default-male.png';
                        if ($gender === 'female') $default_avatar = 'assets/img/default-female.png';
                        $avatar = ($profile_pic && file_exists($profile_pic)) ? $profile_pic : $default_avatar;
                        ?>
                        <img src="<?php echo $avatar; ?>" alt="Profile Picture" class="rounded-circle mb-3" style="width:120px;height:120px;object-fit:cover;">
                        <form method="POST" enctype="multipart/form-data" class="mt-2">
                            <input type="file" name="profile_pic" accept="image/*" class="form-control form-control-sm mb-2">
                            <button type="submit" name="upload_pic" class="btn btn-outline-primary btn-sm w-100">Upload</button>
                        </form>
                    </div>
                    <div class="col-12 col-md-8">
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                                    <small class="text-muted">This is your login username</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nickname</label>
                                    <input type="text" class="form-control" name="nickname" value="<?php echo htmlspecialchars($nickname ?? ''); ?>" required>
                                    <small class="text-muted">This is the name that will be displayed to other users</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <input type="text" class="form-control" value="<?php echo ucfirst($role); ?>" disabled>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Registered</label>
                                    <input type="text" class="form-control" value="<?php echo $created_at; ?>" disabled>
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-success">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Change Password</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="bg-dark text-light py-4">
    <div class="container text-center">
        EcoMap &copy; <?php echo date('Y'); ?>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 