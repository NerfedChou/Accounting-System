# Docker Setup Guide

This document explains how to use Docker for both development and production environments.

## Prerequisites

- Docker 20.10+
- Docker Compose 2.0+
- Make (optional, for Makefile commands)

## Architecture

### Development Environment
- **Uses volumes** to mount source code
- **Hot reload** - changes reflect immediately
- **Xdebug** enabled for debugging
- **PHPMyAdmin** included for database management
- **Relaxed security** settings for development

### Production Environment
- **Baked image** - code is built into the image
- **Optimized PHP** with OPcache enabled
- **No dev tools** - smaller image size
- **Security hardened** with proper headers
- **Redis sessions** for horizontal scaling

## Quick Start

### Development

```bash
# Using docker.sh script
./docker.sh up dev

# Or using Makefile
make dev-up

# Or using docker-compose directly
docker-compose -f docker-compose.dev.yml up -d
```

Access:
- **Application**: http://localhost:8080
- **PHPMyAdmin**: http://localhost:8081
- **MySQL**: localhost:3306

### Production

```bash
# Build production image
./docker.sh build prod
# or
make prod-build

# Start production containers
./docker.sh up prod
# or
make prod-up
```

## Docker Management Script

The `docker.sh` script provides easy commands for managing Docker environments.

```bash
./docker.sh [command] [environment]
```

### Available Commands

| Command | Description | Example |
|---------|-------------|---------|
| `up` | Start containers | `./docker.sh up dev` |
| `down` | Stop containers | `./docker.sh down dev` |
| `restart` | Restart containers | `./docker.sh restart dev` |
| `build` | Build/rebuild images | `./docker.sh build prod` |
| `logs` | Show container logs | `./docker.sh logs dev` |
| `shell` | Access app container | `./docker.sh shell dev` |
| `mysql` | Access MySQL shell | `./docker.sh mysql dev` |
| `composer` | Run composer | `./docker.sh composer 'install' dev` |
| `test` | Run tests | `./docker.sh test dev` |
| `migrate` | Run migrations | `./docker.sh migrate dev` |
| `seed` | Seed database | `./docker.sh seed dev` |
| `fresh` | Fresh DB setup | `./docker.sh fresh dev` |
| `clean` | Remove all data | `./docker.sh clean dev` |

## Makefile Commands

Alternatively, use Make commands:

```bash
# Development
make dev-up           # Start dev environment
make shell            # Access container
make logs             # View logs
make migrate          # Run migrations
make test             # Run tests
make fresh            # Fresh database

# Production
make prod-build       # Build production image
make prod-up          # Start production
make prod-down        # Stop production

# General
make help             # Show all commands
```

## Container Structure

### Development (docker-compose.dev.yml)

Services:
- **app**: PHP 8.2-FPM with Xdebug
- **nginx**: Web server
- **mysql**: MySQL 8.0 database
- **redis**: Cache and sessions
- **phpmyadmin**: Database GUI

Volumes:
- `./` → `/var/www/html` (source code)
- `mysql-data` → MySQL data persistence
- `redis-data` → Redis data persistence

### Production (docker-compose.prod.yml)

Services:
- **app**: PHP 8.2-FPM (optimized, baked)
- **nginx**: Web server with security headers
- **mysql**: MySQL 8.0 database
- **redis**: Cache and sessions

No source code volumes - everything is baked into the image.

## Environment Variables

### Development (.env)

```bash
cp .env.example .env
```

Default development settings are already configured.

### Production (.env.production)

```bash
cp .env.production .env
```

**⚠️ IMPORTANT**: Change these before deployment:
- `DB_PASSWORD`
- `DB_ROOT_PASSWORD`
- `JWT_SECRET`
- `REDIS_PASSWORD`

## Detailed Workflows

### Initial Setup (First Time)

```bash
# 1. Clone repository
git clone <repository-url>
cd Accounting-System

# 2. Copy environment file
cp .env.example .env

# 3. Start Docker containers
./docker.sh up dev

# 4. Install dependencies
./docker.sh composer 'install' dev

# 5. Run migrations
./docker.sh migrate dev

# 6. Seed database
./docker.sh seed dev

# 7. Access application
open http://localhost:8080
```

### Daily Development Workflow

```bash
# Start containers
./docker.sh up dev

# View logs
./docker.sh logs dev

# Run tests
./docker.sh test dev

# Access container shell
./docker.sh shell dev

# Stop when done
./docker.sh down dev
```

### Running Database Operations

```bash
# Create new migration
./docker.sh shell dev
> php migrate create create_users_table

# Run migrations
./docker.sh migrate dev

# Rollback migration
docker-compose -f docker-compose.dev.yml exec app php migrate down

# Fresh database (destructive)
./docker.sh fresh dev
```

### Running Tests

```bash
# All tests
./docker.sh test dev

# With coverage
make test-coverage ENV=dev

# Specific test
docker-compose -f docker-compose.dev.yml exec app \
  vendor/bin/phpunit tests/Unit/Domain/UserTest.php
```

### Accessing Services

```bash
# PHP container shell
./docker.sh shell dev

# MySQL shell
./docker.sh mysql dev

# Execute one-off command
docker-compose -f docker-compose.dev.yml exec app php --version

# Run composer
./docker.sh composer 'require package/name' dev
```

## Production Deployment

### Building Production Image

