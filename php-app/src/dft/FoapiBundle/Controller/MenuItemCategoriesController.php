<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MenuItemCategoriesController extends Controller
{
    public function listAction()
    {
        // Get the menu item categories service.
        $menuItemCategoriesService = $this->container->get('dft_foapi.menu_item_categories');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $menuItemCategoriesService->fetchAll()
            )
        );
    }
}
