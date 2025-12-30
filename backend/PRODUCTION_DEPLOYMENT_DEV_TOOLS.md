# Production Deployment Checklist: `/dev/notify` & `/dev/fcm-test`

## Required Files for Production

### 1. Routes
- ✅ `backend/routes/web.php` (lines 105, 108-110)
  - `/dev/fcm-test` route
  - `/dev/notify` routes (GET, POST, GET /next)

### 2. Controllers
- ✅ `backend/app/Http/Controllers/Dev/NotificationPlaygroundController.php`
- ✅ `backend/app/Http/Controllers/Api/PushController.php` (for `/api/push/*` endpoints)

### 3. Views (Blade Templates)
- ✅ `backend/resources/views/dev/notify.blade.php`
- ✅ `backend/resources/views/fcm/test.blade.php`

### 4. Public Assets (Service Worker)
- ✅ `backend/public/firebase-messaging-sw.js`
  - **IMPORTANT**: Update Firebase config in this file for production if different from dev

### 5. Models (Required Dependencies)
- ✅ `backend/app/Models/Notification.php`
- ✅ `backend/app/Models/Appointment.php`
- ✅ `backend/app/Models/User.php`
- ✅ `backend/app/Models/DeviceToken.php`

### 6. Jobs (Queue Processing)
- ✅ `backend/app/Jobs/SendNotificationJob.php`
- ✅ `backend/app/Services/Notifications/NotificationChannelService.php`

### 7. Services (FCM Integration)
- ✅ `backend/app/Services/Push/FcmService.php`
- ✅ `backend/app/Support/DeviceTokenOwnerResolver.php` (if exists)

### 8. Configuration Files
- ✅ `backend/config/firebase.php`
- ✅ `backend/config/push.php` (if exists)
- ✅ `backend/config/app.php` (for timezone)

### 9. Environment Variables (.env)
Required in production `.env`:
```env
# Firebase Configuration
FIREBASE_CREDENTIALS=/path/to/service-account.json
FIREBASE_PROJECT_ID=snoutiqapp
FIREBASE_DATABASE_URL=https://snoutiqapp-default-rtdb.firebaseio.com
FIREBASE_STORAGE_BUCKET=snoutiqapp.firebasestorage.app

# Queue Configuration
QUEUE_CONNECTION=database  # or redis/sqs

# App Configuration
APP_TIMEZONE=Asia/Kolkata  # or your timezone
APP_URL=https://your-domain.com
```

### 10. Database Tables
Ensure these tables exist (run migrations):
- ✅ `notifications`
- ✅ `device_tokens`
- ✅ `appointments`
- ✅ `users`
- ✅ `jobs` (for queue)
- ✅ `failed_jobs` (for queue)

### 11. Composer Dependencies
Ensure these packages are installed:
- ✅ `kreait/firebase-php` (for FCM)
- ✅ Laravel Queue package

## Production Deployment Steps

### Step 1: Copy Files
```bash
# Controllers
cp backend/app/Http/Controllers/Dev/NotificationPlaygroundController.php /path/to/production/app/Http/Controllers/Dev/

# Views
cp backend/resources/views/dev/notify.blade.php /path/to/production/resources/views/dev/
cp backend/resources/views/fcm/test.blade.php /path/to/production/resources/views/fcm/

# Service Worker
cp backend/public/firebase-messaging-sw.js /path/to/production/public/

# Routes (merge into existing web.php)
# Add lines 105, 108-110 from routes/web.php
```

### Step 2: Update Routes
Add to production `routes/web.php`:
```php
Route::view('/dev/fcm-test', 'fcm.test')->name('dev.fcm-test');
Route::get('/dev/notify', [\App\Http\Controllers\Dev\NotificationPlaygroundController::class, 'index'])->name('dev.notify');
Route::post('/dev/notify', [\App\Http\Controllers\Dev\NotificationPlaygroundController::class, 'send'])->name('dev.notify.send');
Route::get('/dev/notify/next', [\App\Http\Controllers\Dev\NotificationPlaygroundController::class, 'nextAppointment'])->name('dev.notify.next');
```

### Step 3: Update Service Worker Config
Edit `public/firebase-messaging-sw.js`:
- Update `firebaseConfig` if production Firebase project is different
- Update `VAPID_KEY` if production VAPID key is different

### Step 4: Set Environment Variables
Update production `.env`:
```bash
# Firebase credentials path (absolute path)
FIREBASE_CREDENTIALS=/var/www/snoutiq/storage/app/firebase/service-account.json

# Firebase project settings
FIREBASE_PROJECT_ID=snoutiqapp
FIREBASE_DATABASE_URL=https://snoutiqapp-default-rtdb.firebaseio.com
FIREBASE_STORAGE_BUCKET=snoutiqapp.firebasestorage.app

# Queue
QUEUE_CONNECTION=database  # or redis

# App
APP_TIMEZONE=Asia/Kolkata
APP_URL=https://your-domain.com
```

