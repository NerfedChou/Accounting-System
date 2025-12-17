<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Identity\Event;

use Domain\Identity\Event\LoginFailed;
use PHPUnit\Framework\TestCase;

final class LoginFailedTest extends TestCase
{
    public function test_creates_event_with_username(): void
    {
        $event = new LoginFailed('john.doe');

        $this->assertEquals('login.failed', $event->eventName());
        $this->assertEquals('john.doe', $event->username());
        $this->assertNull($event->ipAddress());
        $this->assertNull($event->reason());
    }

    public function test_creates_event_with_all_data(): void
    {
        $event = new LoginFailed('john.doe', '192.168.1.1', 'Invalid password');

        $this->assertEquals('john.doe', $event->username());
        $this->assertEquals('192.168.1.1', $event->ipAddress());
        $this->assertEquals('Invalid password', $event->reason());
    }

    public function test_to_array_contains_required_data(): void
    {
        $event = new LoginFailed('john.doe', '192.168.1.1', 'Invalid password');
        $array = $event->toArray();

        $this->assertArrayHasKey('username', $array);
        $this->assertArrayHasKey('ip_address', $array);
        $this->assertArrayHasKey('reason', $array);
        $this->assertArrayHasKey('occurred_on', $array);
    }

    public function test_to_array_omits_null_fields(): void
    {
        $event = new LoginFailed('john.doe');
        $array = $event->toArray();

        $this->assertArrayHasKey('username', $array);
        $this->assertArrayNotHasKey('ip_address', $array);
        $this->assertArrayNotHasKey('reason', $array);
    }
}
