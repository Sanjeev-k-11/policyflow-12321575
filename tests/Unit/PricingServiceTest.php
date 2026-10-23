<?php

namespace Tests\Unit;

use App\Services\PricingService;
use Carbon\Carbon;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PricingServiceTest extends TestCase
{
    private PricingService $pricingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricingService = new PricingService();
    }

    /**
     * Requirement check:
     * PLUS, 5 days, international, 300000, age 28 gives 225.00
     * Formula: 20 × 5 × 1.00 × 1.80 × 1.25 = ₹225.00
     */
    public function test_calculates_specification_example_correctly(): void
    {
        $result = $this->pricingService->calculate(
            planCode: 'PLUS',
            sumInsured: 300000,
            destination: 'INTERNATIONAL',
            dateOfBirth: '1998-04-10',
            tripStart: '2026-11-10',
            tripEnd: '2026-11-14'
        );

        $this->assertEquals(5, $result['travel_days']);
        $this->assertEquals(28, $result['age_at_trip_start']);
        $this->assertEquals('20.00', $result['base_daily_rate']);
        $this->assertEquals('1.00', $result['age_multiplier']);
        $this->assertEquals('1.80', $result['destination_multiplier']);
        $this->assertEquals('1.25', $result['coverage_multiplier']);
        $this->assertEquals('225.00', $result['calculated_premium']);
    }

    /**
     * Age multiplier boundaries:
     * 0-17: 1.10
     * 18-60: 1.00
     * 61-70: 1.50
     * 71+: 2.00
     */
    public function test_age_multiplier_boundaries(): void
    {
        $this->assertEquals('1.10', $this->pricingService->determineAgeMultiplier(0));
        $this->assertEquals('1.10', $this->pricingService->determineAgeMultiplier(16));
        $this->assertEquals('1.10', $this->pricingService->determineAgeMultiplier(17));
        $this->assertEquals('1.00', $this->pricingService->determineAgeMultiplier(18));
        $this->assertEquals('1.00', $this->pricingService->determineAgeMultiplier(40));
        $this->assertEquals('1.00', $this->pricingService->determineAgeMultiplier(60));
        $this->assertEquals('1.50', $this->pricingService->determineAgeMultiplier(61));
        $this->assertEquals('1.50', $this->pricingService->determineAgeMultiplier(69));
        $this->assertEquals('1.50', $this->pricingService->determineAgeMultiplier(70));
        $this->assertEquals('2.00', $this->pricingService->determineAgeMultiplier(71));
        $this->assertEquals('2.00', $this->pricingService->determineAgeMultiplier(85));
    }

    public function test_single_day_trip_is_minimum_one_day(): void
    {
        $result = $this->pricingService->calculate(
            planCode: 'BASIC',
            sumInsured: 100000,
            destination: 'DOMESTIC',
            dateOfBirth: '2000-01-01',
            tripStart: '2026-05-01',
            tripEnd: '2026-05-01'
        );

        $this->assertEquals(1, $result['travel_days']);
        $this->assertEquals('12.00', $result['base_daily_rate']);
        $this->assertEquals('1.00', $result['destination_multiplier']);
        $this->assertEquals('1.00', $result['coverage_multiplier']);
        $this->assertEquals('12.00', $result['calculated_premium']);
    }

    public function test_rejects_unsupported_plan_code(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported plan code');

        $this->pricingService->calculate(
            planCode: 'GOLD_VIP',
            sumInsured: 100000,
            destination: 'DOMESTIC',
            dateOfBirth: '2000-01-01',
            tripStart: '2026-05-01',
            tripEnd: '2026-05-05'
        );
    }

    public function test_rejects_unsupported_sum_insured(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported sum insured');

        $this->pricingService->calculate(
            planCode: 'PLUS',
            sumInsured: 250000,
            destination: 'DOMESTIC',
            dateOfBirth: '2000-01-01',
            tripStart: '2026-05-01',
            tripEnd: '2026-05-05'
        );
    }

    public function test_rejects_trip_end_before_trip_start(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Trip end date');

        $this->pricingService->calculate(
            planCode: 'PLUS',
            sumInsured: 300000,
            destination: 'DOMESTIC',
            dateOfBirth: '2000-01-01',
            tripStart: '2026-05-10',
            tripEnd: '2026-05-05'
        );
    }
}
