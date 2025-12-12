# GitHub Actions CI/CD

> **Master Architect Reference**: Complete GitHub Actions workflows documentation.

## Overview

The Accounting System uses GitHub Actions for:
- **Continuous Integration**: Code quality, testing, security scanning
- **Continuous Deployment**: Docker image building and deployment
- **Documentation**: Validation and link checking

---

## Workflow Files

```
.github/
├── workflows/
│   ├── ci.yml              # Main CI pipeline
│   ├── docker-build.yml    # Docker image building
│   └── docs-validation.yml # Documentation checks
├── dependabot.yml          # Dependency updates
└── markdown-link-check-config.json
```

---

## CI Workflow (ci.yml)

### Purpose
Runs on every push and PR to ensure code quality.

### Jobs

#### 1. Lint Job
- PHP_CodeSniffer (PSR-12 standard)
- PHPStan level 8 analysis

#### 2. Test Job
- Unit tests with coverage
- Integration tests with MySQL

#### 3. Security Job
- Composer security audit
- Psalm taint analysis

### Full Configuration

```yaml
name: Continuous Integration

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

env:
  PHP_VERSION: '8.2'

jobs:
  lint:
    name: Code Standards & Static Analysis
    runs-on: ubuntu-latest

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ env.PHP_VERSION }}
          extensions: mbstring, pdo, pdo_mysql, redis
          tools: composer, phpcs, phpstan
          coverage: none

      - name: Get Composer cache directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: ${{ runner.os }}-composer-

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress --no-suggest

      - name: Run PHP_CodeSniffer
        run: vendor/bin/phpcs --standard=PSR12 src/
        continue-on-error: false

      - name: Run PHPStan
        run: vendor/bin/phpstan analyse src/ --level=8 --memory-limit=1G

  test:
    name: Tests
    runs-on: ubuntu-latest
    needs: lint

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: accounting_test
          MYSQL_USER: test_user
          MYSQL_PASSWORD: test_password
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping -h 127.0.0.1"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=5

      redis:
        image: redis:7-alpine
        ports:
          - 6379:6379
        options: >-
          --health-cmd="redis-cli ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=5

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ env.PHP_VERSION }}
          extensions: mbstring, pdo, pdo_mysql, redis
          tools: composer
          coverage: xdebug

      - name: Get Composer cache directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: ${{ runner.os }}-composer-

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Wait for MySQL
        run: |
          while ! mysqladmin ping -h 127.0.0.1 --silent; do
            sleep 1
          done

      - name: Run database migrations
        run: php artisan migrate --force 2>/dev/null || echo "No migrations to run"
        env:
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: accounting_test
          DB_USERNAME: test_user
          DB_PASSWORD: test_password

      - name: Run Unit Tests
        run: vendor/bin/phpunit --testsuite=unit --coverage-clover=coverage-unit.xml
        env:
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: accounting_test
          DB_USERNAME: test_user
          DB_PASSWORD: test_password

      - name: Run Integration Tests
        run: vendor/bin/phpunit --testsuite=integration --coverage-clover=coverage-integration.xml
        env:
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: accounting_test
          DB_USERNAME: test_user
          DB_PASSWORD: test_password
          REDIS_HOST: 127.0.0.1
          REDIS_PORT: 6379

      - name: Upload coverage to Codecov
        uses: codecov/codecov-action@v4
        with:
          files: coverage-unit.xml,coverage-integration.xml
          fail_ci_if_error: false

  security:
    name: Security Scanning
    runs-on: ubuntu-latest
    needs: lint

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ env.PHP_VERSION }}
          extensions: mbstring
          tools: composer

      - name: Get Composer cache directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: ${{ runner.os }}-composer-

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Security Check (Composer Audit)
        run: composer audit

      - name: Run Psalm Security Analysis
        run: vendor/bin/psalm --taint-analysis --no-progress
        continue-on-error: true

  build-check:
    name: Build Verification
    runs-on: ubuntu-latest
    needs: [lint, test, security]

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Build Docker image
        uses: docker/build-push-action@v5
        with:
          context: .
          file: docker/php/Dockerfile
          push: false
          tags: accounting-system:test
          cache-from: type=gha
          cache-to: type=gha,mode=max
```

---

## Docker Build Workflow (docker-build.yml)

### Purpose
Builds and pushes Docker images to GitHub Container Registry.

### Triggers
- Push to main branch
- Tags matching `v*`
- Pull requests to main

### Configuration

```yaml
name: Docker Image Build

on:
  push:
    branches: [ main ]
    tags:
      - 'v*'
  pull_request:
    branches: [ main ]

env:
  REGISTRY: ghcr.io
  IMAGE_NAME: ${{ github.repository }}

jobs:
  build-and-push:
    name: Build and Push
    runs-on: ubuntu-latest
    permissions:
      contents: read
      packages: write

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Set up QEMU
        uses: docker/setup-qemu-action@v3

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Log in to Container Registry
        if: github.event_name != 'pull_request'
        uses: docker/login-action@v3
        with:
          registry: ${{ env.REGISTRY }}
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Extract metadata
        id: meta
        uses: docker/metadata-action@v5
        with:
          images: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}
          tags: |
            type=ref,event=branch
            type=ref,event=pr
            type=semver,pattern={{version}}
            type=semver,pattern={{major}}.{{minor}}
            type=sha,prefix=
            type=raw,value=latest,enable={{is_default_branch}}

      - name: Build and push PHP image
        uses: docker/build-push-action@v5
        with:
          context: .
          file: docker/php/Dockerfile
          platforms: linux/amd64,linux/arm64
          push: ${{ github.event_name != 'pull_request' }}
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}
          cache-from: type=gha
          cache-to: type=gha,mode=max
          build-args: |
            APP_ENV=production

      - name: Build and push Nginx image
        uses: docker/build-push-action@v5
        with:
          context: .
          file: docker/nginx/Dockerfile
          platforms: linux/amd64,linux/arm64
          push: ${{ github.event_name != 'pull_request' }}
          tags: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}-nginx:${{ github.sha }}
          cache-from: type=gha
          cache-to: type=gha,mode=max
```

