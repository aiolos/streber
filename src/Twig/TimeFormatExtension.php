<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TimeFormatExtension extends AbstractExtension
{
    public function getFilters()
    {
        return array(
            new TwigFilter('time_format_long', array($this, 'timeFormatFilter')),
            new TwigFilter('time_format_short', array($this, 'timeFormatShortFilter')),
        );
    }

    public function timeFormatFilter($inputSeconds)
    {
        $hours = floor($inputSeconds / 3600);
        $minutes = floor(($inputSeconds % 3600) / 60);
        $seconds = $inputSeconds % 60;
        return ($hours ? $hours . 'u ' : '')
            . str_pad($minutes, 2, '0', STR_PAD_LEFT) . 'm '
            . str_pad($seconds, 2, '0', STR_PAD_LEFT) . 's';
    }

    public function timeFormatShortFilter($inputSeconds)
    {
        $hours = floor($inputSeconds / 3600);
        $minutes = floor(($inputSeconds % 3600) / 60);
        $seconds = $inputSeconds % 60;
        return ($hours ? $hours . ':' : '')
            . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ':'
            . str_pad($seconds, 2, '0', STR_PAD_LEFT);
    }
}