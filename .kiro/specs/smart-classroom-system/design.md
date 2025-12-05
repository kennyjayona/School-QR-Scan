# Design Document

## Overview

The Smart Classroom Attendance and Grading System is a three-tier web application built with PHP, MySQL, and JavaScript. The system uses a traditional server-side rendering architecture with AJAX for real-time interactions. The application follows a role-based access control (RBAC) pattern with four distinct user roles: Admin, Teacher, Advisor, and Student. The frontend uses Bootstrap 5 for responsive design and Tailwind CSS for utility styling, with a custom DepEd color scheme (Blue #0038A8, Red #CE1126, Yellow #FCD116). The system integrates third-party libraries including html5-qrcode for webcam-based QR scanning, phpqrcode for QR code generation, and SMS gateway APIs for parent notifications.


## Architecture

### System Architecture Pattern

The application follows a **Layered Monolithic Architecture** with clear separation of concerns:

1. **Presentation Layer** (Frontend)
   - HTML/PHP views with embedded PHP logic
   - Bootstrap 5 + Tailwind CSS for styling
   - JavaScript for client-side interactions
   - html5-qrcode library for QR scanning

2. **Application Layer** (Backend)
   - PHP scripts handling business logic
   - Session-based authentication
   - Role-based access control middleware
   - AJAX endpoints for asynchronous operations

3. **Data Layer** (Database)
   - MySQL database with normalized schema
   - PDO with prepared statements for security
   - Foreign key constraints for referential integrity

### Directory Structure

```
smart-classroom/
├── admin/                  # Admin-specific pages
│   ├── dashboard_admin.php
│   ├── manage_students.php
│   ├── manage_teachers.php
│   ├── analytics.php
│   └── reports.php
├── teacher/                # Teacher-specific pages
│   ├── dashboard_teacher.php
│   ├── attendance.php
│   └── grades.php
├── advisor/                # Advisor-specific pages
│   └── dashboard_advisor.php
├── student/                # Student-specific pages
│   ├── dashboard_student.php
│   ├── my_attendance.php
│   └── my_grades.php
├── assets/                 # Static resources
│   ├── css/
│   │   ├── global-theme.css
│   │   ├── style.css
│   │   └── enhanced-style.css
│   └── js/
│       ├── main.js
│       └── theme-toggle.js
├── includes/               # Shared components
│   ├── header.php
│   ├── footer.php
│   ├── permissions.php
│   └── activity_logger.php
├── qrcodes/                # Generated QR code images
├── logs/                   # Application logs
├── config.php              # Database configuration
├── db_connect.php          # Database connection
├── login.php               # Authentication entry point
├── dashboard.php           # Role-based dashboard router
├── qr_generate.php         # QR code generation
├── qr_scan.html            # QR scanner interface
├── attendance_handler.php  # Attendance processing
└── send_sms.php            # SMS notification handler
```

### Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **CSS Frameworks**: Bootstrap 5.3, Tailwind CSS 3.x
- **JavaScript Libraries**: 
  - html5-qrcode (QR scanning)
  - Chart.js (data visualization)
- **PHP Libraries**:
  - phpqrcode (QR generation)
  - PDO (database access)
- **SMS Gateway**: Semaphore API / Twilio API

## Components and Interfaces

### 1. Authentication Module

**Purpose**: Manages user login, session handling, and role-based access control.

**Components**:
- `login.php` - Login form and authentication logic
- `logout.php` - Session destruction
- `includes/permissions.php` - RBAC middleware

**Key Functions**:
```php
// Authentication
authenticate($username, $password): bool
createSession($user_data): void
destroySession(): void

// Authorization
checkRole($required_role): bool
hasPermission($permission): bool
```

**Security Features**:
- Password hashing with `password_hash()` (bcrypt)
- Session regeneration on login
- Login attempt throttling (3 attempts, 15-minute lockout)
- SQL injection prevention via prepared statements
- XSS protection via `htmlspecialchars()`

### 2. QR Code Generation Module

**Purpose**: Creates unique QR codes for each student containing their student ID.

**Components**:
- `qr_generate.php` - QR code generation endpoint
- `phpqrcode` library

**Key Functions**:
```php
generateStudentQR($student_id): string
saveQRImage($student_id, $image_data): bool
```

**QR Code Format**:
- Content: Student ID (e.g., "2024-001")
- Size: 300x300 pixels
- Error correction: Level H (high)
- Storage: `/qrcodes/{student_id}.png`

### 3. QR Scanner Module

**Purpose**: Browser-based webcam QR code scanning for attendance tracking.

**Components**:
- `qr_scan.html` - Scanner interface
- `html5-qrcode` library
- `attendance_handler.php` - Backend processor

**Key Functions**:
```javascript
// Frontend
initializeScanner(): void
onScanSuccess(decodedText): void
sendAttendanceData(student_id): Promise

// Backend (attendance_handler.php)
recordAttendance($student_id): array
checkDuplicateAttendance($student_id, $date): bool
```

**Workflow**:
1. User opens `qr_scan.html`
2. Browser requests webcam permission
3. html5-qrcode library scans for QR codes
4. On successful scan, extract student ID
5. AJAX POST to `attendance_handler.php`
6. Backend validates and records attendance
7. Trigger SMS notification
8. Return success/error response

### 4. Attendance Management Module

**Purpose**: Records, validates, and displays attendance data.

**Components**:
- `attendance_handler.php` - Attendance recording
- `teacher/attendance.php` - Teacher attendance view
- `student/my_attendance.php` - Student attendance view
- `admin/reports.php` - Admin attendance reports

**Key Functions**:
```php
recordAttendance($student_id, $date, $time, $status): bool
getStudentAttendance($student_id, $date_range): array
getClassAttendance($section, $date): array
calculateAttendanceRate($student_id, $period): float
```

**Business Rules**:
- One attendance record per student per day
- Duplicate scans on same day are rejected
- Default status: "Present"
- Attendance time recorded to the second
- SMS notification triggered on successful recording

### 5. Grade Management Module

**Purpose**: Allows teachers to enter, update, and manage student grades.

**Components**:
- `teacher/grades.php` - Grade entry interface
- `student/my_grades.php` - Student grade view
- `admin/analytics.php` - Grade analytics

**Key Functions**:
```php
addGrade($student_id, $subject, $grade, $term, $remarks): bool
updateGrade($grade_id, $new_grade): bool
getStudentGrades($student_id, $term): array
calculateTermAverage($student_id, $term): float
calculateOverallAverage($student_id): float
```

**Validation Rules**:
- Grade range: 0-100
- Required fields: student_id, subject, grade, term
- Unique constraint: (student_id, subject, term)
- Numeric validation for grade values

### 6. SMS Notification Module

**Purpose**: Sends real-time SMS notifications to parents when students arrive.

**Components**:
- `send_sms.php` - SMS gateway integration
- SMS gateway API (Semaphore/Twilio)

**Key Functions**:
```php
sendSMS($phone_number, $message): array
formatAttendanceMessage($student_name, $time): string
logSMSAttempt($phone, $status, $response): void
retrySMS($phone, $message, $max_retries): bool
```

**Message Format**:
```
Good morning! Your child [Student Name] has arrived at school at [Time].
```

**Error Handling**:
- Retry logic: Up to 2 retries on failure
- Logging: All attempts logged to database
- Graceful degradation: System continues if SMS fails
- Timeout: 10-second API timeout

### 7. Dashboard Module

**Purpose**: Provides role-specific dashboards with relevant data and actions.

**Components**:
- `dashboard.php` - Role-based router
- `admin/dashboard_admin.php` - Admin dashboard
- `teacher/dashboard_teacher.php` - Teacher dashboard
- `advisor/dashboard_advisor.php` - Advisor dashboard
- `student/dashboard_student.php` - Student dashboard

**Dashboard Features by Role**:

**Admin Dashboard**:
- Total students, teachers, sections
- Daily attendance summary
- Recent activities log
- Quick actions: Add student, generate QR, view reports
- Analytics charts (attendance trends, grade distribution)

**Teacher Dashboard**:
- Assigned classes and sections
- Today's attendance count
- Recent grade entries
- Quick actions: Scan QR, enter grades, view class list

**Advisor Dashboard**:
- Assigned section overview
- Section attendance rate
- Section grade average
- Student performance alerts

**Student Dashboard**:
- Personal attendance rate
- Current grades by subject
- Term average and overall average
- Attendance history

### 8. User Management Module

**Purpose**: Allows admins to create, update, and delete user accounts.

**Components**:
- `admin/manage_students.php` - Student CRUD
- `admin/manage_teachers.php` - Teacher CRUD
- `register.php` - User registration
- `admin_registration.php` - Admin registration

**Key Functions**:
```php
createUser($username, $password, $role, $details): int
updateUser($user_id, $data): bool
deleteUser($user_id): bool
getUsersByRole($role): array
assignTeacherToSection($teacher_id, $section_id): bool
```

### 9. Reporting and Analytics Module

**Purpose**: Generates reports and visualizes data for decision-making.

**Components**:
- `admin/reports.php` - Report generation
- `admin/analytics.php` - Data visualization
- Chart.js for graphs

**Report Types**:
1. **Attendance Reports**
   - Daily attendance by section
   - Student attendance history
   - Absentee list
   - Attendance rate trends

2. **Grade Reports**
   - Student grade cards
   - Class performance summary
   - Subject-wise analysis
   - Top performers list

**Export Formats**:
- PDF (using FPDF or similar)
- CSV (native PHP)
- Excel (using PhpSpreadsheet)

**Filters**:
- Date range
- Section/Class
- Year level
- Subject
- Student

### 10. Theme Toggle Module

**Purpose**: Provides light/dark mode switching with DepEd color scheme.

**Components**:
- `assets/css/global-theme.css` - CSS variables
- `assets/js/theme-toggle.js` - Toggle logic
- `includes/header.php` - Toggle button

**Color Scheme**:

**Light Mode**:
- Primary: #0038A8 (DepEd Blue)
- Secondary: #CE1126 (DepEd Red)
- Accent: #FCD116 (DepEd Yellow)
- Background: #FFFFFF
- Text: #1F2937

**Dark Mode**:
- Primary: #0038A8 (DepEd Blue)
- Secondary: #CE1126 (DepEd Red)
- Accent: #FCD116 (DepEd Yellow)
- Background: #1F2937
- Text: #F3F4F6

**Implementation**:
- CSS custom properties for theming
- LocalStorage for persistence
- JavaScript toggle with smooth transitions

## Data Models

### Database Schema

#### Users Table
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'advisor', 'student') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_username (username),
    INDEX idx_role (role)
);
```

#### Students Table
```sql
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    section VARCHAR(50) NOT NULL,
    year_level INT NOT NULL,
    contact_number VARCHAR(15) NOT NULL,
    parent_contact VARCHAR(15) NOT NULL,
    email VARCHAR(100),
    qr_code_path VARCHAR(255),
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_student_id (student_id),
    INDEX idx_section (section),
    INDEX idx_year_level (year_level)
);
```

#### Attendance Table
```sql
CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(20) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    status ENUM('Present', 'Absent', 'Late', 'Excused') DEFAULT 'Present',
    remarks TEXT,
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_attendance (student_id, date),
    INDEX idx_date (date),
    INDEX idx_student_date (student_id, date)
);
```

#### Grades Table
```sql
CREATE TABLE grades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(20) NOT NULL,
    subject VARCHAR(100) NOT NULL,
    grade DECIMAL(5,2) NOT NULL,
    term VARCHAR(20) NOT NULL,
    school_year VARCHAR(20) NOT NULL,
    remarks TEXT,
    teacher_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_grade (student_id, subject, term, school_year),
    INDEX idx_student_id (student_id),
    INDEX idx_term (term),
    CHECK (grade >= 0 AND grade <= 100)
);
```

#### SMS Logs Table
```sql
CREATE TABLE sms_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    phone_number VARCHAR(15) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('sent', 'failed', 'pending') NOT NULL,
    response TEXT,
    student_id VARCHAR(20),
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at)
);
```

#### Activity Logs Table
```sql
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);
```

#### Sections Table
```sql
CREATE TABLE sections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_name VARCHAR(50) UNIQUE NOT NULL,
    year_level INT NOT NULL,
    advisor_id INT,
    school_year VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (advisor_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_year_level (year_level)
);
```

### Entity Relationships

```
users (1) ----< (M) students (user_id)
users (1) ----< (M) sections (advisor_id)
users (1) ----< (M) grades (teacher_id)
users (1) ----< (M) attendance (recorded_by)
users (1) ----< (M) activity_logs (user_id)

