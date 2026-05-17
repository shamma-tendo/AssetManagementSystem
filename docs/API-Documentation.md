# API Documentation and Testing Suite

## Overview

The Asset Management System includes a comprehensive API Documentation and Testing Suite that provides complete visibility into all API endpoints, automated testing capabilities, and detailed performance analytics.

## Features

### 📚 **API Documentation**
- **Auto-generated Documentation**: Automatically generated from route definitions
- **Interactive Examples**: Real-world usage examples for all endpoints
- **Schema Definitions**: Complete data models and validation rules
- **Error Code Reference**: Detailed error handling documentation
- **Version Management**: API versioning and changelog tracking
- **Search Functionality**: Full-text search across documentation

### 🧪 **API Testing Suite**
- **Automated Testing**: Comprehensive test coverage for all endpoints
- **Performance Monitoring**: Response time and performance analytics
- **Test Reports**: Detailed test execution reports and trends
- **Coverage Analysis**: Test coverage metrics and recommendations
- **Real-time Monitoring**: Live API health and status monitoring

### 📊 **Analytics & Insights**
- **Usage Statistics**: API usage patterns and trends
- **Performance Metrics**: Response time analysis and optimization
- **Error Analysis**: Error rate tracking and categorization
- **Health Monitoring**: System health and dependency status

## Documentation Endpoints

### Base Documentation
```
GET /api/docs                    # Complete API documentation
GET /api/docs/endpoints         # Endpoints by category/tag
GET /api/docs/endpoint          # Specific endpoint details
GET /api/docs/schemas           # Data model schemas
GET /api/docs/examples          # Usage examples
GET /api/docs/errors            # Error code reference
GET /api/docs/rate-limits       # Rate limiting information
GET /api/docs/versioning        # API versioning details
GET /api/docs/changelog         # Version changelog
```

### Search and Export
```
GET /api/docs/search            # Search documentation
GET /api/docs/statistics        # API statistics
GET /api/docs/health            # Health check
POST /api/docs/export            # Export documentation
```

## Testing Endpoints

### Test Execution
```
POST /api/docs/tests/run        # Run test suite
GET /api/docs/tests/results     # Get test results
POST /api/docs/tests/report     # Generate test report
```

## Usage Examples

### Getting Complete Documentation
```bash
curl -X GET "http://localhost:8000/api/docs" \
     -H "Accept: application/json"
```

### Searching Documentation
```bash
curl -X GET "http://localhost:8000/api/docs/search?query=asset" \
     -H "Accept: application/json"
```

### Running API Tests
```bash
curl -X POST "http://localhost:8000/api/docs/tests/run" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"suite": "all"}'
```

### Exporting Documentation
```bash
curl -X POST "http://localhost:8000/api/docs/export" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{
       "format": "openapi",
       "section": "all",
       "version": "v1"
     }'
```

## Test Suites

### Available Test Suites
- **all**: Complete test suite (all endpoints)
- **authentication**: Authentication and authorization tests
- **assets**: Asset management tests
- **work_orders**: Work order management tests
- **maintenance**: Maintenance scheduling tests
- **iot**: IoT sensor integration tests
- **predictive**: Predictive maintenance tests
- **analytics**: Analytics dashboard tests
- **reports**: Reporting system tests
- **mobile**: Mobile API tests

### Test Categories

#### Authentication Tests
- Login with valid credentials
- Login with invalid credentials
- Token refresh functionality
- Logout authenticated users
- Unauthorized access attempts

#### Asset Management Tests
- CRUD operations for assets
- Asset search and filtering
- Asset categorization
- Asset lifecycle management
- Asset validation rules

#### Work Order Tests
- Work order creation and management
- Assignment and status updates
- Priority and scheduling
- Work order completion
- Notification systems

#### IoT Integration Tests
- Sensor registration and management
- Real-time data ingestion
- Alert generation and handling
- Sensor health monitoring
- Data quality validation

#### Predictive Maintenance Tests
- Model training and evaluation
- Prediction generation
- Recommendation creation
- Model performance monitoring
- Accuracy validation

## Report Types

### Summary Report
```json
{
  "type": "summary",
  "period": {
    "from": "2024-01-01",
    "to": "2024-12-31"
  },
  "data": {
    "latest_run": {
      "test_id": "test_123456",
      "executed_at": "2024-01-01T12:00:00Z",
      "duration": 45,
      "summary": {
        "total_tests": 150,
        "passed": 145,
        "failed": 5,
        "success_rate": 96.7
      }
    },
    "overall_health": "healthy",
    "recommendations": [
      "Review failing authentication tests",
      "Optimize slow endpoint responses"
    ]
  }
}
```

