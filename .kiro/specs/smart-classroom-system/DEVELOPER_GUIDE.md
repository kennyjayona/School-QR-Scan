# Developer Quick Reference Guide

## New Architecture Overview

### Barcode System (Code 128)

#### Generation (Client-Side)
```javascript
// Using JsBarcode
JsBarcode("#barcode", "2024-001", {
    format: "CODE128",
    width: 2,
    height: 100,
    displayValue: true,
    fontSize: 14,
    margin: 10
});
```

#### Scanning (Client-Side)
```javascript
// Using QuaggaJS
Quagga.init({
    inputStream: {
        type: "LiveStream",
        target: document.querySelector('#scanner-container'),
        constraints: {
            facingMode: "environment" // Use back camera on mobile
        }
    },
    decoder: {
        readers: ["code_128_reader"] // Only Code 128
    }
}, function(err) {
    if (err) {
        console.error(err);
        return;
    }
    Quagga.start();
});

Quagga.onDetected(function(result) {
    const code = result.codeResult.code;
    // Send to backend
    recordAttendance(code, 'time_in');
});
```

### Public Scanner Page Structure

```php
// scan.php - No authentication required
<?php
// No session check!
// No login redirect!
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Scanner</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Mobile-first design -->
    <div class="container mx-auto px-4 py-8">
        <!-- Mode Selection -->
        <div class="mb-6">
            <button id="timeInBtn" class="w-full md:w-auto px-8 py-4 bg-blue-600 text-white rounded-lg text-lg">
                Time In
            </button>
            <button id="timeOutBtn" class="w-full md:w-auto px-8 py-4 bg-red-600 text-white rounded-lg text-lg">
                Time Out
            </button>
        </div>
        
        <!-- Scanner Container -->
        <div id="scanner-container" class="w-full h-64 md:h-96 bg-black rounded-lg"></div>
        
        <!-- Result Display -->
        <div id="result" class="mt-6 p-4 rounded-lg hidden"></div>
    </div>
    
    <script src="assets/js/scanner.js"></script>
</body>
</html>
```

### Tailwind CSS Mobile-First Approach

#### Breakpoints
```css
/* Mobile first - no prefix needed */
.class { /* 0px and up */ }

/* Tablet */
@media (min-width: 640px) { .sm:class }

/* Desktop */
@media (min-width: 768px) { .md:class }
@media (min-width: 1024px) { .lg:class }
@media (min-width: 1280px) { .xl:class }
```

#### Example Component
```html
<!-- Mobile-first button -->
<button class="
    w-full          <!-- Full width on mobile -->
    md:w-auto       <!-- Auto width on desktop -->
    px-6 py-3       <!-- Padding -->
    text-lg         <!-- Large text for touch -->
    bg-blue-600     <!-- Background -->
    text-white      <!-- Text color -->
    rounded-lg      <!-- Rounded corners -->
    hover:bg-blue-700  <!-- Hover state -->
    active:bg-blue-800 <!-- Active state for touch -->
    transition      <!-- Smooth transitions -->
">
    Scan Barcode
</button>
```

#### DepEd Color Palette (Tailwind Config)
```javascript
// tailwind.config.js
module.exports = {
    theme: {
        extend: {
            colors: {
                'deped-blue': '#0038A8',
                'deped-red': '#CE1126',
                'deped-yellow': '#FCD116',
            }
        }
    }
}
```

### Navigation Structure

#### Admin Header (No Scanner Links)
```php
<!-- includes/admin_header.php -->
<nav>
    <a href="dashboard_admin.php">Dashboard</a>
    <!-- NO Time In/Out links -->
    <a href="manage_students.php">Students</a>
    <a href="manage_teachers.php">Teachers</a>
    <a href="reports.php">Reports</a>
</nav>
```

#### Public Access
```html
<!-- login.php or index.php -->
<div class="text-center mt-6">
    <a href="scan.php" class="text-blue-600 underline">
        Go to Attendance Scanner
    </a>
</div>
```

### Attendance Handler Updates

