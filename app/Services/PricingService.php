<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class PricingService
{
    public const BASE_RATES = [
        'BASIC' => '12.00',
        'PLUS' => '20.00',
        'PREMIUM' => '30.00',
    ];

    public const DESTINATION_MULTIPLIERS = [
        'DOMESTIC' => '1.00',
        'INTERNATIONAL' => '1.80',
    ];

    public const COVERAGE_MULTIPLIERS = [
        100000 => '1.00',
        300000 => '1.25',
        500000 => '1.50',
    ];

    

    public function calculate(
        string $planCode,
        int $sumInsured,
        string $destination,
        CarbonInterface|string $dateOfBirth,
        CarbonInterface|string $tripStart,
        CarbonInterface|string $tripEnd
    ): array {
        $planCodeUpper = strtoupper(trim($planCode));
        if (!isset(self::BASE_RATES[$planCodeUpper])) {
            throw new InvalidArgumentException("Unsupported plan code [{$planCode}]. Allowed: BASIC, PLUS, PREMIUM");
        }
        $baseDailyRate = self::BASE_RATES[$planCodeUpper];

        $destinationUpper = strtoupper(trim($destination));
        if (!isset(self::DESTINATION_MULTIPLIERS[$destinationUpper])) {
            throw new InvalidArgumentException("Unsupported destination [{$destination}]. Allowed: DOMESTIC, INTERNATIONAL");
        }
        $destinationMultiplier = self::DESTINATION_MULTIPLIERS[$destinationUpper];

        if (!isset(self::COVERAGE_MULTIPLIERS[$sumInsured])) {
            throw new InvalidArgumentException("Unsupported sum insured [{$sumInsured}]. Allowed: 100000, 300000, 500000");
        }
        $coverageMultiplier = self::COVERAGE_MULTIPLIERS[$sumInsured];

        $dob = $dateOfBirth instanceof CarbonInterface ? $dateOfBirth : Carbon::parse($dateOfBirth);
        $start = $tripStart instanceof CarbonInterface ? $tripStart : Carbon::parse($tripStart);
        $end = $tripEnd instanceof CarbonInterface ? $tripEnd : Carbon::parse($tripEnd);

        if ($end->lt($start)) {
            throw new InvalidArgumentException("Trip end date [{$end->toDateString()}] must be greater than or equal to trip start date [{$start->toDateString()}]");
        }

        
        $travelDays = (int) $start->diffInDays($end) + 1;
        if ($travelDays < 1) {
            $travelDays = 1;
        }

        
        $ageAtTripStart = (int) $dob->diffInYears($start);
        if ($ageAtTripStart < 0) {
            throw new InvalidArgumentException("Date of birth cannot be after trip start date");
        }

        $ageMultiplier = $this->determineAgeMultiplier($ageAtTripStart);

        
        
        $totalDaily = bcmul($baseDailyRate, (string) $travelDays, 4);

        
        $afterAge = bcmul($totalDaily, $ageMultiplier, 4);

        
        $afterDestination = bcmul($afterAge, $destinationMultiplier, 4);

        
        $finalRaw = bcmul($afterDestination, $coverageMultiplier, 4);

        
        $roundedPremium = $this->bcRound($finalRaw, 2);

        return [
            'plan_code' => $planCodeUpper,
            'sum_insured' => $sumInsured,
            'destination' => $destinationUpper,
            'travel_days' => $travelDays,
            'age_at_trip_start' => $ageAtTripStart,
            'base_daily_rate' => $baseDailyRate,
            'age_multiplier' => $ageMultiplier,
            'destination_multiplier' => $destinationMultiplier,
            'coverage_multiplier' => $coverageMultiplier,
            'calculated_premium' => $roundedPremium,
        ];
    }

    

    public function determineAgeMultiplier(int $age): string
    {
        if ($age <= 17) {
            return '1.10';
        }
        if ($age <= 60) {
            return '1.00';
        }
        if ($age <= 70) {
            return '1.50';
        }
        return '2.00';
    }

    

    public function bcRound(string $number, int $precision = 2): string
    {
        if (strpos($number, '.') === false) {
            return number_format((float) $number, $precision, '.', '');
        }

        $isNegative = str_starts_with($number, '-');
        $cleanNumber = ltrim($number, '-');

        
        $addend = '0.' . str_repeat('0', $precision) . '5';
        $sum = bcadd($cleanNumber, $addend, $precision + 1);

        
        $dotPos = strpos($sum, '.');
        $result = substr($sum, 0, $dotPos + 1 + $precision);

        return ($isNegative ? '-' : '') . $result;
    }
}
