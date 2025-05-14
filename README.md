# EcoMap - Smart Waste Management System

## Overview
EcoMap is a web-based platform designed to make waste management smarter and more efficient for communities. The system provides an intuitive interface for managing waste collection, educational resources, and environmental initiatives.

## Features

### Core Functionality
- **User Authentication System**
  - Secure login/registration
  - Role-based access (Admin/User)
  - Profile management

### Educational Resources
- **Resource Management**
  - Multiple content types (Guides, Presentations, Videos)
  - File upload support (PDF, PPT, PPTX, MP4)
  - YouTube video integration
  - Custom thumbnails
  - Search and filter functionality

### Design Features
- **Modern UI Elements**
  - Responsive design
  - Card-based layout
  - Custom animations and transitions
  - Interactive hover effects
  - Custom notification system
  - Modal dialogs for confirmations
  - Glassmorphism effects

### Admin Dashboard
- **Material Management**
  - Upload/Edit/Delete educational materials
  - Category management
  - Content organization
  - User management

### Navigation
- **Intuitive Menu Structure**
  - Home
  - Schedule
  - Education
  - Pick up points
  - Ecobot
  - Life360
  - Dashboard
  - Manage Materials

## Technical Specifications

### Frontend
- HTML5
- CSS3
- JavaScript
- Bootstrap 5.3.0
- Font Awesome 6.0.0

### Backend
- PHP
- MySQL Database

### Dependencies
- Bootstrap 5.3.0
- Font Awesome 6.0.0
- Custom CSS/JS libraries

## Installation

1. Clone the repository to your local machine
2. Set up a web server (Apache/Nginx) with PHP support
3. Import the database schema (provided in `database/schema.sql`)
4. Configure database connection in `config/database.php`
5. Ensure proper permissions for upload directories:
   - `assets/resources/`
   - `assets/thumbnails/`
   - `assets/images/`

## Configuration

1. Database Configuration (`config/database.php`):
```php
$host = "your_host";
$username = "your_username";
$password = "your_password";
$database = "your_database";
```

2. File Upload Settings:
- Maximum file size: 10MB
- Supported formats: PDF, PPT, PPTX, MP4, JPG, PNG, GIF

## Usage

### User Guide
1. Register/Login to access the platform
2. Browse educational resources
3. Use search and filter options
4. Download materials or watch videos
5. Access the dashboard for personalized features

### Admin Guide
1. Login with admin credentials
2. Access the admin dashboard
3. Manage educational materials
4. Monitor user activity
5. Update system content

## Security Features
- Password hashing
- Session management
- SQL injection prevention
- XSS protection
- CSRF protection
- Secure file upload validation

## Contributing
1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License
This project is licensed under the MIT License - see the LICENSE file for details.

## Contact
- Email: info@ecomap.com
- Phone: (123) 456-7890

## Acknowledgments
- Bootstrap team
- Font Awesome
- All contributors and testers 