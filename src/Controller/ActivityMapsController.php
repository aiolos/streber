<?php

namespace App\Controller;

use App\Entity\ActivityMap;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ActivityMapsController extends AbstractController
{
    /**
     * @Route("/maps")
     */
    public function maps()
    {
        $maps = $this->getEntityManager()->getRepository(ActivityMap::class)->findAll();

        return $this->render('views/maps/maps.html.twig', [
            'maps' => $maps,
        ]);
    }

    /**
     * @Route("/maps/view/{mapId}")
     */
    public function view($mapId)
    {
        $map = $this->getEntityManager()->getRepository(ActivityMap::class)->find($mapId);

        return $this->render('views/maps/view.html.twig', [
            'map' => $map,
        ]);
    }

    /**
     * @Route("/maps/add")
     */
    public function add(Request $request)
    {
        $group = new ActivityMap();

        $form = $this->buildForm($group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $group = $form->getData();
            $this->getEntityManager()->persist($group);
            $this->getEntityManager()->flush();

            return $this->redirect('/maps');
        }

        return $this->render('views/maps/add.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/maps/edit/{mapId}")
     */
    public function edit(Request $request, $mapId)
    {
        $map = $this->getEntityManager()->getRepository(ActivityMap::class)->find($mapId);

        $form = $this->buildForm($map);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $map = $form->getData();

            $this->getEntityManager()->persist($map);
            $this->getEntityManager()->flush();

            return $this->redirect('/maps/view/' . $mapId);
        }

        return $this->render('views/maps/edit.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    private function buildForm(&$group)
    {
        return $this->createFormBuilder($group)
            ->add('name', TextType::class)
            ->add('slug', TextType::class)
            ->add('save', SubmitType::class, array('label' => 'Opslaan'))
            ->getForm();
    }
}
