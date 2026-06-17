# Weather API - Senior PHP Developer Exam

A Laravel-based weather API that integrates with OpenWeatherMap to provide real-time and cached weather data.

## Features

- ✅ **Real-time Weather Data**: Fetch current weather information from OpenWeatherMap API
- ✅ **Intelligent Caching**: Cache weather data for 10 minutes to reduce API calls
- ✅ **Rate Limiting**: Built-in rate limiting (60 requests/minute per IP) to prevent abuse
- ✅ **Robust Error Handling**: Comprehensive error handling for API failures and invalid requests
- ✅ **JSON-First API**: All responses (including errors) return JSON format
- ✅ **Comprehensive Testing**: Full test coverage with Feature and Unit tests (16 tests, 102 assertions)
- ✅ **Modern PHP 8+**: Following Laravel best practices and modern PHP standards

## API Endpoints

### 1. GET /weather/{city}
Fetches real-time weather data from OpenWeatherMap API.

**Example Request:**
```bash
curl http://localhost:8000/weather/London
```

**Example Response:**
```json
{
    "city": "London",
    "temperature": 15.5,
    "weather_description": "clear sky",
    "timestamp": "2026-06-18T10:30:00+00:00",
    "source": "external"
}
```

### 2. GET /weather/{city}/cached
Returns weather data with caching. First call fetches from API and caches for 10 minutes. Subsequent calls within 10 minutes return cached data.

**Example Request:**
```bash
curl http://localhost:8000/weather/London/cached
```

**Example Response (from cache):**
```json
{
    "city": "London",
    "temperature": 15.5,
    "weather_description": "clear sky",
    "timestamp": "2026-06-18T10:30:00+00:00",
    "source": "cache"
}
```

**Error Response (404):**
```json
{
    "error": "City 'InvalidCity' not found"
}
```

**Route Not Found (404):**
```json
{
    "error": "Route not found",
    "message": "The requested endpoint does not exist"
}
```

**Rate Limit Exceeded (429):**
```json
{
    "message": "Too Many Attempts.",
    "exception": "Illuminate\\Http\\Exceptions\\ThrottleRequestsException"
}
```

> **Note**: All responses, including errors and 404 pages, return JSON format. API is rate-limited to **60 requests per minute per IP address**.

## Installation & Setup

### Prerequisites
- PHP 8.1 or higher
- Composer

### Step 1: Clone the Repository
```bash
git clone <repository-url>
cd shiperp-exam
```

### Step 2: Install Dependencies
```bash
composer install
```

### Step 3: Environment Configuration
```bash
# Copy the example environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Step 4: Configure OpenWeatherMap API Key

1. **Register for a free API key:**
   - Visit [https://openweathermap.org/api](https://openweathermap.org/api)
   - Click "Get API Key" or "Sign Up"
   - Complete the registration form
   - Verify your email address

2. **Get your API key:**
   - Log in to [https://home.openweathermap.org/](https://home.openweathermap.org/)
   - Go to "API keys" tab
   - Copy your API key (or generate a new one)

3. **⚠️ IMPORTANT: Wait for activation!**
   - New API keys take **1-2 hours to activate**
   - You'll get `401 Invalid API key` errors until activation completes
   - This is normal - just wait and try again later

4. **Add your API key to `.env` file:**

```env
OPENWEATHERMAP_API_KEY=your_actual_api_key_here
OPENWEATHERMAP_BASE_URL=https://api.openweathermap.org/data/2.5
```

> **Security Note:** Never commit your actual API key to Git! The `.env` file is in `.gitignore` for this reason.

### Step 5: Run the Application
```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

> **Note**: No database setup required! The app uses file-based caching by default. If you prefer database caching, change `CACHE_STORE=file` to `CACHE_STORE=database` in `.env` and run `php artisan migrate`.

## Testing Your Setup

### Verify Your API Key

Before running the application, test if your OpenWeatherMap API key is active:

```bash
# Replace YOUR_API_KEY with your actual key
curl "https://api.openweathermap.org/data/2.5/weather?q=London&appid=YOUR_API_KEY"
```

**Success Response (200):**
```json
{"coord":{"lon":-0.1257,"lat":51.5085},"weather":[...],"main":{"temp":280.32,...}
```

**API Key Not Active (401):**
```json
{"cod":401, "message": "Invalid API key. Please see https://openweathermap.org/faq#error401 for more info."}
```
→ Wait 1-2 hours after registration and try again.

### Test the API Endpoints

Once your key is active and the app is running:

```bash
# Test real-time weather
curl http://localhost:8000/weather/London

# Test cached weather
curl http://localhost:8000/weather/London/cached
```

## Troubleshooting

| Error | Cause | Solution |
|-------|-------|----------|
| `401 Invalid API key` | API key not activated yet | Wait 1-2 hours after registration |
| `401 Invalid API key` | Wrong API key in `.env` | Double-check you copied the correct key |
| `404 City not found` | Invalid city name | Use a valid city name (e.g., "London", "Paris") |
| `429 Too Many Attempts` | Rate limit exceeded | Wait 1 minute before making more requests (limit: 60/min) |
| `503 Unable to connect` | Network/API issues | Check internet connection, try again later |
| `SSL certificate error` | Windows PHP CA bundle issue | Already fixed in code with `verify => false` for development |

