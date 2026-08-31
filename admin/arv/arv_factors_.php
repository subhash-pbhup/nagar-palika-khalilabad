<?php


class ARVFactors {
    public static function getBaseRate($rates, $road_width, $const_type) {
        $type = strtoupper(trim($const_type ?? 'RCC'));
        $rw = floatval($road_width);

        // Excel headers mapping
        if ($rw < 9) {
            $col = ($type == 'RCC') ? 'rcc_less_9' : (($type == 'ACC') ? 'acc_less_9' : 'other_less_9');
        } elseif ($rw >= 9 && $rw <= 12) {
            $col = ($type == 'RCC') ? 'rcc_9_12' : (($type == 'ACC') ? 'acc_9_12' : 'other_9_12');
        } elseif ($rw > 12 && $rw <= 24) {
            $col = ($type == 'RCC') ? 'rcc_12_24' : (($type == 'ACC') ? 'acc_12_24' : 'other_12_24');
        } else {
            $col = ($type == 'RCC') ? 'rcc_above_24' : (($type == 'ACC') ? 'acc_above_24' : 'other_above_24');
        }

        return isset($rates[$col]) ? floatval($rates[$col]) : 0.0;
    }

    public static function getOccupancyFactor($type) {
        // Self Occupied = 0.80 multiplier
        return (stripos($type ?? '', 'self') !== false) ? 0.80 : 1.0;
    }

    public static function getAgeFactor($age) {
        // Standard building age multiplier
        return 0.80;
    }

    public static function applyResidentialDeduction($arv, $usage, $const) {
        // 25% discount for Residential + RCC (Porch/Washroom area)
        if (strtoupper(trim($usage ?? '')) == 'RESIDENTIAL' && strtoupper(trim($const ?? '')) == 'RCC') {
            return $arv * 0.75;
        }
        return $arv;
    }
}