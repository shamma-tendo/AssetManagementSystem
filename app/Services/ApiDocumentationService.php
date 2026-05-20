<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

class ApiDocumentationService
{
    /**
     * Generate comprehensive API documentation.
     */
    public function generateDocumentation(): array
    {
        return [
            'info' => $this->getApiInfo(),
            'servers' => $this->getServers(),
            'authentication' => $this->getAuthenticationInfo(),
            'endpoints' => $this->getAllEndpoints(),
            'schemas' => $this->getSchemas(),
            'examples' => $this->getExamples(),
            'errors' => $this->getErrorCodes(),
            'rate_limits' => $this->getRateLimits(),
            'versioning' => $this->getVersioningInfo(),
            'changelog' => $this->getChangelog(),
        ];
    }

    /**
     * Get API information.
     */
    private function getApiInfo(): array
    {
        return [
            'title' => 'Asset Management System API',
            'description' => 'Comprehensive API for managing assets, maintenance, IoT sensors, predictive maintenance, and advanced analytics',
            'version' => '1.0.0',
            'contact' => [
                'name' => 'API Support',
                'email' => 'api-support@assetmanagement.com',
                'url' => 'https://assetmanagement.com/support',
            ],
            'license' => [
                'name' => 'MIT',
                'url' => 'https://opensource.org/licenses/MIT',
            ],
            'terms_of_service' => 'https://assetmanagement.com/terms',
            'base_url' => config('app.url') . '/api',
            'documentation_url' => config('app.url') . '/api/docs',
        ];
    }

    /**
     * Get server information.
     */
    private function getServers(): array
    {
        return [
            [
                'url' => config('app.url') . '/api',
                'description' => 'Production server',
                'variables' => [
                    'version' => [
                        'default' => 'v1',
                        'enum' => ['v1'],
                        'description' => 'API version',
                    ],
                ],
            ],
            [
                'url' => 'https://staging.assetmanagement.com/api',
                'description' => 'Staging server',
                'variables' => [
                    'version' => [
                        'default' => 'v1',
                        'enum' => ['v1'],
                        'description' => 'API version',
                    ],
                ],
            ],
            [
                'url' => 'https://dev.assetmanagement.com/api',
                'description' => 'Development server',
                'variables' => [
                    'version' => [
                        'default' => 'v1',
                        'enum' => ['v1'],
                        'description' => 'API version',
                    ],
                ],
            ],
        ];
    }

    /**
     * Get authentication information.
     */
    private function getAuthenticationInfo(): array
    {
        return [
            'type' => 'Bearer Token',
            'description' => 'Use Laravel Sanctum Bearer tokens for authentication',
            'how_to_obtain' => [
                'step1' => 'Login via POST /api/auth/login',
                'step2' => 'Receive token in response',
                'step3' => 'Include token in Authorization header: Bearer {token}',
            ],
            'token_expiration' => 'Tokens expire after 24 hours by default',
            'refresh_tokens' => 'Use POST /api/auth/refresh to refresh tokens',
            'permissions' => 'Tokens inherit user permissions (RBAC)',
            'examples' => [
                'curl' => 'curl -H "Authorization: Bearer your-token-here" https://api.example.com/endpoint',
                'javascript' => 'fetch("https://api.example.com/endpoint", { headers: { "Authorization": "Bearer your-token-here" } })',
                'php' => '$response = Http::withToken("your-token-here")->get("https://api.example.com/endpoint");',
            ],
        ];
    }

    /**
     * Get all API endpoints.
     */
    private function getAllEndpoints(): array
    {
        $routes = Route::getRoutes();
        $endpoints = [];

        foreach ($routes as $route) {
            if ($this->isApiRoute($route)) {
                $endpoint = $this->extractEndpointInfo($route);
                if ($endpoint) {
                    $endpoints[] = $endpoint;
                }
            }
        }

        return $this->groupEndpointsByTag($endpoints);
    }

    /**
     * Check if route is an API route.
     */
    private function isApiRoute($route): bool
    {
        $uri = $route->uri();
        return str_starts_with($uri, 'api/') && !str_contains($uri, 'docs');
    }

