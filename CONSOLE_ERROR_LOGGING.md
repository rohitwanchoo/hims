# Browser Console Error Logging

This system automatically captures JavaScript console errors from the browser and logs them to the server.

## How It Works

1. **JavaScript Logger** (`public/js/console-logger.js`)
   - Automatically loads on every page
   - Captures console.error() and console.warn() calls
   - Captures unhandled JavaScript errors
   - Captures unhandled Promise rejections
   - Sends errors to server without blocking the page

2. **Server Endpoint** (`/api/log-frontend-error`)
   - Public endpoint (no authentication required)
   - Receives error data from browser
   - Logs to separate frontend log file

3. **Log File Location**
   storage/logs/frontend-YYYY-MM-DD.log (dated files)

## Viewing Frontend Errors

### View Today's Errors (Live)
tail -f storage/logs/frontend-$(date +%Y-%m-%d).log

### View Last 100 Lines from Today
tail -n 100 storage/logs/frontend-$(date +%Y-%m-%d).log

### View All Frontend Logs
cat storage/logs/frontend-*.log

### Search for Specific Errors
grep "TypeError" storage/logs/frontend-*.log

### Clear Today's Log
sudo truncate -s 0 storage/logs/frontend-$(date +%Y-%m-%d).log

### List All Frontend Log Files
ls -lh storage/logs/frontend-*.log

## Log Format

Each error entry contains:
- Level: ERROR or WARNING
- Timestamp: When the error occurred
- URL: The page where error happened
- User Agent: Browser information
- Message: The actual error message

## Daily Rotation

The frontend log rotates daily automatically and keeps logs for 14 days.

## Testing

To test if error logging is working:

1. Open browser console (F12)
2. Type: console.error('Test error message')
3. Check storage/logs/frontend-$(date +%Y-%m-%d).log
