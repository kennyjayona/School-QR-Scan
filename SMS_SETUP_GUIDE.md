# SMS Gateway Setup Guide
## Automatic Parent Notifications for Student Attendance

This system automatically sends SMS notifications to parents when students TIME IN or TIME OUT using an Android SMS Gateway device.

---

## 📱 What You Need

1. **Android Device** (phone or tablet with SIM card)
2. **SMS Gateway App** (one of the following):
   - SMS Gateway Ultimate (Recommended)
   - SMStoWeb Pro
   - SMS Gateway API
3. **Wi-Fi Network** (same network for both Android device and web server)
4. **Active SIM Card** with SMS credits

---

## 🚀 Quick Setup (5 Steps)

### Step 1: Install SMS Gateway App on Android

1. Open Google Play Store on your Android device
2. Search for "SMS Gateway Ultimate" or "SMStoWeb Pro"
3. Install the app
4. Grant all required permissions (SMS, Phone, Storage)

### Step 2: Configure the Android App

1. Open the SMS Gateway app
2. Go to **Settings** or **Configuration**
3. Enable **HTTP Server**
4. Note the **IP Address** and **Port** (e.g., `192.168.1.5:8080`)
5. Set **API Endpoint** to `/send` (if required)
6. Enable **Auto-start** (optional but recommended)

### Step 3: Update Server Configuration

1. Open `sms_config.php` in your project
2. Find this line:
   ```php
   define('SMS_GATEWAY_URL', 'http://192.168.1.5:8080/send');
   ```
3. Replace `192.168.1.5:8080` with your Android device's IP and port
4. Save the file

### Step 4: Create SMS Logs Table

1. Open phpMyAdmin or MySQL client
2. Select your `smart_classroom` database
3. Run the SQL script from `sms_logs_table.sql`:
   ```sql
   CREATE TABLE IF NOT EXISTS sms_logs (
       id INT AUTO_INCREMENT PRIMARY KEY,
       student_id INT NOT NULL,
       phone_number VARCHAR(20) NOT NULL,
       message TEXT NOT NULL,
       status VARCHAR(20) NOT NULL DEFAULT 'Pending',
       response TEXT DEFAULT NULL,
       created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
       INDEX idx_student_id (student_id),
       INDEX idx_status (status),
       FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
   );
   ```

### Step 5: Test the Connection

1. Login as **Admin**
2. Navigate to `http://your-server/smart_classroom/sms_test.php`
3. Enter your phone number
4. Click **Send Test SMS**
5. Check if you receive the SMS

---

## 📋 How It Works

### TIME IN Process

1. Student scans QR code at school entrance
2. System records TIME IN in `school_attendance` table
3. System automatically sends SMS to parent:
   ```
   Hi! Your child [Student Name] has TIMED IN at 7:45 AM on November 5, 2025. - Smart Classroom
   ```
4. SMS is logged in `sms_logs` table

### TIME OUT Process

1. Student scans QR code at school exit
2. System records TIME OUT in `school_attendance` table
3. System automatically sends SMS to parent:
   ```
   Hi! Your child [Student Name] has TIMED OUT at 3:30 PM on November 5, 2025. - Smart Classroom
   ```
4. SMS is logged in `sms_logs` table

---

## 🗂️ File Structure

```
smart_classroom/
├── includes/
│   └── sms_gateway.php          # SMS Gateway Helper Class
├── sms_config.php                # SMS Configuration
├── sms_logs_table.sql            # Database Table Script
├── sms_test.php                  # SMS Testing Page
├── school_attendance_handler.php # Attendance Handler with SMS
├── qr_scan_time_in.php          # TIME IN Scanner
├── qr_scan_time_out.php         # TIME OUT Scanner
└── SMS_SETUP_GUIDE.md           # This file
```

---

## ⚙️ Configuration Options

### Enable/Disable SMS

Edit `sms_config.php`:

```php
// Set to false for testing without sending SMS
define('SMS_ENABLED', true);
```

### Change Gateway URL

Edit `sms_config.php`:

```php
// Update with your Android device IP
define('SMS_GATEWAY_URL', 'http://192.168.1.5:8080/send');
```

### Adjust Timeout

Edit `sms_config.php`:

