<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;

class SegmentController extends AbstractController
{
    /**
     * @Route("/view/segments")
     */
    public function segments()
    {
        $activity = $this->getStravaClient()->getAthleteStarredSegments();

        return $this->render('views/segments.html.twig', [
            'activity' => $activity,
        ]);
    }

    /**
     * @Route("/view/segments/{segmentId}")
     */
    public function segment($segmentId)
    {
        $segment = $this->getStravaClient()->getSegment($segmentId);

        return $this->render('views/segment.html.twig', [
            'segment' => $segment,
        ]);
    }
}
