<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Helpers\GPXEncoder;
use App\Helpers\SVGEncoder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ActivityController extends AbstractController
{
    /**
     * @Route("/activities")
     */
    public function activities()
    {
        $athlete = $this->getStravaClient()->getAthlete();
        $athleteStats = $this->getStravaClient()->getAthleteStats($athlete['id']);
        $this->cache->set('strava.athlete.stats', $athleteStats);

        return $this->render('views/activities/activities.html.twig');
    }

    /**
     * @Route("/activities/list")
     * @param Request $request
     * @return JsonResponse
     * @throws \Strava\API\Exception
     */
    public function list(Request $request)
    {
        $perPage = 10;
        $page = ($request->get('start', 0) / $perPage) + 1;
        $activities = $this->getStravaClient()->getAthleteActivities(null, null, $page, $perPage);

        return new JsonResponse([
            'activities' => $activities,
            'recordsTotal' => $this->cache->get('strava.athlete.stats')['all_ride_totals']['count'],
            'recordsFiltered' => $this->cache->get('strava.athlete.stats')['all_ride_totals']['count'],
        ]);
    }

    /**
     * @Route("/activities/{activityId}")
     */
    public function activity($activityId)
    {
        $activity = $this->getStravaActivity($activityId);
        $photos = $this->getStravaPhotos($activityId);

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
            'activityId' => $activityId,
            'activityEntity' => $activityEntity,
            'kudos' => $this->getStravaClient()->getActivityKudos($activityId),
            'stream' => ['type' => 'activity', 'id' => $activityId],
            'photos' => $photos,
        ]);
    }

    /**
     * @Route("/activities/{activityId}/map")
     */
    public function map($activityId)
    {
        $activity = $this->getStravaActivity($activityId);

        return $this->render('views/activities/map.html.twig', [
            'activity' => $activity,
        ]);
    }

    /**
     * @Route("/activities/{activityId}/svg")
     */
    public function svg($activityId)
    {
        $activity = $this->getStravaActivity($activityId);

        $svg = new SVGEncoder('#445000', '#FFFFFF');
        $svg->addElements([$activity['map']['polyline']]);

        $response = new Response($svg->draw());
        $response->headers->set('Content-Type', 'image/svg+xml');

        return $response;
    }

    /**
     * @Route("/activities/{activityId}/gpx")
     * @param integer $activityId
     * @return Response
     * @throws \Strava\API\Exception
     */
    public function getGpx($activityId)
    {
        $activity = $this->getStravaActivity($activityId);

        $fileName = substr($activity['start_date_local'], 0, 10) . '-' . str_replace([' ', ','], '', ucwords($activity['name']));

        $response = new Response();
        $response->setContent(GPXEncoder::createGPX($activity['map']['polyline'], $activity['name']));
        $response->headers->set('Content-Type', 'application/gpx+xml');
        $response->headers->set('Content-Disposition', "attachment; filename=" . $fileName . ".gpx");

        return $response;
    }

    /**
     * @Route("/activities/{activityId}/flush")
     * @param integer $activityId
     * @return Response
     * @throws \Strava\API\Exception
     */
    public function clearActivityCache($activityId)
    {
        $this->cache->deleteItem('strava.photos.' . $activityId);
        $this->cache->deleteItem('strava.activity.' . $activityId);

        return $this->redirect('/activities/' . $activityId);
    }
}