> **Note for Windows Users**: The code includes `verify => false` for SSL requests to handle Windows certificate issues. For production deployment on Linux, this should be removed or configured with proper CA certificates.

## Running Tests

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Suites
```bash
# Run Feature tests only
php artisan test --testsuite=Feature

# Run Unit tests only
php artisan test --testsuite=Unit

# Run with coverage (requires Xdebug)
php artisan test --coverage
```

### Test Coverage
The project includes comprehensive tests:
- **Feature Tests** (`tests/Feature/WeatherApiTest.php`): 11 tests covering API endpoints, caching behavior, error handling, rate limiting, and JSON 404 responses
- **Unit Tests** (`tests/Unit/WeatherServiceTest.php`): 5 tests for the WeatherService in isolation
- **Total**: 16 tests with 102 assertions - all passing ✅

## High-Level Architecture

### Design Approach

This application follows **Laravel best practices** with a clear separation of concerns and applies key **SOLID principles**:

#### **SOLID Principles Applied:**

1. ✅ **Single Responsibility Principle (SRP)**
   - `WeatherService`: Solely responsible for external API communication
   - `WeatherController`: Solely responsible for HTTP request/response handling and caching
   - Each class has one clear purpose and one reason to change

2. ✅ **Dependency Inversion Principle (DIP)** - Partial
   - Constructor injection used for dependencies
   - Controller depends on service abstraction (though not via interface)

> **Note**: For production, consider implementing interfaces (`WeatherServiceInterface`) to fully apply Open/Closed Principle and complete Dependency Inversion.

#### **Architecture Patterns:**

#### 1. **Service Layer Pattern**
- **`WeatherService`**: Encapsulates all OpenWeatherMap API communication
- Benefits: Single responsibility, easy to test, reusable across controllers
- Handles API calls, error handling, and data transformation

#### 2. **Controller Responsibility**
- **`WeatherController`**: Handles HTTP requests/responses and caching logic
- Delegates external API communication to `WeatherService`
- Manages cache operations using Laravel's Cache facade
- Returns consistent JSON responses with appropriate HTTP status codes

#### 3. **Configuration Management**
- API credentials stored in `config/services.php`
- Environment variables in `.env` for security
- No hardcoded credentials in codebase

#### 4. **Error Handling Strategy**
- Try-catch blocks for graceful error handling
- Specific error codes for different failure scenarios:
  - `400`: Invalid input (empty city name)
  - `404`: City not found
  - `500`: General API errors
  - `503`: Connection/timeout errors
- Logging of errors for debugging

#### 5. **Caching Strategy**
- Cache key: `weather_{city_lowercase}`
- TTL: 10 minutes
- Cache driver: File-based (no database required, can use Redis for production)
- Cache-aside pattern: Check cache first, fetch from API if miss

#### 6. **Testing Strategy**
- **HTTP Facade Mocking**: No actual API calls during tests
- **Cache Flushing**: Clean state for each test
- **Time Travel**: Testing cache expiration
- **Edge Cases**: Invalid cities, connection errors, empty inputs

### Project Structure
```
app/
├── Http/Controllers/
│   └── WeatherController.php    # Handles API requests
├── Services/
│   └── WeatherService.php       # External API integration
config/
└── services.php                 # Third-party service config
routes/
└── web.php                      # Route definitions
tests/
├── Feature/
│   └── WeatherApiTest.php       # End-to-end tests
└── Unit/
    └── WeatherServiceTest.php   # Service layer tests
```

### Why This Approach?

1. **Maintainability**: Clear separation makes it easy to modify or replace components
2. **Testability**: Service layer can be tested independently of HTTP layer
3. **Scalability**: Easy to add more weather providers or caching strategies
4. **Error Resilience**: Comprehensive error handling prevents application crashes
5. **Performance**: Caching reduces API calls and improves response time

## Technical Decisions

- **Laravel HTTP Client**: Modern, elegant API with built-in timeout and error handling
- **File-based Cache**: Simple, zero-config caching with no database dependency
- **Rate Limiting**: Laravel throttle middleware (60 requests/minute per IP) to prevent API abuse
- **Constructor Injection**: Proper dependency injection for testability
- **Response Formatting**: Consistent JSON structure with clear source indicator
- **Validation**: Input sanitization to prevent empty/invalid city names
- **SSL Verification Disabled**: For Windows development compatibility (should be enabled in production)

## Production Considerations

For production deployment, consider:

1. **Enable SSL verification** - Remove `'verify' => false` from HTTP client and configure proper CA certificates
2. **Use Redis for caching** instead of file-based cache for better performance and scalability
3. **Adjust rate limiting** - Current limit is 60/min per IP; consider user-based or API key-based throttling for production
4. **Queue failed API calls** for retry logic with exponential backoff
5. **API key rotation** strategy and secure key management
6. **Monitoring and alerting** for API failures and performance metrics
7. **CORS configuration** if needed for frontend integration
8. **Environment-specific configuration** for different deployment stages

## License

This project is created for examination purposes.
