# Docker Setup & Deployment

> **Master Architect Reference**: Complete Docker configuration and deployment guide.

## Overview

The Accounting System uses Docker for containerized deployment with the following services:
- **PHP Application** (PHP-FPM 8.2)
- **Nginx** (Web Server)
- **MySQL 8.0** (Database)
- **Redis** (Cache & Sessions)

---

## Directory Structure

```
/
├── docker/
│   ├── php/
│   │   ├── Dockerfile
│   │   └── php.ini
│   ├── nginx/
│   │   ├── Dockerfile
│   │   └── default.conf
│   └── mysql/
│       └── init.sql
├── docker-compose.yml
├── docker-compose.dev.yml
├── docker-compose.prod.yml
└── .env.docker
```

---

## Docker Compose Configuration

### Base Configuration (docker-compose.yml)

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: accounting-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
      - ./docker/php/php.ini:/usr/local/etc/php/conf.d/custom.ini
    networks:
      - accounting-network
    depends_on:
      - mysql
      - redis

  nginx:
    build:
      context: .
      dockerfile: docker/nginx/Dockerfile
    container_name: accounting-nginx
    restart: unless-stopped
    ports:
      - "${APP_PORT:-8080}:80"
    volumes:
      - ./:/var/www
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    networks:
      - accounting-network
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    container_name: accounting-mysql
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD:-rootpassword}
      MYSQL_DATABASE: ${DB_DATABASE:-accounting}
      MYSQL_USER: ${DB_USERNAME:-accounting_user}
      MYSQL_PASSWORD: ${DB_PASSWORD:-secret}
    ports:
      - "${DB_PORT:-3306}:3306"
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/init.sql:/docker-entrypoint-initdb.d/init.sql
    networks:
      - accounting-network
    command: --default-authentication-plugin=mysql_native_password

  redis:
    image: redis:7-alpine
    container_name: accounting-redis
    restart: unless-stopped
    ports:
      - "${REDIS_PORT:-6379}:6379"
    volumes:
      - redis_data:/data
    networks:
      - accounting-network

networks:
  accounting-network:
    driver: bridge

volumes:
  mysql_data:
  redis_data:
```

### Development Override (docker-compose.dev.yml)

```yaml
version: '3.8'

services:
  app:
    build:
      args:
        - APP_ENV=development
    environment:
      - APP_ENV=development
      - APP_DEBUG=true
      - XDEBUG_MODE=debug,coverage
    volumes:
      - ./:/var/www
      - ./docker/php/xdebug.ini:/usr/local/etc/php/conf.d/xdebug.ini

  nginx:
    ports:
      - "8080:80"

  mysql:
    ports:
      - "3306:3306"

  # Development tools
  phpmyadmin:
    image: phpmyadmin:latest
    container_name: accounting-phpmyadmin
    environment:
      PMA_HOST: mysql
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD:-rootpassword}
    ports:
      - "8081:80"
    networks:
      - accounting-network
    depends_on:
      - mysql

  mailhog:
    image: mailhog/mailhog
    container_name: accounting-mailhog
    ports:
      - "1025:1025"
      - "8025:8025"
    networks:
      - accounting-network
```

### Production Override (docker-compose.prod.yml)

```yaml
version: '3.8'

services:
  app:
    build:
      args:
        - APP_ENV=production
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
    restart: always
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 1G
        reservations:
          cpus: '0.5'
          memory: 256M

  nginx:
    restart: always
    deploy:
      resources:
        limits:
          cpus: '1'
          memory: 256M

  mysql:
    restart: always
    ports: []  # Don't expose in production
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 2G

  redis:
    restart: always
    ports: []  # Don't expose in production
```

---

## Dockerfiles

### PHP Dockerfile (docker/php/Dockerfile)

```dockerfile
FROM php:8.2-fpm-alpine

ARG APP_ENV=production

# Install system dependencies
RUN apk add --no-cache \
    bash \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    icu-dev \
    mysql-client

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    intl \
    opcache

# Install Redis extension
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Development: Install Xdebug
RUN if [ "$APP_ENV" = "development" ]; then \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps; \
fi

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application
COPY --chown=www-data:www-data . /var/www

# Production: Install dependencies without dev
RUN if [ "$APP_ENV" = "production" ]; then \
    composer install --no-dev --optimize-autoloader --no-interaction; \
else \
    composer install --no-interaction; \
fi

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
```

### Nginx Dockerfile (docker/nginx/Dockerfile)

```dockerfile
FROM nginx:alpine

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

WORKDIR /var/www

EXPOSE 80
```

### Nginx Configuration (docker/nginx/default.conf)

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/public;
    index index.php index.html;

    charset utf-8;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml application/json;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    # PHP-FPM configuration
    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;

        # Timeouts
        fastcgi_connect_timeout 60s;
        fastcgi_send_timeout 60s;
        fastcgi_read_timeout 60s;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Deny access to sensitive directories
    location ~ ^/(config|vendor|tests)/ {
        deny all;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # Health check endpoint
    location /health {
        access_log off;
        return 200 'OK';
        add_header Content-Type text/plain;
    }

    error_page 404 /index.php;
}
```

### PHP Configuration (docker/php/php.ini)

