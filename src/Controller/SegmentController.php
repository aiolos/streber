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
        $streams = $this->getStreams('segment', $segmentId);

        return $this->render('views/segment.html.twig', [
            'segment' => $segment,
            'efforts' => $this->getStravaClient()->getSegmentEffort($segmentId),
            'streams' => $streams,
        ]);
    }

    /**
     * @Route("/view/segments/{segmentId}/effort/{effortId}")
     */
    public function effort($segmentId, $effortId)
    {
        $segment = $this->getStravaClient()->getSegment($segmentId);
        $efforts = $this->getStravaClient()->getSegmentEffort($segmentId);
        $efforts = array_filter($efforts, function ($element) use ($effortId)  {
            return ($element['id'] == $effortId);
        });
        $streams = $this->getStreams('segmenteffort', $effortId);

        return $this->render('views/effort.html.twig', [
            'segment' => $segment,
            'effort' => $efforts[0],
            'streams' => $streams,
        ]);
    }
}