```php
// Maximum wait time for SMS Gateway response
define('SMS_TIMEOUT', 10);
```

---

## 🔧 Troubleshooting

### Problem: SMS not sending

**Solutions:**
1. Check if Android device and server are on same Wi-Fi
2. Verify SMS Gateway app is running
3. Check IP address in `sms_config.php` matches Android device
4. Test connection using `sms_test.php`
5. Check Android device has SMS credits
6. Review error logs in `sms_logs` table

### Problem: "Connection Error"

**Solutions:**
1. Ping Android device from server: `ping 192.168.1.5`
2. Check firewall settings on Android device
3. Ensure SMS Gateway app HTTP server is started
4. Try accessing gateway URL in browser: `http://192.168.1.5:8080`

### Problem: SMS sent but not received

**Solutions:**
1. Check phone number format (should be +639XXXXXXXXX)
2. Verify SIM card has SMS credits
3. Check if number is blocked or invalid
4. Review SMS Gateway app logs
5. Test with different phone number

### Problem: "Student not found"

**Solutions:**
1. Ensure student has `parent_contact` field filled
2. Check QR code contains correct student ID
3. Verify student exists in database
4. Check `students` table has `parent_contact` column

---

## 📊 Database Schema

### sms_logs Table

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Auto-increment primary key |
| student_id | INT | Foreign key to students table |
| phone_number | VARCHAR(20) | Parent phone number |
| message | TEXT | SMS message content |
| status | VARCHAR(20) | Success or Failed |
| response | TEXT | Gateway response |
| created_at | DATETIME | Timestamp |

### school_attendance Table

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Auto-increment primary key |
| student_id | INT | Foreign key to students table |
| date | DATE | Attendance date |
| time_in | TIME | Time in timestamp |
| time_out | TIME | Time out timestamp |
| status | VARCHAR(20) | On Time or Late |
| created_at | DATETIME | Record creation time |

---

## 🔐 Security Notes

1. **Network Security**: Keep Android device and server on secure Wi-Fi
2. **Access Control**: Only admins can access `sms_test.php`
3. **Data Privacy**: SMS logs contain personal phone numbers
4. **Rate Limiting**: Consider implementing SMS rate limits
5. **Backup**: Regularly backup `sms_logs` table

---

## 📱 Supported SMS Gateway Apps

### SMS Gateway Ultimate (Recommended)
- **Play Store**: [SMS Gateway Ultimate](https://play.google.com/store/apps/details?id=com.smsgateway)
- **Features**: HTTP API, Auto-start, Multiple devices
- **Cost**: Free with ads, Pro version available

### SMStoWeb Pro
- **Play Store**: [SMStoWeb Pro](https://play.google.com/store/apps/details?id=com.smstowebpro)
- **Features**: REST API, Webhooks, Scheduling
- **Cost**: Paid app

### SMS Gateway API
- **Play Store**: [SMS Gateway API](https://play.google.com/store/apps/details?id=com.smsgatewayapi)
- **Features**: Simple HTTP API, Lightweight
- **Cost**: Free

---

## 🎯 API Endpoints

### Send SMS (Android Gateway)

```
GET http://192.168.1.5:8080/send?phone=+639388043855&msg=Hello
```

**Parameters:**
- `phone`: Phone number with country code
- `msg`: URL-encoded message text

**Response:**
```json
{
  "success": true,
  "message": "SMS sent successfully"
}
```

---

## 📞 Support

For issues or questions:
1. Check `sms_logs` table for error messages
2. Review Android SMS Gateway app logs
3. Test connection using `sms_test.php`
4. Check server error logs
5. Verify network connectivity

---

## ✅ Checklist

- [ ] Android device with SIM card
- [ ] SMS Gateway app installed
- [ ] HTTP server started in app
- [ ] IP address noted
- [ ] `sms_config.php` updated
- [ ] `sms_logs` table created
- [ ] Test SMS sent successfully
- [ ] Students have parent phone numbers
- [ ] TIME IN/OUT tested

---

## 🎉 You're All Set!

Once setup is complete, SMS notifications will be sent automatically whenever students TIME IN or TIME OUT. Monitor the `sms_logs` table to track all SMS activity.

**Happy Teaching! 📚**
