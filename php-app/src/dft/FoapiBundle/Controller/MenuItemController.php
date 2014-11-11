<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MenuItemController extends Controller
{
    public function indexAction()
    {
        // TODO: Implement.
        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }

    public function listAction()
    {
        // Get the menu item service, that provides relevant logic.
        $menuItemService = $this->container->get('dft_foapi.menu_item');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $menuItemService->fetchAll(
                        $this->container->get('dft_foapi.login')->getAuthenticatedUserId()
                 )
            )
        );
    }
}
