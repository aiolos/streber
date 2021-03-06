<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class ConnectController extends AbstractController
{
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
                                'read_all',
                                'profile:read_all',
                                'activity:read_all',
                            ]
                        ])
                    ]
                );
            } else {
                $token = $this->getOAuth()->getAccessToken('authorization_code', [
                    'code' => $_GET['code']
                ]);

                $user = $this->getUser();
                $user->setStravaToken($token->getToken());
                $user->setRefreshToken($token->getRefreshToken());
                $user->setTokenExpires($token->getExpires());
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();

                return $this->render('views/dashboard/success.html.twig');
            }
        } catch (\Exception $e) {
            return new Response($e->getMessage());
        }
    }
}
