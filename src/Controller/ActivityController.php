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

        return $this->render('views/activities.html.twig', [
            'activities' => $activities,
        ]);
    }

    /**
     * @Route("/view/activities/{activityId}")
     */
    public function activity($activityId)
    {
        $activity = $this->getStravaClient()->getActivity($activityId);

        return $this->render('views/activity.html.twig', [
            'activity' => $activity,
            'kudos' => $this->getStravaClient()->getActivityKudos($activityId),
        ]);
    }
}
