<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\ActivityMap;
use App\Helpers\GPXEncoder;
use App\Helpers\SVGEncoder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
            'recordsTotal' => $this->getAthleteStats()['all_ride_totals']['count'],
            'recordsFiltered' => $this->getAthleteStats()['all_ride_totals']['count'],
        ]);
    }

    /**
     * @Route("/activities/{activityId}")
     */
    public function activity($activityId)
    {
        $activity = $this->getActivity($activityId);
        return $this->render('views/activities/activity.html.twig', [
            'activity' => $activity->getResponse(),
            'activityId' => $activityId,
            'activityEntity' => $activity,
            'kudos' => $this->getStravaClient()->getActivityKudos($activityId),
            'stream' => ['type' => 'activity', 'id' => $activityId],
            'photos' => $activity->getPhotos(),
        ]);
    }

    /**
     * @Route("/activities/{activityId}/edit")
     */
    public function edit(Request $request, $activityId)
    {
        $activity = $this->getActivity($activityId);

        $form = $this->createFormBuilder($activity)
            ->add('activityMap', EntityType::class, [
                'class' => ActivityMap::class,
                'choice_label' => 'name',
                'required' => false,
            ])
            ->add('save', SubmitType::class, array('label' => 'Opslaan'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $activity = $form->getData();

            $this->getEntityManager()->persist($activity);
            $this->getEntityManager()->flush();

            return $this->redirect('/activities/' . $activityId);
        }

        return $this->render('views/activities/edit.html.twig', array(
            'form' => $form->createView(),
        ));
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
        $this->cache->delete('strava.photos.' . $activityId);
        $this->cache->delete('strava.activity.' . $activityId);

        return $this->redirect('/activities/' . $activityId);
    }

    private function getAthleteStats(): array
    {
        return $this->cache->get('strava.athlete.stats', function () {
            $athlete = $this->getStravaClient()->getAthlete();
            return $this->getStravaClient()->getAthleteStats($athlete['id']);
        });
    }
}