    /**
     * Extract endpoint information from route.
     */
    private function extractEndpointInfo($route): ?array
    {
        $uri = $route->uri();
        $methods = $route->methods();
        $action = $route->getAction('uses');

        if (!is_string($action)) {
            return null;
        }

        // Parse controller and method
        $parts = explode('@', $action);
        if (count($parts) !== 2) {
            return null;
        }

        [$controller, $method] = $parts;
        $controllerName = class_basename($controller);

        // Get method reflection for parameter analysis
        $reflection = new ReflectionMethod($controller, $method);
        $parameters = $this->extractParameters($reflection);
        $responses = $this->extractResponses($controllerName, $method);

        return [
            'path' => '/' . str_replace('api/', '', $uri),
            'methods' => array_diff($methods, ['HEAD', 'OPTIONS']),
            'summary' => $this->generateSummary($controllerName, $method),
            'description' => $this->generateDescription($controllerName, $method),
            'tags' => $this->generateTags($controllerName),
            'parameters' => $parameters,
            'responses' => $responses,
            'security' => $this->requiresAuthentication($controllerName, $method),
            'controller' => $controllerName,
            'method' => $method,
        ];
    }

    /**
     * Extract parameters from method reflection.
     */
    private function extractParameters(ReflectionMethod $reflection): array
    {
        $parameters = [];

        foreach ($reflection->getParameters() as $param) {
            $paramInfo = $this->analyzeParameter($param);
            if ($paramInfo) {
                $parameters[] = $paramInfo;
            }
        }

        return $parameters;
    }

    /**
     * Analyze a single parameter.
     */
    private function analyzeParameter(ReflectionParameter $param): ?array
    {
        $name = $param->getName();
        $type = $this->getParameterType($param);
        $required = !$param->isDefaultValueAvailable();

        // Skip Laravel's Request objects
        if ($type && str_contains($type, 'Request')) {
            return null;
        }

        return [
            'name' => $name,
            'in' => $this->getParameterLocation($name),
            'description' => $this->generateParameterDescription($name, $type),
            'required' => $required,
            'schema' => [
                'type' => $this->mapTypeToSchema($type),
                'example' => $this->generateParameterExample($name, $type),
            ],
        ];
    }

    /**
     * Get parameter type.
     */
    private function getParameterType(ReflectionParameter $param): ?string
    {
        $type = $param->getType();
        return $type ? $type->getName() : null;
    }

    /**
     * Get parameter location (path, query, body).
     */
    private function getParameterLocation(string $name): string
    {
        // Path parameters are typically UUIDs or IDs
        if (str_ends_with($name, 'id') || str_contains($name, '_id')) {
            return 'path';
        }

        // Common query parameters
        $queryParams = ['page', 'per_page', 'sort', 'order', 'search', 'filter', 'from', 'to'];
        if (in_array($name, $queryParams)) {
            return 'query';
        }

        // Default to body for complex objects
        return 'body';
    }

    /**
     * Map PHP type to JSON schema type.
     */
    private function mapTypeToSchema(?string $type): string
    {
        return match($type) {
            'int', 'integer' => 'integer',
            'float', 'double' => 'number',
            'bool', 'boolean' => 'boolean',
            'array' => 'array',
            'string' => 'string',
            default => 'string',
        };
    }

    /**
     * Generate parameter example.
     */
    private function generateParameterExample(string $name, ?string $type): mixed
    {
        return match($name) {
            'page' => 1,
            'per_page' => 15,
            'sort' => 'created_at',
            'order' => 'desc',
            'search' => 'example search term',
            'from', 'date_from' => '2024-01-01',
            'to', 'date_to' => '2024-12-31',
            'status' => 'active',
            'priority' => 'medium',
            'type' => 'maintenance',
            default => match($type) {
                'int', 'integer' => 123,
                'float', 'double' => 123.45,
                'bool', 'boolean' => true,
                'array' => ['example', 'array'],
                default => 'example value',
            },
        };
    }

    /**
     * Generate parameter description.
     */
    private function generateParameterDescription(string $name, ?string $type): string
    {
        return match($name) {
            'page' => 'Page number for pagination',
            'per_page' => 'Number of items per page',
            'sort' => 'Field to sort by',
            'order' => 'Sort direction (asc or desc)',
            'search' => 'Search term for filtering results',
            'from', 'date_from' => 'Start date for date range filtering',
            'to', 'date_to' => 'End date for date range filtering',
            'status' => 'Status filter for records',
            'priority' => 'Priority level filter',
            'type' => 'Type filter for records',
            default => "Parameter: {$name} ({$type})",
        };
    }

