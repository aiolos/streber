<?php

namespace App\Controller;

use App\Helpers\SVGEncoder;
use DateTime;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MapsController extends AbstractController
{
    /**
     * @Route("/maps/{count}")
     */
    public function maps($count)
    {
        return $this->render('views/activities/maps.html.twig', ['count' => $count]);
    }

    /**
     * @Route("/svg/{count}")
     */
    public function multi($count = 10)
    {
        $activities = [];
        $page = 1;
        while ($count > 0) {
            $count -= 200;
            $newActivities = $this->getStravaClient()->getAthleteActivities(null, null, $page, 200);
            if ($count < 0) {
                $newActivities = array_slice($newActivities, 0, (int) (200 + $count));
            }
            $activities = array_merge($activities, $newActivities);
            $page++;
        }

        return $this->createSvg($activities);
    }

    /**
     * @Route("/year/{year}")
     */
    public function year($year = null)
    {
        if (is_null($year)) {
            $year = date('Y');
        }

        return $this->render('views/activities/year.html.twig', ['year' => $year]);
    }

    /**
     * @Route("/svg/year/{year}")
     */
    public function yearSvg($year = null)
    {
        if (is_null($year)) {
            $year = date('Y');
        }

        $beginYearTimestamp = DateTime::createFromFormat('Y-m-d', $year . '-01-01');
        $endYearTimestamp = DateTime::createFromFormat('Y-m-d', $year + 1 . '-01-01');
        if ($beginYearTimestamp === false || $endYearTimestamp === false) {
            throw new \Exception('Something went wrong in creating the begin/end year timestamp');
        }
        $activities = $this->getStravaClient()->getAthleteActivities(
            $endYearTimestamp->getTimestamp(),
            $beginYearTimestamp->getTimestamp(),
            1,
            200
        );
        $activities = array_reverse($activities);

        return $this->createSvg($activities);
    }

    private function createSvg($activities)
    {
        $svgEncoder = new SVGEncoder('#445000', 'beige');

        foreach ($activities as $activity) {
            $svgEncoder->addElements([$activity['id'] => $activity['map']['summary_polyline']]);
        }

        $response = new Response($svgEncoder->draw());
        $response->headers->set('Content-Type', 'image/svg+xml');

        return $response;
    }
}
