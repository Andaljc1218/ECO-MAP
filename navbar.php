<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Determine if we are in the admin directory
$currentDir = basename(dirname($_SERVER['SCRIPT_FILENAME']));
$inAdmin = ($currentDir === 'admin');

function nav_link($file, $text, $activeFile) {
    $isActive = (basename($_SERVER['PHP_SELF']) == $activeFile) ? ' active' : '';
    echo '<a class="nav-link' . $isActive . '" href="' . $file . '">' . $text . '</a>';
}

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT profile_pic, gender FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($profile_pic, $gender);
    $stmt->fetch();
    $stmt->close();

    $web_root = '/Ecomap3/'; // Change if your project folder is different

    $default_avatar = $web_root . 'assets/img/default-other.png';
    if ($gender === 'male') $default_avatar = $web_root . 'assets/img/default-male.png';
    if ($gender === 'female') $default_avatar = $web_root . 'assets/img/default-female.png';

    $avatar = $default_avatar;
    if ($profile_pic) {
        $pic_path = $_SERVER['DOCUMENT_ROOT'] . $web_root . ltrim($profile_pic, '/');
        if (file_exists($pic_path)) {
            $avatar = $web_root . ltrim($profile_pic, '/');
        }
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="<?php echo $inAdmin ? '../index.php' : 'index.php'; ?>">EcoMap</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <?php nav_link($inAdmin ? '../index.php' : 'index.php', 'Home', 'index.php'); ?>
                </li>
                <li class="nav-item">
                    <?php nav_link($inAdmin ? '../schedule.php' : 'schedule.php', 'Schedule', 'schedule.php'); ?>
                </li>
                <li class="nav-item">
                    <?php nav_link($inAdmin ? '../education.php' : 'education.php', 'Education', 'education.php'); ?>
                </li>
                <li class="nav-item">
                    <?php
                    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                        nav_link($inAdmin ? 'pickup_points.php' : 'admin/pickup_points.php', 'Pick up points', 'pickup_points.php');
                    } else {
                        nav_link($inAdmin ? '../pickup_points.php' : 'pickup_points.php', 'Pick up points', 'pickup_points.php');
                    }
                    ?>
                </li>
                <li class="nav-item">
                    <?php nav_link($inAdmin ? '../ecobot.php' : 'ecobot.php', 'Ecobot', 'ecobot.php'); ?>
                </li>
                <li class="nav-item">
                    <?php nav_link($inAdmin ? '../life360.php' : 'life360.php', 'Life360', 'life360.php'); ?>
                </li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li class="nav-item">
                    <?php nav_link($inAdmin ? 'dashboard.php' : 'admin/dashboard.php', 'Dashboard', 'dashboard.php'); ?>
                </li>
                <li class="nav-item">
                    <?php nav_link($inAdmin ? 'education_manage.php' : 'admin/education_manage.php', 'Manage Materials', 'education_manage.php'); ?>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav align-items-center">
                <?php if(isset($_SESSION['user_id'])): ?>
                <li class="nav-item d-flex align-items-center">
                    <a href="<?php echo $inAdmin ? '../profile.php' : 'profile.php'; ?>"
                       class="nav-link d-flex align-items-center gap-2"
                       style="padding-top: 0; padding-bottom: 0;">
                        <img src="<?php echo $avatar; ?>" alt="Profile"
                             class="rounded-circle"
                             style="width:32px;height:32px;object-fit:cover;margin-top:2px;">
                        <span>Profile</span>
                    </a>
                </li>
                <li class="nav-item">
                    <?php nav_link($inAdmin ? '../logout.php' : 'logout.php', 'Logout', 'logout.php'); ?>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <?php nav_link($inAdmin ? '../login.php' : 'login.php', 'Login', 'login.php'); ?>
                </li>
                <li class="nav-item">
                    <?php nav_link($inAdmin ? '../register.php' : 'register.php', 'Register', 'register.php'); ?>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav> 