    /**
     * Extract responses from controller method.
     */
    private function extractResponses(string $controller, string $method): array
    {
        $responses = [];

        // Success responses
        if (str_starts_with($method, 'index') || str_starts_with($method, 'show') || str_starts_with($method, 'list')) {
            $responses[200] = [
                'description' => 'Successful operation',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'success' => ['type' => 'boolean'],
                                'data' => ['type' => 'object'],
                                'pagination' => ['type' => 'object'],
                            ],
                        ],
                        'example' => $this->generateSuccessExample($controller, $method),
                    ],
                ],
            ];
        } elseif (str_starts_with($method, 'store') || str_starts_with($method, 'create')) {
            $responses[201] = [
                'description' => 'Resource created successfully',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'success' => ['type' => 'boolean'],
                                'message' => ['type' => 'string'],
                                'data' => ['type' => 'object'],
                            ],
                        ],
                        'example' => $this->generateSuccessExample($controller, $method),
                    ],
                ],
            ];
        } else {
            $responses[200] = [
                'description' => 'Successful operation',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'success' => ['type' => 'boolean'],
                                'message' => ['type' => 'string'],
                                'data' => ['type' => 'object'],
                            ],
                        ],
                        'example' => $this->generateSuccessExample($controller, $method),
                    ],
                ],
            ];
        }

        // Error responses
        $responses[401] = [
            'description' => 'Unauthorized - Authentication required',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean'],
                            'message' => ['type' => 'string'],
                        ],
                    ],
                    'example' => [
                        'success' => false,
                        'message' => 'Unauthenticated.',
                    ],
                ],
            ],
        ];

        $responses[403] = [
            'description' => 'Forbidden - Insufficient permissions',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean'],
                            'message' => ['type' => 'string'],
                        ],
                    ],
                    'example' => [
                        'success' => false,
                        'message' => 'This action is unauthorized.',
                    ],
                ],
            ],
        ];

        $responses[404] = [
            'description' => 'Not Found - Resource not found',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean'],
                            'message' => ['type' => 'string'],
                        ],
                    ],
                    'example' => [
                        'success' => false,
                        'message' => 'Resource not found.',
                    ],
                ],
            ],
        ];

        $responses[422] = [
            'description' => 'Validation Error - Invalid input data',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean'],
                            'message' => ['type' => 'string'],
                            'errors' => ['type' => 'object'],
                        ],
                    ],
                    'example' => [
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => [
                            'field_name' => ['The field name is required.'],
                        ],
                    ],
                ],
            ],
        ];

        $responses[500] = [
            'description' => 'Internal Server Error',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean'],
                            'message' => ['type' => 'string'],
                        ],
                    ],
                    'example' => [
                        'success' => false,
                        'message' => 'Internal server error.',
                    ],
                ],
            ],
        ];

        return $responses;
    }

    /**
     * Generate success example.
     */
    private function generateSuccessExample(string $controller, string $method): array
    {
        return match($controller) {
            'AssetController' => $this->generateAssetExample($method),
            'WorkOrderController' => $this->generateWorkOrderExample($method),
            'MaintenanceScheduleController' => $this->generateMaintenanceScheduleExample($method),
            'IoTController' => $this->generateIoTExample($method),
            'PredictiveMaintenanceController' => $this->generatePredictiveMaintenanceExample($method),
            'AdvancedAnalyticsController' => $this->generateAnalyticsExample($method),
            default => [
                'success' => true,
                'message' => 'Operation completed successfully',
                'data' => ['example' => 'data'],
            ],
        };
    }

    /**
     * Generate asset example.
     */
    private function generateAssetExample(string $method): array
    {
        if (str_starts_with($method, 'index')) {
            return [
                'success' => true,
                'data' => [
                    [
                        'id' => 'uuid-string',
                        'name' => 'Example Asset',
                        'description' => 'Asset description',
                        'category_id' => 'uuid-string',
                        'location_id' => 'uuid-string',
                        'status' => 'active',
                        'purchase_cost' => 10000.00,
                        'current_value' => 7500.00,
                        'created_at' => '2024-01-01T00:00:00Z',
                        'updated_at' => '2024-01-01T00:00:00Z',
                    ],
                ],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 15,
                    'total' => 100,
                    'last_page' => 7,
                ],
            ];
        }

        return [
            'success' => true,
            'message' => 'Asset operation completed',
            'data' => [
                'id' => 'uuid-string',
                'name' => 'Example Asset',
                'description' => 'Asset description',
                'category_id' => 'uuid-string',
                'location_id' => 'uuid-string',
                'status' => 'active',
                'purchase_cost' => 10000.00,
                'current_value' => 7500.00,
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-01T00:00:00Z',
            ],
        ];
    }

    /**
     * Generate work order example.
     */
    private function generateWorkOrderExample(string $method): array
    {
        if (str_starts_with($method, 'index')) {
            return [
                'success' => true,
                'data' => [
                    [
                        'id' => 'uuid-string',
                        'title' => 'Preventive Maintenance',
                        'description' => 'Routine maintenance check',
                        'asset_id' => 'uuid-string',
                        'priority' => 'medium',
                        'status' => 'pending',
                        'assigned_to' => 'uuid-string',
                        'created_at' => '2024-01-01T00:00:00Z',
                    ],
                ],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 15,
                    'total' => 50,
                    'last_page' => 4,
                ],
            ];
        }

        return [
            'success' => true,
            'message' => 'Work order operation completed',
            'data' => [
                'id' => 'uuid-string',
                'title' => 'Preventive Maintenance',
                'description' => 'Routine maintenance check',
                'asset_id' => 'uuid-string',
                'priority' => 'medium',
                'status' => 'pending',
                'assigned_to' => 'uuid-string',
                'created_at' => '2024-01-01T00:00:00Z',
            ],
        ];
    }

    /**
     * Generate maintenance schedule example.
     */
    private function generateMaintenanceScheduleExample(string $method): array
    {
        return [
            'success' => true,
            'message' => 'Maintenance schedule operation completed',
            'data' => [
                'id' => 'uuid-string',
                'asset_id' => 'uuid-string',
                'maintenance_type' => 'preventive',
                'scheduled_date' => '2024-06-15',
                'description' => 'Scheduled maintenance',
                'status' => 'scheduled',
            ],
        ];
    }

    /**
     * Generate IoT example.
     */
    private function generateIoTExample(string $method): array
    {
        if (str_contains($method, 'sensor')) {
            return [
                'success' => true,
                'message' => 'Sensor operation completed',
                'data' => [
                    'id' => 'uuid-string',
                    'name' => 'Temperature Sensor',
                    'sensor_type_id' => 'uuid-string',
                    'asset_id' => 'uuid-string',
                    'status' => 'active',
                    'last_reading_at' => '2024-01-01T12:00:00Z',
                ],
            ];
        }

        return [
            'success' => true,
            'message' => 'IoT operation completed',
            'data' => ['example' => 'iot data'],
        ];
    }

    /**
     * Generate predictive maintenance example.
     */
    private function generatePredictiveMaintenanceExample(string $method): array
    {
        if (str_contains($method, 'model')) {
            return [
                'success' => true,
                'message' => 'Predictive model operation completed',
                'data' => [
                    'id' => 'uuid-string',
                    'name' => 'Failure Prediction Model',
                    'model_type' => 'failure_prediction',
                    'algorithm' => 'random_forest',
                    'accuracy_score' => 0.85,
                    'is_active' => true,
                ],
            ];
        }

        return [
            'success' => true,
            'message' => 'Predictive maintenance operation completed',
            'data' => ['example' => 'predictive data'],
        ];
    }

    /**
     * Generate analytics example.
     */
    private function generateAnalyticsExample(string $method): array
    {
        return [
            'success' => true,
            'data' => [
                'overview' => [
                    'total_assets' => 100,
                    'active_assets' => 85,
                    'maintenance_cost' => 50000.00,
                    'availability' => 95.5,
                ],
                'trends' => [
                    'asset_utilization' => 78.5,
                    'efficiency_score' => 82.3,
                    'downtime_hours' => 12.5,
                ],
            ],
            'generated_at' => '2024-01-01T12:00:00Z',
        ];
    }

    /**
     * Check if endpoint requires authentication.
     */
    private function requiresAuthentication(string $controller, string $method): bool
    {
        // Auth endpoints don't require authentication for login/register
        if ($controller === 'AuthController' && in_array($method, ['login', 'register'])) {
            return false;
        }

        // All other endpoints require authentication
        return true;
    }

    /**
     * Generate summary for endpoint.
     */
    private function generateSummary(string $controller, string $method): string
    {
        $resource = $this->getResourceName($controller);
        $action = $this->getActionName($method);

        return match($action) {
            'list' => "List all {$resource}",
            'show' => "Get a specific {$resource}",
            'store' => "Create a new {$resource}",
            'update' => "Update a {$resource}",
            'destroy' => "Delete a {$resource}",
            default => "{$action} {$resource}",
        };
    }

    /**
     * Generate description for endpoint.
     */
    private function generateDescription(string $controller, string $method): string
    {
        $resource = $this->getResourceName($controller);
        $action = $this->getActionName($method);

        return match($controller) {
            'AssetController' => $this->generateAssetDescription($action),
            'WorkOrderController' => $this->generateWorkOrderDescription($action),
            'MaintenanceScheduleController' => $this->generateMaintenanceScheduleDescription($action),
            'IoTController' => $this->generateIoTDescription($action),
            'PredictiveMaintenanceController' => $this->generatePredictiveMaintenanceDescription($action),
            'AdvancedAnalyticsController' => $this->generateAnalyticsDescription($action),
            default => "Endpoint for {$action} operation on {$resource}",
        };
    }

    /**
     * Generate asset description.
     */
    private function generateAssetDescription(string $action): string
    {
        return match($action) {
            'list' => 'Retrieve a paginated list of assets with optional filtering and sorting',
            'show' => 'Get detailed information about a specific asset including its history and relationships',
            'store' => 'Create a new asset with the provided details and assign it to a category and location',
            'update' => 'Update asset information including status, location, and other properties',
            'destroy' => 'Delete an asset (soft delete - asset is marked as deleted but retained in database)',
            default => "Asset {$action} operation",
        };
    }

    /**
     * Generate work order description.
     */
    private function generateWorkOrderDescription(string $action): string
    {
        return match($action) {
            'list' => 'Retrieve work orders with filtering by status, priority, assigned technician, or asset',
            'show' => 'Get detailed work order information including tasks, parts used, and time tracking',
            'store' => 'Create a new work order and optionally assign it to a technician and schedule it',
            'update' => 'Update work order details, reassign technicians, or change priority and status',
            'destroy' => 'Cancel or delete a work order (soft delete)',
            default => "Work order {$action} operation",
        };
    }

    /**
     * Generate maintenance schedule description.
     */
    private function generateMaintenanceScheduleDescription(string $action): string
    {
        return match($action) {
            'list' => 'Get maintenance schedules with filtering by date range, asset, or maintenance type',
            'show' => 'Get detailed maintenance schedule information including assigned resources and dependencies',
            'store' => 'Create a new maintenance schedule with recurring patterns and resource requirements',
            'update' => 'Update maintenance schedule, change dates, or modify resource assignments',
            'destroy' => 'Delete a maintenance schedule',
            default => "Maintenance schedule {$action} operation",
        };
    }

    /**
     * Generate IoT description.
     */
    private function generateIoTDescription(string $action): string
    {
        return match($action) {
            'list' => 'List IoT devices, sensors, or readings with filtering and real-time data',
            'show' => 'Get detailed IoT device information including configuration and recent readings',
            'store' => 'Register new IoT devices or create sensor readings',
            'update' => 'Update IoT device configuration or calibration settings',
            'destroy' => 'Remove IoT device from system',
            default => "IoT {$action} operation",
        };
    }

    /**
     * Generate predictive maintenance description.
     */
    private function generatePredictiveMaintenanceDescription(string $action): string
    {
        return match($action) {
            'list' => 'List predictive models, predictions, or maintenance recommendations',
            'show' => 'Get detailed predictive model information including performance metrics and training history',
            'store' => 'Create new predictive models or generate maintenance recommendations',
            'update' => 'Update predictive model parameters or retrain with new data',
            'destroy' => 'Delete predictive model or recommendation',
            default => "Predictive maintenance {$action} operation",
        };
    }

    /**
     * Generate analytics description.
     */
    private function generateAnalyticsDescription(string $action): string
    {
        return match($action) {
            'list' => 'Get comprehensive analytics data with customizable filters and time periods',
            'show' => 'Get detailed analytics insights and recommendations',
            'store' => 'Generate custom reports or export analytics data',
            'update' => 'Update analytics configuration or refresh cached data',
            'destroy' => 'Clear analytics cache or delete custom reports',
            default => "Analytics {$action} operation",
        };
    }

    /**
     * Get resource name from controller.
     */
    private function getResourceName(string $controller): string
    {
        return match($controller) {
            'AssetController' => 'asset',
            'CategoryController' => 'category',
            'LocationController' => 'location',
            'DepartmentController' => 'department',
            'UserController' => 'user',
            'AuthController' => 'authentication',
            'ReportController' => 'report',
            'WorkOrderController' => 'work order',
            'MaintenanceScheduleController' => 'maintenance schedule',
            'MobileApiController' => 'mobile API',
            'InspectionController' => 'inspection',
            'InventoryController' => 'inventory',
            'DepreciationController' => 'depreciation',
            'IoTController' => 'IoT device',
            'PredictiveMaintenanceController' => 'predictive maintenance',
            'AdvancedAnalyticsController' => 'analytics',
            default => strtolower(str_replace('Controller', '', $controller)),
        };
    }

    /**
     * Get action name from method.
     */
    private function getActionName(string $method): string
    {
        return match($method) {
            'index' => 'list',
            'show' => 'show',
            'store' => 'store',
            'update' => 'update',
            'destroy' => 'destroy',
            'create' => 'store',
            'edit' => 'update',
            'delete' => 'destroy',
            default => $method,
        };
    }

    /**
     * Generate tags for endpoint.
     */
    private function generateTags(string $controller): array
    {
        return match($controller) {
            'AssetController' => ['Assets', 'Asset Management'],
            'CategoryController' => ['Categories', 'Configuration'],
            'LocationController' => ['Locations', 'Configuration'],
            'DepartmentController' => ['Departments', 'Configuration'],
            'UserController' => ['Users', 'Administration'],
            'AuthController' => ['Authentication', 'Security'],
            'ReportController' => ['Reports', 'Analytics'],
            'WorkOrderController' => ['Work Orders', 'Maintenance'],
            'MaintenanceScheduleController' => ['Schedules', 'Maintenance'],
            'MobileApiController' => ['Mobile', 'API'],
            'InspectionController' => ['Inspections', 'Quality'],
            'InventoryController' => ['Inventory', 'Parts'],
            'DepreciationController' => ['Depreciation', 'Finance'],
            'IoTController' => ['IoT', 'Sensors', 'Devices'],
            'PredictiveMaintenanceController' => ['Predictive Maintenance', 'AI', 'ML'],
            'AdvancedAnalyticsController' => ['Analytics', 'Dashboard', 'Business Intelligence'],
            default => ['API'],
        };
    }

    /**
     * Group endpoints by tags.
     */
    private function groupEndpointsByTag(array $endpoints): array
    {
        $grouped = [];

        foreach ($endpoints as $endpoint) {
            foreach ($endpoint['tags'] as $tag) {
                if (!isset($grouped[$tag])) {
                    $grouped[$tag] = [];
                }
                $grouped[$tag][] = $endpoint;
            }
        }

        return $grouped;
    }

    /**
     * Get data schemas.
     */
    private function getSchemas(): array
    {
        return [
            'Asset' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string', 'format' => 'uuid', 'description' => 'Unique identifier'],
                    'name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Asset name'],
                    'description' => ['type' => 'string', 'nullable' => true, 'description' => 'Asset description'],
                    'category_id' => ['type' => 'string', 'format' => 'uuid', 'description' => 'Category reference'],
                    'location_id' => ['type' => 'string', 'format' => 'uuid', 'description' => 'Location reference'],
                    'status' => ['type' => 'string', 'enum' => ['active', 'inactive', 'maintenance', 'retired'], 'description' => 'Asset status'],
                    'purchase_cost' => ['type' => 'number', 'format' => 'float', 'description' => 'Purchase cost'],
                    'current_value' => ['type' => 'number', 'format' => 'float', 'description' => 'Current value'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time', 'description' => 'Creation timestamp'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time', 'description' => 'Last update timestamp'],
                ],
            ],
            'WorkOrder' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string', 'format' => 'uuid'],
                    'title' => ['type' => 'string', 'maxLength' => 255],
                    'description' => ['type' => 'string', 'nullable' => true],
                    'asset_id' => ['type' => 'string', 'format' => 'uuid'],
                    'priority' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'critical']],
                    'status' => ['type' => 'string', 'enum' => ['pending', 'in_progress', 'completed', 'cancelled']],
                    'assigned_to' => ['type' => 'string', 'format' => 'uuid', 'nullable' => true],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Sensor' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string', 'format' => 'uuid'],
                    'name' => ['type' => 'string', 'maxLength' => 255],
                    'sensor_type_id' => ['type' => 'string', 'format' => 'uuid'],
                    'asset_id' => ['type' => 'string', 'format' => 'uuid'],
                    'status' => ['type' => 'string', 'enum' => ['active', 'inactive', 'maintenance', 'error']],
                    'last_reading_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                ],
            ],
            'PredictiveModel' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string', 'format' => 'uuid'],
                    'name' => ['type' => 'string', 'maxLength' => 255],
                    'model_type' => ['type' => 'string', 'enum' => ['failure_prediction', 'remaining_useful_life', 'anomaly_detection']],
                    'algorithm' => ['type' => 'string', 'enum' => ['random_forest', 'neural_network', 'lstm']],
                    'accuracy_score' => ['type' => 'number', 'format' => 'float'],
                    'is_active' => ['type' => 'boolean'],
                ],
            ],
            'Pagination' => [
                'type' => 'object',
                'properties' => [
                    'current_page' => ['type' => 'integer'],
                    'per_page' => ['type' => 'integer'],
                    'total' => ['type' => 'integer'],
                    'last_page' => ['type' => 'integer'],
                ],
            ],
            'ErrorResponse' => [
                'type' => 'object',
                'properties' => [
                    'success' => ['type' => 'boolean', 'example' => false],
                    'message' => ['type' => 'string'],
                    'errors' => ['type' => 'object', 'nullable' => true],
                ],
            ],
        ];
    }

    /**
     * Get API examples.
     */
    private function getExamples(): array
    {
        return [
            'authentication' => [
                'login' => [
                    'description' => 'Login to get access token',
                    'request' => [
                        'method' => 'POST',
                        'url' => '/api/auth/login',
                        'headers' => [
                            'Content-Type' => 'application/json',
                        ],
                        'body' => [
                            'email' => 'user@example.com',
                            'password' => 'password123',
                        ],
                    ],
                    'response' => [
                        'success' => true,
                        'data' => [
                            'user' => [
                                'id' => 'uuid',
                                'name' => 'John Doe',
                                'email' => 'user@example.com',
                            ],
                            'token' => 'bearer-token-string',
                        ],
                    ],
                ],
                'protected_request' => [
                    'description' => 'Example of making an authenticated request',
                    'request' => [
                        'method' => 'GET',
                        'url' => '/api/assets',
                        'headers' => [
                            'Authorization' => 'Bearer your-token-here',
                            'Content-Type' => 'application/json',
                        ],
                    ],
                ],
            ],
            'crud_operations' => [
                'create_asset' => [
                    'description' => 'Create a new asset',
                    'request' => [
                        'method' => 'POST',
                        'url' => '/api/assets',
                        'headers' => [
                            'Authorization' => 'Bearer token',
                            'Content-Type' => 'application/json',
                        ],
                        'body' => [
                            'name' => 'New Asset',
                            'description' => 'Asset description',
                            'category_id' => 'uuid',
                            'location_id' => 'uuid',
                            'purchase_cost' => 10000.00,
                        ],
                    ],
                ],
                'list_assets' => [
                    'description' => 'List assets with pagination',
                    'request' => [
                        'method' => 'GET',
                        'url' => '/api/assets?page=1&per_page=10&sort=name&order=asc',
                        'headers' => [
                            'Authorization' => 'Bearer token',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get error codes.
     */
    private function getErrorCodes(): array
    {
        return [
            'authentication_errors' => [
                401 => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required',
                    'description' => 'The request requires authentication. Please provide a valid bearer token.',
                ],
                403 => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Insufficient permissions',
                    'description' => 'The authenticated user does not have permission to perform this action.',
                ],
            ],
            'validation_errors' => [
                422 => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid input data',
                    'description' => 'The request data failed validation. Check the errors field for details.',
                ],
            ],
            'resource_errors' => [
                404 => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Resource not found',
                    'description' => 'The requested resource does not exist.',
                ],
                409 => [
                    'code' => 'CONFLICT',
                    'message' => 'Resource conflict',
                    'description' => 'The request conflicts with the current state of the resource.',
                ],
            ],
            'server_errors' => [
                500 => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Internal server error',
                    'description' => 'An unexpected error occurred on the server.',
                ],
                503 => [
                    'code' => 'SERVICE_UNAVAILABLE',
                    'message' => 'Service unavailable',
                    'description' => 'The service is temporarily unavailable. Please try again later.',
                ],
            ],
        ];
    }

    /**
     * Get rate limits.
     */
    private function getRateLimits(): array
    {
        return [
            'authentication' => [
                'login' => [
                    'requests_per_minute' => 10,
                    'requests_per_hour' => 100,
                    'description' => 'Login endpoint rate limiting',
                ],
                'refresh' => [
                    'requests_per_minute' => 20,
                    'requests_per_hour' => 200,
                    'description' => 'Token refresh rate limiting',
                ],
            ],
            'general_api' => [
                'authenticated_users' => [
                    'requests_per_minute' => 1000,
                    'requests_per_hour' => 10000,
                    'description' => 'Authenticated user rate limits',
                ],
                'guest_users' => [
                    'requests_per_minute' => 100,
                    'requests_per_hour' => 1000,
                    'description' => 'Unauthenticated user rate limits',
                ],
            ],
            'resource_intensive' => [
                'analytics' => [
                    'requests_per_minute' => 100,
                    'requests_per_hour' => 1000,
                    'description' => 'Analytics endpoints rate limiting',
                ],
                'reports' => [
                    'requests_per_minute' => 50,
                    'requests_per_hour' => 500,
                    'description' => 'Report generation rate limiting',
                ],
            ],
        ];
    }

    /**
     * Get versioning information.
     */
    private function getVersioningInfo(): array
    {
        return [
            'current_version' => 'v1',
            'versioning_strategy' => 'URL path versioning',
            'supported_versions' => ['v1'],
            'deprecated_versions' => [],
            'version_lifecycle' => [
                'v1' => [
                    'status' => 'active',
                    'released_at' => '2024-01-01',
                    'support_until' => '2025-12-31',
                    'deprecation_date' => null,
                ],
            ],
            'backwards_compatibility' => [
                'breaking_changes' => 'Major version updates only',
                'field_additions' => 'Non-breaking (optional fields)',
                'field_removals' => 'Breaking - requires major version',
                'field_type_changes' => 'Breaking - requires major version',
            ],
        ];
    }

    /**
     * Get changelog.
     */
    private function getChangelog(): array
    {
        return [
            'v1.0.0' => [
                'date' => '2024-01-01',
                'description' => 'Initial release of Asset Management System API',
                'features' => [
                    'Complete asset management CRUD operations',
                    'Work order management system',
                    'Maintenance scheduling and tracking',
                    'IoT sensor integration',
                    'Predictive maintenance algorithms',
                    'Advanced analytics dashboard',
                    'User authentication and authorization',
                    'Mobile API endpoints',
                    'Comprehensive reporting system',
                ],
                'endpoints' => [
                    'total' => 85,
                    'authenticated' => 75,
                    'public' => 10,
                ],
                'breaking_changes' => [],
                'security_updates' => [
                    'Laravel Sanctum authentication',
                    'Role-based access control (RBAC)',
                    'Input validation and sanitization',
                    'Rate limiting implementation',
                ],
            ],
        ];
    }
}
