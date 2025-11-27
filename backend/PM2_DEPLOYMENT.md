# PM2 Deployment for Queue Worker

## Step 1: Install PM2 (if not installed)
```bash
npm install -g pm2
```

## Step 2: Start Queue Worker with PM2
```bash
cd /path/to/your/backend
pm2 start "php artisan queue:work --tries=3 --timeout=300" --name snoutiq-queue
```

## Step 3: Save PM2 Configuration
```bash
pm2 save
```

## Step 4: Setup PM2 to Start on Boot (Run Once)
```bash
pm2 startup
# Follow the command it shows you (usually requires sudo)
```

## Step 5: Remove 10-Second Logic (After Code Deploy)
```bash
php artisan tinker --execute="App\Models\ScheduledPushNotification::where('frequency', 'ten_seconds')->where('is_active', true)->update(['is_active' => false, 'next_run_at' => null]);"
php artisan queue:clear
```

## Useful PM2 Commands

**Check Status:**
```bash
pm2 status
```

**View Logs:**
```bash
pm2 logs snoutiq-queue
```

**Restart After Code Deploy:**
```bash
pm2 restart snoutiq-queue
```

**Stop:**
```bash
pm2 stop snoutiq-queue
```

**Delete:**
```bash
pm2 delete snoutiq-queue
```

## After Code Deployment
Always restart the queue worker:
```bash
pm2 restart snoutiq-queue
```

