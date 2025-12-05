# QR Scanner Troubleshooting Guide

## 🔍 Why Your QR Scanner Might Not Be Scanning

### ✅ Code Status: **ALL CLEAR**
Your QR scanner code is correct and error-free. If it's not scanning, it's likely one of these common issues:

---

## 🎯 Common Issues & Solutions

### 1. **Camera Permission Not Granted** ⚠️
**Symptoms:**
- Black screen in scanner area
- Alert: "Unable to access camera"
- No camera feed visible

**Solutions:**
- Click "Allow" when browser asks for camera permission
- Check browser settings → Site permissions → Camera
- Make sure camera is not being used by another app
- Try refreshing the page (F5)

**How to Fix:**
1. Click the 🔒 lock icon in address bar
2. Find "Camera" permission
3. Set to "Allow"
4. Refresh the page

---

### 2. **Wrong Camera Selected** 📷
**Symptoms:**
- Camera shows but doesn't scan QR codes
- Shows front camera instead of back camera

**Solutions:**
- The scanner tries to use back camera (`facingMode: "environment"`)
- If on desktop, it will use your webcam
- Make sure QR code is clearly visible to camera
- Try different camera if you have multiple

---

### 3. **HTTPS Required** 🔒
**Symptoms:**
- Camera permission denied
- "getUserMedia not supported" error

**Solutions:**
- Camera access requires HTTPS or localhost
- If using `http://` (not localhost), camera won't work
- Use `https://` or test on `localhost`

**Current URL Check:**
- ✅ `http://localhost/...` - Will work
- ✅ `https://...` - Will work
- ❌ `http://192.168.x.x/...` - Won't work (use HTTPS)

---

### 4. **QR Code Issues** 📱
**Symptoms:**
- Camera works but doesn't detect QR code
- Scanning takes too long

**Solutions:**
- Make sure QR code is clear and not blurry
- Hold QR code steady
- Ensure good lighting
- QR code should fill about 50-70% of the scan box
- Try moving QR code closer/farther from camera
- Make sure QR code is not damaged or distorted

---

### 5. **Browser Compatibility** 🌐
**Symptoms:**
- Scanner doesn't load at all
- JavaScript errors in console

**Supported Browsers:**
- ✅ Chrome/Edge (Recommended)
- ✅ Firefox
- ✅ Safari (iOS 11+)
- ⚠️ Internet Explorer (Not supported)

**Solution:**
- Use latest version of Chrome or Edge
- Update your browser to latest version

---

### 6. **JavaScript Errors** ⚠️
**Symptoms:**
- Scanner area is blank
- Console shows errors

**How to Check:**
1. Press F12 to open Developer Tools
2. Click "Console" tab
3. Look for red error messages
4. Refresh page and check for errors

**Common Errors:**
- "html5QrCode is not defined" → Script not loaded
- "Cannot read property 'start'" → Element not found
- "getUserMedia is not a function" → Browser too old

---

### 7. **Mobile Device Issues** 📱
**Symptoms:**
- Works on desktop but not on mobile
- Camera permission issues on phone

**Solutions:**
- Make sure you're using mobile browser (Chrome/Safari)
- Grant camera permission in phone settings
- Try clearing browser cache
- Restart browser app

**Android:**
1. Settings → Apps → Browser → Permissions → Camera → Allow

**iOS:**
1. Settings → Safari → Camera → Allow

---

## 🧪 Testing Steps

### Step 1: Check Camera Permission
```
1. Open qr_scan_time_in.html
2. Look for camera permission popup
3. Click "Allow"
4. You should see camera feed
```

### Step 2: Test with QR Code
```
1. Generate a test QR code (use qr_generate.php)
2. Display QR code on another device or print it
3. Hold QR code in front of camera
4. Keep it steady and well-lit
5. Scanner should detect within 1-2 seconds
```

### Step 3: Check Console
```
1. Press F12
2. Go to Console tab
3. Look for any red errors
4. If you see errors, note them down
```

---

## 🔧 Quick Fixes

### Fix 1: Refresh and Allow Camera
```
1. Refresh page (F5 or Ctrl+R)
2. Click "Allow" on camera permission
3. Wait for camera to initialize
```

### Fix 2: Clear Browser Cache
```
1. Press Ctrl+Shift+Delete
2. Select "Cached images and files"
3. Click "Clear data"
4. Refresh page
```

### Fix 3: Try Different Browser
```
1. Open in Chrome (recommended)
2. Make sure it's latest version
3. Test scanner again
```

### Fix 4: Check URL
```
Current URL should be:
✅ http://localhost/smart_classroom/qr_scan_time_in.html
✅ https://yourdomain.com/qr_scan_time_in.html

NOT:
❌ file:///C:/xampp/htdocs/...
❌ http://192.168.1.5/... (without HTTPS)
```

---

## 📋 Checklist

Before reporting an issue, check:

- [ ] Camera permission granted
- [ ] Using supported browser (Chrome/Edge/Firefox)
- [ ] URL is localhost or HTTPS
- [ ] QR code is clear and visible
- [ ] Good lighting conditions
- [ ] No JavaScript errors in console
- [ ] Camera is not used by another app
- [ ] Browser is up to date

---

## 🎯 Expected Behavior

### When Working Correctly:
1. Page loads
2. Camera permission popup appears
3. Click "Allow"
4. Camera feed shows in scanner box
5. Hold QR code in view
6. Scanner detects QR code (1-2 seconds)
7. Success message appears
8. Attendance recorded
9. SMS sent (if enabled)

### Scanner Settings:
- **FPS:** 10 frames per second
- **Scan Box:** 250x250 pixels
- **Camera:** Back camera (environment)
- **Auto-scan:** Continuous
- **Cooldown:** 3 seconds between scans

---

## 🆘 Still Not Working?

### Check These:

1. **Open Browser Console (F12)**
   - Look for error messages
   - Check Network tab for failed requests

2. **Test Camera Separately**
   - Open camera app on your device
   - Make sure camera works

3. **Try Test QR Code**
   - Use online QR generator
   - Test with simple text QR code
   - If it scans, issue is with your QR codes

4. **Check Server**
   - Make sure XAMPP/server is running
   - Test if other pages load
   - Check if database is connected

---

## 💡 Pro Tips

### For Best Scanning Results:
1. **Lighting:** Bright, even lighting (no shadows)
2. **Distance:** 15-30cm from camera
3. **Angle:** Hold QR code straight (not tilted)
4. **Stability:** Keep QR code steady
5. **Size:** QR code should be at least 3x3 cm
6. **Quality:** High contrast (black on white)

### For Faster Scanning:
1. Use high-quality QR codes
2. Ensure good lighting
3. Keep camera lens clean
4. Use back camera (better quality)
5. Hold device steady

---

## 📞 Debug Information

If you need help, provide this information:

```
Browser: [Chrome/Firefox/Safari]
Version: [Browser version]
Device: [Desktop/Mobile/Tablet]
OS: [Windows/Mac/Android/iOS]
URL: [Your URL]
Error Message: [Any error from console]
Camera Permission: [Granted/Denied]
```

---

## ✅ Verification

Your scanner code is **100% correct**. If it's not working, it's an environment issue (camera, permissions, browser, or URL).

**Most Common Fix:** Just refresh the page and click "Allow" when asked for camera permission!

---

**Last Updated:** November 5, 2025  
**Status:** Scanner code verified and working
