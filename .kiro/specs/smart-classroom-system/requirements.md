# Requirements Document

## Introduction

The Smart Classroom Attendance and Grading System is a web-based application designed to automate attendance tracking using QR code scanning technology, manage student grades, and provide real-time SMS notifications to parents. The system serves three user roles: Administrators, Teachers, and Students, each with specific capabilities for managing educational data and monitoring student performance.

## Glossary

- **System**: The Smart Classroom Attendance and Grading System web application
- **QR Scanner Module**: The browser-based webcam component that reads student QR codes using html5-qrcode library
- **Attendance Handler**: The PHP backend component that processes scanned QR codes and records attendance
- **SMS Gateway**: The external API service (Semaphore or Twilio) that sends notifications to parents
- **Admin Dashboard**: The administrative interface for managing users, viewing reports, and analyzing data
- **Grade Entry Module**: The teacher interface for recording and managing student grades
- **Student Portal**: The student-facing interface for viewing attendance and grades
- **Authentication Module**: The login system that manages user sessions and role-based access

## Requirements

### Requirement 1

**User Story:** As an administrator, I want to manage user accounts with role-based access control, so that only authorized users can access specific system features.

#### Acceptance Criteria

1. THE System SHALL provide login functionality that authenticates users with username and password credentials
2. WHEN a user enters valid credentials, THE System SHALL create a session and redirect the user to their role-specific dashboard
3. THE System SHALL hash all passwords using secure hashing algorithms before storing them in the database
4. IF a user enters invalid credentials three consecutive times, THEN THE System SHALL temporarily block login attempts for that account for 15 minutes
5. THE System SHALL enforce role-based access control where Admin users access administrative functions, Teacher users access teaching functions, and Student users access student functions

### Requirement 2

**User Story:** As an administrator, I want to manage student records and generate unique QR codes, so that each student has a scannable identifier for attendance tracking.

#### Acceptance Criteria

1. THE System SHALL provide functionality to create, read, update, and delete student records containing student ID, name, section, year level, and contact number
2. WHEN an administrator creates a new student record, THE System SHALL automatically generate a unique QR code containing the student ID using the phpqrcode library
3. THE System SHALL store generated QR code images in the qrcodes directory with filenames matching the student ID
4. THE System SHALL prevent duplicate student IDs by validating uniqueness before saving new records
5. THE System SHALL provide functionality to export the student list to CSV format

### Requirement 3

**User Story:** As a teacher, I want to scan student QR codes using my webcam to record attendance, so that I can quickly and accurately track which students are present.

#### Acceptance Criteria

1. THE QR Scanner Module SHALL request webcam permission from the browser and display a live camera feed
2. WHEN the QR Scanner Module detects a valid student QR code, THE System SHALL send the student ID to the Attendance Handler via AJAX
3. THE Attendance Handler SHALL record the student ID, current date, current time, and status as "Present" in the attendance table
4. IF a student has already been marked present for the current date, THEN THE System SHALL reject the duplicate entry and display a warning message
5. WHEN attendance is successfully recorded, THE System SHALL trigger the SMS Gateway to send a notification to the parent contact number

### Requirement 4

**User Story:** As a teacher, I want to enter and manage student grades by subject and term, so that I can maintain accurate academic records.

#### Acceptance Criteria

1. THE Grade Entry Module SHALL provide a form to input student ID, subject name, grade value, term, and remarks
2. THE System SHALL validate that grade values are numeric and within the acceptable range of 0 to 100
3. WHEN a teacher submits a grade entry, THE System SHALL check for duplicate entries with the same student ID, subject, and term combination
4. IF a duplicate grade entry is detected, THEN THE System SHALL reject the submission and display an error message
5. THE System SHALL automatically calculate the average grade across all subjects for each term

### Requirement 5

**User Story:** As a student, I want to view my attendance history and grades online, so that I can monitor my academic progress.

#### Acceptance Criteria

1. WHEN a student logs into the Student Portal, THE System SHALL display the student's complete attendance history with date, time, and status
2. THE Student Portal SHALL display all grades organized by subject and term
3. THE System SHALL calculate and display the term average and overall average for the logged-in student
4. THE System SHALL restrict students to viewing only their own attendance and grade data
5. THE Student Portal SHALL provide a printable view of attendance and grade records

### Requirement 6

**User Story:** As an administrator, I want to generate attendance and performance reports with filtering options, so that I can analyze trends and make data-driven decisions.

#### Acceptance Criteria

1. THE Admin Dashboard SHALL provide filters for date range, section, year level, and attendance status
2. WHEN an administrator applies filters, THE System SHALL display attendance records matching all selected criteria
3. THE Admin Dashboard SHALL display summary statistics including total students, daily attendance count, and top absentees
4. THE System SHALL provide functionality to export filtered reports to PDF or CSV format
5. WHERE Chart.js is integrated, THE Admin Dashboard SHALL display visual charts representing attendance trends and grade distributions

### Requirement 7

**User Story:** As a parent, I want to receive real-time SMS notifications when my child arrives at school, so that I can stay informed about their attendance.

#### Acceptance Criteria

1. WHEN the Attendance Handler successfully records attendance, THE System SHALL invoke the SMS Gateway with the parent contact number and attendance message
2. THE SMS Gateway SHALL format the message to include the student name and time of arrival
3. IF the SMS Gateway returns an error response, THEN THE System SHALL retry the SMS transmission up to 2 additional times
4. THE System SHALL log all SMS transmission attempts including success and failure status
5. THE System SHALL continue normal operation even if SMS transmission fails

### Requirement 8

**User Story:** As a system user, I want the application to handle errors gracefully and provide clear feedback, so that I understand what went wrong and how to proceed.

#### Acceptance Criteria

1. IF the QR Scanner Module cannot access the webcam, THEN THE System SHALL display an alert message instructing the user to grant camera permissions
2. WHEN the QR Scanner Module detects an invalid or unrecognized QR code, THE System SHALL display an error message without recording attendance
3. THE System SHALL log all database connection errors to the error_log.txt file in the logs directory
4. THE System SHALL validate all form inputs and display specific error messages for invalid data
5. WHEN a database operation fails, THE System SHALL display a user-friendly error message and log the technical details for administrator review

### Requirement 9

**User Story:** As a system administrator, I want the database to maintain referential integrity and proper indexing, so that data remains consistent and queries perform efficiently.

#### Acceptance Criteria

1. THE System SHALL create a MySQL database with tables for users, students, attendance, and grades
2. THE System SHALL enforce unique constraints on student_id fields to prevent duplicate student records
3. THE System SHALL use foreign key relationships where student_id in attendance and grades tables references the students table
4. THE System SHALL create indexes on frequently queried fields including student_id, date, and section
5. THE System SHALL use prepared statements for all database queries to prevent SQL injection attacks
