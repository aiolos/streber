<?php

namespace App\Helpers;

use phpGPX\Models\GpxFile;
use phpGPX\Models\Link;
use phpGPX\Models\Metadata;
use phpGPX\Models\Point;
use phpGPX\Models\Segment;
use phpGPX\Models\Track;

/**
 * PHP GPX Encoder
 * Using GPX Encoding from https://github.com/Sibyx/phpGPX
 */
class GPXEncoder
{
    public static function createGPX(string $polyline, string $title): string
    {
        $link = new Link();
        $link->href = "https://spiritus-santos.nl";
        $link->text = 'Blijven Trappen';
        $gpx_file = new GpxFile();
        $gpx_file->metadata = new Metadata();
        $gpx_file->metadata->time = new \DateTime();
        $gpx_file->metadata->description = "This file is generated from a blog on https://spiritus-santos.nl";
        $gpx_file->metadata->links[] = $link;
        $track = new Track();
        $track->name = sprintf($title);
        $track->type = 'RIDE';
        $track->source = sprintf("Garmin Edge 1000");
        $segment = new Segment();

        $coordinates = PolylineEncoder::decodeValue($polyline);
        array_map(function ($element) use ($segment) {
            $point = new Point(Point::TRACKPOINT);
            $point->latitude = $element['x'];
            $point->longitude = $element['y'];

            $segment->points[] = $point;
        }, $coordinates);
        $track->segments[] = $segment;
        $track->recalculateStats();
        $gpx_file->tracks[] = $track;

        return $gpx_file->toXML()->saveXML();
    }

    public static function createGPXfromCoordinates(string $title, array $features): string
    {
        $link = new Link();
        $link->href = "https://spiritus-santos.nl";
        $gpx_file = new GpxFile();
        $gpx_file->metadata = new Metadata();
        $gpx_file->metadata->time = new \DateTime();
        $gpx_file->metadata->description = "This file is generated from a blog on https://spiritus-santos.nl";
        $gpx_file->metadata->links[] = $link;
        foreach ($features as $feature) {
            $track = new Track();
            $track->name = sprintf($title);
            $track->type = 'RIDE';
            $track->source = sprintf("");
            $segment = new Segment();

            array_map(function ($element) use ($segment) {
                $point = new Point(Point::TRACKPOINT);
                if (is_array($element[0])) {
                    foreach ($element as $arrayElement) {
                        $point = new Point(Point::TRACKPOINT);
                        $point->longitude = $arrayElement[0];
                        $point->latitude = $arrayElement[1];

                        $segment->points[] = $point;
                    }
                } else {
                    $point->longitude = $element[0];
                    $point->latitude = $element[1];

                    if (strlen($point->latitude) && strlen($point->longitude)) {
                        $segment->points[] = $point;
                    }
                }
            }, $feature['geometry']['coordinates']);
            $track->segments[] = $segment;

            $track->recalculateStats();
            $gpx_file->tracks[] = $track;
        }

        return $gpx_file->toXML()->saveXML();
    }
}
