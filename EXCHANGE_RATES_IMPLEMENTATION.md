# CBSL Exchange Rates Update Implementation

## 📋 Overview

This implementation adds an admin-only button to the accounting dashboard that fetches today's exchange rates from the Central Bank of Sri Lanka (CBSL) API and automatically updates or inserts them into the database.

## ✨ Features Implemented

### 1. **Admin-Only Dashboard Button**

- Location: [Accounting Dashboard](app/views/accounting/system_isuru/accounting.php)
- Button Text: `🔄 Update Rates (CBSL)`
- Visibility: Only shown to users with `admin` role
- Action: Displays confirmation dialog before updating

### 2. **Frontend AJAX Handler**

- Location: [accounting.php - JavaScript section](app/views/accounting/system_isuru/accounting.php)
- Features:
  - Sends async POST request to backend
  - Shows loading spinner during fetch
  - Displays success/error alerts with auto-dismiss (5 seconds)
  - Handles network errors gracefully
  - Shows detailed information about updated/inserted rates

### 3. **Backend Controller Logic**

- Location: [accountingcontroller.php - Lines 261-485](app/controllers/accounting/system_isuru/accountingcontroller.php)
- Endpoint: `?action=update_exchange_rates`
- Features:
  - **Admin Security Check**: Verifies user is admin before proceeding
  - **API Connection**: Fetches from CBSL public API with fallback endpoints
  - **Error Handling**:
    - 10-second timeout for API calls
    - Graceful handling of unreachable services
    - JSON validation
    - Proper exception handling
  - **Database Operations**:
    - Uses prepared statements (mysqli) for SQL injection prevention
    - Updates existing rates for today
    - Inserts new rates if they don't exist
    - Skips LKR (always 1.0)
    - Tracks updated vs. inserted count
    - Validates currency exists in system
  - **Accounting Rules**:
    - Only updates TODAY's rates (no past dates)
    - Source always saved as `CBSL`
    - Tracks `created_by` and timestamps
    - Uses selling rate as primary `rate_to_lkr` field

## 🔄 How It Works

### Step 1: User Clicks Button

```
[Admin clicks "Update Rates (CBSL)" button]
    ↓
[Confirmation dialog appears]
    ↓
[User confirms action]
```

### Step 2: AJAX Request Sent

```
POST Request: ?action=update_exchange_rates
Headers: X-Requested-With: XMLHttpRequest
```

### Step 3: Backend Processing

```
Check admin role
    ↓
Get today's date
    ↓
Connect to CBSL API
    ↓
Decode JSON response
    ↓
For each currency:
  - Check if currency exists in system
  - Check if rate exists for today
  - If exists: UPDATE with new rate
  - If not exists: INSERT new rate
    ↓
Return JSON response with summary
```

### Step 4: Frontend Response Display

```
Success Alert: "✅ Exchange rates for 2026-02-17 updated successfully.
               Updated: 42 • Inserted: 3"
    ↓
[Auto-dismisses after 5 seconds]
```

## 📊 Database Operations

### UPDATE Logic (if rate exists for today)

```php
UPDATE exchange_rates
SET rate_to_lkr = ?,
    buying_rate = ?,
    selling_rate = ?,
    source = 'CBSL',
    updated_by = ?,
    updated_at = NOW()
WHERE currency_id = ? AND rate_date = ?
```

### INSERT Logic (if rate doesn't exist for today)

```php
INSERT INTO exchange_rates
(currency_id, rate_date, rate_to_lkr, buying_rate, selling_rate, source, created_by, created_at)
VALUES (?, ?, ?, ?, ?, 'CBSL', ?, NOW())
```

## 🔒 Security Features

1. **Admin-Only Access**:
   - Frontend button only visible to admins
   - Backend validates `$_SESSION['role'] === 'admin'`

2. **SQL Injection Prevention**:
   - All queries use prepared statements
   - Parameter binding with type hints

3. **No Direct HTML Scraping**:
   - Uses official JSON API
   - Validates JSON structure
   - Skips invalid entries

4. **Date Validation**:
   - Only updates today's rates using `date('Y-m-d')`
   - No capability to modify past dates

## ⚠️ Error Handling

### Handled Scenarios:

- ✅ API unreachable → Clear error message with suggestion to retry
- ✅ Invalid JSON response → Validation error
- ✅ Empty API response → Helpful message
- ✅ Missing currency in system → Skipped with notification
- ✅ Database operation failure → Counted as failed, not fatal
- ✅ Network timeout → User-friendly error
- ✅ Non-admin user → Access denied message

### User Feedback:

