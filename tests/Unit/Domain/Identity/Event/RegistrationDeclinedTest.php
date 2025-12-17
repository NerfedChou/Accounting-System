<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Identity\Event;

use Domain\Identity\Event\RegistrationDeclined;
use Domain\Identity\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

final class RegistrationDeclinedTest extends TestCase
{
    public function test_creates_event_with_required_data(): void
    {
        $userId = UserId::generate();
        $declinedBy = UserId::generate();
        $reason = 'Insufficient documentation';

        $event = new RegistrationDeclined($userId, $declinedBy, $reason);

        $this->assertEquals('registration.declined', $event->eventName());
        $this->assertTrue($event->userId()->equals($userId));
        $this->assertTrue($event->declinedBy()->equals($declinedBy));
        $this->assertEquals($reason, $event->reason());
    }

    public function test_to_array_contains_all_data(): void
    {
        $userId = UserId::generate();
        $declinedBy = UserId::generate();
        $reason = 'Insufficient documentation';

        $event = new RegistrationDeclined($userId, $declinedBy, $reason);
        $array = $event->toArray();

        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('declined_by', $array);
        $this->assertArrayHasKey('reason', $array);
        $this->assertArrayHasKey('occurred_on', $array);
    }
}