```php
// attendance_handler.php
<?php
// Accept scan_type parameter
$student_id = $_POST['student_id'];
$scan_type = $_POST['scan_type']; // 'time_in' or 'time_out'

if ($scan_type === 'time_in') {
    // Check for duplicate Time In
    $check = $conn->prepare("SELECT id FROM school_attendance WHERE student_id = ? AND date = CURDATE() AND time_in IS NOT NULL");
    $check->bind_param("i", $student_id);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Already timed in today']);
        exit;
    }
    
    // Record Time In
    $status = (date('H:i:s') < '08:00:00') ? 'On Time' : 'Late';
    $stmt = $conn->prepare("INSERT INTO school_attendance (student_id, date, time_in, status) VALUES (?, CURDATE(), NOW(), ?)");
    $stmt->bind_param("is", $student_id, $status);
    $stmt->execute();
    
    // Send SMS
    sendSMS($parent_contact, "Your child has arrived at school at " . date('h:i A'));
    
} else if ($scan_type === 'time_out') {
    // Check if Time In exists
    $check = $conn->prepare("SELECT id FROM school_attendance WHERE student_id = ? AND date = CURDATE() AND time_in IS NOT NULL");
    $check->bind_param("i", $student_id);
    $check->execute();
    
    if ($check->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No Time In record found']);
        exit;
    }
    
    // Record Time Out
    $stmt = $conn->prepare("UPDATE school_attendance SET time_out = NOW() WHERE student_id = ? AND date = CURDATE()");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
}

echo json_encode(['success' => true, 'message' => 'Attendance recorded']);
?>
```

### Database Migration Script

```sql
-- Rename column
ALTER TABLE students CHANGE qr_code_path barcode_path VARCHAR(255);

-- Update directory references (if stored in DB)
UPDATE students SET barcode_path = REPLACE(barcode_path, '/qrcodes/', '/barcodes/');

-- Ensure school_attendance table has time_out column
ALTER TABLE school_attendance ADD COLUMN IF NOT EXISTS time_out TIME NULL AFTER time_in;
```

### Component Examples

#### Card Component
```html
<div class="bg-white rounded-lg shadow-md p-6 mb-4">
    <h3 class="text-xl font-bold text-gray-800 mb-2">Card Title</h3>
    <p class="text-gray-600">Card content goes here</p>
</div>
```

#### Form Input
```html
<div class="mb-4">
    <label class="block text-gray-700 font-semibold mb-2" for="input">
        Label
    </label>
    <input 
        type="text" 
        id="input"
        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        placeholder="Enter value"
    >
</div>
```

#### Modal (Tailwind)
```html
<!-- Modal Overlay -->
<div id="modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <!-- Modal Content -->
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold mb-4">Modal Title</h3>
        <p class="text-gray-600 mb-6">Modal content</p>
        <div class="flex justify-end gap-2">
            <button class="px-4 py-2 bg-gray-200 rounded-lg" onclick="closeModal()">
                Cancel
            </button>
            <button class="px-4 py-2 bg-blue-600 text-white rounded-lg">
                Confirm
            </button>
        </div>
    </div>
</div>

<script>
function closeModal() {
    document.getElementById('modal').classList.add('hidden');
}
</script>
```

### Mobile Optimization Tips

1. **Touch Targets**: Minimum 44x44px
```html
<button class="min-w-[44px] min-h-[44px] px-6 py-3">Button</button>
```

2. **Viewport Meta Tag**: Always include
```html
<meta name="viewport" content="width=device-width, initial-scale=1.0">
```

3. **Font Sizes**: Readable on mobile
```html
<p class="text-base md:text-lg">Text that scales</p>
```

4. **Spacing**: More generous on mobile
```html
<div class="p-4 md:p-6 lg:p-8">Content</div>
```

5. **Images**: Responsive
```html
<img src="image.jpg" class="w-full h-auto" alt="Description">
```

### Testing Checklist

- [ ] Test barcode scanning on iPhone
- [ ] Test barcode scanning on Android
- [ ] Test on Chrome mobile
- [ ] Test on Safari mobile
- [ ] Test on slow 3G network
- [ ] Test touch interactions
- [ ] Test landscape orientation
- [ ] Test with different barcode sizes
- [ ] Test Time In flow
- [ ] Test Time Out flow
- [ ] Test duplicate prevention
- [ ] Test SMS notifications

### Common Issues & Solutions

#### Issue: Barcode not scanning
**Solution**: Ensure good lighting, hold steady, adjust distance

#### Issue: Camera not starting
**Solution**: Check HTTPS (required for camera access), check permissions

#### Issue: Tailwind classes not working
**Solution**: Check CDN link, verify class names, check JIT compiler

#### Issue: Mobile layout broken
**Solution**: Check viewport meta tag, test responsive classes, verify breakpoints

### Performance Optimization

1. **Lazy Load Images**
```html
<img src="image.jpg" loading="lazy" alt="Description">
```

2. **Optimize Tailwind**
```bash
# Production build
npx tailwindcss -o output.css --minify
```

3. **Cache Static Assets**
```php
header('Cache-Control: public, max-age=31536000');
```

4. **Compress Images**
- Use WebP format
- Optimize with tools like TinyPNG
- Serve responsive images

---

**Quick Links:**
- [Tailwind CSS Docs](https://tailwindcss.com/docs)
- [QuaggaJS Docs](https://serratus.github.io/quaggaJS/)
- [JsBarcode Docs](https://github.com/lindell/JsBarcode)
