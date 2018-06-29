<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;

class AthleteController extends AbstractController
{
    /**
     * @Route("/view/athlete/{athleteId}")
     */
    public function athlete($athleteId = null)
    {
        if (strlen($this->getUser()->getStravaToken()) === 0) {
            return $this->redirect('/connect');
        }
        $athlete = $this->getStravaClient()->getAthlete($athleteId);

        $athleteStats = $this->getStravaClient()->getAthleteStats($athlete['id']);

        return $this->render('views/athlete.html.twig', [
            'athlete' => $athlete,
            'athleteStats' => $athleteStats
        ]);
    }

    /**
     * @Route("/view/profile")
     */
    public function profile($athleteId = null)
    {
        $athlete = $this->getStravaClient()->getAthlete($athleteId);

        return $this->render('views/profile.html.twig', [
            'athlete' => $athlete,
        ]);
    }
}
