# 🔍 QR Scanner Debug Guide

## ✅ All Files Updated with Enhanced Error Handling

### Files Modified:
1. ✅ `school_attendance_handler.php` - Enhanced with logging and detailed error messages
2. ✅ `qr_scan_time_in.html` - Added console logging and better error display
3. ✅ `qr_scan_time_out.html` - Added console logging and better error display
4. ✅ `qr_scan_time_in.php` - Added console logging and better error display
5. ✅ `qr_scan_time_out.php` - Added console logging and SMS toggle
6. ✅ `test_scan.php` - Updated to use correct student ID

---

## 🧪 Testing Steps

### Step 1: Run Test Scan
```
http://localhost/smart_classroom/test_scan.php
```

**Expected Result:**
- ✅ SUCCESS! Attendance recorded
- Shows student name, time, and status

**If Error:**
- Check the error message displayed
- Look for "Student not found" → Add student to database
- Look for "Database error" → Check database connection

---

### Step 2: Test Real QR Scanner

#### Option A: HTML Scanner (No Login Required)
```
http://localhost/smart_classroom/qr_scan_time_in.html
```

#### Option B: PHP Scanner (Login Required)
```
http://localhost/smart_classroom/qr_scan_time_in.php
```

---

## 🐛 Debugging Tools

### 1. Browser Console (F12)
Open Developer Tools and check Console tab for:
- `QR Code Scanned: [value]` - Shows what was scanned
- `Response status: 200` - Server responded OK
- `Response text: {...}` - Raw server response
- `Parsed data: {...}` - Parsed JSON data

### 2. PHP Error Logs
Check XAMPP error logs:
```
C:\xampp\apache\logs\error.log
```

Look for:
- "Student found: ID=X, Name=Y" - Student lookup successful
- "TIME IN successful" - Attendance recorded
- Any error messages

### 3. Network Tab (F12)
Check Network tab for:
- POST request to `school_attendance_handler.php`
- Status code (should be 200)
- Response preview (JSON data)

---

## 🔧 Common Issues & Fixes

### Issue 1: "Student not found"
**Cause:** QR code contains ID that doesn't exist in database

**Fix:**
1. Check what ID is in the QR code
2. Go to admin panel → Manage Students
3. Verify student exists with that exact ID
4. Or generate new QR code for existing student

### Issue 2: "Connection Error"
**Cause:** Can't reach the server

**Fix:**
1. Check XAMPP - Apache must be running
2. Check file path - must be in `htdocs/smart_classroom/`
3. Check browser console for exact error

### Issue 3: "Invalid JSON response"
**Cause:** PHP error before JSON output

**Fix:**
1. Check PHP error logs
2. Look at "Response text" in console
3. Fix any PHP syntax errors shown

### Issue 4: Camera Not Working
**Cause:** Browser permissions or HTTPS required

**Fix:**
1. Allow camera permissions when prompted
2. If using Chrome, may need HTTPS or localhost
3. Try different browser (Firefox is more lenient)

### Issue 5: "Already timed in today"
**Cause:** Student already has TIME IN record

**Fix:**
- This is normal! Student can only time in once per day
- To test again, delete today's record from database:
```sql
DELETE FROM school_attendance WHERE date = CURDATE();
```

---

## 📊 Database Check

### View Today's Attendance:
```sql
SELECT sa.*, s.first_name, s.last_name, s.student_id
FROM school_attendance sa
JOIN students s ON sa.student_id = s.id
WHERE sa.date = CURDATE()
ORDER BY sa.time_in DESC;
```

### Clear Today's Records (for testing):
```sql
DELETE FROM school_attendance WHERE date = CURDATE();
```

### Check Student IDs:
```sql
SELECT id, student_id, first_name, last_name FROM students;
```

---

## 🎯 Quick Test Checklist

- [ ] XAMPP running (Apache + MySQL)
- [ ] Database `smart_classroom` exists
- [ ] Table `school_attendance` exists
- [ ] Table `students` has data
- [ ] Student ID in QR matches database
- [ ] Browser allows camera access
- [ ] No PHP errors in logs
- [ ] Console shows "QR Code Scanned"
- [ ] Response status is 200
- [ ] JSON response is valid

---

## 📱 QR Code Format

The QR code should contain just the **student_id** value:
```
mark
```

NOT:
```
{"student_id": "mark"}
```

The handler expects a plain string, not JSON.

---

## 🚀 Success Indicators

When working correctly, you'll see:

**In Browser:**
- ✅ Green success message
- Student name displayed
- Time displayed
- Status (On Time/Late)
- Success beep sound

**In Console:**
```
QR Code Scanned: mark
Response status: 200
Response text: {"status":"success",...}
Parsed data: {status: "success", ...}
```

**In Database:**
- New record in `school_attendance` table
- `time_in` populated
- `status` set to "On Time" or "Late"

---

## 💡 Pro Tips

1. **Test with test_scan.php first** - Eliminates camera/QR issues
2. **Check console ALWAYS** - Shows exact errors
3. **Use HTML version for testing** - No login required
4. **Clear today's records** - To test multiple times
5. **Check student_id format** - Must match exactly

---

## 📞 Still Having Issues?

1. Run `test_connection.php` - Verify database setup
2. Run `test_scan.php` - Verify handler works
3. Check browser console - See exact error
4. Check PHP error logs - See server errors
5. Verify student exists with correct ID

All error messages now include debug information to help identify the issue!