---

## Documentation Validation (docs-validation.yml)

### Purpose
Ensures documentation quality and link integrity.

### Configuration

```yaml
name: Documentation Validation

on:
  push:
    paths:
      - 'docs/**'
      - 'README.md'
      - 'CHANGELOG.md'
  pull_request:
    paths:
      - 'docs/**'
      - 'README.md'
      - 'CHANGELOG.md'

jobs:
  markdown-lint:
    name: Markdown Lint
    runs-on: ubuntu-latest

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Lint Markdown files
        uses: DavidAnson/markdownlint-cli2-action@v14
        with:
          globs: |
            README.md
            CHANGELOG.md
            docs/**/*.md

  link-check:
    name: Link Validation
    runs-on: ubuntu-latest

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Check for broken links
        uses: gaurav-nelson/github-action-markdown-link-check@v1
        with:
          use-quiet-mode: 'yes'
          config-file: '.github/markdown-link-check-config.json'
          folder-path: 'docs/'

  structure-check:
    name: Documentation Structure
    runs-on: ubuntu-latest

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Check required files
        run: |
          required_files=(
            "docs/01-architecture/overview.md"
            "docs/01-architecture/bounded-contexts.md"
            "docs/01-architecture/hexagonal-architecture.md"
            "docs/01-architecture/event-catalog.md"
            "docs/01-architecture/technology-decisions.md"
          )

          missing=0
          for file in "${required_files[@]}"; do
            if [ ! -f "$file" ]; then
              echo "Missing: $file"
              missing=$((missing + 1))
            fi
          done

          if [ $missing -gt 0 ]; then
            echo "Error: $missing required documentation files are missing"
            exit 1
          fi

          echo "All required documentation files present"
```

---

## Dependabot Configuration

```yaml
# .github/dependabot.yml
version: 2

updates:
  # PHP dependencies
  - package-ecosystem: "composer"
    directory: "/"
    schedule:
      interval: "weekly"
      day: "monday"
    open-pull-requests-limit: 10
    reviewers:
      - "team/developers"
    labels:
      - "dependencies"
      - "php"
    groups:
      dev-dependencies:
        dependency-type: "development"
        patterns:
          - "phpunit/*"
          - "phpstan/*"
          - "vimeo/psalm"

  # GitHub Actions
  - package-ecosystem: "github-actions"
    directory: "/"
    schedule:
      interval: "weekly"
      day: "monday"
    open-pull-requests-limit: 10
    labels:
      - "dependencies"
      - "github-actions"

  # Docker
  - package-ecosystem: "docker"
    directory: "/docker/php"
    schedule:
      interval: "weekly"
    open-pull-requests-limit: 5
    labels:
      - "dependencies"
      - "docker"
```

---

## Secrets Configuration

Required secrets in GitHub repository:

| Secret | Description |
|--------|-------------|
| `GITHUB_TOKEN` | Auto-provided by GitHub |
| `CODECOV_TOKEN` | For coverage uploads |
| `DEPLOY_SSH_KEY` | For deployment (if needed) |
| `SLACK_WEBHOOK` | For notifications (optional) |

---

## Branch Protection Rules

Recommended settings for `main` branch:

1. **Require pull request reviews**
   - Required approvals: 1
   - Dismiss stale reviews

2. **Require status checks**
   - lint
   - test
   - security
   - build-check

3. **Require branches to be up to date**

4. **Include administrators**

---

## Workflow Triggers Summary

| Workflow | Push to main | Push to develop | PR to main | Tags |
|----------|--------------|-----------------|------------|------|
| CI | ✅ | ✅ | ✅ | ❌ |
| Docker Build | ✅ | ❌ | ✅ | ✅ |
| Docs Validation | ✅ (docs only) | ✅ (docs only) | ✅ (docs only) | ❌ |

---

## Best Practices

### 1. Cache Dependencies
Always cache Composer dependencies to speed up builds.

### 2. Parallel Jobs
Run lint, test, and security jobs in parallel when possible.

### 3. Fail Fast
Use `needs:` to ensure failing jobs stop dependent jobs early.

### 4. Matrix Builds
For multiple PHP versions:
```yaml
strategy:
  matrix:
    php: ['8.2', '8.3']
```

### 5. Artifacts
Save test results and coverage reports:
```yaml
- uses: actions/upload-artifact@v4
  with:
    name: test-results
    path: coverage/
```

### 6. Notifications
Add Slack/email notifications for failures:
```yaml
- name: Notify on failure
  if: failure()
  uses: 8398a7/action-slack@v3
  with:
    status: failure
    webhook_url: ${{ secrets.SLACK_WEBHOOK }}
```
