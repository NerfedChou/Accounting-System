<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Audit\Entity;

use Domain\Audit\Entity\ActivityLog;
use Domain\Audit\ValueObject\ActivityId;
use Domain\Audit\ValueObject\ActivityType;
use Domain\Audit\ValueObject\Actor;
use Domain\Audit\ValueObject\AuditSeverity;
use Domain\Company\ValueObject\CompanyId;
use Domain\Audit\ValueObject\RequestContext;
use PHPUnit\Framework\TestCase;

final class ActivityLogTest extends TestCase
{
    public function testCategoryAndSeverityDelegation(): void
    {
        // Mock dependencies
        $id = ActivityId::generate();
        $companyId = CompanyId::generate();
        $actor = Actor::system();
        $type = ActivityType::LOGIN_FAILED; // Security severity, Authentication category
        $context = RequestContext::empty();
        
        $log = new ActivityLog(
            $id,
            $companyId,
            $actor,
            $type,
            'user',
            'user-123',
            'login',
            [],
            [],
            [],
            $context,
            new \DateTimeImmutable()
        );

        $this->assertEquals('authentication', $log->category());
        $this->assertEquals(AuditSeverity::SECURITY, $log->severity());
    }
}
