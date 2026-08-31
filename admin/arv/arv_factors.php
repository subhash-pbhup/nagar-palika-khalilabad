<?php

function getOccupancyFactor($type)
{
    return ($type === 'Rented') ? 1.0 : 0.7;
}

function getUsageFactor($type)
{
    if ($type === 'Commercial') return 1.5;
    if ($type === 'Industrial') return 2.0;
    return 1.0; // Residential
}

function getConstructionFactor($type)
{
    if ($type === 'RCC') return 1.0;
    if ($type === 'Asbestos') return 0.8;
    return 0.7;
}

function getTimeFactor($from, $to)
{
    if (!$from || !$to) return 1;

    $months = (strtotime($to) - strtotime($from)) / (30 * 86400);
    return max(0.1, min(1, $months / 12));
}
?>