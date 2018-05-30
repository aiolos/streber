<?php

namespace App\Controller;

use App\Helpers\SVGEncoder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MapsController extends AbstractController
{
    /**
     * @Route("/view/maps/{count}")
     */
    public function maps($count)
    {
        return $this->render('views/activities/maps.html.twig', ['count' => $count]);
    }

    /**
     * @Route("/view/svg/{count}")
     */
    public function multi($count = 10)
    {
        $activities = $this->getStravaClient()->getAthleteActivities(null, null, null, $count);

        $svgEncoder = new SVGEncoder('#445000', 'beige');

        foreach ($activities as $activity) {
            $svgEncoder->addElements([$activity['id'] => $activity['map']['summary_polyline']]);
        }

        $response = new Response($svgEncoder->draw());
        $response->headers->set('Content-Type', 'image/svg+xml');

        return $response;
    }
}
