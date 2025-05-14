<?php
session_start();
require_once "config/database.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch all educational materials from the database
$materials = [];
$result = $conn->query("SELECT * FROM educational_materials ORDER BY uploaded_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $ext = strtolower(pathinfo($row['filename'], PATHINFO_EXTENSION));
        $img = 'assets/images/default-doc.png';
        if ($ext === 'pdf') $img = 'assets/images/pdf-icon.png';
        if ($ext === 'ppt' || $ext === 'pptx') $img = 'assets/images/ppt-icon.png';
        if ($row['category'] === 'video') $img = 'assets/images/video-icon.png';
        if (!empty($row['thumbnail'])) {
            $img = 'assets/thumbnails/' . $row['thumbnail'];
        }
        $materials[] = [
            'title' => $row['title'],
            'description' => $row['description'],
            'filename' => $row['filename'],
            'category' => $row['category'],
            'type' => $ext,
            'img' => $img,
            'video_type' => isset($row['video_type']) ? $row['video_type'] : ''
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Education - EcoMap</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
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
    }

    .filter-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 30px;
    }
    .tab-btn {
        border: none;
        padding: 10px 20px;
        margin: 0 5px;
        border-radius: 25px;
        background: #fff;
        color: #198754;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .tab-btn:hover {
        background: #198754;
        color: #fff;
        transform: translateY(-2px);
    }
    .tab-btn.active {
        background: #198754;
        color: #fff;
    }
    .search-box {
        border-radius: 25px;
        padding: 10px 20px;
        border: 1px solid #ddd;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .search-btn {
        border-radius: 25px;
        padding: 10px 25px;
        margin-left: 10px;
    }
    .education-card {
        transition: transform 0.3s ease;
        border: none;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    }
    .education-card:hover {
        transform: translateY(-5px);
    }
    .card-img-top {
        height: 200px;
        object-fit: cover;
    }
    .badge {
        padding: 8px 15px;
        border-radius: 15px;
        font-weight: 500;
    }
    .card-body {
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .card-text.flex-grow-1 {
        flex-grow: 1;
    }
    .desc-hover-parent {
        position: relative;
        overflow: visible;
    }
    .desc-popup, .desc-hover-parent:hover .desc-popup {
        display: none !important;
    }
    @media (max-width: 900px) {
        .desc-popup {
            left: 50%;
            top: 100%;
            transform: translate(-50%, 10px);
        }
        .desc-hover-parent:hover .desc-popup {
            left: 50%;
            top: 100%;
            transform: translate(-50%, 10px);
        }
    }
    .custom-search-group .form-control {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
        height: 48px;
        font-size: 1rem;
    }
    .custom-search-group .search-btn {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        height: 48px;
        font-size: 1rem;
        display: flex;
        align-items: center;
    }
    .card-action-wrap {
        width: 100%;
        margin: 0 auto;
    }
    .card-action-btn {
        width: 100%;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 8px;
    }
    .card-action-badge {
        width: 100%;
        margin-top: 4px;
        margin-bottom: 2px;
        font-size: 1rem;
        border-radius: 12px;
        padding: 8px 0;
        letter-spacing: 1px;
    }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!-- Education Content -->
    <div class="container my-5">
        <h2 class="text-center mb-4">Educational Resources</h2>
        
        <div class="filter-section">
            <div class="row mb-4">
                <div class="col-md-8 mx-auto">
                    <div class="input-group custom-search-group">
                        <input type="text" class="form-control search-box" id="resourceSearch" placeholder="Search resources...">
                        <button class="btn btn-success search-btn" type="button" id="searchButton">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 text-center">
                    <button class="btn tab-btn active" data-category="all">All Resources</button>
                    <button class="btn tab-btn" data-category="guide">Guides</button>
                    <button class="btn tab-btn" data-category="presentation">Presentations</button>
                    <button class="btn tab-btn" data-category="video">Videos</button>
                </div>
            </div>
        </div>

        <div class="row" id="resourcesGrid">
            <?php if (empty($materials)): ?>
            <div class="col-12 text-center py-5" id="noResourcesMessage">
                <h4 class="text-muted">No resources available</h4>
                <p class="text-muted">Check back later for educational materials.</p>
            </div>
            <?php else: ?>
            <?php foreach ($materials as $i => $res): ?>
            <div class="col-md-4 mb-4 resource-card" data-category="<?php echo $res['category']; ?>" data-title="<?php echo strtolower($res['title']); ?>" data-description="<?php echo htmlspecialchars($res['description']); ?>">
                <div class="card education-card h-100 d-flex flex-column position-relative desc-hover-parent description-trigger" data-desc="<?php echo htmlspecialchars($res['description']); ?>">
                    <img src="<?php echo $res['img']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($res['title']); ?>">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?php echo htmlspecialchars($res['title']); ?></h5>
                        <div class="mt-auto d-flex flex-column align-items-center card-action-wrap">
                            <?php if ($res['category'] === 'video'): ?>
                                <?php if (!empty($res['filename']) && isset($res['video_type']) && ($res['video_type'] === 'mp4' || $res['video_type'] === 'youtube')): ?>
                                    <a href="#" class="btn btn-success w-100 mb-2 card-action-btn no-desc-modal" data-bs-toggle="modal" data-bs-target="#videoModal<?php echo $i; ?>">
                                        <i class="fas fa-play"></i> Watch
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">No video available</span>
                                <?php endif; ?>
                                <span class="badge bg-secondary w-100 d-block text-center card-action-badge">VIDEO</span>
                            <?php elseif (!empty($res['filename'])): ?>
                                <a href="assets/resources/<?php echo $res['filename']; ?>" class="btn btn-success w-100 mb-2 card-action-btn no-desc-modal" download>
                                    <i class="fas fa-download"></i> Download
                                </a>
                                <span class="badge bg-secondary w-100 d-block text-center card-action-badge"><?php echo strtoupper($res['type']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Video Modals -->
    <?php foreach ($materials as $i => $res): if ($res['category'] === 'video' && !empty($res['filename']) && isset($res['video_type']) && ($res['video_type'] === 'mp4' || $res['video_type'] === 'youtube')): ?>
    <div class="modal fade" id="videoModal<?php echo $i; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo htmlspecialchars($res['title']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="ratio ratio-16x9">
                        <?php if ($res['video_type'] === 'mp4'): ?>
                            <video controls style="width:100%;height:100%;">
                                <source src="assets/resources/<?php echo $res['filename']; ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        <?php elseif ($res['video_type'] === 'youtube'): ?>
                            <?php
                            $yt_id = '';
                            if (preg_match('~(?:youtu.be/|youtube.com/(?:embed/|v/|watch\?v=|watch\?.+&v=))([^&?/\s]{11})~', $res['filename'], $matches)) {
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
    <?php endif; endforeach; ?>

    <!-- Description Modal -->
    <div class="modal fade" id="descModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="descModalTitle">Description</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="descModalBody">
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
    // Tab filtering
    const tabBtns = document.querySelectorAll('.tab-btn');
    const resourceCards = document.querySelectorAll('.resource-card');

    function showNoResultsMessage(message) {
        let noResults = document.querySelector('#noResourcesMessage');
        if (!noResults) {
            noResults = document.createElement('div');
            noResults.className = 'col-12 text-center py-5';
            noResults.id = 'noResourcesMessage';
            document.getElementById('resourcesGrid').appendChild(noResults);
        }
        noResults.innerHTML = `
            <h4 class="text-muted">${message}</h4>
            <p class="text-muted">Try a different category or search term.</p>
        `;
    }

    function hideNoResultsMessage() {
        const noResults = document.querySelector('#noResourcesMessage');
        if (noResults) {
            noResults.remove();
        }
    }

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            tabBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const cat = btn.getAttribute('data-category');
            let visibleCount = 0;
            
            resourceCards.forEach(card => {
                if (cat === 'all' || card.getAttribute('data-category') === cat) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (visibleCount === 0) {
                showNoResultsMessage('No resources found in this category.');
            } else {
                hideNoResultsMessage();
            }
        });
    });

    // Search filtering
    document.getElementById('searchButton').addEventListener('click', () => {
        const searchTerm = document.getElementById('resourceSearch').value.toLowerCase();
        let visibleCount = 0;
        
        resourceCards.forEach(card => {
            const title = card.getAttribute('data-title');
            const desc = card.getAttribute('data-description');
            if (title.includes(searchTerm) || desc.includes(searchTerm)) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (visibleCount === 0) {
            showNoResultsMessage('No resources match your search.');
        } else {
            hideNoResultsMessage();
        }
    });

    document.getElementById('resourceSearch').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            document.getElementById('searchButton').click();
        }
    });
    // Description modal logic
    const descModal = new bootstrap.Modal(document.getElementById('descModal'));
    document.querySelectorAll('.description-trigger').forEach(card => {
        card.addEventListener('click', function(e) {
            // Prevent modal if clicking download/watch button
            if (e.target.closest('.no-desc-modal')) return;
            const desc = this.getAttribute('data-desc');
            const title = this.querySelector('.card-title')?.textContent || 'Description';
            document.getElementById('descModalTitle').textContent = title;
            document.getElementById('descModalBody').innerHTML = desc ? desc.replace(/\n/g, '<br>') : '<em>No description</em>';
            descModal.show();
        });
    });
    // Prevent event bubbling from action buttons
    const noDescBtns = document.querySelectorAll('.no-desc-modal');
    noDescBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
    </script>
</body>
</html> 