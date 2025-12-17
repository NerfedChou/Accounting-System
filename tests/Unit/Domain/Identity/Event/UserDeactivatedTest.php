<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Identity\Event;

use Domain\Identity\Event\UserDeactivated;
use Domain\Identity\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

final class UserDeactivatedTest extends TestCase
{
    public function test_creates_event_with_required_data(): void
    {
        $userId = UserId::generate();
        $deactivatedBy = UserId::generate();
        $reason = 'Violation of terms';

        $event = new UserDeactivated($userId, $deactivatedBy, $reason);

        $this->assertEquals('user.deactivated', $event->eventName());
        $this->assertTrue($event->userId()->equals($userId));
        $this->assertTrue($event->deactivatedBy()->equals($deactivatedBy));
        $this->assertEquals($reason, $event->reason());
        $this->assertNotNull($event->occurredOn());
    }

    public function test_to_array_contains_all_data(): void
    {
        $userId = UserId::generate();
        $deactivatedBy = UserId::generate();
        $reason = 'Violation of terms';

        $event = new UserDeactivated($userId, $deactivatedBy, $reason);
        $array = $event->toArray();

        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('deactivated_by', $array);
        $this->assertArrayHasKey('reason', $array);
        $this->assertArrayHasKey('occurred_on', $array);
    }
}
