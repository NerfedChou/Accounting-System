<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared\ValueObject\DateTime;

use Domain\Shared\ValueObject\DateTime\DateValue;
use Domain\Shared\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

final class DateValueTest extends TestCase
{
    public function test_creates_from_valid_string(): void
    {
        $dateString = '2025-12-31';
        $date = DateValue::fromString($dateString);

        $this->assertEquals($dateString, $date->toString());
    }

    public function test_creates_from_native_datetime(): void
    {
        $native = new DateTimeImmutable('2025-01-01 12:00:00');
        $date = DateValue::fromNative($native);

        $this->assertEquals('2025-01-01', $date->toString());
    }

    public function test_creates_today(): void
    {
        $date = DateValue::today();
        $this->assertInstanceOf(DateValue::class, $date);
        $this->assertEquals(date('Y-m-d'), $date->toString());
    }

    public function test_throws_exception_for_invalid_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DateValue::fromString('2025-13-45'); // Invalid month/day
    }

    public function test_equals_returns_true_for_same_date(): void
    {
        $d1 = DateValue::fromString('2025-01-01');
        $d2 = DateValue::fromString('2025-01-01');

        $this->assertTrue($d1->equals($d2));
    }

    public function test_equals_returns_false_for_different_date(): void
    {
        $d1 = DateValue::fromString('2025-01-01');
        $d2 = DateValue::fromString('2025-01-02');

        $this->assertFalse($d1->equals($d2));
    }

    public function test_is_before(): void
    {
        $early = DateValue::fromString('2025-01-01');
        $late = DateValue::fromString('2025-01-02');

        $this->assertTrue($early->isBefore($late));
    }

    public function test_is_after(): void
    {
        $early = DateValue::fromString('2025-01-01');
        $late = DateValue::fromString('2025-01-02');

        $this->assertTrue($late->isAfter($early));
    }
}
