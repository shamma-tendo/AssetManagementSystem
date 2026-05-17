<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserRole;
use App\Services\ApiDocumentationService;
use App\Services\ApiTestingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    use RefreshDatabase;

    protected ApiDocumentationService $documentationService;
    protected ApiTestingService $testingService;

    /**
     * Set up test data.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->documentationService = app(ApiDocumentationService::class);
        $this->testingService = app(ApiTestingService::class);
        
        // Create test users
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        Sanctum::actingAs($admin);
    }

    /**
     * Test comprehensive API documentation.
     */
    public function test_api_documentation(): void
    {
        $response = $this->getJson('/api/docs');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'info',
                         'servers',
                         'authentication',
                         'endpoints',
                         'schemas',
                         'examples',
                         'errors',
                         'rate_limits',
                         'versioning',
                         'changelog',
                     ],
                     'meta' => [
                         'version',
                         'format',
                         'generated_at',
                         'cache_ttl',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('title', $data['info']);
        $this->assertArrayHasKey('version', $data['info']);
        $this->assertArrayHasKey('description', $data['info']);
        $this->assertIsArray($data['endpoints']);
        $this->assertIsArray($data['schemas']);
    }

    /**
     * Test API documentation with filters.
     */
    public function test_api_documentation_with_filters(): void
    {
        $response = $this->getJson('/api/docs?format=json&version=v1&examples=0&schemas=0');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $meta = $response->json('meta');
        $this->assertEquals('json', $meta['format']);
        $this->assertEquals('v1', $meta['version']);
    }

    /**
     * Test endpoints by tag.
     */
    public function test_endpoints_by_tag(): void
    {
        $response = $this->getJson('/api/docs/endpoints?tag=Assets');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'tag',
                         'endpoints',
                         'total_count',
                     ],
                     'meta' => [
                         'version',
                         'generated_at',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertEquals('Assets', $data['tag']);
        $this->assertIsArray($data['endpoints']);
        $this->assertIsInt($data['total_count']);
    }

    /**
     * Test all endpoints.
     */
    public function test_all_endpoints(): void
    {
        $response = $this->getJson('/api/docs/endpoints');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $data = $response->json('data');
        $this->assertNull($data['tag']);
        $this->assertIsArray($data['endpoints']);
        $this->assertGreaterThan(0, $data['total_count']);
    }

    /**
     * Test endpoint detail.
     */
    public function test_endpoint_detail(): void
    {
        $response = $this->getJson('/api/docs/endpoint?method=GET&path=/assets');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'path',
                         'methods',
                         'summary',
                         'description',
                         'tags',
                         'parameters',
                         'responses',
                         'security',
                         'controller',
                         'method',
                     ],
                     'meta' => [
                         'generated_at',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertEquals('/assets', $data['path']);
        $this->assertContains('GET', $data['methods']);
    }

    /**
     * Test endpoint detail validation.
     */
    public function test_endpoint_detail_validation(): void
    {
        $response = $this->getJson('/api/docs/endpoint');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'method',
                     'path',
                 ]);
    }

    /**
     * Test endpoint not found.
     */
    public function test_endpoint_not_found(): void
    {
        $response = $this->getJson('/api/docs/endpoint?method=GET&path=/nonexistent');

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Endpoint not found',
                 ]);
    }

    /**
     * Test API schemas.
     */
    public function test_api_schemas(): void
    {
        $response = $this->getJson('/api/docs/schemas');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data',
                     'meta' => [
                         'format',
                         'generated_at',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('Asset', $data);
        $this->assertArrayHasKey('WorkOrder', $data);
        $this->assertArrayHasKey('Pagination', $data);
        $this->assertArrayHasKey('ErrorResponse', $data);
    }

    /**
     * Test specific schema.
     */
    public function test_specific_schema(): void
    {
        $response = $this->getJson('/api/docs/schemas?schema=Asset');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('Asset', $data);
        $this->assertArrayHasKey('type', $data['Asset']);
        $this->assertArrayHasKey('properties', $data['Asset']);
    }

    /**
     * Test schema not found.
     */
    public function test_schema_not_found(): void
    {
        $response = $this->getJson('/api/docs/schemas?schema=Nonexistent');

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Schema not found',
                 ]);
    }

    /**
     * Test API examples.
     */
    public function test_api_examples(): void
    {
        $response = $this->getJson('/api/docs/examples');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data',
                     'meta' => [
                         'generated_at',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('authentication', $data);
        $this->assertArrayHasKey('crud_operations', $data);
    }

    /**
     * Test specific example category.
     */
    public function test_specific_example_category(): void
    {
        $response = $this->getJson('/api/docs/examples?category=authentication');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('authentication', $data);
        $this->assertArrayHasKey('login', $data['authentication']);
    }

    /**
     * Test specific example.
     */
    public function test_specific_example(): void
    {
        $response = $this->getJson('/api/docs/examples?category=authentication&example=login');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('authentication', $data);
        $this->assertArrayHasKey('login', $data['authentication']);
    }

    /**
     * Test example category not found.
     */
    public function test_example_category_not_found(): void
    {
        $response = $this->getJson('/api/docs/examples?category=nonexistent');

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Example category not found',
                 ]);
    }

    /**
     * Test error codes.
     */
    public function test_error_codes(): void
    {
        $response = $this->getJson('/api/docs/errors');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data',
                     'meta' => [
                         'generated_at',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('authentication_errors', $data);
        $this->assertArrayHasKey('validation_errors', $data);
        $this->assertArrayHasKey('resource_errors', $data);
        $this->assertArrayHasKey('server_errors', $data);
    }

    /**
     * Test specific error category.
     */
    public function test_specific_error_category(): void
    {
        $response = $this->getJson('/api/docs/errors?category=authentication_errors');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('authentication_errors', $data);
        $this->assertArrayHasKey(401, $data['authentication_errors']);
        $this->assertArrayHasKey(403, $data['authentication_errors']);
    }

    /**
     * Test specific error code.
     */
    public function test_specific_error_code(): void
    {
        $response = $this->getJson('/api/docs/errors?category=authentication_errors&code=401');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('authentication_errors', $data);
        $this->assertArrayHasKey(401, $data['authentication_errors']);
    }

    /**
     * Test rate limits.
     */
    public function test_rate_limits(): void
    {
        $response = $this->getJson('/api/docs/rate-limits');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data',
                     'meta' => [
                         'generated_at',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('authentication', $data);
        $this->assertArrayHasKey('general_api', $data);
        $this->assertArrayHasKey('resource_intensive', $data);
    }

    /**
     * Test versioning.
     */
    public function test_versioning(): void
    {
        $response = $this->getJson('/api/docs/versioning');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data',
                     'meta' => [
                         'generated_at',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('current_version', $data);
        $this->assertArrayHasKey('versioning_strategy', $data);
        $this->assertArrayHasKey('supported_versions', $data);
        $this->assertArrayHasKey('version_lifecycle', $data);
        $this->assertArrayHasKey('backwards_compatibility', $data);
    }

    /**
     * Test changelog.
     */
    public function test_changelog(): void
    {
        $response = $this->getJson('/api/docs/changelog');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data',
                     'meta' => [
                         'generated_at',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('v1.0.0', $data);
        $this->assertArrayHasKey('date', $data['v1.0.0']);
        $this->assertArrayHasKey('features', $data['v1.0.0']);
        $this->assertArrayHasKey('endpoints', $data['v1.0.0']);
    }

    /**
     * Test search documentation.
     */
    public function test_search_documentation(): void
    {
        $response = $this->getJson('/api/docs/search?query=asset');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data',
                     'meta' => [
                         'query',
                         'section',
                         'limit',
                         'searched_at',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $meta = $response->json('meta');
        $this->assertEquals('asset', $meta['query']);
    }

    /**
     * Test search validation.
     */
    public function test_search_validation(): void
    {
        $response = $this->getJson('/api/docs/search');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'query',
                 ]);
    }

    /**
     * Test search with section filter.
     */
    public function test_search_with_section_filter(): void
    {
        $response = $this->getJson('/api/docs/search?query=asset&section=endpoints');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $meta = $response->json('meta');
        $this->assertEquals('asset', $meta['query']);
        $this->assertEquals('endpoints', $meta['section']);
    }

    /**
     * Test API statistics.
     */
    public function test_api_statistics(): void
    {
        $response = $this->getJson('/api/docs/statistics');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'overview',
                         'endpoints_by_method',
                         'endpoints_by_tag',
                         'authentication',
                         'response_codes',
                         'testing',
                     ],
                     'meta' => [
                         'generated_at',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('total_endpoints', $data['overview']);
        $this->assertArrayHasKey('total_tags', $data['overview']);
        $this->assertIsArray($data['endpoints_by_method']);
        $this->assertIsArray($data['endpoints_by_tag']);
    }

    /**
     * Test health check.
     */
    public function test_health_check(): void
    {
        $response = $this->getJson('/api/docs/health');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'status' => 'healthy',
                         'service' => 'API Documentation',
                         'timestamp',
                         'dependencies' => [
                             'documentation_service' => 'healthy',
                             'testing_service' => 'healthy',
                             'cache' => 'healthy',
                         ],
                     ],
                 ]);
    }

    /**
     * Test export documentation.
     */
    public function test_export_documentation(): void
    {
        $response = $this->postJson('/api/docs/export', [
            'format' => 'json',
            'section' => 'all',
            'version' => 'v1',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Documentation exported successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'content',
                         'content_type',
                         'file_extension',
                     ],
                     'meta' => [
                         'format',
                         'section',
                         'version',
                         'exported_at',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertEquals('json', $data['content_type']);
        $this->assertEquals('json', $data['file_extension']);
    }

    /**
     * Test export validation.
     */
    public function test_export_validation(): void
    {
        $response = $this->postJson('/api/docs/export', [
            'format' => 'invalid_format',
        ]);

        $response->assertStatus(500); // Service will throw exception for invalid format
    }

    /**
     * Test export different formats.
     */
    public function test_export_different_formats(): void
    {
        $formats = ['json', 'yaml', 'markdown', 'openapi'];

        foreach ($formats as $format) {
            $response = $this->postJson('/api/docs/export', [
                'format' => $format,
                'section' => 'info',
                'version' => 'v1',
            ]);

            $response->assertStatus(200);
            
            $data = $response->json('data');
            $this->assertEquals($format, $response->json('meta.format'));
            
            $expectedExtension = match($format) {
                'openapi' => 'json',
                default => $format,
            };
            $this->assertEquals($expectedExtension, $data['file_extension']);
        }
    }

    /**
     * Test run API tests.
     */
    public function test_run_api_tests(): void
    {
        $response = $this->postJson('/api/docs/tests/run', [
            'suite' => 'authentication',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'test_id',
                     'summary' => [
                         'total_tests',
                         'passed',
                         'failed',
                         'success_rate',
                         'response_time',
                         'category_results',
                         'status',
                     ],
                     'execution_time',
                     'suite',
                     'endpoint',
                 ]);

        $data = $response->json();
        $this->assertIsString($data['test_id']);
        $this->assertEquals('authentication', $data['suite']);
        $this->assertIsArray($data['summary']);
    }

    /**
     * Test run all tests.
     */
    public function test_run_all_tests(): void
    {
        $response = $this->postJson('/api/docs/tests/run', [
            'suite' => 'all',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $data = $response->json();
        $this->assertEquals('all', $data['suite']);
        $this->assertGreaterThan(0, $data['summary']['total_tests']);
    }

    /**
     * Test run tests for specific endpoint.
     */
    public function test_run_tests_for_endpoint(): void
    {
        $response = $this->postJson('/api/docs/tests/run', [
            'suite' => 'assets',
            'endpoint' => 'assets',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $data = $response->json();
        $this->assertEquals('assets', $data['suite']);
        $this->assertEquals('assets', $data['endpoint']);
    }

    /**
     * Test get test results.
     */
    public function test_get_test_results(): void
    {
        // First run a test to get results
        $runResponse = $this->postJson('/api/docs/tests/run', [
            'suite' => 'authentication',
        ]);
        
        $testId = $runResponse->json('test_id');

        // Now get the results
        $response = $this->getJson("/api/docs/tests/results?test_id={$testId}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'summary',
                         'results',
                         'started_at',
                         'completed_at',
                         'duration',
                     ],
                     'meta' => [
                         'limit',
                         'generated_at',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertEquals($testId, $data['id']);
        $this->assertIsArray($data['results']);
    }

    /**
     * Test get latest test results.
     */
    public function test_get_latest_test_results(): void
    {
        $response = $this->getJson('/api/docs/tests/results');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $data = $response->json('data');
        $this->assertIsArray($data);
    }

    /**
     * Test generate test report.
     */
    public function test_generate_test_report(): void
    {
        $response = $this->postJson('/api/docs/tests/report', [
            'type' => 'summary',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'type',
                         'period',
                         'data',
                     ],
                     'meta' => [
                         'type',
                         'format',
                         'generated_at',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertEquals('summary', $data['type']);
    }

    /**
     * Test generate detailed test report.
     */
    public function test_generate_detailed_test_report(): void
    {
        $response = $this->postJson('/api/docs/tests/report', [
            'type' => 'detailed',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $data = $response->json('data');
        $this->assertEquals('detailed', $data['type']);
    }

    /**
     * Test generate trend test report.
     */
    public function test_generate_trend_test_report(): void
    {
        $response = $this->postJson('/api/docs/tests/report', [
            'type' => 'trend',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $data = $response->json('data');
        $this->assertEquals('trend', $data['type']);
    }

    /**
     * Test generate coverage test report.
     */
    public function test_generate_coverage_test_report(): void
    {
        $response = $this->postJson('/api/docs/tests/report', [
            'type' => 'coverage',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $data = $response->json('data');
        $this->assertEquals('coverage', $data['type']);
    }

    /**
     * Test documentation service methods.
     */
    public function test_documentation_service_methods(): void
    {
        $documentation = $this->documentationService->generateDocumentation();

        $this->assertArrayHasKey('info', $documentation);
        $this->assertArrayHasKey('servers', $documentation);
        $this->assertArrayHasKey('authentication', $documentation);
        $this->assertArrayHasKey('endpoints', $documentation);
        $this->assertArrayHasKey('schemas', $documentation);
        $this->assertArrayHasKey('examples', $documentation);
        $this->assertArrayHasKey('errors', $documentation);
        $this->assertArrayHasKey('rate_limits', $documentation);
        $this->assertArrayHasKey('versioning', $documentation);
        $this->assertArrayHasKey('changelog', $documentation);

        // Test info structure
        $this->assertArrayHasKey('title', $documentation['info']);
        $this->assertArrayHasKey('version', $documentation['info']);
        $this->assertArrayHasKey('description', $documentation['info']);

        // Test endpoints structure
        $this->assertIsArray($documentation['endpoints']);
        foreach ($documentation['endpoints'] as $tag => $endpoints) {
            $this->assertIsArray($endpoints);
            foreach ($endpoints as $endpoint) {
                $this->assertArrayHasKey('path', $endpoint);
                $this->assertArrayHasKey('methods', $endpoint);
                $this->assertArrayHasKey('summary', $endpoint);
            }
        }
    }

    /**
     * Test testing service methods.
     */
    public function test_testing_service_methods(): void
    {
        $testSuites = $this->testingService->getAvailableTestSuites();
        $this->assertIsArray($testSuites);
        $this->assertArrayHasKey('all', $testSuites);
        $this->assertArrayHasKey('authentication', $testSuites);
        $this->assertArrayHasKey('assets', $testSuites);

        $testCoverage = $this->testingService->getTestCoverage();
        $this->assertIsArray($testCoverage);
        $this->assertArrayHasKey('overall', $testCoverage);
        $this->assertArrayHasKey('by_category', $testCoverage);
    }

    /**
     * Test documentation without authentication.
     */
    public function test_documentation_without_authentication(): void
    {
        Sanctum::actingAs(null);

        // Documentation endpoints should be public
        $response = $this->getJson('/api/docs');
        $response->assertStatus(200);

        $response = $this->getJson('/api/docs/schemas');
        $response->assertStatus(200);

        $response = $this->getJson('/api/docs/examples');
        $response->assertStatus(200);

        $response = $this->getJson('/api/docs/errors');
        $response->assertStatus(200);

        $response = $this->getJson('/api/docs/health');
        $response->assertStatus(200);
    }

    /**
     * Test testing endpoints without authentication.
     */
    public function test_testing_endpoints_without_authentication(): void
    {
        Sanctum::actingAs(null);

        // Testing endpoints should require authentication
        $response = $this->postJson('/api/docs/tests/run', ['suite' => 'all']);
        $response->assertStatus(401);

        $response = $this->getJson('/api/docs/tests/results');
        $response->assertStatus(401);

        $response = $this->postJson('/api/docs/tests/report', ['type' => 'summary']);
        $response->assertStatus(401);
    }

    /**
     * Test documentation caching.
     */
    public function test_documentation_caching(): void
    {
        // First call should generate and cache data
        $response1 = $this->getJson('/api/docs');
        $response1->assertStatus(200);

        // Second call should return cached data
        $response2 = $this->getJson('/api/docs');
        $response2->assertStatus(200);

        // Both responses should have the same data
        $this->assertEquals($response1->json('data'), $response2->json('data'));
    }

    /**
     * Test documentation performance.
     */
    public function test_documentation_performance(): void
    {
        $startTime = microtime(true);

        $response = $this->getJson('/api/docs');

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);

        // Documentation should respond within reasonable time (less than 2 seconds)
        $this->assertLessThan(2000, $responseTime, 'Documentation response time should be less than 2 seconds');
    }

    /**
     * Test documentation data consistency.
     */
    public function test_documentation_data_consistency(): void
    {
        $response = $this->getJson('/api/docs');
        $fullData = $response->json('data');

        // Test filtered documentation
        $response = $this->getJson('/api/docs?examples=0&schemas=0');
        $filteredData = $response->json('data');

        // Filtered data should not have examples and schemas
        $this->assertArrayNotHasKey('examples', $filteredData);
        $this->assertArrayNotHasKey('schemas', $filteredData);

        // But should have other sections
        $this->assertArrayHasKey('info', $filteredData);
        $this->assertArrayHasKey('endpoints', $filteredData);
    }

    /**
     * Test error handling.
     */
    public function test_error_handling(): void
    {
        // Test invalid test suite
        $response = $this->postJson('/api/docs/tests/run', [
            'suite' => 'nonexistent_suite',
        ]);

        $response->assertStatus(500); // Service will throw exception

        // Test invalid report type
        $response = $this->postJson('/api/docs/tests/report', [
            'type' => 'invalid_type',
        ]);

        $response->assertStatus(500); // Service will throw exception
    }

    /**
     * Test documentation with different versions.
     */
    public function test_documentation_with_different_versions(): void
    {
        $versions = ['v1'];

        foreach ($versions as $version) {
            $response = $this->getJson("/api/docs?version={$version}");
            $response->assertStatus(200);

            $meta = $response->json('meta');
            $this->assertEquals($version, $meta['version']);
        }
    }

    /**
     * Test search functionality.
     */
    public function test_search_functionality(): void
    {
        // Test searching for different terms
        $searchTerms = ['asset', 'work order', 'maintenance', 'sensor'];

        foreach ($searchTerms as $term) {
            $response = $this->getJson("/api/docs/search?query={$term}");
            $response->assertStatus(200);

            $data = $response->json('data');
            $this->assertIsArray($data);

            $meta = $response->json('meta');
            $this->assertEquals($term, $meta['query']);
        }
    }

    /**
     * Test export with different sections.
     */
    public function test_export_with_different_sections(): void
    {
        $sections = ['all', 'info', 'endpoints', 'schemas'];

        foreach ($sections as $section) {
            $response = $this->postJson('/api/docs/export', [
                'format' => 'json',
                'section' => $section,
                'version' => 'v1',
            ]);

            $response->assertStatus(200);

            $meta = $response->json('meta');
            $this->assertEquals($section, $meta['section']);
        }
    }

    /**
     * Test test suite execution.
     */
    public function test_test_suite_execution(): void
    {
        $suites = ['authentication', 'assets', 'work_orders', 'maintenance'];

        foreach ($suites as $suite) {
            $response = $this->postJson('/api/docs/tests/run', [
                'suite' => $suite,
            ]);

            $response->assertStatus(200);

            $data = $response->json();
            $this->assertEquals($suite, $data['suite']);
            $this->assertIsArray($data['results']);
            $this->assertIsArray($data['summary']);
        }
    }

    /**
     * Test test report generation.
     */
    public function test_test_report_generation(): void
    {
        $reportTypes = ['summary', 'detailed', 'trend', 'coverage'];

        foreach ($reportTypes as $type) {
            $response = $this->postJson('/api/docs/tests/report', [
                'type' => $type,
            ]);

            $response->assertStatus(200);

            $data = $response->json('data');
            $this->assertEquals($type, $data['type']);
            $this->assertArrayHasKey('period', $data);
            $this->assertArrayHasKey('data', $data);
        }
    }
}
