# Architecture Audit Fixes Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Fix all 25 PHPStan errors and architectural violations identified in the architecture audit to achieve clean static analysis and proper DDD/Hexagonal compliance.

**Architecture:** Fix violations in order of dependency - create missing DTOs first, then fix services that use them, then fix controllers and tests. Each task follows TDD: write test (if applicable), implement fix, verify with PHPStan.

**Tech Stack:** PHP 8.2, PHPStan Level 5, PHPUnit 10.5

---

## Summary of Issues

| Priority | Issue | File | Type |
|----------|-------|------|------|
| HIGH | Missing `LogActivityRequest` class | Domain/Audit | Missing DTO |
| HIGH | `MysqlSessionRepository` not found | Infrastructure/Container | Dead reference |
| HIGH | `TotpService` direct instantiation | Api/Controller/AuthController | DI violation |
| MEDIUM | Wrong Email import | tests/InMemoryUserRepository | Namespace error |
| MEDIUM | Missing return statement | ActivityLogService | Code error |
| MEDIUM | PHPDoc return type mismatch | ReportRepositoryInterface | Doc error |
| MEDIUM | Duplicate EventDispatcher interface | Domain/Shared/Event | Redundancy |
| LOW | Unused property warning | CompanyScopingMiddleware | Warning |
| LOW | Unsafe static constructor | ValidatedRequest | Warning |

---

## Task 1: Create LogActivityRequest DTO

**Files:**
- Create: `src/Domain/Audit/Service/LogActivityRequest.php`

**Step 1: Create the LogActivityRequest class**

```php
<?php

declare(strict_types=1);

namespace Domain\Audit\Service;

use Domain\Audit\ValueObject\ActivityType;
use Domain\Audit\ValueObject\Actor;
use Domain\Audit\ValueObject\RequestContext;

/**
 * Request DTO for logging activity.
 */
final readonly class LogActivityRequest
{
    /**
     * @param string $companyId
     * @param Actor $actor
     * @param ActivityType $activityType
     * @param array{type: string, id: string, action: string} $entityInfo
     * @param array{prev?: array, new?: array, changes?: array} $stateInfo
     * @param RequestContext $context
     */
    public function __construct(
        public string $companyId,
        public Actor $actor,
        public ActivityType $activityType,
        public array $entityInfo,
        public array $stateInfo,
        public RequestContext $context
    ) {
    }
}
```

**Step 2: Verify PHPStan passes for this file**

Run: `docker exec accounting-app-dev vendor/bin/phpstan analyse src/Domain/Audit/Service/LogActivityRequest.php --level=5`
Expected: No errors

**Step 3: Commit**

```bash
git add src/Domain/Audit/Service/LogActivityRequest.php
git commit -m "feat(audit): add LogActivityRequest DTO for activity logging

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>"
```

---

## Task 2: Fix ActivityLogService Missing Return Statement

**Files:**
- Modify: `src/Domain/Audit/Service/ActivityLogService.php:142`

**Step 1: Add missing return statement**

Find the end of the `logActivity` method (around line 142) and add the return statement. The method creates `$linkedLog` but never returns it.

Replace the closing section of the method (after `$this->repository->save($linkedLog);`):

```php
        // 5. Persist
        $this->repository->save($linkedLog);

        return $linkedLog;
    }
```

**Step 2: Remove stale PHPDoc that references wrong parameters**

Replace the PHPDoc block above `logActivity` method (lines 29-39):

```php
    /**
     * Create and log an activity with cryptographic hash chaining.
     */
    public function logActivity(LogActivityRequest $request): ActivityLog {
```

**Step 3: Verify PHPStan passes**

Run: `docker exec accounting-app-dev vendor/bin/phpstan analyse src/Domain/Audit/Service/ActivityLogService.php --level=5`
Expected: No errors (reduced from 19)

**Step 4: Commit**

```bash
git add src/Domain/Audit/Service/ActivityLogService.php
git commit -m "fix(audit): add missing return statement in ActivityLogService

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>"
```

---

## Task 3: Remove MysqlSessionRepository Dead Reference

**Files:**
- Modify: `src/Infrastructure/Container/ContainerBuilder.php:115-117`

**Step 1: Remove or comment out the dead reference**

The `SessionRepositoryInterface` is bound to a non-existent `MysqlSessionRepository`. Since sessions are handled by Redis via `SessionAuthenticationService`, remove this binding.

Find and delete these lines (115-117):

```php
        $container->singleton(SessionRepositoryInterface::class, fn(ContainerInterface $c) =>
            new MysqlSessionRepository($c->get(PDO::class))
        );
```

**Step 2: Remove the unused import if present**

Check if `SessionRepositoryInterface` is still used elsewhere in the file. If not, remove from the `use` statements at the top (line 14):

