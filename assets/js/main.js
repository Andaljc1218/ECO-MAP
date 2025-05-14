// Initialize the map
let map = L.map('map').setView([13.7575, 121.0583], 13); // Centered on Batangas City

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

// Add OpenStreetMap tiles
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Function to add pickup points to the map
function addPickupPoints(points) {
    points.forEach(point => {
        L.marker([point.latitude, point.longitude])
            .bindPopup(`
                <strong>${point.name}</strong><br>
                Schedule: ${point.schedule}
            `)
            .addTo(map);
    });
}

// Function to fetch pickup points from the server
async function fetchPickupPoints() {
    try {
        const response = await fetch('api/get_pickup_points.php');
        const points = await response.json();
        addPickupPoints(points);
    } catch (error) {
        console.error('Error fetching pickup points:', error);
    }
}

// Function to handle user registration
async function handleRegistration(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    try {
        const response = await fetch('api/register.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.success) {
            window.location.href = 'login.php';
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Error during registration:', error);
        alert('An error occurred during registration. Please try again.');
    }
}

// Function to handle user login
async function handleLogin(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    try {
        const response = await fetch('api/login.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.success) {
            window.location.href = 'index.php';
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Error during login:', error);
        alert('An error occurred during login. Please try again.');
    }
}

// Function to handle Life360 integration
function initializeLife360() {
    // Add Life360 integration code here
    console.log('Life360 integration initialized');
}

// Function to handle schedule display
function displaySchedule(schedules) {
    const scheduleContainer = document.querySelector('.schedule-container');
    if (!scheduleContainer) return;

    schedules.forEach(schedule => {
        const scheduleItem = document.createElement('div');
        scheduleItem.className = 'schedule-item';
        scheduleItem.innerHTML = `
            <h4>${schedule.area}</h4>
            <p>Time: ${schedule.pickup_time}</p>
            <p>Days: ${schedule.pickup_days}</p>
        `;
        scheduleContainer.appendChild(scheduleItem);
    });
}

// Initialize the application
document.addEventListener('DOMContentLoaded', () => {
    // Fetch and display pickup points
    fetchPickupPoints();

    // Initialize Life360 if user is logged in
    if (document.body.classList.contains('logged-in')) {
        initializeLife360();
    }

    // Add event listeners for forms
    const registrationForm = document.querySelector('#registration-form');
    if (registrationForm) {
        registrationForm.addEventListener('submit', handleRegistration);
    }

    const loginForm = document.querySelector('#login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
}); 