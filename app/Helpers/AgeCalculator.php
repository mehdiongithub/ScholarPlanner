<?php

namespace App\Helpers;

use DateTime;

class AgeCalculator {
    /**
     * Dynamically calculate age from date of birth.
     */
    public static function calculateAge(?string $dob): ?int {
        if (empty($dob)) {
            return null;
        }

        try {
            $birthDate = new DateTime($dob);
            $today = new DateTime('today');
            return $birthDate->diff($today)->y;
        } catch (\Exception $e) {
            return null;
        }
    }
}