### Detailed Report
```json
{
  "type": "detailed",
  "data": {
    "test_run": {
      "id": "test_123456",
      "summary": {...},
      "results": [...]
    },
    "failed_tests": [...],
    "performance_analysis": {
      "statistics": {
        "mean": 245.5,
        "median": 198.2,
        "p95": 567.8,
        "p99": 892.3
      },
      "slowest_endpoints": [...],
      "fastest_endpoints": [...]
    },
    "error_analysis": {
      "error_types": {...},
      "most_common_errors": [...],
      "error_distribution": {...}
    }
  }
}
```

### Coverage Report
```json
{
  "type": "coverage",
  "data": {
    "overall": {
      "total_tests": 150,
      "total_categories": 10,
      "total_endpoints": 85
    },
    "by_category": {
      "authentication": {
        "total_tests": 8,
        "endpoints": 4,
        "methods": {"GET": 2, "POST": 4, "DELETE": 2}
      },
      "assets": {
        "total_tests": 25,
        "endpoints": 6,
        "methods": {"GET": 8, "POST": 6, "PUT": 6, "DELETE": 5}
      }
    },
    "recommendations": [
      "Category 'maintenance' has low test coverage",
      "Missing PATCH method tests for assets"
    ]
  }
}
```

## Export Formats

### JSON Format
```json
{
  "content": "{...complete documentation...}",
  "content_type": "application/json",
  "file_extension": "json"
}
```

### OpenAPI Format
```json
{
  "content": "{...OpenAPI specification...}",
  "content_type": "application/json",
  "file_extension": "json"
}
```

### Markdown Format
```json
{
  "content": "# API Documentation\n\n## Information\n...",
  "content_type": "text/markdown",
  "file_extension": "md"
}
```

### YAML Format
```json
{
  "content": "# YAML Export\n{...documentation...}",
  "content_type": "text/yaml",
  "file_extension": "yaml"
}
```

## Performance Monitoring

### Response Time Metrics
- **Average Response Time**: Mean response time across all tests
- **Median Response Time**: 50th percentile response time
- **95th Percentile**: P95 response time for performance analysis
- **99th Percentile**: P99 response time for worst-case analysis

### Performance Analysis
```json
{
  "performance_analysis": {
    "statistics": {
      "mean": 245.5,
      "median": 198.2,
      "p95": 567.8,
      "p99": 892.3
    },
    "slowest_endpoints": [
      {
        "endpoint": "POST /api/analytics/dashboard",
        "response_time": 1250,
        "test_name": "Get dashboard analytics"
      }
    ],
    "fastest_endpoints": [
      {
        "endpoint": "GET /api/docs/health",
        "response_time": 45,
        "test_name": "Health check"
      }
    ]
  }
}
```

## Error Analysis

### Error Categories
- **Connection Errors**: Network connectivity issues
- **Status Code Mismatches**: Unexpected HTTP status codes
- **Field Validation Errors**: Missing or invalid response fields
- **Unknown Errors**: Unclassified error types

### Error Distribution
```json
{
  "error_analysis": {
    "error_types": {
      "connection_error": 2,
      "status_code_mismatch": 3,
      "field_validation_error": 1,
      "unknown_error": 0
    },
    "most_common_errors": {
      "Expected status 200, got 404": 2,
      "Connection timeout": 1
    },
    "error_distribution": {
      "connection_error": [...],
      "status_code_mismatch": [...]
    }
  }
}
```

## Health Monitoring

### System Health Check
```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "service": "API Documentation",
    "version": "1.0.0",
    "timestamp": "2024-01-01T12:00:00Z",
    "dependencies": {
      "documentation_service": "healthy",
      "testing_service": "healthy",
      "cache": "healthy"
    }
  }
}
```

### Health Metrics
- **Documentation Service**: Status of documentation generation service
- **Testing Service**: Status of automated testing service
- **Cache**: Status of caching system
- **Database**: Database connectivity and performance
- **API Endpoints**: Overall endpoint health status

## Configuration

### Cache Settings
```php
'cache_settings' => [
    'default_ttl' => 15, // minutes
    'real_time_ttl' => 1, // minute
    'heavy_computation_ttl' => 30, // minutes
]
```

### Rate Limits
```php
'rate_limits' => [
    'authentication' => [
        'login' => ['requests_per_minute' => 10],
        'refresh' => ['requests_per_minute' => 20],
    ],
    'general_api' => [
        'authenticated_users' => ['requests_per_minute' => 1000],
        'guest_users' => ['requests_per_minute' => 100],
    ],
    'resource_intensive' => [
        'analytics' => ['requests_per_minute' => 100],
        'reports' => ['requests_per_minute' => 50],
    ],
]
```

## Best Practices

### Documentation Best Practices
1. **Keep Documentation Updated**: Auto-generate from code to ensure accuracy
2. **Use Clear Examples**: Provide realistic, copy-pasteable examples
3. **Document Errors**: Include all possible error responses
4. **Version Control**: Maintain clear versioning and changelog
5. **Search Optimization**: Use descriptive names and descriptions

