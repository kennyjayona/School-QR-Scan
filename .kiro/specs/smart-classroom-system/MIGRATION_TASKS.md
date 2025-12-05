# Migration Tasks: QR to Barcode & Bootstrap to Tailwind

## Overview
This document outlines the tasks required to migrate the Smart Classroom System from QR codes to Code 128 barcodes and from Bootstrap to Tailwind CSS with a mobile-first approach.

## Phase 1: Barcode System Implementation

### Task 1.1: Install and Configure Barcode Libraries
- [ ] Install JsBarcode library via CDN or npm
- [ ] Install QuaggaJS library for barcode scanning
- [ ] Configure QuaggaJS for Code 128 format
- [ ] Test barcode generation and scanning locally
- _Requirements: 2.2, 3.1_

### Task 1.2: Create Public Barcode Scanner Page
- [ ] Create `scan.php` as public page (no authentication)
- [ ] Implement Time In / Time Out mode selection
- [ ] Integrate QuaggaJS for Code 128 scanning
- [ ] Add mobile-optimized camera viewport
- [ ] Implement AJAX attendance submission
- [ ] Add success/error feedback UI
- [ ] Test on mobile devices (iOS/Android)
- _Requirements: 3.1, 3.2, 3.3, 3.7_

### Task 1.3: Update Barcode Generation System
- [ ] Create `barcode_generate.php` endpoint
- [ ] Implement JsBarcode integration for Code 128
- [ ] Update student management to generate barcodes
- [ ] Create `/barcodes/` directory for storage
- [ ] Update database schema: `qr_code_path` → `barcode_path`
- [ ] Migrate existing QR codes to barcodes
- [ ] Update bulk generation page for barcodes
- _Requirements: 2.2, 2.3_

### Task 1.4: Update Attendance Handler
- [ ] Modify `attendance_handler.php` to accept scan_type parameter
- [ ] Implement Time In / Time Out logic
- [ ] Update duplicate checking for Time In only
- [ ] Add validation: Time Out requires existing Time In
- [ ] Update SMS notification for Time In events
- [ ] Test attendance recording flow
- _Requirements: 3.3, 3.4, 3.5, 3.6_

### Task 1.5: Update Student ID Cards
- [ ] Update student profile pages to show barcodes
- [ ] Create printable ID card template with barcode
- [ ] Update `student/my_barcode.php` (rename from my_qr.php)
- [ ] Test barcode printing quality
- _Requirements: 2.2_

## Phase 2: Tailwind CSS Migration (Mobile-First)

### Task 2.1: Setup Tailwind CSS
- [ ] Install Tailwind CSS via CDN or build process
- [ ] Configure tailwind.config.js with DepEd colors
- [ ] Setup JIT compiler for development
- [ ] Create custom utility classes for DepEd theme
- [ ] Remove all Bootstrap CSS references
- [ ] Remove all Bootstrap JavaScript dependencies
- _Requirements: Design Architecture_

### Task 2.2: Create Tailwind Component Library
- [ ] Create reusable button components
- [ ] Create card components
- [ ] Create form input components
- [ ] Create modal components (replace Bootstrap modals)
- [ ] Create navigation components
- [ ] Create table components
- [ ] Create alert/notification components
- [ ] Document component usage
- _Requirements: Design Architecture_

### Task 2.3: Migrate Admin Pages to Tailwind
- [ ] Migrate `admin/dashboard_admin.php`
- [ ] Migrate `admin/manage_students.php`
- [ ] Migrate `admin/manage_teachers.php`
- [ ] Migrate `admin/manage_classrooms.php`
- [ ] Migrate `admin/manage_subjects.php`
- [ ] Migrate `admin/analytics.php`
- [ ] Migrate `admin/reports.php`
- [ ] Migrate `admin/grades_report.php`
- [ ] Migrate `admin/user_management.php`
- [ ] Test all admin functionality
- _Requirements: Design Architecture_

### Task 2.4: Migrate Teacher Pages to Tailwind
- [ ] Migrate `teacher/dashboard_teacher.php`
- [ ] Migrate `teacher/my_subjects.php`
- [ ] Migrate `teacher/grades.php`
- [ ] Test all teacher functionality
- _Requirements: Design Architecture_

### Task 2.5: Migrate Advisor Pages to Tailwind
- [ ] Migrate `advisor/dashboard_advisor.php`
- [ ] Migrate `advisor/my_classrooms.php`
- [ ] Migrate `advisor/classroom_subjects.php`
- [ ] Migrate `advisor/subject_students.php`
- [ ] Migrate `advisor/attendance.php`
- [ ] Migrate `advisor/grades.php`
- [ ] Migrate `advisor/students.php`
- [ ] Test all advisor functionality
- _Requirements: Design Architecture_

### Task 2.6: Migrate Student Pages to Tailwind
- [ ] Migrate `student/dashboard_student.php`
- [ ] Migrate `student/my_attendance.php`
- [ ] Migrate `student/my_grades.php`
- [ ] Migrate `student/my_barcode.php`
- [ ] Test all student functionality
- _Requirements: Design Architecture_

