# SMS System - Quick Reference Card

## 🚀 Quick Start (3 Minutes)

### 1. Setup Android Device
```
1. Install "SMS Gateway Ultimate" from Play Store
2. Open app → Start HTTP Server
3. Note IP address (e.g., 192.168.1.5:8080)
```

### 2. Configure Server
```php
// Edit sms_config.php
define('SMS_GATEWAY_URL', 'http://192.168.1.5:8080/send');
```

### 3. Create Database Table
```sql
-- Run in phpMyAdmin
SOURCE sms_logs_table.sql;
```

### 4. Test
```
Visit: http://your-server/smart_classroom/sms_test.php
Send test SMS to your phone
```

---

## 📱 Key Files

| File | Purpose |
|------|---------|
| `includes/sms_gateway.php` | SMS sending logic |
| `sms_config.php` | Configuration |
| `school_attendance_handler.php` | Attendance + SMS |
| `qr_scan_time_in.php` | TIME IN scanner |
| `qr_scan_time_out.php` | TIME OUT scanner |
| `sms_test.php` | Testing page |
| `sms_logs_table.sql` | Database schema |

---

## 💻 Code Examples

### Send SMS
```php
require_once 'includes/sms_gateway.php';

$result = SMSGateway::sendSMS('+639388043855', 'Hello!');

if ($result['success']) {
    echo "Sent!";
}
```

### Send TIME IN Notification
```php
SMSGateway::sendTimeInNotification(
    $studentId,
    'Juan Dela Cruz',
    '2025-11-05 07:45:00',
    '+639388043855'
);
```

### Log SMS
```php
SMSGateway::logSMS(
    $studentId,
    '+639388043855',
    'Message text',
    'Success',
    'Gateway response'
);
```

---

## 🔧 Configuration

### Change Gateway URL
```php
// sms_config.php
define('SMS_GATEWAY_URL', 'http://YOUR_IP:PORT/send');
```

### Enable/Disable
```php
define('SMS_ENABLED', true); // or false
```

### Timeout
```php
define('SMS_TIMEOUT', 10); // seconds
```

---

## 📊 Database Queries

### View Recent SMS
```sql
SELECT * FROM sms_logs 
ORDER BY created_at DESC 
LIMIT 20;
```

### Check Failed SMS
```sql
SELECT * FROM sms_logs 
WHERE status = 'Failed' 
ORDER BY created_at DESC;
```

### Today's SMS Count
```sql
SELECT COUNT(*) as total, status 
FROM sms_logs 
WHERE DATE(created_at) = CURDATE() 
GROUP BY status;
```

### Student's SMS History
```sql
SELECT * FROM sms_logs 
WHERE student_id = 1 
ORDER BY created_at DESC;
```

---

## 🐛 Troubleshooting

| Problem | Solution |
|---------|----------|
| SMS not sending | Check Android app is running |
| Connection error | Verify IP address in config |
| Invalid phone | Use format: +639XXXXXXXXX |
| No parent contact | Add phone to student record |
| Gateway timeout | Check Wi-Fi connection |

---

## 📞 Phone Number Formats

All these formats work:
- `+639388043855` ✅
- `639388043855` ✅
- `09388043855` ✅

System auto-converts to: `+639388043855`

---

## 🎯 SMS Message Templates

### TIME IN
```
Hi! Your child [Name] has TIMED IN at [Time] on [Date]. - Smart Classroom
```

### TIME OUT
```
Hi! Your child [Name] has TIMED OUT at [Time] on [Date]. - Smart Classroom
```

---

## ⚡ Quick Commands

### Test SMS from Command Line
```bash
curl "http://192.168.1.5:8080/send?phone=+639388043855&msg=Test"
```

### Check Gateway Status
```bash
curl http://192.168.1.5:8080/status
```

### View Error Logs
```bash
tail -f logs/error_log.txt
```

---

## 🔐 Security Checklist

- [ ] Android device on secure Wi-Fi
- [ ] SMS test page admin-only
- [ ] Database backups enabled
- [ ] Error logging active
- [ ] Phone numbers encrypted (optional)

---

## 📱 Recommended Android Apps

1. **SMS Gateway Ultimate** ⭐
   - Free, reliable, easy setup

2. **SMStoWeb Pro**
   - Paid, advanced features

3. **SMS Gateway API**
   - Free, lightweight

---

## 🎉 Success Indicators

✅ Test SMS received
✅ TIME IN sends SMS
✅ TIME OUT sends SMS
✅ SMS logged in database
✅ No errors in logs

---

## 📚 Full Documentation

- **Setup Guide**: `SMS_SETUP_GUIDE.md`
- **Implementation**: `SMS_IMPLEMENTATION_SUMMARY.md`
- **This Card**: `SMS_QUICK_REFERENCE.md`

---

**Need Help?**
1. Check `sms_test.php`
2. Review `sms_logs` table
3. Read `SMS_SETUP_GUIDE.md`
4. Check Android app logs

---

**System Ready! 🚀**