### Testing Best Practices
1. **Comprehensive Coverage**: Test all endpoints and error conditions
2. **Regular Execution**: Run tests regularly to catch regressions
3. **Performance Monitoring**: Track response times and performance trends
4. **Error Analysis**: Analyze and categorize test failures
5. **Continuous Improvement**: Use test results to guide API improvements

### Performance Best Practices
1. **Response Time Monitoring**: Track and optimize slow endpoints
2. **Cache Optimization**: Implement appropriate caching strategies
3. **Load Testing**: Test under realistic load conditions
4. **Resource Management**: Monitor memory and CPU usage
5. **Scalability Planning**: Plan for increased traffic and usage

## Troubleshooting

### Common Issues

#### Documentation Not Loading
- Check cache status: `GET /api/docs/health`
- Clear cache: `POST /api/analytics/clear-cache`
- Verify service dependencies

#### Tests Failing
- Check authentication tokens
- Verify API endpoints are accessible
- Review test configuration
- Check network connectivity

#### Performance Issues
- Monitor response times: `GET /api/docs/statistics`
- Check system resources
- Review database performance
- Analyze slow queries

#### Export Failures
- Verify export format is supported
- Check section parameters
- Ensure sufficient permissions
- Review file size limits

### Debug Information

#### Enable Debug Mode
```php
// In .env file
APP_DEBUG=true
```

#### Check Logs
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Test execution logs
grep "API test" storage/logs/laravel.log
```

#### Monitor Cache
```php
// Check cache status
Cache::get('api_docs_v1_json_1_1');

// Clear specific cache
Cache::forget('api_docs_v1_json_1_1');
```

## Integration

### CI/CD Integration
```yaml
# GitHub Actions example
- name: Run API Tests
  run: |
    curl -X POST "http://api.example.com/docs/tests/run" \
         -H "Authorization: Bearer ${{ secrets.API_TOKEN }}" \
         -H "Content-Type: application/json" \
         -d '{"suite": "all"}'
```

### Monitoring Integration
```javascript
// Prometheus metrics example
const apiMetrics = {
  responseTime: api_response_time_seconds,
  errorRate: api_error_rate_percentage,
  testCoverage: api_test_coverage_percentage,
};
```

### Documentation Integration
```html
<!-- Swagger UI integration -->
<script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@3.52.5/swagger-ui-bundle.js"></script>
<script>
  SwaggerUIBundle({
    url: '/api/docs?format=openapi',
    dom_id: '#swagger-ui',
    presets: [SwaggerUIBundle.presets.apis, SwaggerUIBundle.SwaggerUIStandalonePreset]
  });
</script>
```

## Security Considerations

### Authentication
- Use secure token-based authentication
- Implement proper token expiration
- Validate user permissions for testing endpoints
- Audit test execution logs

### Data Protection
- Sanitize sensitive data in documentation
- Use environment-specific test data
- Implement rate limiting for testing endpoints
- Monitor for abuse or excessive requests

### Access Control
- Restrict documentation modification access
- Implement role-based test execution permissions
- Audit documentation changes
- Secure export functionality

## Future Enhancements

### Planned Features
- **Real-time Collaboration**: Multiple users editing documentation
- **Advanced Search**: AI-powered search with natural language
- **Performance Benchmarking**: Industry comparison metrics
- **Automated Suggestions**: AI-driven API improvement recommendations
- **Integration Marketplace**: Third-party tool integrations

### Roadmap
1. **Q1 2024**: Enhanced search and filtering
2. **Q2 2024**: Performance benchmarking
3. **Q3 2024**: Real-time collaboration features
4. **Q4 2024**: AI-powered recommendations

## Support

### Getting Help
- **Documentation**: `/api/docs`
- **Health Check**: `/api/docs/health`
- **Error Reference**: `/api/docs/errors`
- **Examples**: `/api/docs/examples`

### Contact
- **API Support**: api-support@assetmanagement.com
- **Documentation Issues**: docs@assetmanagement.com
- **Testing Issues**: testing@assetmanagement.com

### Community
- **GitHub Repository**: https://github.com/assetmanagement/api
- **Discord Community**: https://discord.gg/assetmanagement
- **Stack Overflow**: Tag with `assetmanagement-api`

---

## Quick Start

1. **Access Documentation**: Visit `/api/docs` for complete API documentation
2. **Run Tests**: Execute `POST /api/docs/tests/run` with your preferred test suite
3. **Monitor Health**: Check `/api/docs/health` for system status
4. **Export Documentation**: Use `POST /api/docs/export` to download documentation
5. **Review Reports**: Generate reports with `POST /api/docs/tests/report`

The API Documentation and Testing Suite provides everything you need to understand, test, and monitor the Asset Management System API effectively.