students (1) ----< (M) attendance (student_id)
students (1) ----< (M) grades (student_id)
students (1) ----< (M) sms_logs (student_id)
```

## Error Handling

### Error Categories

1. **Authentication Errors**
   - Invalid credentials
   - Session expired
   - Insufficient permissions
   - Account locked

2. **Validation Errors**
   - Invalid input format
   - Missing required fields
   - Duplicate entries
   - Out-of-range values

3. **Database Errors**
   - Connection failure
   - Query execution failure
   - Constraint violations
   - Deadlocks

4. **External Service Errors**
   - SMS gateway timeout
   - SMS gateway API error
   - Webcam access denied
   - QR code generation failure

5. **System Errors**
   - File system errors
   - Memory exhaustion
   - PHP errors/warnings

### Error Handling Strategy

#### Frontend Error Handling

```javascript
// AJAX error handling
function handleAjaxError(xhr, status, error) {
    let message = 'An error occurred. Please try again.';
    
    if (xhr.status === 401) {
        message = 'Session expired. Please login again.';
        window.location.href = 'login.php';
    } else if (xhr.status === 403) {
        message = 'You do not have permission to perform this action.';
    } else if (xhr.status === 404) {
        message = 'Resource not found.';
    } else if (xhr.status === 500) {
        message = 'Server error. Please contact administrator.';
    }
    
    showAlert(message, 'error');
}

