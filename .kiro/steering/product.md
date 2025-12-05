# Smart Classroom System

A comprehensive web-based classroom management system for educational institutions with mobile-first design.

## Core Purpose

Streamline classroom operations through barcode-based attendance tracking, grade management, and real-time parent notifications.

## Key Features

- **Barcode Attendance**: Public scanning page with Code 128 barcodes for time-in/time-out with automatic late detection (cutoff: 8:00 AM)
- **SMS Notifications**: Real-time alerts to parents via Semaphore API when students arrive/leave
- **Grade Management**: Multi-quarter grading system (1st-4th Quarter) with subject-based tracking
- **Role-Based Access**: Four user types (Admin, Advisor, Teacher, Student) with distinct permissions
- **Reports & Analytics**: Attendance rates, grade summaries, and exportable PDF/CSV reports
- **Mobile-First Design**: Optimized for smartphones and tablets using Tailwind CSS
- **Public Scan Page**: No authentication required for barcode scanning

## User Roles

- **Admin**: Full system control, user management, analytics
- **Advisor**: Classroom management, student enrollment, teacher assignment
- **Teacher**: Grade entry, reports viewing
- **Student**: View personal grades, attendance history, download barcode
- **Public**: Access barcode scanning page for attendance (no login required)

## System Status

Production-ready (v1.0.0) with 92/100 health score. Security hardened with bcrypt passwords, SQL injection protection, XSS prevention, and rate limiting (5 attempts, 15-min lockout).
