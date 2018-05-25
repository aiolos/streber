<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;

class ActivityController extends AbstractController
{
    /**
     * @Route("/view/activities")
     */
    public function activities()
    {
        $activities = $this->getStravaClient()->getAthleteActivities();

        return $this->render('views/activities/activities.html.twig', [
            'activities' => $activities,
        ]);
    }

    /**
     * @Route("/view/activities/{activityId}")
     */
    public function activity($activityId)
    {
        $activity = $this->getStravaClient()->getActivity($activityId);

        return $this->render('views/activities/activity.html.twig', [
            'activity' => $activity,
            'kudos' => $this->getStravaClient()->getActivityKudos($activityId),
        ]);
    }

    /**
     * @Route("/view/activities/{activityId}/map")
     */
    public function map($activityId)
    {
        $activity = $this->getStravaClient()->getActivity($activityId);

        return $this->render('views/activities/map.html.twig', [
            'activity' => $activity,
//            'map' => $activity['map']['polyline'],
        ]);
    }
}
