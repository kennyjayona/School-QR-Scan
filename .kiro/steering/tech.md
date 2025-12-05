# Technology Stack

## Core Technologies

- **Backend**: PHP 7.4+ with MySQLi (procedural and OOP)
- **Database**: MySQL 5.7+ with InnoDB engine
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Server**: Apache with mod_rewrite

## Libraries & Frameworks

### Frontend
- **Bootstrap 5.3.0**: UI framework and responsive grid
- **Font Awesome 6.4.0**: Icon library
- **QRCode.js**: QR code generation
- **html5-qrcode**: Camera-based QR scanning
- **JsBarcode**: Barcode generation
- **html2canvas**: HTML to image conversion for ID cards

### Backend
- **PHP GD Extension**: Image manipulation for QR codes
- **PHP MySQLi**: Database connectivity with prepared statements

## External APIs

- **Semaphore SMS API**: Parent notifications (configurable in `sms_config.php`)
- Alternative: Twilio support available

## Database Architecture

- **Character Set**: utf8mb4 (supports emojis and international characters)
- **Collation**: utf8mb4_unicode_ci (case-insensitive)
- **Engine**: InnoDB (foreign keys, transactions)
- **Indexes**: Composite indexes on common query patterns
- **Views**: Pre-built views for attendance/grade summaries
- **Stored Procedures**: `sp_get_attendance_rate`, `sp_get_average_grade`

## Common Commands

### Database Setup
```bash
# Create and import database
mysql -u root -p
CREATE DATABASE smart_classroom;
exit

# Import schema
mysql -u root -p smart_classroom < database.sql

# Import sample data (optional)
mysql -u root -p smart_classroom < sample_data.sql

# Optimize database
mysql -u root -p smart_classroom < optimize_database.sql
```

### Development Server (XAMPP)
```bash
# Start Apache and MySQL
# Access: http://localhost/smart_classroom/
```

### File Permissions (Production)
```bash
chmod 755 uploads/ logs/
chmod 600 config.php db_connect.php
chown -R www-data:www-data /path/to/smart_classroom
```

## Configuration Files

- **config.php**: Database credentials, SMS API keys, system settings
- **config.example.php**: Template for configuration
- **db_connect.php**: Database connection and helper functions
- **sms_config.php**: SMS gateway configuration

## Security Features

- Bcrypt password hashing (`password_hash`, `password_verify`)
- Prepared statements for SQL injection prevention
- `htmlspecialchars()` for XSS protection
- Session security (httponly, samesite flags)
- Rate limiting on login attempts
- CSRF protection via POST-only sensitive operations