### Step 5: Upload Firebase Service Account JSON
```bash
# Place Firebase service account JSON file at:
/path/to/production/storage/app/firebase/service-account.json

# Ensure proper permissions:
chmod 600 /path/to/production/storage/app/firebase/service-account.json
chown www-data:www-data /path/to/production/storage/app/firebase/service-account.json
```

### Step 6: Run Migrations (if not already done)
```bash
cd /path/to/production
php artisan migrate
```

### Step 7: Clear Cache
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### Step 8: Ensure Queue Worker is Running
```bash
# Using supervisor/systemd/pm2
php artisan queue:work --tries=3 --timeout=300

# Or with PM2:
pm2 start "php artisan queue:work --tries=3 --timeout=300" --name snoutiq-queue
```

## Security Considerations

### ⚠️ IMPORTANT: Protect Dev Routes in Production

These routes should be **protected** in production. Add middleware to restrict access:

**Option 1: IP Whitelist (Recommended)**
```php
// In routes/web.php, wrap dev routes:
Route::middleware(['ip'])->group(function () {
    Route::view('/dev/fcm-test', 'fcm.test')->name('dev.fcm-test');
    Route::get('/dev/notify', [NotificationPlaygroundController::class, 'index'])->name('dev.notify');
    // ... other dev routes
});
```

Then in `app/Http/Middleware/CheckIp.php` (create if doesn't exist):
```php
public function handle($request, Closure $next)
{
    $allowedIps = ['127.0.0.1', 'YOUR_OFFICE_IP', 'YOUR_VPN_IP'];
    if (!in_array($request->ip(), $allowedIps)) {
        abort(403, 'Access denied');
    }
    return $next($request);
}
```

**Option 2: Authentication Middleware**
```php
Route::middleware(['auth'])->group(function () {
    // Only authenticated users (admins) can access
    Route::view('/dev/fcm-test', 'fcm.test')->name('dev.fcm-test');
    // ...
});
```

**Option 3: Environment Check**
```php
// In NotificationPlaygroundController.php constructor:
public function __construct()
{
    if (app()->environment('production')) {
        $this->middleware('auth'); // or IP check
    }
}
```

## Testing After Deployment

1. **Test FCM Test Page:**
   - Visit: `https://your-domain.com/dev/fcm-test`
   - Register service worker
   - Get FCM token
   - Register token with user_id

2. **Test Notification Playground:**
   - Visit: `https://your-domain.com/dev/notify`
   - Send test notification to a user
   - Check queue worker logs
   - Verify notification in database

3. **Check Queue Processing:**
   ```bash
   tail -f storage/logs/laravel.log | grep -i notification
   ```

## Troubleshooting

### Issue: Service Worker Not Loading
- Check `public/firebase-messaging-sw.js` is accessible
- Verify Firebase config matches production
- Check browser console for errors

### Issue: FCM Not Sending
- Verify `FIREBASE_CREDENTIALS` path is correct
- Check service account JSON file exists and is readable
- Check queue worker is running
- Review `storage/logs/laravel.log` for errors

### Issue: Routes Not Found
- Run `php artisan route:clear`
- Run `php artisan route:list | grep dev` to verify routes

### Issue: 500 Errors
- Check Laravel logs: `storage/logs/laravel.log`
- Verify all dependencies installed: `composer install --no-dev`
- Check file permissions on storage directory

## File Checklist Summary

```
✅ Routes: routes/web.php
✅ Controller: app/Http/Controllers/Dev/NotificationPlaygroundController.php
✅ API Controller: app/Http/Controllers/Api/PushController.php
✅ Views: resources/views/dev/notify.blade.php
✅ Views: resources/views/fcm/test.blade.php
✅ Service Worker: public/firebase-messaging-sw.js
✅ Models: app/Models/Notification.php, Appointment.php, User.php, DeviceToken.php
✅ Jobs: app/Jobs/SendNotificationJob.php
✅ Services: app/Services/Notifications/NotificationChannelService.php
✅ Services: app/Services/Push/FcmService.php
✅ Config: config/firebase.php
✅ Config: config/push.php (if exists)
✅ Env: .env (FIREBASE_* variables)
✅ Firebase Credentials: storage/app/firebase/service-account.json
```

## Quick Deploy Script

```bash
#!/bin/bash
# deploy-dev-tools.sh

PROD_PATH="/var/www/snoutiq"
BACKEND_PATH="./backend"

echo "Deploying dev tools to production..."

# Copy files
cp -r $BACKEND_PATH/app/Http/Controllers/Dev $PROD_PATH/app/Http/Controllers/
cp $BACKEND_PATH/resources/views/dev/notify.blade.php $PROD_PATH/resources/views/dev/
cp $BACKEND_PATH/resources/views/fcm/test.blade.php $PROD_PATH/resources/views/fcm/
cp $BACKEND_PATH/public/firebase-messaging-sw.js $PROD_PATH/public/

# Clear cache
cd $PROD_PATH
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "Deployment complete! Don't forget to:"
echo "1. Update routes/web.php"
echo "2. Update .env with Firebase credentials"
echo "3. Upload Firebase service-account.json"
echo "4. Add security middleware to protect dev routes"
```










