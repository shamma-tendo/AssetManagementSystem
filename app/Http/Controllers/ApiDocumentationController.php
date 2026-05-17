<?php

namespace App\Http\Controllers;

use App\Services\ApiDocumentationService;
use App\Services\ApiTestingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;

class ApiDocumentationController extends Controller
{
    protected ApiDocumentationService $documentationService;
    protected ApiTestingService $testingService;

    public function __construct(
        ApiDocumentationService $documentationService,
        ApiTestingService $testingService
    ) {
        $this->documentationService = $documentationService;
        $this->testingService = $testingService;
    }

    /**
     * Get comprehensive API documentation.
     */
    public function documentation(Request $request): JsonResponse
    {
        $format = $request->get('format', 'json');
        $version = $request->get('version', 'v1');
        $includeExamples = $request->get('examples', true);
        $includeSchemas = $request->get('schemas', true);

        $cacheKey = "api_docs_{$version}_{$format}_" . ($includeExamples ? '1' : '0') . '_' . ($includeSchemas ? '1' : '0');
        
        $documentation = Cache::remember($cacheKey, now()->addHours(6), function () use ($includeExamples, $includeSchemas) {
            $docs = $this->documentationService->generateDocumentation();
            
            if (!$includeExamples) {
                unset($docs['examples']);
            }
            
            if (!$includeSchemas) {
                unset($docs['schemas']);
            }
            
            return $docs;
        });

        return response()->json([
            'success' => true,
            'data' => $documentation,
            'meta' => [
                'version' => $version,
                'format' => $format,
                'generated_at' => now()->toISOString(),
                'cache_ttl' => '6 hours',
            ],
        ]);
    }