```php
use Domain\Identity\Repository\SessionRepositoryInterface;
```

**Step 3: Verify PHPStan passes**

Run: `docker exec accounting-app-dev vendor/bin/phpstan analyse src/Infrastructure/Container/ContainerBuilder.php --level=5`
Expected: No errors

**Step 4: Commit**

```bash
git add src/Infrastructure/Container/ContainerBuilder.php
git commit -m "fix(container): remove dead MysqlSessionRepository reference

Sessions are handled by Redis via SessionAuthenticationService.

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>"
```

---

## Task 4: Fix AuthController TotpService DI Violation

**Files:**
- Modify: `src/Api/Controller/AuthController.php`

**Step 1: Add TotpService to constructor**

Replace the constructor (lines 22-26):

```php
    public function __construct(
        private readonly AuthenticationServiceInterface $authService,
        private readonly UserRepositoryInterface $userRepository,
        private readonly \Infrastructure\Service\TotpService $totpService
    ) {
    }
```

**Step 2: Fix the verifyOtp method**

Replace the `verifyOtp` method (lines 141-145):

```php
    private function verifyOtp(User $user, string $code): bool
    {
        return $this->totpService->verify($user->otpSecret(), $code);
    }
```

**Step 3: Update public/index.php to inject TotpService**

Find where `AuthController` is instantiated in `public/index.php` and add the TotpService parameter.

Find this pattern:
```php
new AuthController(
    $container->get(AuthenticationServiceInterface::class),
    $container->get(UserRepositoryInterface::class)
)
```

Replace with:
```php
new AuthController(
    $container->get(AuthenticationServiceInterface::class),
    $container->get(UserRepositoryInterface::class),
    $container->get(\Infrastructure\Service\TotpService::class)
)
```

**Step 4: Verify PHPStan passes**

Run: `docker exec accounting-app-dev vendor/bin/phpstan analyse src/Api/Controller/AuthController.php --level=5`
Expected: No errors

**Step 5: Commit**

```bash
git add src/Api/Controller/AuthController.php public/index.php
git commit -m "fix(auth): inject TotpService via constructor instead of direct instantiation

Fixes DI pattern violation - TotpService is now properly injected.

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>"
```

---

## Task 5: Fix InMemoryUserRepository Email Import

**Files:**
- Modify: `tests/Unit/Domain/Identity/Repository/InMemoryUserRepository.php:9`

**Step 1: Fix the Email import namespace**

Replace line 9:
```php
use Domain\Identity\ValueObject\Email;
```

With:
```php
use Domain\Shared\ValueObject\Email;
```

**Step 2: Verify tests pass**

Run: `docker exec accounting-app-dev vendor/bin/phpunit tests/Unit/Domain/Identity/Repository/ --no-coverage`
Expected: Tests pass

**Step 3: Commit**

```bash
git add tests/Unit/Domain/Identity/Repository/InMemoryUserRepository.php
git commit -m "fix(tests): correct Email import namespace in InMemoryUserRepository

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>"
```

---

## Task 6: Fix ReportRepositoryInterface PHPDoc

**Files:**
- Modify: `src/Domain/Reporting/Repository/ReportRepositoryInterface.php:28-30`

**Step 1: Fix the PHPDoc return type**

The PHPDoc says `@return array<string, mixed>|null` but the method returns `?Report`.

Replace lines 25-30:
```php
    /**
     * Find report by ID.
     */
    public function findById(ReportId $id): ?Report;
```

Also fix the duplicate docblock above (lines 17-22). Remove the duplicate:
```php
    /**
     * Save generated report for history.
     *
    /**
     * Save generated report for history.
     */
```

Should be just:
```php
    /**
     * Save generated report for history.
     */
```

**Step 2: Verify PHPStan passes**

Run: `docker exec accounting-app-dev vendor/bin/phpstan analyse src/Domain/Reporting/Repository/ReportRepositoryInterface.php --level=5`
Expected: No errors

**Step 3: Commit**

```bash
git add src/Domain/Reporting/Repository/ReportRepositoryInterface.php
git commit -m "fix(reporting): correct PHPDoc return type in ReportRepositoryInterface

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>"
```

---

## Task 7: Remove Duplicate EventDispatcher Interface

**Files:**
- Delete: `src/Domain/Shared/Event/EventDispatcher.php`

**Step 1: Verify EventDispatcherInterface exists and is complete**

Check that `src/Domain/Shared/Event/EventDispatcherInterface.php` has the `dispatch` method.

**Step 2: Search for usages of EventDispatcher (not Interface)**

Run: `grep -r "use Domain\\Shared\\Event\\EventDispatcher;" src/`

If any files use `EventDispatcher` instead of `EventDispatcherInterface`, update them.

**Step 3: Delete the duplicate file**

