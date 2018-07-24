<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;

class SegmentController extends AbstractController
{
    /**
     * @Route("/segments")
     */
    public function segments()
    {
        $activity = $this->getStravaClient()->getAthleteStarredSegments();

        return $this->render('views/segments/segments.html.twig', [
            'activity' => $activity,
        ]);
    }

    /**
     * @Route("/segments/{segmentId}")
     */
    public function segment($segmentId)
    {
        $segment = $this->getStravaClient()->getSegment($segmentId);

        return $this->render('views/segments/segment.html.twig', [
            'segment' => $segment,
            'efforts' => $this->getStravaClient()->getSegmentEffort($segmentId),
            'stream' => ['type' => 'segment', 'id' => $segmentId],
        ]);
    }

    /**
     * @Route("/segments/{segmentId}/effort/{effortId}")
     */
    public function effort($segmentId, $effortId)
    {
        $segment = $this->getStravaClient()->getSegment($segmentId);
        $efforts = $this->getStravaClient()->getSegmentEffort($segmentId);
        $efforts = array_filter($efforts, function ($element) use ($effortId)  {
            return ($element['id'] == $effortId);
        });

        return $this->render('views/segments/effort.html.twig', [
            'segment' => $segment,
            'effort' => $efforts[0],
            'stream' => ['type' => 'segmenteffort', 'id' => $effortId],
        ]);
    }
}
