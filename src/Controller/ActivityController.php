<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Helpers\SVGEncoder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ActivityController extends AbstractController
{
    /**
     * @Route("/activities")
     */
    public function activities()
    {
        $activities = $this->getStravaClient()->getAthleteActivities();

        return $this->render('views/activities/activities.html.twig', [
            'activities' => $activities,
        ]);
    }

    /**
     * @Route("/activities/{activityId}")
     */
    public function activity($activityId)
    {
        $activity = $this->getStravaClient()->getActivity($activityId);
        $streams = $this->getStreams('activity', $activityId);
        $photos = $this->getStravaClient()->getActivityPhotos($activityId);

        $activityEntity = $this->getEntityManager()->getRepository(Activity::class)->find($activityId);
        if ($activityEntity === null) {
            $activityEntity = new Activity();
            $activityEntity->setId($activityId);
            $activityEntity->setUser($this->getUser());
            $activityEntity->setName($activity['name']);
            $this->getEntityManager()->persist($activityEntity);
            $this->getEntityManager()->flush();
        }

        return $this->render('views/activities/activity.html.twig', [
            'activity' => $activity,
            'activityEntity' => $activityEntity,
            'kudos' => $this->getStravaClient()->getActivityKudos($activityId),
            'streams' => $streams,
            'photos' => $photos,
        ]);
    }

    /**
     * @Route("/activities/{activityId}/map")
     */
    public function map($activityId)
    {
        $activity = $this->getStravaClient()->getActivity($activityId);

        return $this->render('views/activities/map.html.twig', [
            'activity' => $activity,
        ]);
    }

    /**
     * @Route("/activities/{activityId}/svg")
     */
    public function svg($activityId)
    {
        $activity = $this->getStravaClient()->getActivity($activityId);

        $svg = SVGEncoder::decodeToSVG($activity['map']['polyline'], '#445000', '#FFFFFF');

        $response = new Response($svg);
        $response->headers->set('Content-Type', 'image/svg+xml');

        return $response;
    }
}