```bash
rm src/Domain/Shared/Event/EventDispatcher.php
```

**Step 4: Verify no broken imports**

Run: `docker exec accounting-app-dev vendor/bin/phpstan analyse src/ --level=5 --no-progress 2>&1 | grep -i "eventdispatcher"`
Expected: No errors related to EventDispatcher

**Step 5: Commit**

```bash
git add -A
git commit -m "refactor(events): remove duplicate EventDispatcher interface

Keep only EventDispatcherInterface for clarity.

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>"
```

---

## Task 8: Fix ActivityLogListener Errors

**Files:**
- Modify: `src/Application/Listener/ActivityLogListener.php`

**Step 1: Check what namespace LogActivityRequest should come from**

The error says it expects `Domain\Audit\Service\LogActivityRequest` but gets `Domain\Audit\ValueObject\LogActivityRequest`.

Update the import to use the correct namespace (from Task 1):
```php
use Domain\Audit\Service\LogActivityRequest;
```

**Step 2: Fix ActivityType constant access**

The error says `ActivityType::CREATE` doesn't exist. Check `Domain\Audit\ValueObject\ActivityType` for the correct constant names.

If `ActivityType` is an enum, the access should be:
```php
ActivityType::CREATE  // if enum case
// or
ActivityType::create()  // if factory method
```

Read the ActivityType file to determine correct usage and update accordingly.

**Step 3: Verify PHPStan passes**

Run: `docker exec accounting-app-dev vendor/bin/phpstan analyse src/Application/Listener/ActivityLogListener.php --level=5`
Expected: No errors

**Step 4: Commit**

```bash
git add src/Application/Listener/ActivityLogListener.php
git commit -m "fix(listener): correct LogActivityRequest import and ActivityType usage

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>"
```

---

## Task 9: Fix CompanyScopingMiddleware Unused Property (Optional)

**Files:**
- Modify: `src/Api/Middleware/CompanyScopingMiddleware.php:20`

**Step 1: Either use or remove the unused property**

The warning says `$userRepository` is written but never read. Either:

Option A - Remove if not needed:
```php
// Remove the property and constructor parameter if truly unused
```

Option B - Add usage (check if it should be used):
```php
// The middleware may need to verify user belongs to company
```

**Step 2: Verify PHPStan passes**

Run: `docker exec accounting-app-dev vendor/bin/phpstan analyse src/Api/Middleware/CompanyScopingMiddleware.php --level=5`
Expected: No errors

**Step 3: Commit**

```bash
git add src/Api/Middleware/CompanyScopingMiddleware.php
git commit -m "fix(middleware): remove unused userRepository property

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>"
```

---

## Task 10: Final Verification

**Step 1: Run full PHPStan analysis**

Run: `docker exec accounting-app-dev vendor/bin/phpstan analyse src/ --level=5 --no-progress`
Expected: 0 errors

**Step 2: Run tests**

Run: `docker exec accounting-app-dev vendor/bin/phpunit --testsuite=unit --no-coverage`
Expected: All tests pass

**Step 3: Verify Docker services running**

Run: `docker ps | grep accounting`
Expected: All 5 containers running

**Step 4: Final commit for any cleanup**

```bash
git status
# If any uncommitted changes remain
git add -A
git commit -m "chore: final cleanup after architecture audit fixes

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>"
```

---

## Verification Checklist

After completing all tasks, verify:

- [ ] `docker exec accounting-app-dev vendor/bin/phpstan analyse src/ --level=5` = 0 errors
- [ ] `docker exec accounting-app-dev vendor/bin/phpunit --testsuite=unit` = All pass
- [ ] AuthController injects TotpService via constructor
- [ ] LogActivityRequest class exists in Domain/Audit/Service/
- [ ] No MysqlSessionRepository reference in ContainerBuilder
- [ ] InMemoryUserRepository uses Domain\Shared\ValueObject\Email
- [ ] Only EventDispatcherInterface exists (no duplicate)
- [ ] All git commits made with proper messages

---

## Dependency Order

Execute tasks in this order due to dependencies:

1. **Task 1** - Create LogActivityRequest (required by Tasks 2, 8)
2. **Task 2** - Fix ActivityLogService (depends on Task 1)
3. **Task 3** - Remove MysqlSessionRepository (independent)
4. **Task 4** - Fix AuthController DI (independent)
5. **Task 5** - Fix Email import (independent)
6. **Task 6** - Fix ReportRepositoryInterface (independent)
7. **Task 7** - Remove duplicate EventDispatcher (independent)
8. **Task 8** - Fix ActivityLogListener (depends on Task 1)
9. **Task 9** - Fix CompanyScopingMiddleware (optional, independent)
10. **Task 10** - Final verification

---

*Implementation plan generated from architecture-audit.md - December 2025*
