<?php

namespace dft\FoapiBundle\Controller;

class MenuItemController extends BaseController
{
    public function deleteAction($menuItemId)
    {
        // Get the menu item service.
        $menuItemService = $this->container->get('dft_foapi.menu_item');

        // Delete this entry.
        $menuItemService->deleteMenuItem(
            $this->getAuthenticatedUserIdAndSubAccountIds(),
            $menuItemId
        );

        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }

    // TODO: Return proper status codes for create, update, delete.
    public function createAction() {
        // _POST values.
        $request = $this->container->get("request");

        // Get the menu item service.
        $menuItemService = $this->container->get('dft_foapi.menu_item');

        // Delete this entry.
        // TODO: Return success false if one of these properties is missing!
        $menuItemService->createMenuItem(
            $this->container->get('dft_foapi.login')->getAuthenticatedUserId(),
            $request->get('item_number'),
            $request->get('category_id'),
            $request->get('item_name'),
            $request->get('size_id'),
            $request->get('price')
        );

        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }

    // TODO: Return proper status codes for create, update, delete.
    public function updateAction($menuItemId) {
        // _POST values.
        $request = $this->container->get("request");

        // Get the menu item service.
        $menuItemService = $this->container->get('dft_foapi.menu_item');

        // Delete this entry.
        // TODO: Return success false if one of these properties is missing!
        $menuItemService->updateMenuItem(
            $this->getAuthenticatedUserIdAndSubAccountIds(),
            $menuItemId,
            $request->get('item_number'),
            $request->get('category_id'),
            $request->get('item_name'),
            $request->get('size_id'),
            $request->get('price')
        );

        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }

    public function listAction()
    {
        // _GET values.
        $query = $this->container->get("request")->query;

        // Get the menu item service.
        $menuItemService = $this->container->get('dft_foapi.menu_item');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $menuItemService->fetchAll(
                    $this->getAuthenticatedUserIdAndSubAccountIds(),
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
