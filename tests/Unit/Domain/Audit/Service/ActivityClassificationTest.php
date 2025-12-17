<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Audit\Service;

use Domain\Audit\Service\ActivityClassification;
use Domain\Audit\ValueObject\ActivityType;
use Domain\Audit\ValueObject\AuditSeverity;
use PHPUnit\Framework\TestCase;

final class ActivityClassificationTest extends TestCase
{
    public function testGetCategory(): void
    {
        $this->assertEquals('authentication', ActivityClassification::getCategory(ActivityType::LOGIN));
        $this->assertEquals('user_management', ActivityClassification::getCategory(ActivityType::USER_CREATED));
        $this->assertEquals('company_management', ActivityClassification::getCategory(ActivityType::COMPANY_UPDATED));
        $this->assertEquals('chart_of_accounts', ActivityClassification::getCategory(ActivityType::ACCOUNT_DEACTIVATED));
        $this->assertEquals('transactions', ActivityClassification::getCategory(ActivityType::TRANSACTION_POSTED));
        $this->assertEquals('approvals', ActivityClassification::getCategory(ActivityType::APPROVAL_GRANTED));
        $this->assertEquals('reports', ActivityClassification::getCategory(ActivityType::REPORT_GENERATED));
        $this->assertEquals('system', ActivityClassification::getCategory(ActivityType::SYSTEM_ERROR));
    }

    public function testGetSeverity(): void
    {
        // Security
        $this->assertEquals(AuditSeverity::SECURITY, ActivityClassification::getSeverity(ActivityType::LOGIN_FAILED));

        // Warning
        $this->assertEquals(AuditSeverity::WARNING, ActivityClassification::getSeverity(ActivityType::USER_DEACTIVATED));
        $this->assertEquals(AuditSeverity::WARNING, ActivityClassification::getSeverity(ActivityType::SYSTEM_ERROR));

        // Info (Default)
        $this->assertEquals(AuditSeverity::INFO, ActivityClassification::getSeverity(ActivityType::LOGIN));
        $this->assertEquals(AuditSeverity::INFO, ActivityClassification::getSeverity(ActivityType::DATA_EXPORTED));
    }

    public function testRequiresAdminNotification(): void
    {
        // True cases
        $this->assertTrue(ActivityClassification::requiresAdminNotification(ActivityType::LOGIN_FAILED));
        $this->assertTrue(ActivityClassification::requiresAdminNotification(ActivityType::SYSTEM_ERROR));
        $this->assertTrue(ActivityClassification::requiresAdminNotification(ActivityType::DATA_EXPORTED));

        // False cases (Default)
        $this->assertFalse(ActivityClassification::requiresAdminNotification(ActivityType::LOGIN));
        $this->assertFalse(ActivityClassification::requiresAdminNotification(ActivityType::USER_DEACTIVATED));
    }
}
