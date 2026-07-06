<?php

namespace App\Http\Controllers\Api\Learn;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentAnalyticsController extends Controller
{
    /**
     * Get analytics data for the student dashboard.
     *
     * @return JsonResponse
     */
    public function getDashboardAnalytics(Request $request)
    {
        // Dummy data for monthly scores trend
        $trend = [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'datasets' => [
                [
                    'label' => 'Monthly Average Score',
                    'data' => [75, 82, 78, 85, 90, 88],
                    'borderColor' => '#4F46E5',
                    'backgroundColor' => 'rgba(79, 70, 229, 0.1)',
                    'fill' => true,
                ],
            ],
        ];

        // Dummy data for student vs class average comparison
        $comparison = [
            'labels' => ['Math', 'Science', 'English', 'History', 'Art', 'Physics'],
            'datasets' => [
                [
                    'label' => 'My Scores',
                    'data' => [85, 72, 90, 78, 95, 80],
                    'backgroundColor' => 'rgba(79, 70, 229, 0.2)',
                    'borderColor' => '#4F46E5',
                    'pointBackgroundColor' => '#4F46E5',
                ],
                [
                    'label' => 'Class Average',
                    'data' => [78, 75, 82, 70, 85, 75],
                    'backgroundColor' => 'rgba(209, 213, 219, 0.2)',
                    'borderColor' => '#9CA3AF',
                    'pointBackgroundColor' => '#9CA3AF',
                ],
            ],
        ];

        return response()->json([
            'trend' => $trend,
            'comparison' => $comparison,
        ]);
    }
}
