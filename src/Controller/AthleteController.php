<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;

class AthleteController extends AbstractController
{
    /**
     * @Route("/athlete/{athleteId}")
     */
    public function athlete($athleteId = null)
    {
        if (strlen($this->getUser()->getStravaToken()) === 0) {
            return $this->redirect('/connect');
        }
        $athlete = $this->getStravaClient()->getAthlete($athleteId);

        $athleteStats = $this->getStravaClient()->getAthleteStats($athlete['id']);
        $activities = $this->getStravaClient()->getAthleteActivities(null, null, null, 1);
        $activity = $this->getStravaClient()->getActivity($activities[0]['id']);

        return $this->render('views/athlete.html.twig', [
            'athlete' => $athlete,
            'athleteStats' => $athleteStats,
            'activity' => $activity,
        ]);
    }

    /**
     * @Route("/profile")
     */
    public function profile($athleteId = null)
    {
        $athlete = $this->getStravaClient()->getAthlete($athleteId);

        return $this->render('views/profile.html.twig', [
            'athlete' => $athlete,
        ]);
    }
}