    /**
     * Get API endpoints by tag/category.
     */
    public function endpointsByTag(Request $request): JsonResponse
    {
        $tag = $request->get('tag');
        $version = $request->get('version', 'v1');

        $documentation = $this->documentationService->generateDocumentation();
        $endpoints = $documentation['endpoints'];

        if ($tag) {
            $endpoints = $endpoints[$tag] ?? [];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'tag' => $tag,
                'endpoints' => $endpoints,
                'total_count' => count($endpoints),
            ],
            'meta' => [
                'version' => $version,
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get specific endpoint documentation.
     */
    public function endpointDetail(Request $request): JsonResponse
    {
        $method = $request->get('method');
        $path = $request->get('path');

        if (!$method || !$path) {
            return response()->json([
                'success' => false,
                'message' => 'Method and path are required',
            ], 422);
        }

        $documentation = $this->documentationService->generateDocumentation();
        $endpoint = $this->findEndpoint($documentation['endpoints'], $method, $path);

        if (!$endpoint) {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $endpoint,
            'meta' => [
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get API schemas.
     */
    public function schemas(Request $request): JsonResponse
    {
        $schema = $request->get('schema');
        $format = $request->get('format', 'json');

        $documentation = $this->documentationService->generateDocumentation();
        $schemas = $documentation['schemas'];

        if ($schema) {
            if (!isset($schemas[$schema])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Schema not found',
                ], 404);
            }
            $schemas = [$schema => $schemas[$schema]];
        }

        return response()->json([
            'success' => true,
            'data' => $schemas,
            'meta' => [
                'format' => $format,
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get API examples.
     */
    public function examples(Request $request): JsonResponse
    {
        $category = $request->get('category');
        $example = $request->get('example');

        $documentation = $this->documentationService->generateDocumentation();
        $examples = $documentation['examples'];

        if ($category) {
            if (!isset($examples[$category])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Example category not found',
                ], 404);
            }
            
            if ($example) {
                if (!isset($examples[$category][$example])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Example not found',
                    ], 404);
                }
                $examples = [$category => [$example => $examples[$category][$example]]];
            } else {
                $examples = [$category => $examples[$category]];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $examples,
            'meta' => [
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get error codes documentation.
     */
    public function errorCodes(Request $request): JsonResponse
    {
        $category = $request->get('category');
        $code = $request->get('code');

        $documentation = $this->documentationService->generateDocumentation();
        $errors = $documentation['errors'];

        if ($category) {
            if (!isset($errors[$category])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error category not found',
                ], 404);
            }
            
            if ($code) {
                if (!isset($errors[$category][$code])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error code not found',
                    ], 404);
                }
                $errors = [$category => [$code => $errors[$category][$code]]];
            } else {
                $errors = [$category => $errors[$category]];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $errors,
            'meta' => [
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get rate limits information.
     */
    public function rateLimits(): JsonResponse
    {
        $documentation = $this->documentationService->generateDocumentation();
        $rateLimits = $documentation['rate_limits'];

        return response()->json([
            'success' => true,
            'data' => $rateLimits,
            'meta' => [
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get versioning information.
     */
    public function versioning(): JsonResponse
    {
        $documentation = $this->documentationService->generateDocumentation();
        $versioning = $documentation['versioning'];

        return response()->json([
            'success' => true,
            'data' => $versioning,
            'meta' => [
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get changelog.
     */
    public function changelog(): JsonResponse
    {
        $documentation = $this->documentationService->generateDocumentation();
        $changelog = $documentation['changelog'];

        return response()->json([
            'success' => true,
            'data' => $changelog,
            'meta' => [
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Run API tests.
     */
    public function runTests(Request $request): JsonResponse
    {
        $testSuite = $request->get('suite', 'all');
        $endpoint = $request->get('endpoint');
        $format = $request->get('format', 'json');

        try {
            $results = $this->testingService->runTestSuite($testSuite, $endpoint);

            return response()->json([
                'success' => true,
                'data' => $results,
                'meta' => [
                    'suite' => $testSuite,
                    'endpoint' => $endpoint,
                    'format' => $format,
                    'executed_at' => now()->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test execution failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get test results.
     */
    public function testResults(Request $request): JsonResponse
    {
        $testId = $request->get('test_id');
        $limit = $request->get('limit', 50);

        try {
            $results = $this->testingService->getTestResults($testId, $limit);

            return response()->json([
                'success' => true,
                'data' => $results,
                'meta' => [
                    'limit' => $limit,
                    'generated_at' => now()->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve test results',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Generate test report.
     */
    public function testReport(Request $request): JsonResponse
    {
        $reportType = $request->get('type', 'summary');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $format = $request->get('format', 'json');

        try {
            $report = $this->testingService->generateTestReport($reportType, $dateFrom, $dateTo);

            return response()->json([
                'success' => true,
                'data' => $report,
                'meta' => [
                    'type' => $reportType,
                    'format' => $format,
                    'generated_at' => now()->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate test report',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Export documentation.
     */
    public function export(Request $request): JsonResponse
    {
        $format = $request->get('format', 'json');
        $section = $request->get('section', 'all');
        $version = $request->get('version', 'v1');

        try {
            $exportData = $this->generateExportData($format, $section, $version);

            return response()->json([
                'success' => true,
                'message' => 'Documentation exported successfully',
                'data' => $exportData,
                'meta' => [
                    'format' => $format,
                    'section' => $section,
                    'version' => $version,
                    'exported_at' => now()->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Search documentation.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('query');
        $section = $request->get('section', 'all');
        $limit = $request->get('limit', 20);

        if (!$query) {
            return response()->json([
                'success' => false,
                'message' => 'Search query is required',
            ], 422);
        }

        try {
            $results = $this->searchDocumentation($query, $section, $limit);

            return response()->json([
                'success' => true,
                'data' => $results,
                'meta' => [
                    'query' => $query,
                    'section' => $section,
                    'limit' => $limit,
                    'searched_at' => now()->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get API statistics.
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->generateApiStatistics();

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'meta' => [
                    'generated_at' => now()->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Health check for API documentation.
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'status' => 'healthy',
                'service' => 'API Documentation',
                'version' => config('app.version', '1.0.0'),
                'timestamp' => now()->toISOString(),
                'dependencies' => [
                    'documentation_service' => 'healthy',
                    'testing_service' => 'healthy',
                    'cache' => 'healthy',
                ],
            ],
        ]);
    }

    // Helper methods

    /**
     * Find specific endpoint in documentation.
     */
    private function findEndpoint(array $endpoints, string $method, string $path): ?array
    {
        foreach ($endpoints as $tag => $tagEndpoints) {
            foreach ($tagEndpoints as $endpoint) {
                if (in_array(strtoupper($method), $endpoint['methods']) && $endpoint['path'] === $path) {
                    return $endpoint;
                }
            }
        }

        return null;
    }

    /**
     * Generate export data.
     */
    private function generateExportData(string $format, string $section, string $version): array
    {
        $documentation = $this->documentationService->generateDocumentation();

        if ($section !== 'all') {
            $documentation = [$section => $documentation[$section] ?? []];
        }

        return match($format) {
            'json' => [
                'content' => json_encode($documentation, JSON_PRETTY_PRINT),
                'content_type' => 'application/json',
                'file_extension' => 'json',
            ],
            'yaml' => [
                'content' => $this->convertToYaml($documentation),
                'content_type' => 'text/yaml',
                'file_extension' => 'yaml',
            ],
            'markdown' => [
                'content' => $this->convertToMarkdown($documentation),
                'content_type' => 'text/markdown',
                'file_extension' => 'md',
            ],
            'openapi' => [
                'content' => $this->convertToOpenApi($documentation),
                'content_type' => 'application/json',
                'file_extension' => 'json',
            ],
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };
    }

    /**
     * Convert documentation to YAML format.
     */
    private function convertToYaml(array $data): string
    {
        // Simple YAML conversion (in production, use a proper YAML library)
        return "# YAML Export\n" . json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Convert documentation to Markdown format.
     */
    private function convertToMarkdown(array $data): string
    {
        $markdown = "# API Documentation\n\n";

        if (isset($data['info'])) {
            $markdown .= "## Information\n\n";
            $markdown .= "**Title:** {$data['info']['title']}\n";
            $markdown .= "**Version:** {$data['info']['version']}\n";
            $markdown .= "**Description:** {$data['info']['description']}\n\n";
        }

        if (isset($data['endpoints'])) {
            $markdown .= "## Endpoints\n\n";
            foreach ($data['endpoints'] as $tag => $endpoints) {
                $markdown .= "### {$tag}\n\n";
                foreach ($endpoints as $endpoint) {
                    $methods = implode(', ', $endpoint['methods']);
                    $markdown .= "#### {$methods} {$endpoint['path']}\n\n";
                    $markdown .= "**Summary:** {$endpoint['summary']}\n\n";
                    $markdown .= "**Description:** {$endpoint['description']}\n\n";
                }
            }
        }

        return $markdown;
    }

    /**
     * Convert documentation to OpenAPI format.
     */
    private function convertToOpenApi(array $data): array
    {
        return [
            'openapi' => '3.0.0',
            'info' => $data['info'],
            'servers' => $data['servers'],
            'paths' => $this->convertEndpointsToPaths($data['endpoints']),
            'components' => [
                'schemas' => $data['schemas'],
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                    ],
                ],
            ],
        ];
    }

    /**
     * Convert endpoints to OpenAPI paths format.
     */
    private function convertEndpointsToPaths(array $endpoints): array
    {
        $paths = [];

        foreach ($endpoints as $tag => $tagEndpoints) {
            foreach ($tagEndpoints as $endpoint) {
                $path = $endpoint['path'];
                if (!isset($paths[$path])) {
                    $paths[$path] = [];
                }

                foreach ($endpoint['methods'] as $method) {
                    $paths[$path][strtolower($method)] = [
                        'summary' => $endpoint['summary'],
                        'description' => $endpoint['description'],
                        'tags' => $endpoint['tags'],
                        'parameters' => $endpoint['parameters'],
                        'responses' => $endpoint['responses'],
                        'security' => $endpoint['security'] ? [['bearerAuth' => []]] : [],
                    ];
                }
            }
        }

        return $paths;
    }

    /**
     * Search documentation.
     */
    private function searchDocumentation(string $query, string $section, int $limit): array
    {
        $documentation = $this->documentationService->generateDocumentation();
        $results = [];
        $query = strtolower($query);

        $searchableSections = $section === 'all' 
            ? ['endpoints', 'schemas', 'examples', 'errors']
            : [$section];

        foreach ($searchableSections as $sectionName) {
            if (!isset($documentation[$sectionName])) {
                continue;
            }

            $sectionResults = $this->searchInSection($documentation[$sectionName], $query, $sectionName);
            $results = array_merge($results, $sectionResults);
        }

        // Limit results
        return array_slice($results, 0, $limit);
    }

    /**
     * Search within a specific section.
     */
    private function searchInSection($section, string $query, string $sectionName): array
    {
        $results = [];

        if ($sectionName === 'endpoints') {
            foreach ($section as $tag => $endpoints) {
                foreach ($endpoints as $endpoint) {
                    if ($this->matchesQuery($endpoint, $query)) {
                        $results[] = [
                            'type' => 'endpoint',
                            'title' => implode(', ', $endpoint['methods']) . ' ' . $endpoint['path'],
                            'description' => $endpoint['summary'],
                            'tag' => $tag,
                            'data' => $endpoint,
                        ];
                    }
                }
            }
        } elseif ($sectionName === 'schemas') {
            foreach ($section as $schemaName => $schema) {
                if (str_contains(strtolower($schemaName), $query)) {
                    $results[] = [
                        'type' => 'schema',
                        'title' => $schemaName,
                        'description' => 'Data schema for ' . $schemaName,
                        'data' => $schema,
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Check if item matches search query.
     */
    private function matchesQuery(array $item, string $query): bool
    {
        $searchable = [
            $item['summary'] ?? '',
            $item['description'] ?? '',
            $item['path'] ?? '',
            implode(' ', $item['tags'] ?? []),
        ];

        $searchableText = strtolower(implode(' ', $searchable));
        return str_contains($searchableText, $query);
    }

    /**
     * Generate API statistics.
     */
    private function generateApiStatistics(): array
    {
        $documentation = $this->documentationService->generateDocumentation();
        
        $totalEndpoints = 0;
        $methodCounts = [];
        $tagCounts = [];

        foreach ($documentation['endpoints'] as $tag => $endpoints) {
            $tagCounts[$tag] = count($endpoints);
            $totalEndpoints += count($endpoints);

            foreach ($endpoints as $endpoint) {
                foreach ($endpoint['methods'] as $method) {
                    $methodCounts[$method] = ($methodCounts[$method] ?? 0) + 1;
                }
            }
        }

        return [
            'overview' => [
                'total_endpoints' => $totalEndpoints,
                'total_tags' => count($documentation['endpoints']),
                'total_schemas' => count($documentation['schemas']),
                'total_examples' => count($documentation['examples']),
                'version' => $documentation['info']['version'],
            ],
            'endpoints_by_method' => $methodCounts,
            'endpoints_by_tag' => $tagCounts,
            'authentication' => [
                'authenticated_endpoints' => $this->countAuthenticatedEndpoints($documentation['endpoints']),
                'public_endpoints' => $totalEndpoints - $this->countAuthenticatedEndpoints($documentation['endpoints']),
            ],
            'response_codes' => $this->getResponseCodeStatistics($documentation['endpoints']),
            'testing' => [
                'test_suites' => $this->testingService->getAvailableTestSuites(),
                'last_test_run' => $this->testingService->getLastTestRun(),
                'test_coverage' => $this->testingService->getTestCoverage(),
            ],
        ];
    }

    /**
     * Count authenticated endpoints.
     */
    private function countAuthenticatedEndpoints(array $endpoints): int
    {
        $count = 0;
        foreach ($endpoints as $tag => $tagEndpoints) {
            foreach ($tagEndpoints as $endpoint) {
                if ($endpoint['security']) {
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Get response code statistics.
     */
    private function getResponseCodeStatistics(array $endpoints): array
    {
        $codes = [];
        foreach ($endpoints as $tag => $tagEndpoints) {
            foreach ($tagEndpoints as $endpoint) {
                foreach (array_keys($endpoint['responses']) as $code) {
                    $codes[$code] = ($codes[$code] ?? 0) + 1;
                }
            }
        }
        return $codes;
    }
}
