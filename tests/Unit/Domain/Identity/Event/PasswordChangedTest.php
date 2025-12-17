<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Identity\Event;

use Domain\Identity\Event\PasswordChanged;
use Domain\Identity\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

final class PasswordChangedTest extends TestCase
{
    public function test_creates_event_with_user_id(): void
    {
        $userId = UserId::generate();

        $event = new PasswordChanged($userId);

        $this->assertEquals('password.changed', $event->eventName());
        $this->assertTrue($event->userId()->equals($userId));
        $this->assertNotNull($event->occurredOn());
    }

    public function test_to_array_contains_required_data(): void
    {
        $userId = UserId::generate();

        $event = new PasswordChanged($userId);
        $array = $event->toArray();

        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('occurred_on', $array);
    }
}
