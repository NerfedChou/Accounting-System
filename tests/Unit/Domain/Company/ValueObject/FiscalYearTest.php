<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Company\ValueObject;

use Domain\Company\ValueObject\FiscalYear;
use Domain\Shared\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FiscalYearTest extends TestCase
{
    public function test_creates_calendar_fiscal_year(): void
    {
        $fiscalYear = FiscalYear::calendar();

        $this->assertEquals(1, $fiscalYear->startMonth());
        $this->assertEquals(1, $fiscalYear->startDay());
    }

    public function test_creates_from_month_and_day(): void
    {
        $fiscalYear = FiscalYear::fromMonthAndDay(4, 1);

        $this->assertEquals(4, $fiscalYear->startMonth());
        $this->assertEquals(1, $fiscalYear->startDay());
    }

    public function test_throws_for_invalid_month(): void
    {
        $this->expectException(InvalidArgumentException::class);

        FiscalYear::fromMonthAndDay(13, 1);
    }

    public function test_throws_for_zero_month(): void
    {
        $this->expectException(InvalidArgumentException::class);

        FiscalYear::fromMonthAndDay(0, 1);
    }

    public function test_throws_for_invalid_day(): void
    {
        $this->expectException(InvalidArgumentException::class);

        FiscalYear::fromMonthAndDay(6, 32);
    }

    public function test_throws_for_invalid_day_in_month(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // February has max 28/29 days
        FiscalYear::fromMonthAndDay(2, 30);
    }

    public function test_gets_start_date_for_calendar_year(): void
    {
        $fiscalYear = FiscalYear::calendar();
        $startDate = $fiscalYear->getStartDate(2025);

        $this->assertEquals('2025-01-01', $startDate->toString());
    }

    public function test_gets_start_date_for_non_calendar_year(): void
    {
        $fiscalYear = FiscalYear::fromMonthAndDay(4, 1); // April 1
        $startDate = $fiscalYear->getStartDate(2025);

        $this->assertEquals('2025-04-01', $startDate->toString());
    }

    public function test_gets_end_date_for_calendar_year(): void
    {
        $fiscalYear = FiscalYear::calendar();
        $endDate = $fiscalYear->getEndDate(2025);

        $this->assertEquals('2025-12-31', $endDate->toString());
    }

    public function test_gets_end_date_for_non_calendar_year(): void
    {
        $fiscalYear = FiscalYear::fromMonthAndDay(4, 1); // April 1
        $endDate = $fiscalYear->getEndDate(2025);

        // Ends March 31, 2026
        $this->assertEquals('2026-03-31', $endDate->toString());
    }

    public function test_equality(): void
    {
        $fiscalYear1 = FiscalYear::fromMonthAndDay(7, 1);
        $fiscalYear2 = FiscalYear::fromMonthAndDay(7, 1);
        $fiscalYear3 = FiscalYear::fromMonthAndDay(1, 1);

        $this->assertTrue($fiscalYear1->equals($fiscalYear2));
        $this->assertFalse($fiscalYear1->equals($fiscalYear3));
    }

    public function test_json_serializable(): void
    {
        $fiscalYear = FiscalYear::fromMonthAndDay(4, 1);
        $json = json_encode($fiscalYear);

        $decoded = json_decode($json, true);
        $this->assertEquals(4, $decoded['start_month']);
        $this->assertEquals(1, $decoded['start_day']);
    }
}
