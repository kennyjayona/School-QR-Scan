# Project Structure

## Directory Organization

```
smart_classroom/
├── admin/              # Admin module (full system control)
├── advisor/            # Advisor module (classroom management)
├── teacher/            # Teacher module (attendance & grades)
├── student/            # Student module (view-only access)
├── includes/           # Shared components and utilities
├── assets/             # Static resources (CSS, JS, images)
├── uploads/            # User-uploaded files (student photos, QR codes)
├── logs/               # System and error logs
└── docker/             # Docker configuration files
```

## Role-Based Modules

Each role has a dedicated folder with role-specific pages:

- **admin/**: `dashboard_admin.php`, `manage_students.php`, `manage_teachers.php`, `manage_classrooms.php`, `manage_subjects.php`, `analytics.php`, `reports.php`, `user_management.php`
- **advisor/**: `dashboard_advisor.php`, `my_classrooms.php`, `classroom_subjects.php`, `subject_students.php`, `attendance.php`, `grades.php`
- **teacher/**: `dashboard_teacher.php`, `my_subjects.php`, `attendance.php`, `grades.php`
- **student/**: `dashboard_student.php`, `my_attendance.php`, `my_grades.php`, `my_qr.php`

## Shared Components (`includes/`)

- **Headers/Footers**: `{role}_header.php`, `{role}_footer.php` (role-specific navigation)
- **Security**: `permissions.php` (RBAC system), `validation.php` (input validation)
- **Utilities**: `error_handler.php`, `activity_logger.php`, `sms_gateway.php`
- **APIs**: `weather-api.php`, `weather.php`

## Entry Points

- **index.php**: Landing page (redirects to dashboard if logged in)
- **login.php**: Authentication with rate limiting
- **register.php**: User registration
- **dashboard.php**: Role-based dashboard router
- **logout.php**: Session termination

## QR Code System

- **qr_generate.php**: Single student QR generation with photo ID card
- **qr_bulk_generate.php**: Batch QR generation
- **qr_scan_time_in.html/php**: Morning check-in scanner
- **qr_scan_time_out.html/php**: Afternoon check-out scanner
- **school_attendance_handler.php**: Processes QR scans, triggers SMS

## Database Files

- **database.sql**: Complete schema with indexes, views, stored procedures
- **sample_data.sql**: Test accounts and sample data
- **optimize_database.sql**: Performance indexes
- **fix_*.sql**: Schema migration scripts

## Assets (`assets/`)

- **css/**: `style.css`, `modern-dashboard.css`, `enhanced-style.css`, `global-theme.css`
- **js/**: `main.js`, `theme-toggle.js`
- **images/**: `favicon.svg`

## Naming Conventions

- **PHP Files**: `snake_case.php` (e.g., `manage_students.php`)
- **Database Tables**: `snake_case` (e.g., `classroom_subjects`)
- **CSS Classes**: `kebab-case` (e.g., `.menu-item`)
- **JavaScript**: `camelCase` for variables/functions
- **Constants**: `UPPER_SNAKE_CASE` (e.g., `DB_HOST`)

## File Upload Structure

```
uploads/
└── students/
    └── {student_id}_{timestamp}.{ext}
```

## Architecture Patterns

- **MVC-like**: Separation of concerns (includes/ for logic, role folders for views)
- **Role-Based Access Control (RBAC)**: Defined in `includes/permissions.php`
- **Session-Based Auth**: User data stored in `$_SESSION` after login
- **Prepared Statements**: All database queries use parameterized queries
- **Centralized Error Handling**: `ErrorHandler` class in `includes/error_handler.php`
- **Input Validation**: `InputValidator` class in `includes/validation.php`

## Key Configuration

- **Late Time Threshold**: 8:00 AM (defined in `config.php` as `LATE_TIME`)
- **Session Timeout**: 3600 seconds (1 hour)
- **Max Login Attempts**: 5 attempts with 15-minute lockout
- **Upload Paths**: Configurable in `config.php`