```ini
[PHP]
; Error handling
display_errors = Off
display_startup_errors = Off
error_reporting = E_ALL
log_errors = On
error_log = /var/log/php/error.log

; Memory and execution
memory_limit = 256M
max_execution_time = 60
max_input_time = 60
post_max_size = 50M
upload_max_filesize = 50M

; Date/Time
date.timezone = Asia/Manila

; Session
session.save_handler = redis
session.save_path = "tcp://redis:6379"
session.gc_maxlifetime = 3600
session.cookie_httponly = 1
session.cookie_secure = 0
session.use_strict_mode = 1

; OPcache
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2
opcache.validate_timestamps = 0
opcache.save_comments = 1

[MySQLi]
mysqli.default_host = mysql
mysqli.default_port = 3306

[Redis]
redis.session.locking_enabled = 1
redis.session.lock_retries = 10
redis.session.lock_wait_time = 10000
```

### Xdebug Configuration (docker/php/xdebug.ini)

```ini
[xdebug]
xdebug.mode = debug,coverage
xdebug.client_host = host.docker.internal
xdebug.client_port = 9003
xdebug.start_with_request = yes
xdebug.idekey = PHPSTORM
xdebug.log_level = 0
```

---

## Database Initialization (docker/mysql/init.sql)

```sql
-- Create databases
CREATE DATABASE IF NOT EXISTS accounting CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS accounting_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Grant privileges
GRANT ALL PRIVILEGES ON accounting.* TO 'accounting_user'@'%';
GRANT ALL PRIVILEGES ON accounting_test.* TO 'accounting_user'@'%';
FLUSH PRIVILEGES;

-- Use main database
USE accounting;

-- Initial schema will be created by migrations
```

---

## Environment Configuration (.env.docker)

```bash
# Application
APP_NAME="Accounting System"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8080
APP_PORT=8080

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=accounting
DB_USERNAME=accounting_user
DB_PASSWORD=secret
DB_ROOT_PASSWORD=rootpassword

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Cache
CACHE_DRIVER=redis

# JWT
JWT_SECRET=your-secret-key-here
JWT_TTL=60

# Mail (Development)
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
```

---

## Usage Commands

### Development

```bash
# Start all services
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d

# View logs
docker-compose logs -f

# View specific service logs
docker-compose logs -f app

# Run composer commands
docker-compose exec app composer install
docker-compose exec app composer test

# Run PHPUnit tests
docker-compose exec app vendor/bin/phpunit

# Run PHPStan
docker-compose exec app vendor/bin/phpstan analyse

# Access MySQL CLI
docker-compose exec mysql mysql -u accounting_user -p accounting

# Stop services
docker-compose down

# Stop and remove volumes (clean slate)
docker-compose down -v
```

### Production

```bash
# Build images
docker-compose -f docker-compose.yml -f docker-compose.prod.yml build

# Start services
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# Scale application
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --scale app=3

# View resource usage
docker stats

# Backup database
docker-compose exec mysql mysqldump -u root -p accounting > backup.sql

# Restore database
docker-compose exec -T mysql mysql -u root -p accounting < backup.sql
```

### Maintenance

```bash
# Update images
docker-compose pull

# Rebuild without cache
docker-compose build --no-cache

# Remove unused images
docker image prune -a

# Remove unused volumes
docker volume prune

# View container shell
docker-compose exec app sh

# Check container health
docker-compose ps
```

---

## Health Checks

### Application Health Check

```php
// public/health.php
<?php
declare(strict_types=1);

header('Content-Type: application/json');

$checks = [];
$healthy = true;

// Database check
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']}",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD']
    );
    $checks['database'] = ['status' => 'healthy'];
} catch (Exception $e) {
    $checks['database'] = ['status' => 'unhealthy', 'error' => $e->getMessage()];
    $healthy = false;
}

// Redis check
try {
    $redis = new Redis();
    $redis->connect($_ENV['REDIS_HOST'], (int)$_ENV['REDIS_PORT']);
    $redis->ping();
    $checks['redis'] = ['status' => 'healthy'];
} catch (Exception $e) {
    $checks['redis'] = ['status' => 'unhealthy', 'error' => $e->getMessage()];
    $healthy = false;
}

http_response_code($healthy ? 200 : 503);
echo json_encode([
    'status' => $healthy ? 'healthy' : 'unhealthy',
    'checks' => $checks,
    'timestamp' => date('c')
], JSON_PRETTY_PRINT);
```

### Docker Health Check Configuration

Add to docker-compose.yml services:

```yaml
services:
  app:
    healthcheck:
      test: ["CMD", "php", "-r", "exit(0);"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s

  nginx:
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/health"]
      interval: 30s
      timeout: 10s
      retries: 3

  mysql:
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 30s
      timeout: 10s
      retries: 3

  redis:
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 30s
      timeout: 10s
      retries: 3
```

---

## Deployment Checklist

### Pre-Deployment
- [ ] Environment variables configured
- [ ] Database migrations ready
- [ ] SSL certificates obtained
- [ ] Backup strategy defined
- [ ] Monitoring configured

### Deployment
- [ ] Build production images
- [ ] Run database migrations
- [ ] Clear application cache
- [ ] Start services
- [ ] Verify health checks

### Post-Deployment
- [ ] Monitor logs for errors
- [ ] Verify all endpoints responding
- [ ] Run smoke tests
- [ ] Update documentation
- [ ] Notify stakeholders
