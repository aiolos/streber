<?php
/**
 * Source: https://github.com/emcconville/google-map-polyline-encoding-tool/blob/master/examples/EncodedToSVG/EncodedToSVG.php
 */
namespace App\Helpers;

use DOMAttr;
use DOMDocument;

class SVGEncoder extends \Polyline
{
    /**
     * Decode string and generate a full SVG document.
     *
     * @param string $encoded - Encoded polyline
     * @param string $lineColor
     * @param string $backgroundColor
     *
     * @return string - SVG document
     *
     * @uses DOMDocument
     */
    public static function decodeToSVG($encoded, $lineColor = 'lightblue', $backgroundColor = 'beige')
    {
        // Create list of points
        $points = parent::decode($encoded);
        // Grab first pair
        list($x, $y) = self::_shiftPoint($points);
        // Path will need to start by moving to first coordinate.
        $path = sprintf('M %f %f L ', $x, $y);
        // Init bounding box's min & max.
        $minX = $maxX = $x;
        $minY = $maxY = $y;
        while ($points) {
            list($x, $y) = self::_shiftPoint($points);
            $path .= sprintf('%f %f, ', $x, $y);
            // Grow MBR
            if ($x < $minX) {
                $minX = $x;
            }
            if ($y < $minY) {
                $minY = $y;
            }
            if ($x > $maxX) {
                $maxX = $x;
            }
            if ($y > $maxY) {
                $maxY = $y;
            }
        }

        // Close poylgon (not in case of Strava activities)
        //$path = rtrim($path, ', ');
        //$path .= ' Z';

        // Create viewBox from MBR points
        $mbr =  sprintf(
            "%f %f %f %f",
            $minX,
            $minY,
            abs($maxX - $minX),
            abs($maxY - $minY)
        );

        return self::_generateSVG($path, $mbr, $lineColor, $backgroundColor);
    }
    /**
     * Shift point tuple from start of list.
     *
     * Remember that latitude is Y, and longitude is X on the coordinate system.
     * Depending on your data set, you may need to adjust signing to match
     * hemispheres.
     *
     * @param array &$points - Reference to list
     *
     * @return array - Tuple of (x, y)
     */
    private static function _shiftPoint( &$points )
    {
        $y = array_shift($points);
        $x = array_shift($points);
        return array( $x, $y * -1 );
    }

    /**
     * Turn path & MBR into a valid SVG string
     *
     * @param string $pathData - Raw line data to path element
     * @param string $viewBox - The known image MBR
     * @param $lineColor
     * @param $backgroundColor
     *
     * @return string - SVG
     */
    private static function _generateSVG($pathData, $viewBox, $lineColor, $backgroundColor)
    {
        // Build XML
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;
        // Root
        $root = $dom->createElementNS('http://www.w3.org/2000/svg', 'svg');
        $root->appendChild(new DomAttr('version', '1.2'));
        $root->appendChild(new DomAttr('viewBox', $viewBox));
        $root->appendChild(new DomAttr('viewport-fill', $backgroundColor));
        $root->appendChild(new DomAttr('style', 'background-color:' . $backgroundColor . ';'));
        // Group
        $g = $dom->createElement('g');
        $g->appendChild(new DomAttr('stroke', $lineColor));
        $g->appendChild(new DomAttr('stroke-width', '0.25%'));
        $g->appendChild(new DomAttr('fill', $backgroundColor));
        // Path
        $p = $dom->createElement('path');
        $p->appendChild(new DomAttr('d', $pathData));
        // Pull it all together
        $g->appendChild($p);
        $root->appendChild($g);
        $dom->appendChild($root);
        return $dom->saveXML();
    }
}