```bash
# Build the image
docker-compose -f docker-compose.prod.yml build

# Or with cache disabled
docker-compose -f docker-compose.prod.yml build --no-cache

# Tag for registry (optional)
docker tag accounting-system:latest your-registry/accounting-system:v1.0.0
```

### Deploying to Production

```bash
# 1. Build production image
./docker.sh build prod

# 2. Test image locally
./docker.sh up prod

# 3. Push to registry (if using)
docker push your-registry/accounting-system:v1.0.0

# 4. Deploy on production server
ssh production-server
docker-compose -f docker-compose.prod.yml pull
docker-compose -f docker-compose.prod.yml up -d

# 5. Run migrations
docker-compose -f docker-compose.prod.yml exec app php migrate up
```

### Zero-Downtime Deployment

```bash
# 1. Build new version
docker-compose -f docker-compose.prod.yml build

# 2. Scale up new version
docker-compose -f docker-compose.prod.yml up -d --scale app=2 --no-recreate

# 3. Health check new containers
# (implement health check endpoint first)

# 4. Remove old containers
docker-compose -f docker-compose.prod.yml up -d --scale app=1
```

## Debugging

### View Container Logs

```bash
# All containers
./docker.sh logs dev

# Specific container
docker-compose -f docker-compose.dev.yml logs -f app
docker-compose -f docker-compose.dev.yml logs -f nginx
docker-compose -f docker-compose.dev.yml logs -f mysql
```

### Xdebug (Development Only)

Xdebug is pre-configured in development mode.

**PHPStorm Setup**:
1. Go to Settings → PHP → Servers
2. Add server:
   - Name: `docker`
   - Host: `localhost`
   - Port: `8080`
   - Debugger: Xdebug
   - Use path mappings: Yes
   - Map `/path/to/project` → `/var/www/html`

3. Set breakpoint and start listening for debug connections

### Container Inspection

```bash
# List running containers
docker-compose -f docker-compose.dev.yml ps

# Inspect container
docker inspect accounting-app-dev

# View container stats
docker stats accounting-app-dev

# Check container health
docker-compose -f docker-compose.dev.yml exec app php --version
```

## Troubleshooting

### Port Already in Use

```bash
# Find process using port 8080
lsof -i :8080
# or
netstat -an | grep 8080

# Kill process or change port in docker-compose.yml
```

### Permission Issues

```bash
# Fix permissions
sudo chown -R $USER:$USER .

# Or run as root
docker-compose -f docker-compose.dev.yml exec -u root app sh
```

### Database Connection Failed

```bash
# Check if MySQL container is running
docker-compose -f docker-compose.dev.yml ps mysql

# Check MySQL logs
docker-compose -f docker-compose.dev.yml logs mysql

# Verify environment variables
docker-compose -f docker-compose.dev.yml exec app env | grep DB_
```

### Cannot Access Application

```bash
# Check nginx logs
docker-compose -f docker-compose.dev.yml logs nginx

# Check PHP-FPM logs
docker-compose -f docker-compose.dev.yml logs app

# Verify nginx configuration
docker-compose -f docker-compose.dev.yml exec nginx nginx -t
```

### Clear Everything and Start Fresh

```bash
# Stop and remove containers
./docker.sh down dev

# Remove volumes (⚠️ data will be lost)
./docker.sh clean dev

# Rebuild images
./docker.sh build dev

# Start fresh
./docker.sh up dev
./docker.sh fresh dev
```

## Performance Optimization

### Production Optimizations

The production setup includes:
- **OPcache** enabled for PHP
- **Gzip compression** for responses
- **Static file caching** (1 year)
- **Rate limiting** (30 req/min per IP)
- **Redis sessions** for horizontal scaling
- **Minimal base image** (Alpine Linux)

### Database Optimizations

```sql
-- Add to MySQL configuration if needed
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
```

## Security Considerations

### Development

- ✓ Xdebug enabled
- ✓ Error display ON
- ✓ Open CORS policy
- ✓ PHPMyAdmin exposed
- ⚠️ **DO NOT use in production**

### Production

- ✓ Error display OFF
- ✓ OPcache enabled
- ✓ Security headers
- ✓ Rate limiting
- ✓ No debug tools
- ✓ Minimal dependencies
- ⚠️ **Change default passwords**
- ⚠️ **Use SSL/TLS**
- ⚠️ **Implement firewall rules**

## Backup and Restore

### Backup Database

```bash
# Export database
docker-compose -f docker-compose.prod.yml exec mysql \
  mysqldump -u accounting_user -p accounting_system > backup.sql

# With timestamp
docker-compose -f docker-compose.prod.yml exec mysql \
  mysqldump -u accounting_user -p accounting_system \
  > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restore Database

```bash
# Import database
docker-compose -f docker-compose.prod.yml exec -T mysql \
  mysql -u accounting_user -p accounting_system < backup.sql
```

## Monitoring

### Container Health

```bash
# View running containers
docker-compose -f docker-compose.prod.yml ps

# View resource usage
docker stats
```

### Application Logs

```bash
# Real-time logs
docker-compose -f docker-compose.prod.yml logs -f

# Last 100 lines
docker-compose -f docker-compose.prod.yml logs --tail=100
```

## Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [PHP Docker Image](https://hub.docker.com/_/php)
- [Nginx Docker Image](https://hub.docker.com/_/nginx)
- [MySQL Docker Image](https://hub.docker.com/_/mysql)

## Support

For issues or questions:
1. Check logs: `./docker.sh logs dev`
2. Review this documentation
3. Check [troubleshooting](#troubleshooting) section
4. Contact development team
