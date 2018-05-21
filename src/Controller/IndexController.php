<?php

namespace App\Controller;

use Strava\API\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{

    /**
     * @Route("/connect")
     */
    public function connect()
    {
        try {
            if (!isset($_GET['code'])) {
                return new Response('<a href="' . $this->getOAuth()->getAuthorizationUrl([
                        // Uncomment required scopes.
                        'scope' => [
                            'public',
                            // 'write',
                            // 'view_private',
                        ]
                    ]) . '">Connect</a>');
            } else {
                $token = $this->getOAuth()->getAccessToken('authorization_code', [
                    'code' => $_GET['code']
                ]);
                $this->session->set('strava_code', $_GET['code']);
                $this->session->set('strava_token', $token);

                return new Response($token->getToken());
            }
        } catch(Exception $e) {
            return new Response($e->getMessage());
        }
    }

    /**
     * @Route("/athlete/{athleteId}")
     */
    public function athlete($athleteId = null)
    {
        try {
            $athlete = $this->getStravaClient()->getAthlete($athleteId);

            return new JsonResponse([
                'athlete' => $athlete,
            ]);
        } catch(Exception $e) {
            print $e->getMessage();
        }
    }

    /**
     * @Route("/followers/{athleteId}")
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
     * @Route("/stats/{athleteId}")
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
     * @Route("/activities")
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
     * @Route("/activities/{activityId}")
     */
    public function activity($activityId)
    {
        try {
            $activity = $this->getStravaClient()->getActivity($activityId);

            return new JsonResponse([
                'activity' => $activity,
            ]);
        } catch(Exception $e) {
            print $e->getMessage();
        }
    }

    /**
     * @Route("/segments/{segmentId}")
     */
    public function segment($segmentId)
    {
        try {
            $segment = $this->getStravaClient()->getSegment($segmentId);

            return new JsonResponse([
                'segment' => $segment,
            ]);
        } catch(Exception $e) {
            print $e->getMessage();
        }
    }

    /**
     * @Route("/club/{clubId}")
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
