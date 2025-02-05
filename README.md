# Alumni Portal Admin Panel

A secure and feature-rich admin panel for managing an Alumni Portal, built with PHP, MySQL, and modern web technologies.

## Features

### 1. News Management
- Create, edit, and delete news articles
- Image upload functionality
- Draft/Published status management
- Rich text editor for content
- Categorization and tagging

### 2. Events Management
- Comprehensive event scheduling
- Location and venue management
- Event type categorization
- Status tracking (upcoming/ongoing/completed/cancelled)
- Image upload for event banners
- Export capabilities

### 3. Alumni Management
- Detailed alumni profiles
- Batch/Year management
- Employment tracking
- Profile image handling
- CSV import/export functionality
- Advanced search and filtering

### 4. User Management
- Role-based access control (Admin/Editor/Viewer)
- Secure password management
- Account status management
- Activity tracking
- Password reset functionality

### 5. Activity Logs
- Comprehensive activity tracking
- Advanced filtering options
- Export capabilities
- Automatic log rotation
- IP tracking

## Security Features

1. **Authentication & Authorization**
   - Secure session management
   - Role-based access control
   - Password hashing with pepper
   - Brute force protection
   - Session fixation prevention

2. **Data Protection**
   - CSRF protection
   - XSS prevention
   - SQL injection prevention
   - Input validation and sanitization
   - Secure file upload handling

3. **Monitoring & Logging**
   - Detailed activity logging
   - IP tracking
   - Failed login attempts monitoring
   - Critical action logging

## Technical Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled
- GD Library for image processing
- SSL certificate (recommended)

## Installation

1. **Database Setup**
   ```sql
   mysql -u root -p < admin/config/database.sql
   ```

2. **Configuration**
   - Copy `config.example.php` to `config.php`
   - Update database credentials and other settings
   ```php
   cp admin/config/config.example.php admin/config/config.php
   ```

3. **File Permissions**
   ```bash
   chmod 755 -R /path/to/project
   chmod 777 -R /path/to/project/uploads
   ```

4. **Dependencies**
   - Install required PHP extensions
   ```bash
   sudo apt-get install php8.0-mysql php8.0-gd php8.0-mbstring
   ```

## Directory Structure

```
alumni-portal/
├── admin/
│   ├── config/
│   │   ├── config.php
│   │   └── database.sql
│   ├── controllers/
│   │   ├── BaseController.php
│   │   ├── NewsController.php
│   │   ├── EventController.php
│   │   ├── AlumniController.php
│   │   ├── UserController.php
│   │   └── ActivityLogController.php
│   ├── includes/
│   │   ├── Security.php
│   │   └── layout.php
│   └── uploads/
│       ├── news/
│       ├── events/
│       └── alumni/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
└── README.md
```

## Security Best Practices

1. **File Upload Security**
   - Validate file types
   - Use secure file names
   - Store files outside web root
   - Implement size limits

2. **Database Security**
   - Use prepared statements
   - Implement connection pooling
   - Regular backups
   - Secure credentials

3. **Session Security**
   - Secure session configuration
   - Session timeout
   - Session regeneration
   - Secure cookie settings

## Usage Guidelines

1. **User Roles**
   - **Admin**: Full access to all features
   - **Editor**: Can manage content but not users
   - **Viewer**: Read-only access to data

2. **Content Management**
   - Use meaningful titles
   - Upload optimized images
   - Follow content guidelines
   - Regular content review

3. **Data Export/Import**
   - Regular data backups
   - Validate CSV formats
   - Handle large datasets
   - Maintain data integrity

## Maintenance

1. **Regular Tasks**
   - Database optimization
   - Log rotation
   - File cleanup
   - Security updates

2. **Monitoring**
   - Check error logs
   - Monitor disk space
   - Track user activities
   - Performance monitoring

## Support

For support and bug reports, please create an issue in the repository or contact the development team.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## Acknowledgments

- Built with [Tailwind CSS](https://tailwindcss.com/)
- Icons by [Font Awesome](https://fontawesome.com/)
- DataTables for table management
- Security best practices from OWASP

## Roadmap

- [ ] API Integration
- [ ] Email Notifications
- [ ] Advanced Analytics
- [ ] Mobile App Integration
- [ ] Social Media Integration
