<?php

namespace dft\FoapiBundle\Controller;

class MenuItemCategoriesController extends BaseController
{
    public function listAction()
    {
        // _GET values.
        $query = $this->container->get("request")->query;

        // Get the menu item categories service.
        $menuItemCategoriesService = $this->container->get('dft_foapi.menu_item_categories');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $menuItemCategoriesService->fetchAll(
                        $this->getAuthenticatedUserIdAndSubAccountIds(),
                        array(
                            // Optional non_empty parameter.
                            "non_empty" => $query->get('non_empty')
                        )
                    )
            )
        );
    }
}
