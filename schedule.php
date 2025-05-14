<?php
session_start();
require_once "config/database.php";
// Automatically clean up expired schedules
@include "api/cleanup_schedules.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule - EcoMap</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Add Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Add Tempus Dominus CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6.10.1/dist/css/tempus-dominus.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="main-content">
        <!-- Schedule Content -->
        <div class="container my-5">
            <h2 class="text-center mb-4">Waste Pickup Schedule</h2>
            <ul class="nav nav-tabs modern-tabs mb-4" id="scheduleTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#listView" type="button" role="tab" aria-controls="listView" aria-selected="true">List View</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendarView" type="button" role="tab" aria-controls="calendarView" aria-selected="false">Calendar View</button>
                </li>
            </ul>
            <div class="tab-content" id="scheduleTabsContent">
                <div class="tab-pane fade show active" id="listView" role="tabpanel" aria-labelledby="list-tab">
                    <form method="GET" action="">
                        <div class="row mb-4">
                            <div class="col-md-8 mx-auto">
                                <div class="input-group custom-search-group">
                                    <input type="text" class="form-control search-box" name="search" placeholder="Search by area..." aria-label="Search by area">
                                    <button class="btn btn-success search-btn" type="submit">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <div class="schedule-container">
                        <div class="row">
                            <div class="col-md-8 mx-auto">
                                <div id="scheduleList">
                                    <!-- Schedule items will be loaded here -->
                                    <div class="text-center">
                                        <div class="spinner"></div>
                                        <p>Loading schedules...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="calendarView" role="tabpanel" aria-labelledby="calendar-tab">
                    <div id="calendar" style="max-width:900px;margin:0 auto 40px auto;"></div>
                </div>
            </div>

            <!-- Add Schedule Form (Admin/Driver only) -->
            <?php if(isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'driver')): ?>
            <div class="row mt-5">
                <div class="col-md-8 mx-auto">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0">Add New Schedule</h4>
                        </div>
                        <div class="card-body">
                            <form id="scheduleForm">
                                <div class="mb-3">
                                    <label for="area" class="form-label">Area</label>
                                    <input type="text" class="form-control" id="area" name="area" required>
                                </div>
                                <div class="mb-3">
                                    <label for="pickup_date" class="form-label">Pickup Date</label>
                                    <input type="date" class="form-control" id="pickup_date" name="pickup_date" required>
                                </div>
                                <div class="mb-3 row time-row">
                                    <div class="col-md-6">
                                        <label for="pickupTime" class="form-label">Pickup Start Time</label>
                                        <input id="pickupTime" name="pickupTime" type="text" class="form-control" required placeholder="e.g. 9:00 AM" />
                                    </div>
                                    <div class="col-md-6">
                                        <label for="pickupEndTime" class="form-label">Pickup End Time</label>
                                        <input id="pickupEndTime" name="pickupEndTime" type="text" class="form-control" placeholder="e.g. 10:00 AM" />
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success">Add Schedule</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5>EcoMap</h5>
                    <p>Making waste management smarter and more efficient.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h5>Contact Us</h5>
                    <p>Email: info@ecomap.com<br>Phone: (123) 456-7890</p>
                    <div class="d-flex justify-content-md-end justify-content-center mt-2">
                        <a href="#" class="social-icon" title="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="social-icon" title="Twitter"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="social-icon" title="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="social-icon" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6.10.1/dist/js/tempus-dominus.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js"></script>
    <script>
        // Load schedules when page loads
        document.addEventListener('DOMContentLoaded', () => {
            loadSchedules();
        });

        // Function to load schedules
        async function loadSchedules() {
            try {
                const response = await fetch('api/get_schedules.php');
                const data = await response.json();
                
                if (data.success) {
                    displaySchedule(data.schedules);
                } else {
                    showError('Failed to load schedules');
                }
            } catch (error) {
                console.error('Error loading schedules:', error);
                showError('Error loading schedules');
            }
        }

        // Function to display schedules
        function displaySchedule(schedules) {
            const scheduleList = document.getElementById('scheduleList');
            scheduleList.innerHTML = '';

            if (schedules.length === 0) {
                scheduleList.innerHTML = '<p class="text-center">No schedules found</p>';
                return;
            }

            schedules.forEach(schedule => {
                const scheduleItem = document.createElement('div');
                scheduleItem.className = 'schedule-item';
                let dayOfWeek = '';
                if (schedule.pickup_date) {
                    const d = new Date(schedule.pickup_date);
                    dayOfWeek = d.toLocaleDateString(undefined, { weekday: 'long' });
                }
                let timeStr = formatTime12h(schedule.pickup_time);
                if (schedule.pickup_end_time) {
                    timeStr += ' – ' + formatTime12h(schedule.pickup_end_time);
                }
                scheduleItem.innerHTML = `
                    <h4>${schedule.area}</h4>
                    <p><strong>Time:</strong> ${timeStr}</p>
                    <p><strong>Date:</strong> ${schedule.pickup_date || 'N/A'}${dayOfWeek ? ' (' + dayOfWeek + ')' : ''}</p>
                    ${(window.isAdminOrDriver ? `<button class='btn btn-danger btn-sm mt-2 delete-schedule-btn' data-id='${schedule.id}'>Delete</button>` : '')}
                `;
                scheduleList.appendChild(scheduleItem);
            });

            // Attach delete event listeners
            if (window.isAdminOrDriver) {
                document.querySelectorAll('.delete-schedule-btn').forEach(btn => {
                    btn.addEventListener('click', async function() {
                        const id = this.getAttribute('data-id');
                        const scheduleItem = this.closest('.schedule-item');
                        
                        // Create confirmation notification
                        const confirmNotification = document.createElement('div');
                        confirmNotification.className = 'custom-notification';
                        confirmNotification.innerHTML = `
                            <i class="fas fa-question-circle"></i>
                            <span>Are you sure you want to delete this schedule?</span>
                            <div class="notification-actions">
                                <button class="btn btn-sm btn-light confirm-yes">Yes</button>
                                <button class="btn btn-sm btn-dark confirm-no">No</button>
                            </div>
                        `;
                        document.body.appendChild(confirmNotification);
                        
                        // Add styles for the confirmation notification
                        confirmNotification.style.transform = 'translateX(0)';
                        confirmNotification.style.backgroundColor = 'rgba(52, 58, 64, 0.95)';
                        
                        // Handle confirmation
                        const handleDelete = async (confirmed) => {
                            confirmNotification.remove();
                            
                            if (confirmed) {
                                try {
                                    const res = await fetch('api/delete_schedule.php', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify({ id })
                                    });
                                    const data = await res.json();
                                    if (data.success) {
                                        showNotification('Schedule deleted successfully');
                                        loadSchedules();
                                        if (window.calendar) window.calendar.refetchEvents();
                                    } else {
                                        showNotification(data.message || 'Failed to delete schedule');
                                    }
                                } catch (error) {
                                    showNotification('Error deleting schedule');
                                }
                            }
                        };
                        
                        confirmNotification.querySelector('.confirm-yes').addEventListener('click', () => handleDelete(true));
                        confirmNotification.querySelector('.confirm-no').addEventListener('click', () => handleDelete(false));
                    });
                });
            }
        }

        // Function to show error message
        function showError(message) {
            const scheduleList = document.getElementById('scheduleList');
            scheduleList.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    ${message}
                </div>
            `;
        }

        // Handle schedule form submission
        const scheduleForm = document.getElementById('scheduleForm');
        if (scheduleForm) {
            scheduleForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(scheduleForm);
                // Only validate area, pickup_date, and pickupTime
                const area = formData.get('area');
                const pickupDate = formData.get('pickup_date');
                const pickupTime = formData.get('pickupTime');
                const pickupEndTime = formData.get('pickupEndTime');
                if (!area || !pickupDate || !pickupTime) {
                    showNotification('Please fill in all required fields');
                    return;
                }
                try {
                    const response = await fetch('api/add_schedule.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    if (data.success) {
                        showNotification('Schedule added successfully');
                        scheduleForm.reset();
                        loadSchedules();
                        if (window.calendar) window.calendar.refetchEvents();
                    } else {
                        showNotification(data.message || 'Failed to add schedule');
                    }
                } catch (error) {
                    console.error('Error adding schedule:', error);
                    showNotification('Error adding schedule');
                }
            });
        }

        // Handle area search
        const searchButton = document.getElementById('searchButton');
        const areaSearch = document.getElementById('areaSearch');
        
        if (searchButton && areaSearch) {
            searchButton.addEventListener('click', () => {
                const searchTerm = areaSearch.value.trim();
                if (searchTerm) {
                    // Filter schedules by area
                    const scheduleItems = document.querySelectorAll('.schedule-item');
                    scheduleItems.forEach(item => {
                        const area = item.querySelector('h4').textContent.toLowerCase();
                        if (area.includes(searchTerm.toLowerCase())) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                } else {
                    // Show all schedules
                    const scheduleItems = document.querySelectorAll('.schedule-item');
                    scheduleItems.forEach(item => {
                        item.style.display = 'block';
                    });
                }
            });
        }

        // FullCalendar integration
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            if (calendarEl) {
                window.calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    height: 600,
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    buttonText: {
                        today: 'Today',
                        month: 'Month',
                        week: 'Week',
                        day: 'Day'
                    },
                    events: 'api/get_schedule_events.php',
                    dayMaxEvents: true,
                    moreLinkContent: function() { return 'Pick up'; },
                    eventContent: function(arg) {
                        const start = formatTime12h(arg.event.start.toTimeString().slice(0,5));
                        const end = arg.event.extendedProps && arg.event.extendedProps.pickup_end_time ? formatTime12h(arg.event.extendedProps.pickup_end_time) : '';
                        const area = arg.event.title;
                        let timeStr = start;
                        if (end) timeStr += ' – ' + end;
                        return { domNodes: [document.createTextNode(timeStr), document.createElement('br'), document.createTextNode(area)] };
                    }
                });
                // Only render when tab is shown
                document.getElementById('calendar-tab').addEventListener('shown.bs.tab', function() {
                    window.calendar.render();
                });
            }
        });

        // Set window.isAdminOrDriver in a script block based on PHP session
        window.isAdminOrDriver = <?php echo (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin','driver'])) ? 'true' : 'false'; ?>;

        // Initialize jQuery Timepicker for time fields
        $(function() {
            $('#pickupTime, #pickupEndTime').timepicker({
                timeFormat: 'hh:mm p',
                interval: 15,
                minTime: '12:00am',
                maxTime: '11:45pm',
                dynamic: false,
                dropdown: true,
                scrollbar: true
            });
        });

        function formatTime12h(timeStr) {
            if (!timeStr) return '';
            // Accepts 'HH:MM:SS' or 'HH:MM' and returns 'h:mm AM/PM'
            const [h, m] = timeStr.split(':');
            let hour = parseInt(h, 10);
            const min = m;
            const ampm = hour >= 12 ? 'PM' : 'AM';
            hour = hour % 12;
            if (hour === 0) hour = 12;
            return `${hour}:${min} ${ampm}`;
        }
    </script>
    <style>
    body {
        min-height: 100vh;
        position: relative;
        padding-bottom: 0;
    }
    /* Remove fixed-footer styles for normal flow */
    footer.fixed-footer {
        position: static !important;
        left: unset !important;
        bottom: unset !important;
        width: 100%;
        z-index: 100;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.08);
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

    .notification-actions {
        display: flex;
        gap: 8px;
        margin-left: 16px;
    }

    .notification-actions button {
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 0.9rem;
        transition: all 0.2s;
    }

    .notification-actions button:hover {
        transform: translateY(-1px);
    }

    .confirm-yes:hover {
        background-color: #dc3545 !important;
        color: white !important;
    }
    </style>

    <!-- Add this div at the end of body but before scripts -->
    <div id="notification" class="custom-notification">
        <i class="fas fa-check-circle"></i>
        <span id="notification-text"></span>
        <span class="close-btn" onclick="hideNotification()">×</span>
    </div>

    <script>
    function showNotification(message) {
        const notification = document.getElementById('notification');
        const notificationText = document.getElementById('notification-text');
        notificationText.textContent = message;
        notification.classList.add('show');
        
        // Auto hide after 3 seconds
        setTimeout(() => {
            hideNotification();
        }, 3000);
    }

    function hideNotification() {
        const notification = document.getElementById('notification');
        notification.classList.remove('show');
    }
    </script>
</body>
</html> 