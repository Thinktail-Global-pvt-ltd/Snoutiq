# Debug 30-Minute Appointment Reminders

## Issues Fixed

1. **Added Logging**: The service now logs why reminders are/aren't being sent
2. **Added 1-minute buffer**: Allows reminders to be sent slightly early to catch edge cases
3. **Created Debug Command**: Run `php artisan notifications:debug-reminders` to see detailed info

## Common Issues & Solutions

### Issue 1: `patient_user_id` Not Found

**Problem**: Appointment `notes` field doesn't have `patient_user_id` in JSON format.

**Solution**: When creating appointment, ensure `notes` contains:
```json
{"patient_user_id": 1}
```

**Check**:
```bash
php artisan tinker
```
```php
$appt = \App\Models\Appointment::find(1);
dd($appt->notes); // Should be JSON with patient_user_id
```

### Issue 2: Time Window Too Strict

**Problem**: Reminder only sends if current time is exactly >= reminder time AND < start time.

**Solution**: Added 1-minute buffer before reminder time. Now sends if:
- Current time >= (reminder time - 1 minute) AND
- Current time < start time

### Issue 3: No Device Tokens

**Problem**: User has no device tokens, so notification fails silently.

**Check**:
```bash
php artisan tinker
```
```php
$userId = 1;
\App\Models\DeviceToken::where('user_id', $userId)->count();
```

### Issue 4: Timezone Mismatch

**Problem**: Appointment time stored in one timezone, but service uses `config('app.timezone')`.

**Check**:
```bash
php artisan tinker
```
```php
config('app.timezone'); // Should match your appointment times
```

## Debug Command

Run this to see detailed debugging info:

```bash
cd backend
php artisan notifications:debug-reminders
```

This will show:
- All appointments being checked
- Their start times and reminder times
- Whether they're in the correct time window
- Whether `patient_user_id` is found
- Whether user has device tokens
- Why reminders are/aren't being sent

## Testing Steps

1. **Create test appointment** (30-40 minutes from now):
```bash
php artisan tinker
```
```php
use App\Models\Appointment;

$appt = Appointment::create([
    'vet_registeration_id' => 1,
    'doctor_id' => 1,
    'name' => 'Test User',
    'mobile' => '9999999999',
    'pet_name' => 'Tommy',
    'appointment_date' => now()->toDateString(),
    'appointment_time' => now()->addMinutes(40)->format('H:i'),
    'status' => 'confirmed',
    'notes' => json_encode(['patient_user_id' => 1]), // IMPORTANT!
]);
```

2. **Run debug command**:
```bash
php artisan notifications:debug-reminders
```

3. **Check logs**:
```bash
tail -f storage/logs/laravel.log | grep -i reminder
```

4. **Check notifications table**:
```bash
php artisan tinker
```
```php
\App\Models\Notification::where('type', 'consult_pre_reminder')->latest()->first();
```

5. **Check queue jobs**:
```bash
php artisan tinker
```
```php
\DB::table('jobs')->latest()->first();
```

## Quick Fix Checklist

- [ ] Scheduler running: `php artisan schedule:work`
- [ ] Queue worker running: `php artisan queue:work --tries=3 --timeout=300`
- [ ] Appointment has `notes` with `patient_user_id` in JSON
- [ ] Appointment status is `confirmed` or `rescheduled`
- [ ] `reminder_30m_sent_at` is NULL
- [ ] User has device tokens in `device_tokens` table
- [ ] Timezone configured correctly in `.env` (`APP_TIMEZONE`)

## Manual Test (Force Send)

To test immediately without waiting 30 minutes:

```bash
php artisan tinker
```
```php
use App\Models\Appointment;
use Carbon\Carbon;

// Get an appointment
$appt = Appointment::where('status', 'confirmed')->first();

// Manually set reminder time to now
$startTime = Carbon::createFromFormat('Y-m-d H:i', $appt->appointment_date . ' ' . $appt->appointment_time);
$appt->appointment_time = now()->addMinutes(30)->format('H:i');
$appt->save();

// Run reminder command
Artisan::call('notifications:consult-reminders');
```















