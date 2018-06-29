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
}
