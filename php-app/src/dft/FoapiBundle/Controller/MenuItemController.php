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
        // _GET values.
        $query = $this->container->get("request")->query;
        // Get the menu item service, that provides relevant logic.
        $menuItemService = $this->container->get('dft_foapi.menu_item');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $menuItemService->fetchAll(
                        $this->container->get('dft_foapi.login')->getAuthenticatedUserId(),
                        array(
                            "category_id" => $query->get('category_id'),
                            "item_name" => $query->get('item_name'),
                            "start" => $query->get('start'),
                            "limit" => $query->get('limit')
                        )
                 )
            )
        );
    }
}
