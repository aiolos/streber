<?php

namespace App\Controller;

use App\Entity\ActivityGroup;
use App\Entity\Post;
use App\Helpers\GPXEncoder;
use App\Repository\PostRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VoiesController extends AbstractController
{
    /**
     * @Route("/voies-vertes")
     */
    public function index(Request $request)
    {
        return $this->render('views/voies/index.html.twig');
    }

    /**
     * @Route("/voies-vertes/map/{itineraire}")
     */
    public function map(Request $request, string $itineraire)
    {
        $features = $this->getAllFeatures();

        $featuresFiltered = $this->filterFeatures($features, $itineraire);

        return $this->render('views/voies/map.html.twig', [
            'features' => json_encode($featuresFiltered),
            'feature' => json_encode(array_pop($featuresFiltered)),
            'itineraire' => $itineraire,
        ]);
    }

    /**
     * @Route("/voies-vertes/gpx/{itineraire}")
     */
    public function gpx(Request $request, string $itineraire)
    {
        $features = $this->getAllFeatures();

        $featuresFiltered = $this->filterFeatures($features, $itineraire);

        $fileName = $itineraire;

        $response = new Response();
        $response->setContent(GPXEncoder::createGPXfromCoordinates($itineraire, $featuresFiltered));
        $response->headers->set('Content-Type', 'application/gpx+xml');
        $response->headers->set('Content-Disposition', "attachment; filename=" . $fileName . ".gpx");

        return $response;
    }

    /**
     * @Route("/voies-vertes/itineraire/{itineraire}")
     */
    public function itineraire(Request $request, string $itineraire)
    {
        $features = $this->getAllFeatures();

        $featuresFiltered = $this->filterFeatures($features, $itineraire);

        return $this->render('views/voies/itineraire.html.twig', [
            'features' => $featuresFiltered,
            'itineraire' => $itineraire,
        ]);
    }

    /**
     * @Route("/voies-vertes/list")
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request)
    {
        $features = $this->getAllFeatures();
        $featuresFiltered = [];
        $itineraires = [];
        foreach ($features as $feature) {
            if (array_key_exists('ITINERAIRE', $feature['properties'])
                && array_key_exists('QRC', $feature['properties'])
                && $feature['properties']['ITINERAIRE'] !== null
                && !in_array($feature['properties']['ITINERAIRE'], $itineraires)
            ) {
                $itineraires[] = $feature['properties']['ITINERAIRE'];
                $featuresFiltered[] = [
                    'name' => $feature['properties']['name'],
                    'itineraire' => $feature['properties']['ITINERAIRE'],
                    'qrc' => $feature['properties']['QRC'],
                    'coordinates' => count($feature['geometry']['coordinates']),
                ];
            }
        }

        return new JsonResponse([
            'features' => $featuresFiltered,
            'recordsTotal' => count($featuresFiltered),
            'recordsFiltered' => count($featuresFiltered),
        ]);
    }

    /**
     * @Route("/voies-vertes/list/{itineraire}")
     * @param Request $request
     * @return JsonResponse
     */
    public function listItineraire(Request $request, string $itineraire)
    {
        $features = $this->getAllFeatures();
        $features = $this->filterFeatures($features, $itineraire);
        $featuresFiltered = [];
        foreach ($features as $feature) {
            $featuresFiltered[] = [
                'name' => $feature['properties']['name'],
                'itineraire' => $feature['properties']['ITINERAIRE'],
                'qrc' => $feature['properties']['QRC'],
                'coordinates' => count($feature['geometry']['coordinates']),
            ];
        }

        return new JsonResponse([
            'features' => $featuresFiltered,
            'recordsTotal' => count($featuresFiltered),
            'recordsFiltered' => count($featuresFiltered),
        ]);
    }

    private function getAllFeatures()
    {
        if (!$this->session->has('features')) {
            $features = [];
            $files = scandir(__DIR__ . '/../../data/voies-vertes/');
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $json = json_decode(file_get_contents(__DIR__ . '/../../data/voies-vertes/' . $file), true);
                $features = array_merge($features, $json['features']);
            }
            $this->session->set('features', $features);
        }

        return $this->session->get('features');
    }

    private function filterFeatures($features, $itineraire)
    {
        return array_filter($features, function ($element) use ($itineraire) {
            if (array_key_exists('ITINERAIRE', $element['properties'])
                && array_key_exists('QRC', $element['properties'])
                && strtolower($element['properties']['ITINERAIRE']) == strtolower($itineraire)
            ) {
                return true;
            }
        });
    }
}
