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
        $athlete = $this->getStravaClient()->getAthlete($athleteId);

        return $this->render('views/athlete.html.twig', [
            'athlete' => $athlete,
        ]);
    }
}
