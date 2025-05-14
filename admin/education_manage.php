<?php
session_start();
require_once "../config/database.php";

// Only allow admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Handle file upload
$uploadMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = isset($_POST['category']) ? $_POST['category'] : 'guide';
    $video_type = '';
    $filename = '';
    $filetype = '';
    $thumbnail = '';
    $uploadDir = '../assets/resources/';
    $thumbDir = '../assets/thumbnails/';
    // Handle thumbnail upload
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $thumbFile = $_FILES['thumbnail'];
        $allowedThumbTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($thumbFile['type'], $allowedThumbTypes)) {
            $thumbName = time() . '_' . basename($thumbFile['name']);
            $thumbTarget = $thumbDir . $thumbName;
            if (move_uploaded_file($thumbFile['tmp_name'], $thumbTarget)) {
                $thumbnail = $thumbName;
            } else {
                $uploadMessage = '<div class="alert alert-danger">Failed to upload thumbnail image.</div>';
            }
        } else {
            $uploadMessage = '<div class="alert alert-danger">Invalid thumbnail image type.</div>';
        }
    }
    if ($category === 'video') {
        $video_option = $_POST['video_option'] ?? '';
        if ($video_option === 'mp4' && isset($_FILES['material']) && $_FILES['material']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['material'];
            $allowedTypes = ['video/mp4'];
            if (in_array($file['type'], $allowedTypes)) {
                $filename = time() . '_' . basename($file['name']);
                $targetPath = $uploadDir . $filename;
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $filetype = $file['type'];
                    $video_type = 'mp4';
                } else {
                    $uploadMessage = '<div class="alert alert-danger">Failed to upload MP4 file.</div>';
                }
            } else {
                $uploadMessage = '<div class="alert alert-danger">Invalid MP4 file type.</div>';
            }
        } elseif ($video_option === 'youtube' && !empty($_POST['youtube_link'])) {
            $filename = trim($_POST['youtube_link']);
            $filetype = 'youtube';
            $video_type = 'youtube';
        } else {
            $uploadMessage = '<div class="alert alert-danger">Please provide a valid MP4 file or YouTube link.</div>';
        }
    } else {
        // Guide or Presentation
        if (isset($_FILES['material']) && $_FILES['material']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['material'];
            $allowedTypes = [
                'application/pdf',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation'
            ];
            if (in_array($file['type'], $allowedTypes)) {
                $filename = time() . '_' . basename($file['name']);
                $targetPath = $uploadDir . $filename;
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $filetype = $file['type'];
                } else {
                    $uploadMessage = '<div class="alert alert-danger">Failed to upload file.</div>';
                }
            } else {
                $uploadMessage = '<div class="alert alert-danger">Invalid file type.</div>';
            }
        }
    }
    // Insert if no error
    if (empty($uploadMessage) && $filename) {
        $stmt = $conn->prepare("INSERT INTO educational_materials (title, description, filename, filetype, category, video_type, thumbnail) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $title, $description, $filename, $filetype, $category, $video_type, $thumbnail);
        $stmt->execute();
        $uploadMessage = '<div class="alert alert-success">Material uploaded successfully!</div>';
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $result = $conn->query("SELECT filename FROM educational_materials WHERE id = $id");
    if ($row = $result->fetch_assoc()) {
        $filePath = '../assets/resources/' . $row['filename'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    $conn->query("DELETE FROM educational_materials WHERE id = $id");
    header("Location: education_manage.php");
    exit;
}

// Handle update
if (isset($_POST['modal_edit_id'])) {
    $edit_id = intval($_POST['modal_edit_id']);
    $edit_title = trim($_POST['modal_edit_title']);
    $edit_description = trim($_POST['modal_edit_description']);
    $edit_category = trim($_POST['modal_edit_category']);
    $thumbnail_update = '';
    $thumbDir = '../assets/thumbnails/';
    // Handle new thumbnail upload
    if (isset($_FILES['modal_edit_thumbnail']) && $_FILES['modal_edit_thumbnail']['error'] === UPLOAD_ERR_OK) {
        $thumbFile = $_FILES['modal_edit_thumbnail'];
        $allowedThumbTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($thumbFile['type'], $allowedThumbTypes)) {
            $thumbName = time() . '_' . basename($thumbFile['name']);
            $thumbTarget = $thumbDir . $thumbName;
            if (move_uploaded_file($thumbFile['tmp_name'], $thumbTarget)) {
                // Delete old thumbnail if exists
                $oldThumbQ = $conn->query("SELECT thumbnail FROM educational_materials WHERE id = $edit_id");
                if ($oldThumbQ && $oldThumb = $oldThumbQ->fetch_assoc()) {
                    if (!empty($oldThumb['thumbnail']) && file_exists($thumbDir . $oldThumb['thumbnail'])) {
                        unlink($thumbDir . $oldThumb['thumbnail']);
                    }
                }
                $thumbnail_update = $thumbName;
            }
        }
    }
    if ($thumbnail_update) {
        $stmt = $conn->prepare("UPDATE educational_materials SET title=?, description=?, category=?, thumbnail=? WHERE id=?");
        $stmt->bind_param("ssssi", $edit_title, $edit_description, $edit_category, $thumbnail_update, $edit_id);
    } else {
        $stmt = $conn->prepare("UPDATE educational_materials SET title=?, description=?, category=? WHERE id=?");
        $stmt->bind_param("sssi", $edit_title, $edit_description, $edit_category, $edit_id);
    }
    $stmt->execute();
    header("Location: education_manage.php");
    exit;
}

// Fetch all materials
$materials = [];
$result = $conn->query("SELECT * FROM educational_materials ORDER BY uploaded_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $materials[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Educational Materials - EcoMap Admin</title>
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
    .materials-table tbody tr:hover {
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
        <h2 class="mb-4">Manage Educational Materials</h2>
        <?php 
        if (!empty($uploadMessage)) {
            $type = strpos($uploadMessage, 'success') !== false ? 'success' : 'error';
            $message = strip_tags($uploadMessage);
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    showNotification('$message', '$type');
                });
            </script>";
        }
        ?>
        <div class="card mb-4">
            <div class="card-header bg-success text-white">Upload New Material</div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="materialForm">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="guide">Guide</option>
                            <option value="presentation">Presentation</option>
                            <option value="video">Video</option>
                        </select>
                    </div>
                    <div id="videoOptions" style="display:none;">
                        <label class="form-label">Video Source</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="video_option" id="videoMp4" value="mp4" checked>
                            <label class="form-check-label" for="videoMp4">Upload MP4</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="video_option" id="videoYoutube" value="youtube">
                            <label class="form-check-label" for="videoYoutube">YouTube Link</label>
                        </div>
                    </div>
                    <div class="mb-3" id="fileInputDiv">
                        <label for="material" class="form-label" id="fileInputLabel">File (PDF, PowerPoint, or MP4)</label>
                        <input type="file" class="form-control" id="material" name="material" accept=".pdf,.ppt,.pptx,.mp4">
                    </div>
                    <div class="mb-3" id="youtubeInputDiv" style="display:none;">
                        <label for="youtube_link" class="form-label">YouTube Link</label>
                        <input type="url" class="form-control" id="youtube_link" name="youtube_link" placeholder="https://www.youtube.com/watch?v=...">
                    </div>
                    <div class="mb-3">
                        <label for="thumbnail" class="form-label">Thumbnail Image (optional)</label>
                        <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept=".jpg,.jpeg,.png,.gif">
                    </div>
                    <button type="submit" class="btn btn-success">Upload</button>
                </form>
            </div>
        </div>
        <div class="mb-4">
            <input type="text" id="materialSearch" class="form-control" placeholder="Search materials by title or description...">
        </div>
        <ul class="nav nav-tabs mb-3" id="categoryTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="guides-tab" data-bs-toggle="tab" data-bs-target="#guides" type="button" role="tab" aria-controls="guides" aria-selected="true">Guides</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="presentations-tab" data-bs-toggle="tab" data-bs-target="#presentations" type="button" role="tab" aria-controls="presentations" aria-selected="false">Presentations</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="videos-tab" data-bs-toggle="tab" data-bs-target="#videos" type="button" role="tab" aria-controls="videos" aria-selected="false">Videos</button>
            </li>
        </ul>
        <div class="tab-content" id="categoryTabsContent">
            <div class="tab-pane fade show active" id="guides" role="tabpanel" aria-labelledby="guides-tab">
                <div class="table-responsive mb-5">
                    <h5>Guides</h5>
                    <table class="table table-bordered table-striped material-table" id="guidesTable">
                        <thead class="table-success">
                            <tr>
                                <th>Thumbnail</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>File/Video</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Uploaded</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $videoModalIndex = 1; ?>
                            <?php foreach ($materials as $mat): if ($mat['category'] !== 'guide') continue; ?>
                            <tr>
                                <td class="text-center align-middle">
                                    <?php
                                    $ext = strtolower(pathinfo($mat['filename'], PATHINFO_EXTENSION));
                                    $defaultImg = '../assets/images/default-doc.png';
                                    if ($ext === 'pdf') $defaultImg = '../assets/images/pdf-icon.png';
                                    if ($ext === 'ppt' || $ext === 'pptx') $defaultImg = '../assets/images/ppt-icon.png';
                                    if ($mat['category'] === 'video') $defaultImg = '../assets/images/video-icon.png';
                                    if (!empty($mat['thumbnail'])) {
                                        echo '<img src="../assets/thumbnails/' . htmlspecialchars($mat['thumbnail']) . '" alt="Thumbnail" style="max-width:60px;max-height:60px;object-fit:cover;border-radius:8px;">';
                                    } else {
                                        echo '<img src="' . $defaultImg . '" alt="Default Icon" style="max-width:60px;max-height:60px;object-fit:cover;border-radius:8px;">';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($mat['title']); ?></td>
                                <td>
                                    <?php
                                    $desc = htmlspecialchars($mat['description']);
                                    $desc_limit = 60;
                                    if (mb_strlen($desc) > $desc_limit) {
                                        $short = mb_substr($desc, 0, $desc_limit) . '...';
                                        echo $short;
                                    } else {
                                        echo $desc;
                                    }
                                    ?>
                                </td>
                                <td class="text-center align-middle">
                                    <?php if ($mat['category'] === 'video' && !empty($mat['filename'])): ?>
                                        <a href="#" class="btn btn-success btn-sm w-100" style="min-width:90px;" data-bs-toggle="modal" data-bs-target="#videoModal<?php echo $videoModalIndex; ?>">
                                            <i class="fas fa-play"></i> Play
                                        </a>
                                    <?php elseif (!empty($mat['filename'])): ?>
                                        <a href="../assets/resources/<?php echo $mat['filename']; ?>" target="_blank" class="btn btn-primary btn-sm w-100" style="min-width:90px;">Download</a>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($mat['filetype']); ?></td>
                                <td><?php echo ucfirst($mat['category']); ?></td>
                                <td><?php echo $mat['uploaded_at']; ?></td>
                                <td class="text-center align-middle">
                                    <div class="d-flex justify-content-center gap-2">
                                        <button type="button" class="btn btn-warning btn-sm edit-btn" 
                                            data-id="<?php echo $mat['id']; ?>"
                                            data-title="<?php echo htmlspecialchars($mat['title'], ENT_QUOTES); ?>"
                                            data-description="<?php echo htmlspecialchars($mat['description'], ENT_QUOTES); ?>"
                                            data-category="<?php echo $mat['category']; ?>">
                                            Edit
                                        </button>
                                        <a href="?delete=<?php echo $mat['id']; ?>" class="btn btn-sm btn-danger" style="min-width:60px;" onclick="return confirm('Delete this material?');">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php $videoModalIndex++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="presentations" role="tabpanel" aria-labelledby="presentations-tab">
                <div class="table-responsive mb-5">
                    <h5>Presentations</h5>
                    <table class="table table-bordered table-striped material-table" id="presentationsTable">
                        <thead class="table-success">
                            <tr>
                                <th>Thumbnail</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>File/Video</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Uploaded</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $videoModalIndex = 1; ?>
                            <?php foreach ($materials as $mat): if ($mat['category'] !== 'presentation') continue; ?>
                            <tr>
                                <td class="text-center align-middle">
                                    <?php
                                    $ext = strtolower(pathinfo($mat['filename'], PATHINFO_EXTENSION));
                                    $defaultImg = '../assets/images/default-doc.png';
                                    if ($ext === 'pdf') $defaultImg = '../assets/images/pdf-icon.png';
                                    if ($ext === 'ppt' || $ext === 'pptx') $defaultImg = '../assets/images/ppt-icon.png';
                                    if ($mat['category'] === 'video') $defaultImg = '../assets/images/video-icon.png';
                                    if (!empty($mat['thumbnail'])) {
                                        echo '<img src="../assets/thumbnails/' . htmlspecialchars($mat['thumbnail']) . '" alt="Thumbnail" style="max-width:60px;max-height:60px;object-fit:cover;border-radius:8px;">';
                                    } else {
                                        echo '<img src="' . $defaultImg . '" alt="Default Icon" style="max-width:60px;max-height:60px;object-fit:cover;border-radius:8px;">';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($mat['title']); ?></td>
                                <td>
                                    <?php
                                    $desc = htmlspecialchars($mat['description']);
                                    $desc_limit = 60;
                                    if (mb_strlen($desc) > $desc_limit) {
                                        $short = mb_substr($desc, 0, $desc_limit) . '...';
                                        echo $short;
                                    } else {
                                        echo $desc;
                                    }
                                    ?>
                                </td>
                                <td class="text-center align-middle">
                                    <?php if ($mat['category'] === 'video' && !empty($mat['filename'])): ?>
                                        <a href="#" class="btn btn-success btn-sm w-100" style="min-width:90px;" data-bs-toggle="modal" data-bs-target="#videoModal<?php echo $videoModalIndex; ?>">
                                            <i class="fas fa-play"></i> Play
                                        </a>
                                    <?php elseif (!empty($mat['filename'])): ?>
                                        <a href="../assets/resources/<?php echo $mat['filename']; ?>" target="_blank" class="btn btn-primary btn-sm w-100" style="min-width:90px;">Download</a>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($mat['filetype']); ?></td>
                                <td><?php echo ucfirst($mat['category']); ?></td>
                                <td><?php echo $mat['uploaded_at']; ?></td>
                                <td class="text-center align-middle">
                                    <div class="d-flex justify-content-center gap-2">
                                        <button type="button" class="btn btn-warning btn-sm edit-btn" 
                                            data-id="<?php echo $mat['id']; ?>"
                                            data-title="<?php echo htmlspecialchars($mat['title'], ENT_QUOTES); ?>"
                                            data-description="<?php echo htmlspecialchars($mat['description'], ENT_QUOTES); ?>"
                                            data-category="<?php echo $mat['category']; ?>">
                                            Edit
                                        </button>
                                        <a href="?delete=<?php echo $mat['id']; ?>" class="btn btn-sm btn-danger" style="min-width:60px;" onclick="return confirm('Delete this material?');">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php $videoModalIndex++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="videos" role="tabpanel" aria-labelledby="videos-tab">
                <div class="table-responsive mb-5">
                    <h5>Videos</h5>
                    <table class="table table-bordered table-striped material-table" id="videosTable">
                        <thead class="table-success">
                            <tr>
                                <th>Thumbnail</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>File/Video</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Uploaded</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $videoModalIndex = 1; ?>
                            <?php foreach ($materials as $mat): if ($mat['category'] !== 'video') continue; ?>
                            <tr>
                                <td class="text-center align-middle">
                                    <?php
                                    $ext = strtolower(pathinfo($mat['filename'], PATHINFO_EXTENSION));
                                    $defaultImg = '../assets/images/default-doc.png';
                                    if ($ext === 'pdf') $defaultImg = '../assets/images/pdf-icon.png';
                                    if ($ext === 'ppt' || $ext === 'pptx') $defaultImg = '../assets/images/ppt-icon.png';
                                    if ($mat['category'] === 'video') $defaultImg = '../assets/images/video-icon.png';
                                    if (!empty($mat['thumbnail'])) {
                                        echo '<img src="../assets/thumbnails/' . htmlspecialchars($mat['thumbnail']) . '" alt="Thumbnail" style="max-width:60px;max-height:60px;object-fit:cover;border-radius:8px;">';
                                    } else {
                                        echo '<img src="' . $defaultImg . '" alt="Default Icon" style="max-width:60px;max-height:60px;object-fit:cover;border-radius:8px;">';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($mat['title']); ?></td>
                                <td>
                                    <?php
                                    $desc = htmlspecialchars($mat['description']);
                                    $desc_limit = 60;
                                    if (mb_strlen($desc) > $desc_limit) {
                                        $short = mb_substr($desc, 0, $desc_limit) . '...';
                                        echo $short;
                                    } else {
                                        echo $desc;
                                    }
                                    ?>
                                </td>
                                <td class="text-center align-middle">
                                    <?php if (isset($mat['video_type']) && $mat['video_type'] === 'mp4'): ?>
                                        <video controls style="width:100%;height:100%;">
                                            <source src="../assets/resources/<?php echo $mat['filename']; ?>" type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                    <?php elseif (isset($mat['video_type']) && $mat['video_type'] === 'youtube'): ?>
                                        <?php
                                        $yt_id = '';
                                        if (preg_match('~(?:youtu.be/|youtube.com/(?:embed/|v/|watch\?v=|watch\?.+&v=))([^&?/\s]{11})~', $mat['filename'], $matches)) {
                                            $yt_id = $matches[1];
                                        }
                                        ?>
                                        <?php if ($yt_id): ?>
                                        <iframe src="https://www.youtube.com/embed/<?php echo $yt_id; ?>" allowfullscreen></iframe>
                                        <?php else: ?>
                                        <div class="alert alert-danger">Invalid YouTube link.</div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($mat['filetype']); ?></td>
                                <td><?php echo ucfirst($mat['category']); ?></td>
                                <td><?php echo $mat['uploaded_at']; ?></td>
                                <td class="text-center align-middle">
                                    <div class="d-flex justify-content-center gap-2">
                                        <button type="button" class="btn btn-warning btn-sm edit-btn" 
                                            data-id="<?php echo $mat['id']; ?>"
                                            data-title="<?php echo htmlspecialchars($mat['title'], ENT_QUOTES); ?>"
                                            data-description="<?php echo htmlspecialchars($mat['description'], ENT_QUOTES); ?>"
                                            data-category="<?php echo $mat['category']; ?>">
                                            Edit
                                        </button>
                                        <a href="?delete=<?php echo $mat['id']; ?>" class="btn btn-sm btn-danger" style="min-width:60px;" onclick="return confirm('Delete this material?');">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php $videoModalIndex++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Video Modals -->
        <?php $videoModalIndex = 1; foreach ($materials as $mat): if ($mat['category'] === 'video' && !empty($mat['filename'])): ?>
        <div class="modal fade" id="videoModal<?php echo $videoModalIndex; ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?php echo htmlspecialchars($mat['title']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="ratio ratio-16x9">
                            <?php if (isset($mat['video_type']) && $mat['video_type'] === 'mp4'): ?>
                                <video controls style="width:100%;height:100%;">
                                    <source src="../assets/resources/<?php echo $mat['filename']; ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            <?php elseif (isset($mat['video_type']) && $mat['video_type'] === 'youtube'): ?>
                                <?php
                                $yt_id = '';
                                if (preg_match('~(?:youtu.be/|youtube.com/(?:embed/|v/|watch\?v=|watch\?.+&v=))([^&?/\s]{11})~', $mat['filename'], $matches)) {
                                    $yt_id = $matches[1];
                                }
                                ?>
                                <?php if ($yt_id): ?>
                                <iframe src="https://www.youtube.com/embed/<?php echo $yt_id; ?>" allowfullscreen></iframe>
                                <?php else: ?>
                                <div class="alert alert-danger">Invalid YouTube link.</div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php $videoModalIndex++; endif; endforeach; ?>
        <!-- Edit Modal -->
        <div class="modal fade" id="editModal" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                  <h5 class="modal-title">Edit Material</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <input type="hidden" name="modal_edit_id" id="modal_edit_id">
                  <div class="mb-3">
                    <label for="modal_edit_title" class="form-label">Title</label>
                    <input type="text" class="form-control" name="modal_edit_title" id="modal_edit_title" required>
                  </div>
                  <div class="mb-3">
                    <label for="modal_edit_description" class="form-label">Description</label>
                    <textarea class="form-control" name="modal_edit_description" id="modal_edit_description" rows="2"></textarea>
                  </div>
                  <div class="mb-3">
                    <label for="modal_edit_category" class="form-label">Category</label>
                    <select class="form-select" name="modal_edit_category" id="modal_edit_category" required>
                      <option value="guide">Guide</option>
                      <option value="presentation">Presentation</option>
                      <option value="video">Video</option>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label for="modal_edit_thumbnail" class="form-label">Thumbnail Image (optional)</label>
                    <input type="file" class="form-control" name="modal_edit_thumbnail" id="modal_edit_thumbnail" accept=".jpg,.jpeg,.png,.gif">
                    <div id="currentThumbnailPreview" class="mt-2"></div>
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
    </div>
    <footer>
        <div class="container text-center">
            EcoMap Admin Dashboard © <?php echo date('Y'); ?>
        </div>
    </footer>
    <script>
    // Show/hide video options and inputs
    const categorySelect = document.getElementById('category');
    const videoOptions = document.getElementById('videoOptions');
    const fileInputDiv = document.getElementById('fileInputDiv');
    const youtubeInputDiv = document.getElementById('youtubeInputDiv');
    const fileInputLabel = document.getElementById('fileInputLabel');
    const videoMp4 = document.getElementById('videoMp4');
    const videoYoutube = document.getElementById('videoYoutube');

    function updateForm() {
        if (categorySelect.value === 'video') {
            videoOptions.style.display = '';
            if (videoMp4.checked) {
                fileInputDiv.style.display = '';
                youtubeInputDiv.style.display = 'none';
                fileInputLabel.textContent = 'MP4 File';
                document.getElementById('material').required = true;
                document.getElementById('youtube_link').required = false;
            } else {
                fileInputDiv.style.display = 'none';
                youtubeInputDiv.style.display = '';
                document.getElementById('material').required = false;
                document.getElementById('youtube_link').required = true;
            }
        } else {
            videoOptions.style.display = 'none';
            fileInputDiv.style.display = '';
            youtubeInputDiv.style.display = 'none';
            fileInputLabel.textContent = 'File (PDF or PowerPoint)';
            document.getElementById('material').required = true;
            document.getElementById('youtube_link').required = false;
        }
    }
    categorySelect.addEventListener('change', updateForm);
    videoMp4.addEventListener('change', updateForm);
    videoYoutube.addEventListener('change', updateForm);
    document.addEventListener('DOMContentLoaded', updateForm);
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof bootstrap === 'undefined' || typeof bootstrap.Modal === 'undefined') {
            console.error('Bootstrap Modal is not loaded!');
            return;
        }
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('modal_edit_id').value = this.getAttribute('data-id');
                document.getElementById('modal_edit_title').value = this.getAttribute('data-title');
                document.getElementById('modal_edit_description').value = this.getAttribute('data-description');
                document.getElementById('modal_edit_category').value = this.getAttribute('data-category');
                var editModal = new bootstrap.Modal(document.getElementById('editModal'));
                editModal.show();
                console.log('Edit modal opened for ID:', this.getAttribute('data-id'));
                // Show current thumbnail preview
                var row = this.closest('tr');
                var thumbCell = row.querySelector('td');
                var thumbImg = thumbCell.querySelector('img');
                var previewDiv = document.getElementById('currentThumbnailPreview');
                if (thumbImg && previewDiv) {
                    previewDiv.innerHTML = '<label class="form-label">Current Thumbnail:</label><br><img src="' + thumbImg.src + '" style="max-width:80px;max-height:80px;border-radius:8px;">';
                }
            });
        });
    });
    </script>
    <script>
    document.getElementById('materialSearch').addEventListener('input', function() {
        const search = this.value.toLowerCase();
        document.querySelectorAll('.material-table tbody tr').forEach(row => {
            const title = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
            const desc = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
            if (title.includes(search) || desc.includes(search)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
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
    document.querySelectorAll('a[href*="delete="]').forEach(link => {
        link.onclick = function(e) {
            e.preventDefault();
            showConfirmDialog('Are you sure you want to delete this material?', () => {
                fetch(this.href)
                    .then(response => {
                        showNotification('Material deleted successfully');
                        setTimeout(() => location.reload(), 1000);
                    })
                    .catch(error => showNotification('Error deleting material', 'error'));
            });
        };
    });

    // Handle form submission
    document.getElementById('materialForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('education_manage.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            if (html.includes('success')) {
                showNotification('Material uploaded successfully');
                setTimeout(() => location.reload(), 1000);
            } else {
                const errorMatch = html.match(/<div class="alert alert-danger">(.*?)<\/div>/);
                if (errorMatch) {
                    showNotification(errorMatch[1], 'error');
                } else {
                    showNotification('Error uploading material', 'error');
                }
            }
        })
        .catch(error => showNotification('Error uploading material', 'error'));
    });
    </script>
    <!-- Bootstrap JS at the end -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 