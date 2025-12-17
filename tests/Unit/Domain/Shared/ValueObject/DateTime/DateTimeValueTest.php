<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared\ValueObject\DateTime;

use Domain\Shared\ValueObject\DateTime\DateTimeValue;
use Domain\Shared\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

final class DateTimeValueTest extends TestCase
{
    public function test_creates_from_valid_string(): void
    {
        $dateString = '2025-12-31 23:59:59';
        $dateTime = DateTimeValue::fromString($dateString);

        $this->assertEquals($dateString, $dateTime->toString());
    }

    public function test_creates_from_native_datetime(): void
    {
        $native = new DateTimeImmutable('2025-01-01 12:00:00');
        $dateTime = DateTimeValue::fromNative($native);

        $this->assertEquals('2025-01-01 12:00:00', $dateTime->toString());
    }

    public function test_creates_for_now(): void
    {
        $dateTime = DateTimeValue::now();
        $this->assertInstanceOf(DateTimeValue::class, $dateTime);
    }

    public function test_throws_exception_for_invalid_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DateTimeValue::fromString('invalid-date');
    }

    public function test_equals_returns_true_for_same_time(): void
    {
        $dt1 = DateTimeValue::fromString('2025-01-01 10:00:00');
        $dt2 = DateTimeValue::fromString('2025-01-01 10:00:00');

        $this->assertTrue($dt1->equals($dt2));
    }

    public function test_equals_returns_false_for_different_time(): void
    {
        $dt1 = DateTimeValue::fromString('2025-01-01 10:00:00');
        $dt2 = DateTimeValue::fromString('2025-01-01 10:00:01');

        $this->assertFalse($dt1->equals($dt2));
    }

    public function test_is_before_returns_correctly(): void
    {
        $early = DateTimeValue::fromString('2025-01-01 10:00:00');
        $late = DateTimeValue::fromString('2025-01-01 11:00:00');

        $this->assertTrue($early->isBefore($late));
        $this->assertFalse($late->isBefore($early));
    }

    public function test_is_after_returns_correctly(): void
    {
        $early = DateTimeValue::fromString('2025-01-01 10:00:00');
        $late = DateTimeValue::fromString('2025-01-01 11:00:00');

        $this->assertTrue($late->isAfter($early));
        $this->assertFalse($early->isAfter($late));
    }

    public function test_diff_in_days(): void
    {
        $start = DateTimeValue::fromString('2025-01-01 10:00:00');
        $end = DateTimeValue::fromString('2025-01-03 10:00:00');

        $this->assertEquals(2, $start->diffInDays($end));
    }

    public function test_json_serialize(): void
    {
        $dateString = '2025-01-01 10:00:00';
        $dateTime = DateTimeValue::fromString($dateString);

        $this->assertEquals('"' . $dateString . '"', json_encode($dateTime));
    }
}
