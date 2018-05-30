<?php
/**
 * Source: https://github.com/emcconville/google-map-polyline-encoding-tool/blob/master/examples/EncodedToSVG/EncodedToSVG.php
 */
namespace App\Helpers;

use DOMAttr;
use DOMDocument;

class SVGEncoder extends \Polyline
{
    protected $backgroundColor;
    protected $lineColor;
    protected $dom;
    protected $root;
    protected $definitions = [];

    const DEF_SIZE = 800;

    public function __construct(string $lineColor = 'lightblue', string $backgroundColor = 'beige')
    {
        $this->lineColor = $lineColor;
        $this->backgroundColor = $backgroundColor;

        $this->dom = new DOMDocument('1.0', 'utf-8');
        $this->dom->formatOutput = true;

        $this->root = $this->dom->createElementNS('http://www.w3.org/2000/svg', 'svg');
        $this->root->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:xlink', 'http://www.w3.org/1999/xlink');
        $this->root->appendChild(new DomAttr('version', '1.2'));
        $this->root->appendChild(new DomAttr('viewport-fill', $this->backgroundColor));
        $this->root->appendChild(new DomAttr('style', 'background-color:' . $this->backgroundColor . ';'));
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
     * Add elements to the definitions array.
     *
     * @param array $encodedElements
     */
    public function addElements(array $encodedElements)
    {
        foreach ($encodedElements as $id => $encodedElement) {
            $decoded = self::decodeElement($encodedElement);

            $svg = $this->dom->createElement('svg');
            $svg->appendChild(new DomAttr('id', $id));
            $svg->appendChild(new DomAttr('width', self::DEF_SIZE . 'px'));
            $svg->appendChild(new DomAttr('height', self::DEF_SIZE . 'px'));
            $svg->appendChild(new DomAttr('viewBox', $decoded['viewbox']));
            $svg->appendChild(new DomAttr('viewport-fill', $this->backgroundColor));
            $svg->appendChild(new DomAttr('style', 'background-color:' . $this->backgroundColor . ';'));

            // Group
            $group = $this->dom->createElement('g');
            $group->appendChild(new DomAttr('stroke', $this->lineColor));
            $group->appendChild(new DomAttr('stroke-width', '0.5%'));
            $group->appendChild(new DomAttr('fill', $this->backgroundColor));
            // Path
            $path = $this->dom->createElement('path');
            $path->appendChild(new DomAttr('d', $decoded['path']));
            // Pull it all together
            $group->appendChild($path);
            $svg->appendChild($group);

            $this->definitions[] = ['id' => $id, 'svg' => $svg];
        }
    }

    /**
     * Draw the SVG from the definitions
     *
     * @return string
     */
    public function draw()
    {
        $defs = $this->dom->createElement('defs');
        $totalDefs = count($this->definitions);
        $cols = (ceil(sqrt($totalDefs)));

        $this->root->appendChild(new DomAttr(
            'viewBox',
            '0 0 '
            . $cols * self::DEF_SIZE . ' '
            . round(sqrt($totalDefs)) * self::DEF_SIZE
        ));

        $count = 0;
        foreach ($this->definitions as $definition) {
            $defs->appendChild($definition['svg']);
            $use = $this->dom->createElement('use');
            $use->appendChild(new DomAttr('xlink:href', '#' . $definition['id']));

            $use->appendChild(new DomAttr('x', ($count % $cols) * self::DEF_SIZE . 'px'));
            $use->appendChild(new DomAttr('y', floor($count / $cols ) * self::DEF_SIZE .'px'));
            $count++;
            $this->root->appendChild($use);
        }

        $this->root->appendChild($defs);

        $this->dom->appendChild($this->root);

        return $this->dom->saveXML();
    }

    /**
     * Decode the Polyline to coordinate SVG path
     *
     * @param $encoded
     * @return array
     */
    private static function decodeElement($encoded)
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

        // Create viewBox from MBR points
        $mbr =  sprintf(
            "%f %f %f %f",
            $minX,
            $minY,
            abs($maxX - $minX),
            abs($maxY - $minY)
        );

        return ['path' => $path, 'viewbox' => $mbr];
    }
}