// QR Scanner error handling
function onScanError(error) {
    if (error.includes('NotAllowedError')) {
        showAlert('Camera permission denied. Please allow camera access.', 'error');
    } else if (error.includes('NotFoundError')) {
        showAlert('No camera found on this device.', 'error');
    } else {
        console.error('QR Scan Error:', error);
    }
}
```

#### Backend Error Handling

```php
// Global error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("[$errno] $errstr in $errfile on line $errline");
    
    if ($errno === E_ERROR || $errno === E_USER_ERROR) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'System error occurred']);
        exit;
    }
});

// Database error handling
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    
    if ($e->getCode() == 23000) { // Integrity constraint violation
        return ['success' => false, 'message' => 'Duplicate entry detected'];
    }
    
    return ['success' => false, 'message' => 'Database operation failed'];
}

// SMS error handling with retry
function sendSMSWithRetry($phone, $message, $maxRetries = 2) {
    $attempts = 0;
    
    while ($attempts <= $maxRetries) {
        $result = sendSMS($phone, $message);
        
        if ($result['success']) {
            logSMS($phone, 'sent', $result['response']);
            return true;
        }
        
        $attempts++;
        if ($attempts <= $maxRetries) {
            sleep(2); // Wait 2 seconds before retry
        }
    }
    
    logSMS($phone, 'failed', 'Max retries exceeded');
    return false;
}
```

### Error Logging

```php
// Log to file
function logError($message, $context = []) {
    $logFile = __DIR__ . '/logs/error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = json_encode($context);
    $logMessage = "[$timestamp] $message | Context: $contextStr\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Log to database
function logActivity($userId, $action, $description) {
    global $pdo;
    $sql = "INSERT INTO activity_logs (user_id, action, description, ip_address) 
            VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $action, $description, $_SERVER['REMOTE_ADDR']]);
}
```

### User-Facing Error Messages

- **Generic**: "An error occurred. Please try again."
- **Authentication**: "Invalid username or password."
- **Permission**: "You do not have permission to access this page."
- **Validation**: "Please enter a valid grade between 0 and 100."
- **Duplicate**: "This student has already been marked present today."
- **Not Found**: "Student not found in the system."
- **Network**: "Unable to connect. Please check your internet connection."

## Testing Strategy

### Testing Levels

#### 1. Unit Testing

**Scope**: Individual functions and methods

**Tools**: PHPUnit for PHP, Jest for JavaScript

**Test Cases**:
- Password hashing and verification
- Grade calculation functions
- Date/time formatting functions
- Input validation functions
- QR code generation

**Example**:
```php
// Test grade average calculation
public function testCalculateTermAverage() {
    $grades = [85, 90, 88, 92];
    $average = calculateAverage($grades);
    $this->assertEquals(88.75, $average);
}
```

#### 2. Integration Testing

**Scope**: Component interactions

**Test Cases**:
- Login flow (authentication + session + redirect)
- Attendance recording (QR scan + database + SMS)
- Grade entry (validation + database + calculation)
- Report generation (query + formatting + export)

**Example**:
```php
// Test attendance recording flow
public function testAttendanceRecording() {
    $studentId = '2024-001';
    $result = recordAttendance($studentId);
    
    $this->assertTrue($result['success']);
    $this->assertDatabaseHas('attendance', ['student_id' => $studentId]);
    $this->assertSMSSent($studentId);
}
```

#### 3. Functional Testing

**Scope**: End-to-end user workflows

**Tools**: Selenium WebDriver, manual testing

**Test Scenarios**:
1. **Admin Workflow**
   - Login as admin
   - Add new student
   - Generate QR code
   - View attendance report
   - Export to CSV

2. **Teacher Workflow**
   - Login as teacher
   - Scan student QR code
   - Verify attendance recorded
   - Enter student grades
   - View class performance

3. **Student Workflow**
   - Login as student
   - View attendance history
   - View grades
   - Check term average

#### 4. Security Testing

**Test Cases**:
- SQL injection attempts
- XSS attacks
- CSRF attacks
- Session hijacking
- Brute force login attempts
- Unauthorized access attempts
- File upload vulnerabilities

**Tools**: OWASP ZAP, manual penetration testing

#### 5. Performance Testing

**Metrics**:
- Page load time < 2 seconds
- Database query time < 100ms
- QR scan response time < 500ms
- Concurrent user capacity: 100+ users

**Tools**: Apache JMeter, browser DevTools

**Test Scenarios**:
- 50 concurrent QR scans
- 100 simultaneous grade entries
- Report generation with 1000+ records
- Database query optimization

#### 6. Usability Testing

**Focus Areas**:
- Navigation intuitiveness
- Form clarity
- Error message helpfulness
- Mobile responsiveness
- Accessibility (WCAG 2.1 Level AA)

**Methods**:
- User observation sessions
- Task completion rate
- Time-on-task measurement
- User satisfaction surveys

### Test Data

**Sample Test Accounts**:
```
Admin: admin / admin123
Teacher: teacher1 / teacher123
Student: student1 / student123
```

**Sample Students**:
- 2024-001: Juan Dela Cruz (Grade 7-A)
- 2024-002: Maria Santos (Grade 7-A)
- 2024-003: Pedro Reyes (Grade 8-B)

### Testing Checklist

- [ ] All user roles can login successfully
- [ ] Role-based access control works correctly
- [ ] QR codes generate properly for all students
- [ ] QR scanner detects and processes codes
- [ ] Attendance records correctly with no duplicates
- [ ] SMS notifications send successfully
- [ ] Grades validate within 0-100 range
- [ ] Grade averages calculate correctly
- [ ] Reports filter and export properly
- [ ] Theme toggle persists across sessions
- [ ] All forms validate input correctly
- [ ] Error messages display appropriately
- [ ] Database constraints prevent invalid data
- [ ] Session timeout works correctly
- [ ] Mobile layout is responsive
- [ ] All CRUD operations work for each entity

### Continuous Testing

- Run unit tests on every code commit
- Perform integration tests before deployment
- Conduct security scans weekly
- Monitor production logs for errors
- Gather user feedback continuously
- Update test cases as features evolve

## Design Decisions and Rationales

### 1. Monolithic Architecture
**Decision**: Use a traditional monolithic PHP application instead of microservices.

**Rationale**: 
- Simpler deployment and maintenance for school environment
- Lower infrastructure requirements
- Easier for school IT staff to manage
- Sufficient for expected user load (< 1000 users)

### 2. Session-Based Authentication
**Decision**: Use PHP sessions instead of JWT tokens.

**Rationale**:
- Native PHP support, no additional libraries
- Server-side session control (can revoke immediately)
- Simpler implementation for traditional web app
- Adequate security for school intranet

### 3. Server-Side Rendering
**Decision**: Use PHP server-side rendering instead of SPA framework.

**Rationale**:
- Better SEO (if needed for public pages)
- Faster initial page load
- Works without JavaScript enabled
- Simpler development for PHP developers

### 4. Bootstrap + Tailwind CSS
**Decision**: Use both Bootstrap and Tailwind CSS.

**Rationale**:
- Bootstrap for component library (modals, dropdowns)
- Tailwind for custom utility styling
- Faster development with pre-built components
- Flexibility for custom designs

### 5. Browser-Based QR Scanning
**Decision**: Use html5-qrcode library instead of native mobile app.

**Rationale**:
- No app installation required
- Works on any device with camera and browser
- Easier updates (just update web code)
- Lower development cost

### 6. SMS Gateway Integration
**Decision**: Integrate with third-party SMS API instead of building own.

**Rationale**:
- Reliable delivery through established providers
- No need to manage SMS infrastructure
- Cost-effective pay-per-message model
- Easy to switch providers if needed

### 7. MySQL Database
**Decision**: Use MySQL instead of PostgreSQL or NoSQL.

**Rationale**:
- Widely available in shared hosting
- Familiar to most PHP developers
- Sufficient features for relational data
- Good performance for expected data volume

### 8. File-Based QR Code Storage
**Decision**: Store QR codes as image files instead of generating on-the-fly.

**Rationale**:
- Faster retrieval (no generation overhead)
- Can be printed or downloaded easily
- Reduces server CPU usage
- Simpler implementation

### 9. DepEd Color Scheme
**Decision**: Use official DepEd colors (Blue, Red, Yellow).

**Rationale**:
- Brand consistency with Department of Education
- Professional appearance
- Recognizable to Filipino educators
- Patriotic color scheme

### 10. Light/Dark Mode
**Decision**: Implement theme toggle with localStorage persistence.

**Rationale**:
- Reduces eye strain for users
- Modern UX expectation
- Improves accessibility
- User preference persistence
