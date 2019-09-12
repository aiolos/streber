<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TimeFormatExtension extends AbstractExtension
{
    public function getFilters()
    {
        return array(
            new TwigFilter('time_format_long', function ($inputSeconds) {
                return $this->timeFormatFilter($inputSeconds);
            }),
            new TwigFilter('time_format_short', function ($inputSeconds) {
                return $this->timeFormatShortFilter($inputSeconds);
            }),
        );
    }

    public function timeFormatFilter($inputSeconds)
    {
        $days = floor($inputSeconds / (3600 * 24));
        $hours = floor(($inputSeconds % (3600 * 24)) / 3600);
        $minutes = floor(($inputSeconds % 3600) / 60);
        $seconds = $inputSeconds % 60;
        return ($days !== 0.0 ? $days . 'd ' : '')
            . ($days || $hours ? $hours . 'u ' : '')
            . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) . 'm '
            . str_pad((string) $seconds, 2, '0', STR_PAD_LEFT) . 's';
    }

    public function timeFormatShortFilter($inputSeconds)
    {
        $hours = floor($inputSeconds / 3600);
        $minutes = floor(($inputSeconds % 3600) / 60);
        $seconds = $inputSeconds % 60;
        return ($hours !== 0.0 ? $hours . ':' : '')
            . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) . ':'
            . str_pad((string) $seconds, 2, '0', STR_PAD_LEFT);
    }
}