- **Success**: Green alert with count of updated/inserted rates
- **Error**: Red alert with specific error details
- **Loading**: Spinner shown during API call
- **Auto-dismiss**: Alerts automatically close after 5 seconds

## 📝 API Response Format

The CBSL API is expected to return JSON in this format:

```json
[
  {
    "currencyCode": "USD",
    "sellingRate": 330.45,
    "buyingRate": 327.55,
    "midRate": 329.00,
    ...
  },
  {
    "currencyCode": "EUR",
    "sellingRate": 360.72,
    "buyingRate": 357.82,
    ...
  }
]
```

The implementation extracts:

- `currencyCode` → matched against `currencies.code`
- `sellingRate` → stored as `rate_to_lkr` (main rate)
- `buyingRate` → stored as `buying_rate` (optional field)

## 🗄️ Database Fields Used

The exchange_rates table expects these columns:

- `exchange_id` - Primary key
- `currency_id` - Foreign key to currencies table
- `rate_date` - Date of the rate (YYYY-MM-DD)
- `rate_to_lkr` - Exchange rate to LKR (primary)
- `buying_rate` - Bank buying rate (optional)
- `selling_rate` - Bank selling rate (optional)
- `source` - Always 'CBSL'
- `created_by` - Username of who created the record
- `created_at` - Timestamp of creation
- `updated_by` - Username of who updated (on updates)
- `updated_at` - Timestamp of last update

## 🚀 Testing the Implementation

### Test Scenario 1: First Update of the Day

1. Log in as admin
2. Click "Update Rates (CBSL)"
3. Confirm dialog
4. Observe insert count (should be > 0 for new currencies)
5. Verify rates appear in accounting modules

### Test Scenario 2: Second Update of the Day

1. Click button again
2. Observe update count (should be > 0)
3. Notice insert count is 0
4. Verify rates were updated to latest values

### Test Scenario 3: Non-Admin Access

1. Log out and log in as non-admin user
2. Verify button is NOT visible
3. Verify endpoint returns 403 if manually accessed

### Test Scenario 4: API Unavailable

1. Simulate API downtime
2. Click button
3. Expect: "Unable to connect to CBSL API" error message

## 📋 Implementation Checklist

- ✅ Admin-only button added to dashboard
- ✅ AJAX frontend handler with loading/error states
- ✅ Backend API fetch from CBSL
- ✅ JSON response parsing and validation
- ✅ UPDATE logic for existing rates (today only)
- ✅ INSERT logic for new rates (today only)
- ✅ Prepared statements for SQL safety
- ✅ Error handling and validation
- ✅ User feedback messages
- ✅ Source field always set to 'CBSL'
- ✅ No table modifications required
- ✅ Comprehensive code comments

## 🔧 Customization Notes

### Change CBSL API Endpoint

If the endpoint changes, edit line 277 in accountingcontroller.php:

```php
$api_endpoints = [
    'https://www.cbsl.gov.lk/cbslweb/api/rates/',  // Change this URL
    'https://cbsl.gov.lk/cbslweb/api/rates/',
];
```

### Adjust Alert Auto-Dismiss Time

In accounting.php JavaScript (line ~775), change the timeout:

```javascript
setTimeout(() => {
  alertDiv.classList.add("fade-out");
  setTimeout(() => alertDiv.remove(), 400);
}, 5000); // Change 5000 to desired milliseconds
```

### Add Additional Currencies

No changes needed. The system automatically updates all currencies that:

1. Exist in the `currencies` table
2. Are returned by the CBSL API
3. Have a valid currency code match

## 📞 Troubleshooting

### Button is not showing

- Check user role: `SELECT role FROM users WHERE id = ?`
- Verify session is set correctly

### "Unable to connect to CBSL API"

- Check if internet connection is available
- Verify CBSL API is not under maintenance
- Check firewall/proxy settings

### "No exchange rates could be processed"

- Verify currencies exist in `currencies` table
- Run: `SELECT code FROM currencies LIMIT 10`
- Add missing currencies to database

### Rates not updating in accounting modules

- Verify UPDATE statement executed successfully
- Check SQL error logs
- Verify today's date is correct: `SELECT CURDATE()`

## 📄 Files Modified

1. **[app/views/accounting/system_isuru/accounting.php](app/views/accounting/system_isuru/accounting.php)**
   - Added admin-only button (lines 60-65)
   - Added AJAX JavaScript handler (lines 750-840)

2. **[app/controllers/accounting/system_isuru/accountingcontroller.php](app/controllers/accounting/system_isuru/accountingcontroller.php)**
   - Added update_exchange_rates handler (lines 261-485)

---

**Implementation Date**: February 17, 2026  
**Status**: ✅ Complete and Ready for Testing
