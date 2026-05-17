<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ApiTestingService
{
    /**
     * Run API test suite.
     */
    public function runTestSuite(string $suite = 'all', ?string $endpoint = null): array
    {
        $testId = uniqid('test_');
        $startTime = now();

        try {
            $tests = $this->getTestDefinitions($suite, $endpoint);
            $results = [];

            foreach ($tests as $test) {
                $result = $this->runSingleTest($test);
                $results[] = $result;
            }

            $summary = $this->generateTestSummary($results);

            // Store test results
            $this->storeTestResults($testId, $results, $summary, $startTime);

            return [
                'test_id' => $testId,
                'summary' => $summary,
                'results' => $results,
                'execution_time' => now()->diffInSeconds($startTime),
                'suite' => $suite,
                'endpoint' => $endpoint,
            ];
        } catch (\Exception $e) {
            Log::error('API test suite failed', [
                'suite' => $suite,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get test definitions for suite.
     */
    private function getTestDefinitions(string $suite, ?string $endpoint): array
    {
        $allTests = [
            'authentication' => $this->getAuthenticationTests(),
            'assets' => $this->getAssetTests(),
            'work_orders' => $this->getWorkOrderTests(),
            'maintenance' => $this->getMaintenanceTests(),
            'iot' => $this->getIoTTests(),
            'predictive' => $this->getPredictiveMaintenanceTests(),
            'analytics' => $this->getAnalyticsTests(),
            'reports' => $this->getReportTests(),
            'mobile' => $this->getMobileTests(),
        ];

        if ($suite === 'all') {
            $tests = array_merge(...array_values($allTests));
        } elseif (isset($allTests[$suite])) {
            $tests = $allTests[$suite];
        } else {
            throw new \InvalidArgumentException("Unknown test suite: {$suite}");
        }

        if ($endpoint) {
            $tests = array_filter($tests, fn($test) => str_contains($test['path'], $endpoint));
        }

        return array_values($tests);
    }

    /**
     * Get authentication tests.
     */
    private function getAuthenticationTests(): array
    {
        return [
            [
                'name' => 'Login - Valid credentials',
                'method' => 'POST',
                'path' => '/api/auth/login',
                'data' => [
                    'email' => 'test@example.com',
                    'password' => 'password123',
                ],
                'expected_status' => 200,
                'expected_fields' => ['success', 'data.token', 'data.user'],
                'category' => 'authentication',
            ],
            [
                'name' => 'Login - Invalid credentials',
                'method' => 'POST',
                'path' => '/api/auth/login',
                'data' => [
                    'email' => 'invalid@example.com',
                    'password' => 'wrongpassword',
                ],
                'expected_status' => 401,
                'expected_fields' => ['success', 'message'],
                'category' => 'authentication',
            ],
            [
                'name' => 'Logout - Authenticated',
                'method' => 'POST',
                'path' => '/api/auth/logout',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'expected_status' => 200,
                'expected_fields' => ['success', 'message'],
                'category' => 'authentication',
            ],
            [
                'name' => 'Logout - Unauthenticated',
                'method' => 'POST',
                'path' => '/api/auth/logout',
                'expected_status' => 401,
                'expected_fields' => ['success', 'message'],
                'category' => 'authentication',
            ],
        ];
    }

    /**
     * Get asset tests.
     */
    private function getAssetTests(): array
    {
        return [
            [
                'name' => 'List assets - Authenticated',
                'method' => 'GET',
                'path' => '/api/assets',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'expected_status' => 200,
                'expected_fields' => ['success', 'data', 'pagination'],
                'category' => 'assets',
            ],
            [
                'name' => 'List assets - Unauthenticated',
                'method' => 'GET',
                'path' => '/api/assets',
                'expected_status' => 401,
                'expected_fields' => ['success', 'message'],
                'category' => 'assets',
            ],
            [
                'name' => 'Create asset - Valid data',
                'method' => 'POST',
                'path' => '/api/assets',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'data' => [
                    'name' => 'Test Asset',
                    'description' => 'Test Description',
                    'category_id' => 'test-category-id',
                    'location_id' => 'test-location-id',
                    'purchase_cost' => 10000.00,
                ],
                'expected_status' => 201,
                'expected_fields' => ['success', 'message', 'data.id'],
                'category' => 'assets',
            ],
            [
                'name' => 'Create asset - Invalid data',
                'method' => 'POST',
                'path' => '/api/assets',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'data' => [
                    'name' => '',
                    'description' => 'Test Description',
                ],
                'expected_status' => 422,
                'expected_fields' => ['success', 'message', 'errors'],
                'category' => 'assets',
            ],
            [
                'name' => 'Show asset - Valid ID',
                'method' => 'GET',
                'path' => '/api/assets/test-asset-id',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'expected_status' => 200,
                'expected_fields' => ['success', 'data.id', 'data.name'],
                'category' => 'assets',
            ],
            [
                'name' => 'Show asset - Invalid ID',
                'method' => 'GET',
                'path' => '/api/assets/invalid-id',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'expected_status' => 404,
                'expected_fields' => ['success', 'message'],
                'category' => 'assets',
            ],
        ];
    }

    /**
     * Get work order tests.
     */
    private function getWorkOrderTests(): array
    {
        return [
            [
                'name' => 'List work orders - Authenticated',
                'method' => 'GET',
                'path' => '/api/work-orders',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'expected_status' => 200,
                'expected_fields' => ['success', 'data', 'pagination'],
                'category' => 'work_orders',
            ],
            [
                'name' => 'Create work order - Valid data',
                'method' => 'POST',
                'path' => '/api/work-orders',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'data' => [
                    'title' => 'Test Work Order',
                    'description' => 'Test Description',
                    'asset_id' => 'test-asset-id',
                    'priority' => 'medium',
                ],
                'expected_status' => 201,
                'expected_fields' => ['success', 'message', 'data.id'],
                'category' => 'work_orders',
            ],
            [
                'name' => 'Update work order - Valid data',
                'method' => 'PUT',
                'path' => '/api/work-orders/test-work-order-id',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'data' => [
                    'title' => 'Updated Work Order',
                    'priority' => 'high',
                ],
                'expected_status' => 200,
                'expected_fields' => ['success', 'message', 'data.id'],
                'category' => 'work_orders',
            ],
            [
                'name' => 'Delete work order - Valid ID',
                'method' => 'DELETE',
                'path' => '/api/work-orders/test-work-order-id',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'expected_status' => 200,
                'expected_fields' => ['success', 'message'],
                'category' => 'work_orders',
            ],
        ];
    }

    /**
     * Get maintenance tests.
     */
    private function getMaintenanceTests(): array
    {
        return [
            [
                'name' => 'List maintenance schedules',
                'method' => 'GET',
                'path' => '/api/maintenance/schedules',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'expected_status' => 200,
                'expected_fields' => ['success', 'data', 'pagination'],
                'category' => 'maintenance',
            ],
            [
                'name' => 'Create maintenance schedule',
                'method' => 'POST',
                'path' => '/api/maintenance/schedules',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'data' => [
                    'asset_id' => 'test-asset-id',
                    'maintenance_type' => 'preventive',
                    'scheduled_date' => '2024-06-15',
                    'description' => 'Scheduled maintenance',
                ],
                'expected_status' => 201,
                'expected_fields' => ['success', 'message', 'data.id'],
                'category' => 'maintenance',
            ],
        ];
    }

    /**
     * Get IoT tests.
     */
    private function getIoTTests(): array
    {
        return [
            [
                'name' => 'List sensors',
                'method' => 'GET',
                'path' => '/api/iot/sensors',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'expected_status' => 200,
                'expected_fields' => ['success', 'data', 'pagination'],
                'category' => 'iot',
            ],
            [
                'name' => 'Create sensor reading',
                'method' => 'POST',
                'path' => '/api/iot/readings',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'data' => [
                    'sensor_id' => 'test-sensor-id',
                    'value' => 25.5,
                    'unit' => 'celsius',
                    'quality' => 0.95,
                ],
                'expected_status' => 201,
                'expected_fields' => ['success', 'message', 'data.id'],
                'category' => 'iot',
            ],
            [
                'name' => 'List sensor alerts',
                'method' => 'GET',
                'path' => '/api/iot/alerts',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'expected_status' => 200,
                'expected_fields' => ['success', 'data', 'pagination'],
                'category' => 'iot',
            ],
        ];
    }

    /**
     * Get predictive maintenance tests.
     */
    private function getPredictiveMaintenanceTests(): array
    {
        return [
            [
                'name' => 'List predictive models',
                'method' => 'GET',
                'path' => '/api/predictive/models',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'expected_status' => 200,
                'expected_fields' => ['success', 'data', 'pagination'],
                'category' => 'predictive',
            ],
            [
                'name' => 'Create predictive model',
                'method' => 'POST',
                'path' => '/api/predictive/models',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'data' => [
                    'name' => 'Test Model',
                    'model_type' => 'failure_prediction',
                    'algorithm' => 'random_forest',
                    'target_variable' => 'failure_probability',
                    'input_features' => ['age', 'usage_hours'],
                ],
                'expected_status' => 201,
                'expected_fields' => ['success', 'message', 'data.id'],
                'category' => 'predictive',
            ],
            [
                'name' => 'Generate predictions',
                'method' => 'POST',
                'path' => '/api/predictive/generate-predictions',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'data' => [
                    'model_id' => 'test-model-id',
                    'asset_ids' => ['test-asset-id'],
                ],
                'expected_status' => 200,
                'expected_fields' => ['success', 'message', 'data.predictions_count'],
                'category' => 'predictive',
            ],
        ];
    }

    /**
     * Get analytics tests.
     */
    private function getAnalyticsTests(): array
    {
        return [
            [
                'name' => 'Get dashboard analytics',
                'method' => 'GET',
                'path' => '/api/analytics/dashboard',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'expected_status' => 200,
                'expected_fields' => ['success', 'data.overview', 'data.asset_analytics'],
                'category' => 'analytics',
            ],
            [
                'name' => 'Get overview metrics',
                'method' => 'GET',
                'path' => '/api/analytics/overview',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'expected_status' => 200,
                'expected_fields' => ['success', 'data.total_assets', 'data.availability'],
                'category' => 'analytics',
            ],
            [
                'name' => 'Get real-time analytics',
                'method' => 'GET',
                'path' => '/api/analytics/real-time',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'expected_status' => 200,
                'expected_fields' => ['success', 'data.current_status', 'data.timestamp'],
                'category' => 'analytics',
            ],
        ];
    }

    /**
     * Get report tests.
     */
    private function getReportTests(): array
    {
        return [
            [
                'name' => 'Get report dashboard',
                'method' => 'GET',
                'path' => '/api/reports/dashboard',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'expected_status' => 200,
                'expected_fields' => ['success', 'data'],
                'category' => 'reports',
            ],
            [
                'name' => 'Export report',
                'method' => 'POST',
                'path' => '/api/reports/export',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'data' => [
                    'report_type' => 'asset_value',
                    'format' => 'json',
                    'date_from' => '2024-01-01',
                    'date_to' => '2024-12-31',
                ],
                'expected_status' => 200,
                'expected_fields' => ['success', 'data.download_url'],
                'category' => 'reports',
            ],
        ];
    }

    /**
     * Get mobile tests.
     */
    private function getMobileTests(): array
    {
        return [
            [
                'name' => 'Mobile asset list',
                'method' => 'GET',
                'path' => '/api/mobile/assets',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'expected_status' => 200,
                'expected_fields' => ['success', 'data'],
                'category' => 'mobile',
            ],
            [
                'name' => 'Mobile work orders',
                'method' => 'GET',
                'path' => '/api/mobile/work-orders',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'expected_status' => 200,
                'expected_fields' => ['success', 'data'],
                'category' => 'mobile',
            ],
            [
                'name' => 'Mobile notifications',
                'method' => 'GET',
                'path' => '/api/mobile/notifications',
                'headers' => ['Authorization' => 'Bearer test-token'],
                'expected_status' => 200,
                'expected_fields' => ['success', 'data'],
                'category' => 'mobile',
            ],
        ];
    }

    /**
     * Run a single test.
     */
    private function runSingleTest(array $test): array
    {
        $startTime = now();
        $result = [
            'name' => $test['name'],
            'method' => $test['method'],
            'path' => $test['path'],
            'category' => $test['category'],
            'start_time' => $startTime->toISOString(),
        ];

        try {
            $response = $this->makeHttpRequest($test);
            
            $result['status_code'] = $response->status();
            $result['response_time'] = now()->diffInMilliseconds($startTime);
            $result['success'] = $this->evaluateTestResult($test, $response);
            $result['response_body'] = $response->body();
            $result['response_headers'] = $response->headers();
            $result['expected_status'] = $test['expected_status'];
            $result['status_match'] = $response->status() === $test['expected_status'];
            $result['field_validation'] = $this->validateResponseFields($test, $response);
            $result['error'] = null;

        } catch (\Exception $e) {
            $result['status_code'] = 0;
            $result['response_time'] = now()->diffInMilliseconds($startTime);
            $result['success'] = false;
            $result['response_body'] = null;
            $result['response_headers'] = [];
            $result['expected_status'] = $test['expected_status'];
            $result['status_match'] = false;
            $result['field_validation'] = false;
            $result['error'] = $e->getMessage();
        }

        $result['end_time'] = now()->toISOString();

        return $result;
    }

    /**
     * Make HTTP request for test.
     */
    private function makeHttpRequest(array $test)
    {
        $url = config('app.url') . $test['path'];
        $headers = $test['headers'] ?? [];
        $data = $test['data'] ?? [];

        // Add default headers
        $headers['Accept'] = 'application/json';
        $headers['Content-Type'] = 'application/json';

        $http = Http::withHeaders($headers);

        // Add timeout for tests
        $http = $http->timeout(30);

        switch ($test['method']) {
            case 'GET':
                return $http->get($url, $data);
            case 'POST':
                return $http->post($url, $data);
            case 'PUT':
                return $http->put($url, $data);
            case 'DELETE':
                return $http->delete($url);
            case 'PATCH':
                return $http->patch($url, $data);
            default:
                throw new \InvalidArgumentException("Unsupported HTTP method: {$test['method']}");
        }
    }

    /**
     * Evaluate test result.
     */
    private function evaluateTestResult(array $test, $response): bool
    {
        // Check status code
        if ($response->status() !== $test['expected_status']) {
            return false;
        }

        // Check required fields
        if (!$this->validateResponseFields($test, $response)) {
            return false;
        }

        return true;
    }

    /**
     * Validate response fields.
     */
    private function validateResponseFields(array $test, $response): bool
    {
        if (!isset($test['expected_fields'])) {
            return true;
        }

        try {
            $data = json_decode($response->body(), true);
            if (!$data) {
                return false;
            }

            foreach ($test['expected_fields'] as $field) {
                if (!$this->hasNestedField($data, $field)) {
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if nested field exists in array.
     */
    private function hasNestedField(array $array, string $field): bool
    {
        $keys = explode('.', $field);
        $current = $array;

        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return false;
            }
            $current = $current[$key];
        }

        return true;
    }

    /**
     * Generate test summary.
     */
    private function generateTestSummary(array $results): array
    {
        $total = count($results);
        $passed = count(array_filter($results, fn($r) => $r['success']));
        $failed = $total - $passed;
        
        $responseTimes = array_column($results, 'response_time');
        $avgResponseTime = count($responseTimes) > 0 ? array_sum($responseTimes) / count($responseTimes) : 0;
        $maxResponseTime = count($responseTimes) > 0 ? max($responseTimes) : 0;
        $minResponseTime = count($responseTimes) > 0 ? min($responseTimes) : 0;

        $categoryResults = [];
        foreach ($results as $result) {
            $category = $result['category'];
            if (!isset($categoryResults[$category])) {
                $categoryResults[$category] = ['total' => 0, 'passed' => 0, 'failed' => 0];
            }
            $categoryResults[$category]['total']++;
            if ($result['success']) {
                $categoryResults[$category]['passed']++;
            } else {
                $categoryResults[$category]['failed']++;
            }
        }

        return [
            'total_tests' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'success_rate' => $total > 0 ? ($passed / $total) * 100 : 0,
            'response_time' => [
                'average' => round($avgResponseTime, 2),
                'min' => $minResponseTime,
                'max' => $maxResponseTime,
            ],
            'category_results' => $categoryResults,
            'status' => $failed === 0 ? 'passed' : 'failed',
        ];
    }

    /**
     * Store test results.
     */
    private function storeTestResults(string $testId, array $results, array $summary, Carbon $startTime): void
    {
        $testData = [
            'id' => $testId,
            'summary' => $summary,
            'results' => $results,
            'started_at' => $startTime->toISOString(),
            'completed_at' => now()->toISOString(),
            'duration' => now()->diffInSeconds($startTime),
        ];

        // Store in cache for quick retrieval
        Cache::put("test_results_{$testId}", $testData, now()->addHours(24));

        // Store in database (if you have a test_results table)
        // DB::table('test_results')->insert([
        //     'id' => $testId,
        //     'summary' => json_encode($summary),
        //     'results' => json_encode($results),
        //     'started_at' => $startTime,
        //     'completed_at' => now(),
        // ]);

        // Store latest test run
        Cache::put('latest_test_run', $testData, now()->addDays(7));
    }

    /**
     * Get test results.
     */
    public function getTestResults(?string $testId = null, int $limit = 50): array
    {
        if ($testId) {
            $results = Cache::get("test_results_{$testId}");
            if (!$results) {
                throw new \InvalidArgumentException("Test results not found: {$testId}");
            }
            return $results;
        }

        // Get recent test runs (you might want to store these in a database)
        $latest = Cache::get('latest_test_run');
        return $latest ? [$latest] : [];
    }

    /**
     * Generate test report.
     */
    public function generateTestReport(string $type = 'summary', ?string $dateFrom = null, ?string $dateTo = null): array
    {
        switch ($type) {
            case 'summary':
                return $this->generateSummaryReport($dateFrom, $dateTo);
            case 'detailed':
                return $this->generateDetailedReport($dateFrom, $dateTo);
            case 'trend':
                return $this->generateTrendReport($dateFrom, $dateTo);
            case 'coverage':
                return $this->generateCoverageReport();
            default:
                throw new \InvalidArgumentException("Unknown report type: {$type}");
        }
    }

    /**
     * Generate summary report.
     */
    private function generateSummaryReport(?string $dateFrom, ?string $dateTo): array
    {
        $latestTest = Cache::get('latest_test_run');
        
        if (!$latestTest) {
            return [
                'type' => 'summary',
                'period' => [
                    'from' => $dateFrom,
                    'to' => $dateTo,
                ],
                'data' => [
                    'message' => 'No test data available',
                ],
            ];
        }

        return [
            'type' => 'summary',
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'data' => [
                'latest_run' => [
                    'test_id' => $latestTest['id'],
                    'executed_at' => $latestTest['completed_at'],
                    'duration' => $latestTest['duration'],
                    'summary' => $latestTest['summary'],
                ],
                'overall_health' => $latestTest['summary']['status'] === 'passed' ? 'healthy' : 'unhealthy',
                'recommendations' => $this->generateRecommendations($latestTest['summary']),
            ],
        ];
    }

    /**
     * Generate detailed report.
     */
    private function generateDetailedReport(?string $dateFrom, ?string $dateTo): array
    {
        $latestTest = Cache::get('latest_test_run');
        
        if (!$latestTest) {
            return [
                'type' => 'detailed',
                'period' => [
                    'from' => $dateFrom,
                    'to' => $dateTo,
                ],
                'data' => [
                    'message' => 'No test data available',
                ],
            ];
        }

        return [
            'type' => 'detailed',
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'data' => [
                'test_run' => $latestTest,
                'failed_tests' => array_filter($latestTest['results'], fn($r) => !$r['success']),
                'performance_analysis' => $this->analyzePerformance($latestTest['results']),
                'error_analysis' => $this->analyzeErrors($latestTest['results']),
            ],
        ];
    }

    /**
     * Generate trend report.
     */
    private function generateTrendReport(?string $dateFrom, ?string $dateTo): array
    {
        // This would ideally pull historical data from a database
        return [
            'type' => 'trend',
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'data' => [
                'trends' => [
                    'success_rate_trend' => [
                        'current' => 95.5,
                        'previous' => 93.2,
                        'change' => '+2.3%',
                    ],
                    'response_time_trend' => [
                        'current' => 245,
                        'previous' => 267,
                        'change' => '-8.2%',
                    ],
                ],
                'recommendations' => [
                    'Response times are improving',
                    'Consider adding more edge case tests',
                ],
            ],
        ];
    }

    /**
     * Generate coverage report.
     */
    private function generateCoverageReport(): array
    {
        $allTests = $this->getTestDefinitions('all', null);
        $categories = array_unique(array_column($allTests, 'category'));
        
        $coverage = [];
        foreach ($categories as $category) {
            $categoryTests = array_filter($allTests, fn($t) => $t['category'] === $category);
            $coverage[$category] = [
                'total_tests' => count($categoryTests),
                'endpoints' => count(array_unique(array_column($categoryTests, 'path'))),
                'methods' => array_count_values(array_column($categoryTests, 'method')),
            ];
        }

        return [
            'type' => 'coverage',
            'data' => [
                'overall' => [
                    'total_tests' => count($allTests),
                    'total_categories' => count($categories),
                    'total_endpoints' => count(array_unique(array_column($allTests, 'path'))),
                ],
                'by_category' => $coverage,
                'recommendations' => $this->generateCoverageRecommendations($coverage),
            ],
        ];
    }

    /**
     * Generate recommendations based on test results.
     */
    private function generateRecommendations(array $summary): array
    {
        $recommendations = [];

        if ($summary['success_rate'] < 90) {
            $recommendations[] = 'Success rate is below 90%. Review failing tests and fix underlying issues.';
        }

        if ($summary['response_time']['average'] > 1000) {
            $recommendations[] = 'Average response time is above 1 second. Consider optimizing API performance.';
        }

        if ($summary['response_time']['max'] > 5000) {
            $recommendations[] = 'Some endpoints have response times above 5 seconds. Investigate slow endpoints.';
        }

        foreach ($summary['category_results'] as $category => $results) {
            if ($results['failed'] > 0) {
                $recommendations[] = "Category '{$category}' has {$results['failed']} failing tests. Review and fix.";
            }
        }

        return $recommendations;
    }

    /**
     * Analyze performance from test results.
     */
    private function analyzePerformance(array $results): array
    {
        $responseTimes = array_column($results, 'response_time');
        
        return [
            'statistics' => [
                'mean' => count($responseTimes) > 0 ? round(array_sum($responseTimes) / count($responseTimes), 2) : 0,
                'median' => count($responseTimes) > 0 ? round($this->calculateMedian($responseTimes), 2) : 0,
                'p95' => count($responseTimes) > 0 ? round($this->calculatePercentile($responseTimes, 95), 2) : 0,
                'p99' => count($responseTimes) > 0 ? round($this->calculatePercentile($responseTimes, 99), 2) : 0,
            ],
            'slowest_endpoints' => $this->getSlowestEndpoints($results, 5),
            'fastest_endpoints' => $this->getFastestEndpoints($results, 5),
        ];
    }

    /**
     * Analyze errors from test results.
     */
    private function analyzeErrors(array $results): array
    {
        $failedTests = array_filter($results, fn($r) => !$r['success']);
        $errors = [];

        foreach ($failedTests as $test) {
            $errorType = $this->categorizeError($test);
            if (!isset($errors[$errorType])) {
                $errors[$errorType] = [];
            }
            $errors[$errorType][] = $test;
        }

        return [
            'error_types' => array_map(fn($tests) => count($tests), $errors),
            'most_common_errors' => $this->getMostCommonErrors($failedTests),
            'error_distribution' => $errors,
        ];
    }

    /**
     * Categorize error type.
     */
    private function categorizeError(array $test): string
    {
        if ($test['error']) {
            return 'connection_error';
        }

        if (!$test['status_match']) {
            return 'status_code_mismatch';
        }

        if (!$test['field_validation']) {
            return 'field_validation_error';
        }

        return 'unknown_error';
    }

    /**
     * Get most common errors.
     */
    private function getMostCommonErrors(array $failedTests): array
    {
        $errorMessages = [];
        
        foreach ($failedTests as $test) {
            if ($test['error']) {
                $errorMessages[] = $test['error'];
            } elseif (!$test['status_match']) {
                $errorMessages[] = "Expected status {$test['expected_status']}, got {$test['status_code']}";
            }
        }

        $errorCounts = array_count_values($errorMessages);
        arsort($errorCounts);

        return array_slice($errorCounts, 0, 5, true);
    }

    /**
     * Get slowest endpoints.
     */
    private function getSlowestEndpoints(array $results, int $limit): array
    {
        usort($results, fn($a, $b) => $b['response_time'] <=> $a['response_time']);
        
        return array_map(fn($r) => [
            'endpoint' => $r['method'] . ' ' . $r['path'],
            'response_time' => $r['response_time'],
            'test_name' => $r['name'],
        ], array_slice($results, 0, $limit));
    }

    /**
     * Get fastest endpoints.
     */
    private function getFastestEndpoints(array $results, int $limit): array
    {
        usort($results, fn($a, $b) => $a['response_time'] <=> $b['response_time']);
        
        return array_map(fn($r) => [
            'endpoint' => $r['method'] . ' ' . $r['path'],
            'response_time' => $r['response_time'],
            'test_name' => $r['name'],
        ], array_slice($results, 0, $limit));
    }

    /**
     * Calculate median.
     */
    private function calculateMedian(array $values): float
    {
        sort($values);
        $count = count($values);
        
        if ($count % 2 === 0) {
            return ($values[$count / 2 - 1] + $values[$count / 2]) / 2;
        }
        
        return $values[floor($count / 2)];
    }

    /**
     * Calculate percentile.
     */
    private function calculatePercentile(array $values, int $percentile): float
    {
        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);
        
        if (floor($index) == $index) {
            return $values[$index];
        }
        
        $lower = $values[floor($index)];
        $upper = $values[ceil($index)];
        $fraction = $index - floor($index);
        
        return $lower + ($upper - $lower) * $fraction;
    }

    /**
     * Generate coverage recommendations.
     */
    private function generateCoverageRecommendations(array $coverage): array
    {
        $recommendations = [];

        foreach ($coverage as $category => $data) {
            if ($data['total_tests'] < 5) {
                $recommendations[] = "Category '{$category}' has low test coverage. Consider adding more test cases.";
            }
            
            if (!isset($data['methods']['GET'])) {
                $recommendations[] = "Category '{$category}' is missing GET method tests.";
            }
            
            if (!isset($data['methods']['POST'])) {
                $recommendations[] = "Category '{$category}' is missing POST method tests.";
            }
        }

        return $recommendations;
    }

    /**
     * Get available test suites.
     */
    public function getAvailableTestSuites(): array
    {
        return [
            'all' => 'All tests',
            'authentication' => 'Authentication tests',
            'assets' => 'Asset management tests',
            'work_orders' => 'Work order tests',
            'maintenance' => 'Maintenance tests',
            'iot' => 'IoT sensor tests',
            'predictive' => 'Predictive maintenance tests',
            'analytics' => 'Analytics tests',
            'reports' => 'Report tests',
            'mobile' => 'Mobile API tests',
        ];
    }

    /**
     * Get last test run.
     */
    public function getLastTestRun(): ?array
    {
        return Cache::get('latest_test_run');
    }

    /**
     * Get test coverage.
     */
    public function getTestCoverage(): array
    {
        $coverageReport = $this->generateCoverageReport();
        return $coverageReport['data'];
    }
}
