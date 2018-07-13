<?php

namespace App\Controller;

use Strava\API\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{

    /**
     * @Route("/api/athlete/{athleteId}")
     */
    public function athlete($athleteId = null)
    {
        try {
            $athlete = $this->getStravaClient()->getAthlete($athleteId);

            $athleteStats = $this->getStravaClient()->getAthleteStats($athlete['id']);

            return new JsonResponse([
                'athlete' => $athlete,
                'athleteStats' => $athleteStats
            ]);
        } catch(Exception $e) {
            print $e->getMessage();
        }
    }

    /**
     * @Route("/api/followers/{athleteId}")
     */
    public function followers($athleteId = null)
    {
        try {
            $athlete = $this->getStravaClient()->getAthleteFollowers($athleteId, 1, 20);

            return new JsonResponse([
                'athlete' => $athlete,
            ]);
        } catch(Exception $e) {
            print $e->getMessage();
        }
    }

    /**
     * @Route("/api/stats/{athleteId}")
     */
    public function stats($athleteId = null)
    {
        try {
            $stats = $this->getStravaClient()->getAthleteStats($athleteId);

            return new JsonResponse([
                'stats' => $stats,
            ]);
        } catch(Exception $e) {
            print $e->getMessage();
        }
    }

    /**
     * @Route("/api/activities")
     */
    public function activities()
    {
        try {
            $activities = $this->getStravaClient()->getAthleteActivities();

            return new JsonResponse([
                'activities' => $activities,
            ]);
        } catch(Exception $e) {
            print $e->getMessage();
        }
    }

    /**
     * @Route("/api/activities/{activityId}")
     */
    public function activity($activityId)
    {
        try {
            $activity = $this->getStravaClient()->getActivity($activityId);

            return new JsonResponse([
                'activity' => $activity,
                'kudos' => $this->getStravaClient()->getActivityKudos($activityId),
            ]);
        } catch(Exception $e) {
            print $e->getMessage();
        }
    }

    /**
     * @Route("/api/segments/{segmentId}")
     */
    public function segment($segmentId)
    {
        try {
            $segment = $this->getStravaClient()->getSegment($segmentId);

            return new JsonResponse([
                'segment' => $segment,
                'efforts' => $this->getStravaClient()->getSegmentEffort($segmentId),
            ]);
        } catch(Exception $e) {
            print $e->getMessage();
        }
    }

    /**
     * @Route("/api/club/{clubId}")
     */
    public function club($clubId)
    {
        try {
            $club = $this->getStravaClient()->getClub($clubId);

            return new JsonResponse([
                'club' => $club,
            ]);
        } catch(Exception $e) {
            print $e->getMessage();
        }
    }
}
