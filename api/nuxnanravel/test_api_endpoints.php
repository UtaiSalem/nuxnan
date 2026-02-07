<?php
/**
 * Integration Test - Course Completion API Endpoints
 * Tests real API endpoints using existing database
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "===========================================\n";
echo "  Course Completion API Endpoint Tests\n";
echo "===========================================\n\n";

// Get test data
$course = Course::first();
$user = User::first();

if (!$course) {
    echo "✗ No course found in database\n";
    exit(1);
}

if (!$user) {
    echo "✗ No user found in database\n";
    exit(1);
}

echo "Using Course ID: {$course->id} - {$course->name}\n";
echo "Using User ID: {$user->id} - {$user->name}\n\n";

// Authenticate user
Auth::login($user);

// Test function
function testEndpoint($app, $kernel, $method, $uri, $data = []) {
    try {
        $request = Request::create($uri, $method, $data);
        $request->headers->set('Accept', 'application/json');
        
        // Set auth cookie/session
        $request->setUserResolver(function() {
            return Auth::user();
        });
        
        $response = $kernel->handle($request);
        $statusCode = $response->getStatusCode();
        $content = $response->getContent();
        
        // Terminate request
        $kernel->terminate($request, $response);
        
        return [
            'status' => $statusCode,
            'success' => $statusCode >= 200 && $statusCode < 300,
            'content' => $content,
            'json' => json_decode($content, true),
        ];
    } catch (\Exception $e) {
        return [
            'status' => 500,
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}

$tests = [
    [
        'name' => 'Get Eligibility Status',
        'method' => 'GET',
        'uri' => "/api/courses/{$course->id}/eligibility",
    ],
    [
        'name' => 'Get Eligibility Summary',
        'method' => 'GET',
        'uri' => "/api/courses/{$course->id}/eligibility/summary",
    ],
    [
        'name' => 'Get Completion Summary',
        'method' => 'GET',
        'uri' => "/api/courses/{$course->id}/completion/summary",
    ],
    [
        'name' => 'Get My Grade',
        'method' => 'GET',
        'uri' => "/api/courses/{$course->id}/completion/my-grade",
    ],
    [
        'name' => 'Get Appeals List',
        'method' => 'GET',
        'uri' => "/api/courses/{$course->id}/appeals",
    ],
    [
        'name' => 'Get Remediation Sessions',
        'method' => 'GET',
        'uri' => "/api/courses/{$course->id}/remediation",
    ],
    [
        'name' => 'Get Certificates',
        'method' => 'GET',
        'uri' => "/api/courses/{$course->id}/certificates",
    ],
];

$passed = 0;
$failed = 0;

foreach ($tests as $test) {
    echo "TEST: {$test['name']}\n";
    echo "  {$test['method']} {$test['uri']}\n";
    
    $result = testEndpoint($app, $kernel, $test['method'], $test['uri']);
    
    if ($result['success']) {
        echo "  ✓ Status: {$result['status']}\n";
        
        // Show response structure
        if (isset($result['json']['data'])) {
            $keys = is_array($result['json']['data']) ? array_keys($result['json']['data']) : ['data'];
            echo "  Response keys: " . implode(', ', array_slice($keys, 0, 5)) . (count($keys) > 5 ? '...' : '') . "\n";
        }
        $passed++;
    } else {
        echo "  ✗ Status: {$result['status']}\n";
        if (isset($result['error'])) {
            echo "  Error: {$result['error']}\n";
        } elseif (isset($result['json']['message'])) {
            echo "  Message: {$result['json']['message']}\n";
        }
        $failed++;
    }
    echo "\n";
}

echo "===========================================\n";
echo "  Results: {$passed} passed, {$failed} failed\n";
echo "===========================================\n";

exit($failed > 0 ? 1 : 0);
