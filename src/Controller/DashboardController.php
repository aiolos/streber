<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function index()
    {
        return $this->render('views/dashboard/index.html.twig');
    }

    /**
     * @Route("/connect")
     */
    public function connect()
    {
        try {
            if (!isset($_GET['code'])) {
                return $this->render(
                    'views/dashboard/connect.html.twig',
                    [
                        'url' => $this->getOAuth()->getAuthorizationUrl([
                            'scope' => [
                                'public',
                                // 'write',
                                // 'view_private',
                            ]
                        ])
                    ]
                );
            } else {
                $token = $this->getOAuth()->getAccessToken('authorization_code', [
                    'code' => $_GET['code']
                ]);
                $this->session->set('strava_code', $_GET['code']);
                $this->session->set('strava_token', $token);

                return $this->render('views/dashboard/success.html.twig');
            }
        } catch(Exception $e) {
            return new Response($e->getMessage());
        }
    }
}
