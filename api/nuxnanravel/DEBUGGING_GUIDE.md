# Laravel Backend Debugging Guide

## Table of Contents
1. [Introduction](#introduction)
2. [Accessing Laravel Logs](#accessing-laravel-logs)
3. [Understanding Log Entries](#understanding-log-entries)
4. [Debugging 500 Errors Step-by-Step](#debugging-500-errors-step-by-step)
5. [Common Causes of 500 Errors in Laravel](#common-causes-of-500-errors-in-laravel)
6. [Debugging Specific Endpoints](#debugging-specific-endpoints)
7. [Enabling Detailed Error Reporting](#enabling-detailed-error-reporting)
8. [Database Debugging](#database-debugging)
9. [Performance Debugging](#performance-debugging)
10. [Best Practices](#best-practices)
11. [Troubleshooting Checklist](#troubleshooting-checklist)
12. [Additional Resources](#additional-resources)

---

## Introduction

### Purpose of the Guide

This guide provides comprehensive instructions for diagnosing and debugging 500 Internal Server Errors in the Laravel API backend. It focuses on practical techniques for accessing and interpreting server-side logs to identify the root cause of errors.

### What 500 Errors Mean

A **500 Internal Server Error** is a generic HTTP status code that indicates the server encountered an unexpected condition that prevented it from fulfilling the request. Unlike 4xx errors (which indicate client-side issues), 500 errors are **server-side problems** that require investigation of the backend code, database, or server configuration.

In the context of this project:
- The frontend (Nuxt 3) makes requests to `localhost:8000/api/academies/1/...`
- The backend (Laravel) returns a 500 status code
- The error details are only available in the server logs
- The frontend only receives a generic error message

### Why Server-Side Logs Are Essential

Server-side logs are critical for debugging because:

1. **Complete Error Information**: They contain the full exception message, stack trace, and context
2. **Request Details**: They show which endpoint, method, and parameters triggered the error
3. **Execution Flow**: They reveal the sequence of events leading to the error
4. **Environment Context**: They include database queries, timing information, and system state
5. **Historical Record**: They maintain a history of errors for pattern analysis

Without logs, you're essentially debugging blind - you know something failed but not why or where.

---

## Accessing Laravel Logs

### Log File Location

Laravel stores all application logs in:

```
api/nuxnanravel/storage/logs/laravel.log
```

This is the primary log file for your application. Laravel may also create additional log files based on your configuration (e.g., `laravel-2025-02-09.log` if daily logging is enabled).

### How to View Logs

#### Using Command Line (Windows)

**Using PowerShell:**

```powershell
# View the entire log file
Get-Content api\nuxnanravel\storage\logs\laravel.log

# View the last 50 lines
Get-Content api\nuxnanravel\storage\logs\laravel.log -Tail 50

# View the last 100 lines
Get-Content api\nuxnanravel\storage\logs\laravel.log -Tail 100

# Search for specific errors
Select-String -Path api\nuxnanravel\storage\logs\laravel.log -Pattern "ERROR"

# Search for specific endpoint
Select-String -Path api\nuxnanravel\storage\logs\laravel.log -Pattern "/api/academies/1/members/search"
```

**Using Command Prompt (cmd.exe):**

```cmd
REM View the entire log file
type api\nuxnanravel\storage\logs\laravel.log

REM View the last 50 lines (requires PowerShell or third-party tools)
REM For cmd, you can use:
more api\nuxnanravel\storage\logs\laravel.log | findstr /C:"ERROR"

REM Search for specific text
findstr /C:"ERROR" /C:"CRITICAL" api\nuxnanravel\storage\logs\laravel.log

REM Search for specific endpoint
findstr /C:"members/search" api\nuxnanravel\storage\logs\laravel.log
```

#### Using Text Editor

Open the log file in your preferred text editor:
- VS Code: Right-click → Open with → VS Code
- Notepad++: Lightweight and handles large files well
- Sublime Text: Fast and efficient for large files

**Tip:** Use "Find" (Ctrl+F) to search for specific error types, timestamps, or request URLs.

#### Using IDE (VS Code)

VS Code provides excellent log file handling:

```bash
# Open log file in VS Code
code api/nuxnanravel/storage/logs/laravel.log
```

Features:
- Syntax highlighting for log entries
- Search and replace functionality
- Split view to compare logs
- Extensions for better log parsing

### Log Rotation and Cleanup

Laravel can automatically rotate logs to prevent files from growing too large. Check your logging configuration in `config/logging.php`:

```php
'daily' => [
    'driver' => 'daily',
    'path' => storage_path('logs/laravel.log'),
    'level' => 'debug',
    'days' => 14,  // Keep logs for 14 days
],
```

**Manual log cleanup:**

```powershell
# Remove logs older than 30 days
Get-ChildItem api\nuxnanravel\storage\logs\*.log | 
    Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-30) } | 
    Remove-Item

# Clear the current log file (be careful!)
Clear-Content api\nuxnanravel\storage\logs\laravel.log
```

### Real-Time Log Monitoring

**Using PowerShell:**

```powershell
# Monitor log file in real-time (similar to tail -f)
Get-Content api\nuxnanravel\storage\logs\laravel.log -Wait -Tail 20
```

**Using Git Bash or WSL (if available):**

```bash
# Traditional tail -f command
tail -f api/nuxnanravel/storage/logs/laravel.log

# Show last 50 lines and follow
tail -f -n 50 api/nuxnanravel/storage/logs/laravel.log
```

**Using VS Code:**

Install the "Log File Highlighter" extension for better log visualization, then use the built-in terminal to monitor logs.

---

## Understanding Log Entries

### Log Levels

Laravel uses the PSR-3 logging standard with the following levels (in order of severity):

| Level | Description | Use Case |
|-------|-------------|----------|
| **DEBUG** | Detailed debug information | Development troubleshooting |
| **INFO** | Interesting events | General application flow |
| **NOTICE** | Normal but significant events | Important but not errors |
| **WARNING** | Exceptional occurrences that aren't errors | Deprecated usage, bad practice |
| **ERROR** | Runtime errors that don't require immediate action | Failed database operations |
| **CRITICAL** | Critical conditions | Application component unavailable |
| **ALERT** | Immediate action required | System down, database unreachable |
| **EMERGENCY** | System is unusable | Complete system failure |

### Log Entry Structure

A typical Laravel log entry looks like this:

```
[2025-02-09 20:32:28] local.ERROR: SQLSTATE[42S02]: Base table or view not found: 1146 Table 'nuxnanravel.member_tags' doesn't exist (SQL: select * from `member_tags` where `academy_id` = 1) {"exception":"[object] (Illuminate\\Database\\QueryException(code: 42S02): SQLSTATE[42S02]: Base table or view not found: 1146 Table 'nuxnanravel.member_tags' doesn't exist (SQL: select * from `member_tags` where `academy_id` = 1) at C:\\wamp64\\www\\nuxnan\\api\\nuxnanravel\\vendor\\laravel\\framework\\src\\Illuminate\\Database\\Connection.php:760)
[stacktrace]
#0 C:\\wamp64\\www\\nuxnan\\api\\nuxnanravel\\vendor\\laravel\\framework\\src\\Illuminate\\Database\\Connection.php(720): Illuminate\\Database\\Connection->runQueryCallback()
#1 C:\\wamp64\\www\\nuxnan\\api\\nuxnanravel\\vendor\\laravel\\framework\\src\\Illuminate\\Database\\Connection.php(432): Illuminate\\Database\\Connection->run()
#2 C:\\wamp64\\www\\nuxnan\\api\\nuxnanravel\\vendor\\laravel\\framework\\src\\Illuminate\\Database\\Query\\Builder.php(2827): Illuminate\\Database\\Connection->select()
...
```

**Breakdown:**

1. **Timestamp**: `[2025-02-09 20:32:28]` - When the error occurred
2. **Environment**: `local` - The application environment (local, staging, production)
3. **Level**: `ERROR` - The severity level
4. **Message**: The error message or exception description
5. **Context**: Additional data in JSON format (exception details)
6. **Stack Trace**: The call stack showing the execution path

### How to Read Stack Traces

The stack trace shows the sequence of function calls that led to the error:

```
#0 C:\\path\\to\\file.php(123): ClassName->methodName()
#1 C:\\path\\to\\file.php(456): AnotherClass->anotherMethod()
#2 C:\\path\\to\\file.php(789): ThirdClass->thirdMethod()
```

**Reading order:**
- **#0** is the bottom of the stack (where the error originated)
- Higher numbers are earlier in the call chain
- The last entry is where the request started

**Key information:**
- **File path**: Where the error occurred
- **Line number**: The specific line (in parentheses)
- **Class and method**: What function was being called

### Identifying the Root Cause

To find the root cause:

1. **Start at #0**: This is usually where the exception was thrown
2. **Look for your application code**: Ignore vendor code initially
3. **Find the first occurrence of your app**: This is likely where the bug is
4. **Check the line number**: Examine the code at that specific line
5. **Read the error message**: Understand what went wrong

**Example:**

```
#0 C:\\wamp64\\www\\nuxnan\\api\\nuxnanravel\\vendor\\laravel\\framework\\src\\Illuminate\\Database\\Connection.php(760): Illuminate\\Database\\Connection->runQueryCallback()
#1 C:\\wamp64\\www\\nuxnan\\api\\nuxnanravel\\vendor\\laravel\\framework\\src\\Illuminate\\Database\\Connection.php(720): Illuminate\\Database\\Connection->run()
#2 C:\\wamp64\\www\\nuxnan\\api\\nuxnanravel\\app\\Http\\Controllers\\AcademyMemberController.php(145): Illuminate\\Database\\Connection->select()
```

The root cause is at line 145 in `AcademyMemberController.php`.

---

## Debugging 500 Errors Step-by-Step

### Step 1: Check Laravel Logs

When you encounter a 500 error, the first step is always to check the logs:

```powershell
# View the most recent errors
Get-Content api\nuxnanravel\storage\logs\laravel.log -Tail 100 | Select-String -Pattern "ERROR|CRITICAL|ALERT|EMERGENCY"
```

**For the specific failing endpoints:**

```powershell
# Search for members/search errors
Select-String -Path api\nuxnanravel\storage\logs\laravel.log -Pattern "members/search"

# Search for member-tags errors
Select-String -Path api\nuxnanravel\storage\logs\laravel.log -Pattern "member-tags"

# Search for members/filter-options errors
Select-String -Path api\nuxnanravel\storage\logs\laravel.log -Pattern "members/filter-options"
```

### Step 2: Identify the Exception Type

Look at the error message to identify the type of exception:

**Common exception types:**

| Exception Type | Common Cause |
|---------------|--------------|
| `QueryException` | Database query error (SQL syntax, missing table) |
| `ModelNotFoundException` | Eloquent model not found in database |
| `AuthenticationException` | User not authenticated |
| `AuthorizationException` | User lacks permission |
| `ValidationException` | Input validation failed |
| `RuntimeException` | General runtime error |
| `ErrorException` | PHP error (undefined variable, etc.) |
| `TypeError` | Type mismatch (wrong argument type) |
| `ArgumentCountError` | Wrong number of arguments |

**Example log entry:**

```
[2025-02-09 20:32:28] local.ERROR: SQLSTATE[42S02]: Base table or view not found...
{"exception":"[object] (Illuminate\\Database\\QueryException(code: 42S02): ..."}
```

This is a `QueryException` indicating a database table doesn't exist.

### Step 3: Locate the Problematic Code

Use the stack trace to find where the error occurred:

1. **Find the first stack frame in your application code** (not vendor)
2. **Navigate to that file and line number**
3. **Examine the code** around that line

**Example:**

```
#2 C:\\wamp64\\www\\nuxnan\\api\\nuxnanravel\\app\\Http\\Controllers\\AcademyMemberController.php(145)
```

Open `api/nuxnanravel/app/Http/Controllers/AcademyMemberController.php` and look at line 145.

### Step 4: Analyze the Error Context

Examine the error context to understand what went wrong:

**For database errors:**
- Check if the table exists
- Verify the column names
- Check the SQL query syntax
- Ensure database connection is working

**For validation errors:**
- Review the validation rules
- Check the input data format
- Verify required fields

**For authentication/authorization errors:**
- Check if user is logged in
- Verify user has the required role/permission
- Check middleware configuration

**For PHP errors:**
- Check for undefined variables
- Verify function/method calls
- Check for syntax errors

### Step 5: Fix the Issue

Based on your analysis, implement a fix:

**Example fixes:**

1. **Missing table**: Run migration
   ```bash
   php artisan migrate
   ```

2. **Wrong column name**: Update the query
   ```php
   // Before
   $members = DB::table('member_tags')->where('academy_id', $academyId)->get();
   
   // After (if column is actually 'academy_identifier')
   $members = DB::table('member_tags')->where('academy_identifier', $academyId)->get();
   ```

3. **Undefined variable**: Define the variable
   ```php
   // Before
   return $member->name;
   
   // After
   $member = Member::find($id);
   return $member->name;
   ```

4. **Missing permission**: Add permission check or update user role

### Step 6: Test the Fix

After implementing the fix:

1. **Clear Laravel cache** (important for configuration changes):
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

2. **Test the endpoint**:
   - Using Postman
   - Using curl
   - Using the frontend application

3. **Check the logs** to ensure no new errors:
   ```powershell
   Get-Content api\nuxnanravel\storage\logs\laravel.log -Tail 20
   ```

4. **Verify the response** is correct (200 OK, proper data)

---

## Common Causes of 500 Errors in Laravel

### 1. Database Connection Issues

**Symptoms:**
- `SQLSTATE[HY000] [2002] Connection refused`
- `SQLSTATE[HY000] [2002] No connection could be made`
- `SQLSTATE[HY000] [1045] Access denied for user`

**Debugging:**
```powershell
# Check .env file
Get-Content api\nuxnanravel\.env | Select-String -Pattern "DB_"

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo()
```

**Fix:**
- Verify database credentials in `.env`
- Ensure MySQL/MariaDB service is running
- Check database exists and user has permissions
- Test connection manually with MySQL client

### 2. Missing or Incorrect Database Tables/Columns

**Symptoms:**
- `SQLSTATE[42S02]: Base table or view not found`
- `SQLSTATE[42S22]: Column not found`

**Debugging:**
```powershell
# Check if table exists
php artisan tinker
>>> Schema::hasTable('member_tags')

# Check table structure
php artisan db:table member_tags
```

**Fix:**
```bash
# Run pending migrations
php artisan migrate

# Or run specific migration
php artisan migrate:refresh --path=database/migrations/2025_06_22_create_member_tags_tables.php

# Check migration status
php artisan migrate:status
```

### 3. PHP Syntax Errors

**Symptoms:**
- `Parse error: syntax error`
- `Unexpected token`

**Debugging:**
```bash
# Check PHP syntax
php -l api/nuxnanravel/app/Http/Controllers/YourController.php

# Run Laravel syntax check
php artisan route:list
```

**Fix:**
- Review the file for syntax errors
- Check for missing semicolons, brackets, quotes
- Ensure proper PHP version compatibility

### 4. Missing Dependencies or Classes

**Symptoms:**
- `Class 'App\Models\SomeModel' not found`
- `Class 'SomeNamespace\SomeClass' not found`

**Debugging:**
```bash
# Check if class exists
php artisan tinker
>>> class_exists('App\Models\Member')

# Check autoload
composer dump-autoload
```

**Fix:**
```bash
# Install missing dependencies
composer install

# Update autoload
composer dump-autoload

# Clear cache
php artisan clear-compiled
```

### 5. Permission Issues (Storage, Cache Directories)

**Symptoms:**
- `file_put_contents(): failed to open stream: Permission denied`
- `mkdir(): Permission denied`

**Debugging:**
```powershell
# Check directory permissions
Get-Acl api\nuxnanravel\storage | Format-List

# Check if directory is writable
php artisan tinker
>>> is_writable(storage_path('logs'))
```

**Fix (Windows):**
```powershell
# Grant write permissions
icacls api\nuxnanravel\storage /grant Users:F /T
icacls api\nuxnanravel\bootstrap\cache /grant Users:F /T

# Or run as administrator
```

### 6. Memory Limit Exceeded

**Symptoms:**
- `Allowed memory size of X bytes exhausted`
- `Fatal error: Out of memory`

**Debugging:**
```bash
# Check current memory limit
php -r "echo ini_get('memory_limit');"
```

**Fix:**
```php
// In php.ini
memory_limit = 256M

// Or temporarily in code
ini_set('memory_limit', '256M');

// Or in .htaccess (Apache)
php_value memory_limit 256M
```

### 7. Timeout Issues

**Symptoms:**
- `Maximum execution time of X seconds exceeded`
- Request hangs and returns 500

**Fix:**
```php
// In php.ini
max_execution_time = 300

// Or temporarily
set_time_limit(300);

// Optimize the slow operation (pagination, indexing, etc.)
```

### 8. Validation Errors Not Handled

**Symptoms:**
- 500 error when validation fails
- No validation error message returned

**Fix:**
```php
// In controller
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
    ]);
    
    // If validation fails, Laravel automatically returns 422
    // If you're getting 500, check if you're catching exceptions improperly
}
```

### 9. Uncaught Exceptions

**Symptoms:**
- Generic 500 error with no specific message
- Exception not handled in try-catch block

**Fix:**
```php
// Add proper exception handling
try {
    $result = SomeService::doSomething();
} catch (\Exception $e) {
    Log::error('Error doing something', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    return response()->json([
        'error' => 'An error occurred',
        'message' => $e->getMessage(),
    ], 500);
}
```

---

## Debugging Specific Endpoints

### Identifying Which Endpoint Caused the Error

When multiple endpoints are failing, you need to identify which one caused each error:

**Method 1: Search logs by URL pattern**

```powershell
# Search for specific endpoint
Select-String -Path api\nuxnanravel\storage\logs\laravel.log -Pattern "/api/academies/1/members/search"

# Search with context (2 lines before and after)
Select-String -Path api\nuxnanravel\storage\logs\laravel.log -Pattern "/api/academies/1/members/search" -Context 2,2
```

**Method 2: Use timestamps**

```powershell
# Get logs from specific time
Get-Content api\nuxnanravel\storage\logs\laravel.log | 
    Select-String -Pattern "2025-02-09 20:3[0-9]"
```

**Method 3: Filter by HTTP method**

```powershell
# Search for GET requests
Select-String -Path api\nuxnanravel\storage\logs\laravel.log -Pattern "GET.*members/search"
```

### Using Request ID Tracking (X-Request-ID Header)

For better request tracking, you can add a unique request ID to each request:

**Step 1: Create a middleware**

```bash
php artisan make:middleware RequestIdMiddleware
```

**Step 2: Implement the middleware**

```php
// app/Http/Middleware/RequestIdMiddleware.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestIdMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-ID') ?? (string) Str::uuid();
        
        // Add to request for later use
        $request->attributes->set('request_id', $requestId);
        
        // Add to all log entries
        Log::withContext([
            'request_id' => $requestId,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);
        
        $response = $next($request);
        
        // Add to response headers
        $response->headers->set('X-Request-ID', $requestId);
        
        return $response;
    }
}
```

**Step 3: Register the middleware**

```php
// bootstrap/app.php (Laravel 11)
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\App\Http\Middleware\RequestIdMiddleware::class);
})

// Or in app/Http/Kernel.php (Laravel 10 and below)
protected $middleware = [
    // ...
    \App\Http\Middleware\RequestIdMiddleware::class,
];
```

**Step 4: Use in frontend**

```typescript
// In your Nuxt 3 API composable
const requestId = crypto.randomUUID()
const response = await $fetch(`/api/academies/${academyId}/members/search`, {
  headers: {
    'X-Request-ID': requestId
  },
  params: { page, per_page, sort_by, sort_order }
})

// The response will include the same X-Request-ID header
console.log('Request ID:', response.headers.get('X-Request-ID'))
```

**Step 5: Search logs by request ID**

```powershell
# Find all log entries for a specific request
Select-String -Path api\nuxnanravel\storage\logs\laravel.log -Pattern "request_id.*550e8400-e29b-41d4-a716-446655440000"
```

### Correlating Frontend Errors with Backend Logs

**Step 1: Capture frontend error details**

```typescript
// In your Nuxt 3 error handler
try {
  const response = await $fetch(`/api/academies/${academyId}/members/search`, {
    params: { page, per_page, sort_by, sort_order }
  })
} catch (error: any) {
  console.error('Frontend error:', {
    url: error.request?.url || '/api/academies/1/members/search',
    status: error.response?.status,
    statusText: error.response?.statusText,
    timestamp: new Date().toISOString(),
    requestId: error.response?.headers?.get('X-Request-ID')
  })
}
```

**Step 2: Search backend logs with timestamp**

```powershell
# Search for errors around the same time
Get-Content api\nuxnanravel\storage\logs\laravel.log | 
    Select-String -Pattern "2025-02-09 20:32:2[0-9]"
```

**Step 3: Match request ID (if implemented)**

```powershell
# Find all entries for the specific request
Select-String -Path api\nuxnanravel\storage\logs\laravel.log -Pattern "550e8400-e29b-41d4-a716-446655440000"
```

### Testing Endpoints Manually with Postman/curl

**Using curl (Windows):**

```cmd
REM Test members/search endpoint
curl -X GET "http://localhost:8000/api/academies/1/members/search?page=1&per_page=20&sort_by=created_at&sort_order=desc" ^
  -H "Accept: application/json" ^
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

REM Test member-tags endpoint
curl -X GET "http://localhost:8000/api/academies/1/member-tags" ^
  -H "Accept: application/json" ^
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

REM Test members/filter-options endpoint
curl -X GET "http://localhost:8000/api/academies/1/members/filter-options" ^
  -H "Accept: application/json" ^
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Using PowerShell:**

```powershell
# Test members/search endpoint
$headers = @{
    "Accept" = "application/json"
    "Authorization" = "Bearer YOUR_TOKEN_HERE"
}

Invoke-RestMethod -Uri "http://localhost:8000/api/academies/1/members/search?page=1&per_page=20&sort_by=created_at&sort_order=desc" `
    -Method GET `
    -Headers $headers
```

**Using Postman:**

1. Create a new request
2. Set method to GET
3. Enter URL: `http://localhost:8000/api/academies/1/members/search`
4. Add headers:
   - `Accept: application/json`
   - `Authorization: Bearer YOUR_TOKEN_HERE`
5. Add query parameters in Params tab
6. Click Send
7. Check response status and body

**Debugging with Postman:**
- Check the **Response** tab for error details (if APP_DEBUG is true)
- Check the **Headers** tab for response headers
- Use **Console** (View > Show Postman Console) to see detailed logs

---

## Enabling Detailed Error Reporting

### APP_DEBUG Environment Variable

The `APP_DEBUG` setting in your `.env` file controls error visibility:

```env
# Development
APP_DEBUG=true

# Production (NEVER use true in production!)
APP_DEBUG=false
```

**When `APP_DEBUG=true`:**
- Detailed error messages shown in browser
- Stack traces displayed
- SQL queries shown (if query logging enabled)
- Environment variables exposed (security risk!)

**When `APP_DEBUG=false`:**
- Generic error page shown
- No stack traces in browser
- Errors still logged to file
- More secure for production

### Displaying Errors in Browser vs. Logs

**Development (APP_DEBUG=true):**

```php
// Errors shown in browser with full details
// Useful for quick debugging
// SECURITY RISK: Never enable in production!
```

**Production (APP_DEBUG=false):**

```php
// Generic error page shown
// Check logs for details
// More secure
```

**Best practice:**
- Use `APP_DEBUG=true` in local development
- Use `APP_DEBUG=false` in staging and production
- Always check logs regardless of APP_DEBUG setting

### Enabling Query Logging

To see all SQL queries in the logs:

**Method 1: Enable in AppServiceProvider**

```php
// app/Providers/AppServiceProvider.php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (config('app.debug')) {
            DB::listen(function ($query) {
                Log::info('SQL Query', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms',
                ]);
            });
        }
    }
}
```

**Method 2: Enable for specific requests**

```php
// In your controller or middleware
DB::enableQueryLog();

// ... execute queries ...

$queries = DB::getQueryLog();
Log::info('Queries executed', ['queries' => $queries]);
```

**View query logs:**

```powershell
Select-String -Path api\nuxnanravel\storage\logs\laravel.log -Pattern "SQL Query"
```

### Using Laravel Telescope (if available)

Laravel Telescope provides a beautiful debug dashboard:

**Installation:**

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

**Access Telescope:**

Navigate to `http://localhost:8000/telescope`

**Features:**
- Request monitoring
- Exception tracking
- Query logging
- Job monitoring
- Command monitoring
- Schedule monitoring

**Note:** Only use in development, not in production.

### Using Laravel Debugbar (if available)

Laravel Debugbar adds a debug bar to your application:

**Installation:**

```bash
composer require barryvdh/laravel-debugbar --dev
php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"
```

**Access Debugbar:**

The debug bar appears at the bottom of your browser when APP_DEBUG=true.

**Features:**
- Queries
- Views
- Routes
- Requests
- Timeline
- Exceptions

---

## Database Debugging

### Checking Database Connection

**Test connection with Artisan:**

```bash
php artisan tinker
>>> DB::connection()->getPdo()
=> PDO { ... }

>>> DB::connection()->getDatabaseName()
=> "nuxnanravel"
```

**Check connection details:**

```bash
php artisan tinker
>>> config('database.connections.mysql')
```

### Verifying Table Structure

**List all tables:**

```bash
php artisan tinker
>>> DB::select('SHOW TABLES')
```

**Describe a specific table:**

```bash
php artisan tinker
>>> DB::select('DESCRIBE member_tags')
```

**Or use Schema builder:**

```bash
php artisan tinker
>>> Schema::getColumnListing('member_tags')
```

**Check if table exists:**

```bash
php artisan tinker
>>> Schema::hasTable('member_tags')
=> true

>>> Schema::hasTable('non_existent_table')
=> false
```

### Running Migrations

**Check migration status:**

```bash
php artisan migrate:status
```

**Run pending migrations:**

```bash
php artisan migrate
```

**Run specific migration:**

```bash
php artisan migrate --path=database/migrations/2025_06_22_create_member_tags_tables.php
```

**Rollback last migration:**

```bash
php artisan migrate:rollback
```

**Rollback multiple migrations:**

```bash
php artisan migrate:rollback --step=3
```

**Reset and re-run all migrations:**

```bash
php artisan migrate:fresh
```

**Warning:** `migrate:fresh` will drop all tables and recreate them!

### Checking Database Logs

**Enable MySQL general log (for debugging):**

```sql
-- Check if general log is enabled
SHOW VARIABLES LIKE 'general_log%';

-- Enable general log
SET GLOBAL general_log = 'ON';

-- View log location
SHOW VARIABLES LIKE 'general_log_file';

-- Disable general log when done
SET GLOBAL general_log = 'OFF';
```

**View MySQL error log:**

```sql
-- Check error log location
SHOW VARIABLES LIKE 'log_error';
```

### Using DB::listen() for Query Logging

As shown in the "Enabling Query Logging" section above, you can use `DB::listen()` to log all queries:

```php
DB::listen(function ($query) {
    Log::channel('queries')->info('Query executed', [
        'sql' => $query->sql,
        'bindings' => $query->bindings,
        'time' => $query->time,
    ]);
});
```

**Create a dedicated query log channel:**

```php
// config/logging.php
'channels' => [
    // ...
    'queries' => [
        'driver' => 'daily',
        'path' => storage_path('logs/queries.log'),
        'level' => 'debug',
        'days' => 7,
    ],
],
```

---

## Performance Debugging

### Identifying Slow Queries

**Using query logging with time threshold:**

```php
DB::listen(function ($query) {
    if ($query->time > 100) { // Log queries slower than 100ms
        Log::warning('Slow query detected', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time . 'ms',
        ]);
    }
});
```

**Using Laravel Telescope:**

Telescope automatically tracks slow queries and displays them in the Queries tab.

**Using MySQL slow query log:**

```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1; -- Log queries taking > 1 second

-- Check slow query log location
SHOW VARIABLES LIKE 'slow_query_log_file';
```

### Memory Usage Profiling

**Log memory usage:**

```php
Log::info('Memory usage', [
    'current' => memory_get_usage(true),
    'peak' => memory_get_peak_usage(true),
]);
```

**Check memory limit:**

```bash
php -r "echo ini_get('memory_limit');"
```

**Profile with Xdebug (if installed):**

```bash
php -dxdebug.mode=profile your-script.php
```

### Execution Time Tracking

**Track execution time:**

```php
$startTime = microtime(true);

// ... your code ...

$executionTime = microtime(true) - $startTime;
Log::info('Execution time', ['seconds' => $executionTime]);
```

**Use Laravel's built-in timer:**

```php
use Illuminate\Support\Benchmark;

$time = Benchmark::measure(function () {
    // ... your code ...
});

Log::info('Execution time', ['seconds' => $time]);
```

**Benchmark multiple operations:**

```php
$times = Benchmark::measure([
    'operation1' => fn() => /* ... */,
    'operation2' => fn() => /* ... */,
    'operation3' => fn() => /* ... */,
]);

Log::info('Benchmark results', ['times' => $times]);
```

### Using Laravel's Built-in Profiling Tools

**Laravel Telescope:**

As mentioned earlier, Telescope provides comprehensive profiling including:
- Request timeline
- Query performance
- Memory usage
- Execution time

**Clockwork (alternative):**

```bash
composer require itsgoingd/clockwork --dev
```

Clockwork provides a Chrome extension for detailed profiling.

---

## Best Practices

### Always Check Logs First

When you encounter an error:
1. **Check the logs first** - This is your primary source of truth
2. **Don't guess** - Let the logs tell you what happened
3. **Search for the specific error** - Use timestamps, URLs, or request IDs
4. **Read the full stack trace** - Understand the execution path

### Use Proper Exception Handling

**Do:**

```php
try {
    $result = SomeService::doSomething();
    return response()->json($result);
} catch (ModelNotFoundException $e) {
    Log::warning('Model not found', [
        'model' => get_class($e->getModel()),
        'id' => $e->getIds(),
    ]);
    return response()->json(['error' => 'Resource not found'], 404);
} catch (ValidationException $e) {
    Log::info('Validation failed', ['errors' => $e->errors()]);
    return response()->json(['errors' => $e->errors()], 422);
} catch (\Exception $e) {
    Log::error('Unexpected error', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    return response()->json(['error' => 'Internal server error'], 500);
}
```

**Don't:**

```php
// Don't catch all exceptions and return generic 500
try {
    $result = SomeService::doSomething();
} catch (\Exception $e) {
    return response()->json(['error' => 'Error'], 500);
}

// Don't suppress exceptions
try {
    $result = SomeService::doSomething();
} catch (\Exception $e) {
    // Do nothing - error is lost!
}
```

### Log Meaningful Error Messages

**Do:**

```php
Log::error('Failed to create academy member', [
    'academy_id' => $academyId,
    'user_id' => $userId,
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
]);
```

**Don't:**

```php
// Too vague
Log::error('Error occurred');

// Missing context
Log::error('Failed to create member');

// Too much noise
Log::error('Error', ['everything' => $request->all()]);
```

### Don't Expose Sensitive Information in Production

**Never in production:**
- Stack traces in responses
- Database credentials
- API keys
- User passwords
- Internal paths
- Environment variables

**Do:**
- Use `APP_DEBUG=false` in production
- Log detailed errors to files (not responses)
- Return generic error messages to clients
- Sanitize logs before sharing

### Use Version Control to Track Changes

**When debugging:**
1. **Check recent commits** - What changed recently?
2. **Use git bisect** - Find which commit introduced the bug
3. **Compare with working version** - What's different?
4. **Create a branch for debugging** - Don't modify main directly

**Useful git commands:**

```bash
# View recent commits
git log --oneline -10

# See what changed in a file
git log -p app/Http/Controllers/AcademyMemberController.php

# Find which commit introduced a bug
git bisect start
git bisect bad  # Current version has bug
git bisect good <working-commit-hash>
# Git will checkout commits, test each one, mark good/bad
git bisect reset  # When done

# Compare two commits
git diff <commit1> <commit2>
```

---

## Troubleshooting Checklist

### Quick Reference for Common Issues

#### Database Issues

- [ ] Check database connection in `.env`
- [ ] Verify MySQL/MariaDB service is running
- [ ] Test connection: `php artisan tinker` → `DB::connection()->getPdo()`
- [ ] Check if table exists: `Schema::hasTable('table_name')`
- [ ] Run pending migrations: `php artisan migrate`
- [ ] Check migration status: `php artisan migrate:status`

#### Permission Issues

- [ ] Check storage directory permissions
- [ ] Check bootstrap/cache directory permissions
- [ ] Grant write permissions (Windows): `icacls storage /grant Users:F /T`
- [ ] Clear cache: `php artisan cache:clear`

#### Cache Issues

- [ ] Clear application cache: `php artisan cache:clear`
- [ ] Clear configuration cache: `php artisan config:clear`
- [ ] Clear route cache: `php artisan route:clear`
- [ ] Clear view cache: `php artisan view:clear`
- [ ] Clear compiled files: `php artisan clear-compiled`

#### Code Issues

- [ ] Check for syntax errors: `php -l file.php`
- [ ] Check for missing classes: `composer dump-autoload`
- [ ] Check for missing dependencies: `composer install`
- [ ] Review recent code changes
- [ ] Check git diff for modifications

### Step-by-Step Troubleshooting Flow

```
1. Encounter 500 Error
   ↓
2. Check Laravel Logs
   → Open: api/nuxnanravel/storage/logs/laravel.log
   → Search: Select-String -Path ... -Pattern "ERROR"
   ↓
3. Identify Error Type
   → Read exception message
   → Note exception class
   ↓
4. Locate Problematic Code
   → Find stack frame in app code (not vendor)
   → Navigate to file and line number
   ↓
5. Analyze Context
   → What operation was being performed?
   → What data was involved?
   → What dependencies are required?
   ↓
6. Implement Fix
   → Based on error type and analysis
   → Test fix locally
   ↓
7. Clear Cache
   → php artisan cache:clear
   → php artisan config:clear
   → php artisan route:clear
   ↓
8. Test Endpoint
   → Use Postman/curl
   → Verify response is 200 OK
   → Check data is correct
   ↓
9. Verify No New Errors
   → Check logs again
   → Ensure no new errors
   ↓
10. Document and Commit
    → Document the fix
    → Commit changes
    → Update documentation if needed
```

### Specific Endpoint Troubleshooting

#### For `/api/academies/1/members/search`

```powershell
# 1. Search logs for this endpoint
Select-String -Path api\nuxnanravel\storage\logs\laravel.log -Pattern "members/search"

# 2. Check controller
# Look at: api/nuxnanravel/app/Http/Controllers/AcademyMemberController.php
# Find the search method

# 3. Check route
php artisan route:list | Select-String "members/search"

# 4. Test manually
curl -X GET "http://localhost:8000/api/academies/1/members/search?page=1&per_page=20"
```

#### For `/api/academies/1/member-tags`

```powershell
# 1. Search logs for this endpoint
Select-String -Path api\nuxnanravel\storage\logs\laravel.log -Pattern "member-tags"

# 2. Check if member_tags table exists
php artisan tinker
>>> Schema::hasTable('member_tags')

# 3. Check migration status
php artisan migrate:status | Select-String "member_tags"

# 4. Run migration if needed
php artisan migrate --path=database/migrations/2025_06_22_create_member_tags_tables.php
```

#### For `/api/academies/1/members/filter-options`

```powershell
# 1. Search logs for this endpoint
Select-String -Path api\nuxnanravel\storage\logs\laravel.log -Pattern "filter-options"

# 2. Check controller method
# Look at: api/nuxnanravel/app/Http/Controllers/AcademyMemberController.php
# Find the filterOptions method

# 3. Check database queries
# Enable query logging (see section above)

# 4. Test manually
curl -X GET "http://localhost:8000/api/academies/1/members/filter-options"
```

---

## Additional Resources

### Laravel Documentation

- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Error Handling](https://laravel.com/docs/11.x/errors)
- [Laravel Logging](https://laravel.com/docs/11.x/logging)
- [Laravel Database](https://laravel.com/docs/11.x/database)
- [Laravel Debugging](https://laravel.com/docs/11.x/deployment)

### Useful Laravel Debugging Packages

1. **Laravel Telescope**
   - Official Laravel debugging tool
   - Installation: `composer require laravel/telescope --dev`
   - Documentation: https://laravel.com/docs/telescope

2. **Laravel Debugbar**
   - Debug bar for Laravel
   - Installation: `composer require barryvdh/laravel-debugbar --dev`
   - GitHub: https://github.com/barryvdh/laravel-debugbar

3. **Clockwork**
   - Chrome extension for debugging
   - Installation: `composer require itsgoingd/clockwork --dev`
   - Website: https://underground.works/clockwork/

4. **Laravel Ray**
   - Desktop debugging app
   - Installation: `composer require spatie/laravel-ray --dev`
   - Website: https://myray.app/

5. **Laravel IDE Helper**
   - IDE autocomplete support
   - Installation: `composer require --dev barryvdh/laravel-ide-helper`
   - GitHub: https://github.com/barryvdh/laravel-ide-helper

### Community Resources

- [Laravel Forums](https://laracasts.com/discuss)
- [Stack Overflow - Laravel Tag](https://stackoverflow.com/questions/tagged/laravel)
- [Laravel News](https://laravel-news.com/)
- [Laravel.io](https://laravel.io/)

### Windows-Specific Resources

- [PowerShell Documentation](https://docs.microsoft.com/en-us/powershell/)
- [Windows Command Prompt Reference](https://docs.microsoft.com/en-us/windows-server/administration/windows-commands/windows-commands)
- [Git for Windows](https://git-scm.com/download/win)

---

## Conclusion

This guide provides comprehensive coverage of debugging 500 Internal Server Errors in Laravel. The key takeaways are:

1. **Always check logs first** - They contain the complete error information
2. **Understand the error type** - Different errors require different approaches
3. **Locate the problematic code** - Use the stack trace to find the exact location
4. **Analyze the context** - Understand what led to the error
5. **Implement and test the fix** - Verify the solution works
6. **Follow best practices** - Proper error handling and logging

For the specific failing endpoints in your project:
- `/api/academies/1/members/search`
- `/api/academies/1/member-tags`
- `/api/academies/1/members/filter-options`

Start by searching the logs for each endpoint, identify the specific error, and follow the troubleshooting flow outlined above.

Remember: Debugging is a systematic process. Take your time, understand the error, and methodically work through the solution.

---

**Document Version:** 1.0  
**Last Updated:** 2025-02-09  
**Project:** nuxnan Laravel Backend  
**Location:** api/nuxnanravel/DEBUGGING_GUIDE.md
