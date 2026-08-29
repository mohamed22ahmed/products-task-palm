# Product Scraping System with Proxy Management

This system implements product scraping from eCommerce sites (Amazon, Jumia, etc.) with a Golang microservice for proxy management and user-agent rotation.

## Architecture

### Components

1. **Laravel Backend** (`product-backend/`)
   - PHP/Laravel API server
   - Product scraping service
   - Database storage
   - API endpoints

2. **Golang Proxy Service** (`proxy-service/`)
   - Proxy rotation management
   - User-agent rotation
   - Health monitoring
   - RESTful API

## Setup Instructions

### 1. Backend (Laravel – PHP)

#### Prerequisites
- PHP 8.3+
- MySQL database
- Composer
- Laravel 13.x

#### Installation

```bash
cd product-backend
composer install
cp .env.example .env
php artisan key:generate
```

#### Database Configuration

Update `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=products
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### Run Migrations

```bash
php artisan migrate
```

#### Add Proxy Service URL

Add to `.env` file:
```env
PROXY_SERVICE_URL=http://localhost:8080
```

#### Start Laravel Server

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

### 2. Golang Proxy Service

#### Prerequisites
- Go 1.21+

#### Installation

```bash
cd proxy-service
go mod init proxy-service
go build -o proxy-service main.go
```

Or use the provided build script:
```bash
chmod +x build.sh
./build.sh
```

#### Configuration

Edit `main.go` to add real proxies:

```go
proxies := []Proxy{
    {Address: "direct", Protocol: "http", LastUsed: time.Now(), FailCount: 0, IsHealthy: true},
    {Address: "proxy1.example.com:8080", Protocol: "http", LastUsed: time.Now(), FailCount: 0, IsHealthy: true},
    {Address: "proxy2.example.com:3128", Protocol: "http", LastUsed: time.Now(), FailCount: 0, IsHealthy: true},
}
```

#### Start Proxy Service

```bash
./proxy-service
```

The service will be available at `http://localhost:8080`

#### Alternative: Mock PHP Service

If Go is not available, use the provided PHP mock service:

```bash
cd proxy-service
php -S localhost:8080 mock-proxy.php
```

## API Endpoints

### Laravel API

#### Get All Products
```http
GET /api/v1/products
```

**Response:**
```json
[
  {
    "id": "uuid",
    "title": "Product Name",
    "price": 99.99,
    "image_url": "https://example.com/image.jpg",
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-01T00:00:00Z"
  }
]
```

#### Scrape Product
```http
POST /api/v1/products/scrape
Content-Type: application/json

{
  "url": "https://www.amazon.com/dp/PRODUCT_ID"
}
```

**Response:**
```json
{
  "id": "uuid",
  "title": "Product Name",
  "price": 99.99,
  "image_url": "https://example.com/image.jpg",
  "created_at": "2024-01-01T00:00:00Z",
  "updated_at": "2024-01-01T00:00:00Z"
}
```

### Golang Proxy Service

#### Get Proxy and User Agent
```http
POST /proxy
Content-Type: application/json

{
  "target_url": "https://example.com"
}
```

**Response:**
```json
{
  "proxy": "proxy.example.com:8080",
  "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36..."
}
```

#### Health Check
```http
GET /health
```

**Response:**
```json
{
  "total_proxies": 3,
  "healthy_proxies": 2,
  "proxies": [
    {
      "address": "proxy1.example.com:8080",
      "protocol": "http",
      "last_used": "2024-01-01T00:00:00Z",
      "fail_count": 0,
      "is_healthy": true
    }
  ]
}
```

#### Report Proxy Result
```http
POST /report
Content-Type: application/json

{
  "proxy": "proxy.example.com:8080",
  "success": true
}
```

## Features

### Laravel Backend
- ✅ Product model with UUID primary key
- ✅ Scraping service using Guzzle HTTP client
- ✅ Integration with Golang proxy service
- ✅ User-agent rotation
- ✅ CAPTCHA detection
- ✅ Error handling and logging
- ✅ MySQL database storage
- ✅ RESTful API endpoints

### Golang Proxy Service
- ✅ Dynamic proxy rotation
- ✅ Health monitoring
- ✅ Automatic failover
- ✅ User-agent rotation
- ✅ Success/failure tracking
- ✅ RESTful API
- ✅ Thread-safe operations

## Testing

### Test Proxy Service Integration

```bash
# Test with mock proxy service
curl -X POST http://localhost:8000/api/v1/products/scrape \
  -H "Content-Type: application/json" \
  -d '{"url":"https://test-proxy.example.com"}'
```

### Test Real Scraping

```bash
# Test with real URL (requires actual proxy configuration)
curl -X POST http://localhost:8000/api/v1/products/scrape \
  -H "Content-Type: application/json" \
  -d '{"url":"https://www.amazon.com/dp/PRODUCT_ID"}'
```

## Database Schema

### Products Table

```sql
CREATE TABLE products (
    id CHAR(36) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    price DOUBLE NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Implementation Details

### No Overrides Policy

All existing functionality was preserved:
- Original `getAllProducts()` method unchanged
- Original API endpoint `/api/v1/products` unchanged
- Database schema enhanced (UUID primary key added)
- Additional methods added for proxy integration

### Proxy Integration Flow

1. Laravel requests proxy from Golang service
2. Golang service returns proxy + user agent
3. Laravel uses proxy for scraping request
4. Laravel reports success/failure back to Golang service
5. Golang service updates proxy health status

### Fallback Mechanism

If Golang proxy service is unavailable:
- Laravel falls back to direct connection
- Uses random user agent from local list
- Logs the failure for monitoring

## Configuration Files

### Laravel .env
```env
PROXY_SERVICE_URL=http://localhost:8080
```

### Golang main.go
- Edit proxy list in `NewProxyService()` function
- Modify user agents in `userAgents` array
- Adjust port in `main()` function

## Troubleshooting

### Common Issues

1. **Proxy service not responding**
   - Check if Golang service is running
   - Verify PROXY_SERVICE_URL in .env
   - Check firewall settings

2. **Scraping failures**
   - Check Laravel logs: `storage/logs/laravel.log`
   - Verify proxy health: `GET http://localhost:8080/health`
   - Test proxy service directly

3. **Database connection errors**
   - Verify MySQL credentials in .env
   - Ensure database exists
   - Run migrations: `php artisan migrate`

## Production Considerations

1. **Security**
   - Use environment variables for sensitive data
   - Implement authentication for proxy service
   - Use HTTPS in production

2. **Performance**
   - Implement caching for proxy responses
   - Add rate limiting
   - Consider connection pooling

3. **Monitoring**
   - Set up logging aggregation
   - Monitor proxy health
   - Track scraping success rates

4. **Proxies**
   - Use reputable proxy providers
   - Implement proxy rotation strategy
   - Monitor proxy quality and performance

## License

This project is for educational purposes. Ensure compliance with terms of service of target websites when implementing scraping functionality.