### Task 2.7: Migrate Shared Components to Tailwind
- [ ] Migrate `includes/admin_header.php`
- [ ] Migrate `includes/teacher_header.php`
- [ ] Migrate `includes/advisor_header.php`
- [ ] Migrate `includes/student_header.php`
- [ ] Migrate `includes/admin_footer.php`
- [ ] Migrate `includes/teacher_footer.php`
- [ ] Migrate `includes/advisor_footer.php`
- [ ] Migrate `includes/student_footer.php`
- [ ] Update navigation menus (remove Time In/Out from admin)
- [ ] Test responsive behavior on mobile devices
- _Requirements: Design Architecture_

### Task 2.8: Migrate Public Pages to Tailwind
- [ ] Migrate `login.php`
- [ ] Migrate `register.php`
- [ ] Migrate `index.php`
- [ ] Migrate `scan.php` (new public scanner)
- [ ] Test mobile responsiveness
- _Requirements: Design Architecture_

### Task 2.9: Mobile-First Optimization
- [ ] Optimize all pages for mobile viewport (320px+)
- [ ] Implement touch-friendly buttons (min 44x44px)
- [ ] Add mobile navigation menu
- [ ] Test on various mobile devices
- [ ] Optimize images for mobile
- [ ] Implement lazy loading where appropriate
- [ ] Test performance on mobile networks
- _Requirements: Design Architecture_

## Phase 3: Navigation and Routing Updates

### Task 3.1: Remove Scanner Links from Admin Interface
- [ ] Remove Time In link from admin navigation
- [ ] Remove Time Out link from admin navigation
- [ ] Remove Time In link from advisor navigation
- [ ] Remove Time Out link from advisor navigation
- [ ] Remove Time In link from teacher navigation
- [ ] Remove Time Out link from teacher navigation
- [ ] Update dashboard quick actions
- _Requirements: 3.7_

### Task 3.2: Add Public Scanner Access
- [ ] Create prominent link to `/scan.php` on login page
- [ ] Add QR code/link for easy mobile access
- [ ] Create instructional page for scanner usage
- [ ] Add scanner link to student dashboard (for reference)
- _Requirements: 3.1_

### Task 3.3: Update Database Schema
- [ ] Rename `qrcodes` directory to `barcodes`
- [ ] Update `students.qr_code_path` to `students.barcode_path`
- [ ] Update `school_attendance` table for Time In/Out
- [ ] Add migration script for existing data
- [ ] Test data integrity after migration
- _Requirements: 2.3, 9.1_

## Phase 4: Testing and Documentation

### Task 4.1: Comprehensive Testing
- [ ] Test barcode generation for all students
- [ ] Test barcode scanning on multiple devices
- [ ] Test Time In / Time Out flow
- [ ] Test duplicate attendance prevention
- [ ] Test SMS notifications
- [ ] Test all role-based dashboards
- [ ] Test responsive design on mobile devices
- [ ] Test cross-browser compatibility
- [ ] Perform security testing
- _Requirements: All_

### Task 4.2: Update Documentation
- [ ] Update README.md with barcode instructions
- [ ] Update installation guide
- [ ] Create barcode scanning user guide
- [ ] Update API documentation
- [ ] Create mobile usage guide
- [ ] Update troubleshooting guide
- _Requirements: Documentation_

### Task 4.3: Performance Optimization
- [ ] Optimize Tailwind CSS bundle size
- [ ] Minify JavaScript files
- [ ] Optimize barcode images
- [ ] Test page load times
- [ ] Implement caching strategies
- [ ] Test on slow mobile networks
- _Requirements: Performance_

## Phase 5: Deployment

### Task 5.1: Staging Deployment
- [ ] Deploy to staging environment
- [ ] Run full test suite
- [ ] Perform user acceptance testing
- [ ] Fix any issues found
- [ ] Get stakeholder approval
- _Requirements: Deployment_

### Task 5.2: Production Deployment
- [ ] Backup production database
- [ ] Deploy code to production
- [ ] Run database migrations
- [ ] Verify all functionality
- [ ] Monitor for errors
- [ ] Communicate changes to users
- _Requirements: Deployment_

### Task 5.3: Post-Deployment
- [ ] Monitor system performance
- [ ] Collect user feedback
- [ ] Address any issues
- [ ] Update documentation based on feedback
- [ ] Plan future enhancements
- _Requirements: Maintenance_

## Priority Order

1. **High Priority** (Phase 1): Barcode system implementation
2. **High Priority** (Phase 3): Navigation updates
3. **Medium Priority** (Phase 2): Tailwind CSS migration
4. **Medium Priority** (Phase 4): Testing
5. **Low Priority** (Phase 5): Deployment

## Estimated Timeline

- Phase 1: 2-3 weeks
- Phase 2: 3-4 weeks
- Phase 3: 1 week
- Phase 4: 1-2 weeks
- Phase 5: 1 week

**Total**: 8-11 weeks

## Notes

- Maintain backward compatibility during migration
- Test thoroughly on mobile devices throughout
- Keep old QR code system as fallback initially
- Document all changes for future reference
- Prioritize mobile user experience
