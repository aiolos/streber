<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\ActivityGroup;
use App\Entity\Post;
use App\Helpers\SVGEncoder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ActivityGroupsController extends AbstractController
{
    /**
     * @Route("/groups")
     */
    public function groups()
    {
        $groups = $this->getEntityManager()->getRepository(ActivityGroup::class)->findAll();

        return $this->render('views/groups/groups.html.twig', [
            'groups' => $groups,
        ]);
    }

    /**
     * @Route("/groups/view/{groupId}")
     */
    public function view($groupId)
    {
        $group = $this->getEntityManager()->getRepository(ActivityGroup::class)->find($groupId);

        return $this->render('views/groups/view.html.twig', [
            'group' => $group,
        ]);
    }

    /**
     * @Route("/groups/add")
     */
    public function add(Request $request)
    {
        /** @var Activity $activity */
        $group = new ActivityGroup();

        $form = $this->buildForm($group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $group = $form->getData();
            $this->getEntityManager()->persist($group);
            $this->getEntityManager()->flush();

            return $this->redirect('/groups');
        }

        return $this->render('views/groups/add.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/groups/edit/{groupId}")
     */
    public function edit(Request $request, $groupId)
    {
        $group = $this->getEntityManager()->getRepository(ActivityGroup::class)->find($groupId);

        $form = $this->buildForm($group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $group = $form->getData();

            $this->getEntityManager()->persist($group);
            $this->getEntityManager()->flush();

            return $this->redirect('/groups/view/' . $groupId);
        }

        return $this->render('views/groups/edit.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    private function buildForm(&$group)
    {
        $form = $this->createFormBuilder($group)
            ->add('title', TextType::class)
            ->add('slug', TextType::class)
            ->add('description', TextareaType::class, ['attr' => ['rows' => 10]])
            ->add('date', DateType::class, ['widget' => 'single_text'])
            ->add('save', SubmitType::class, array('label' => 'Opslaan'))
            ->getForm();

        return $form;
    }
}
