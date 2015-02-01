<?php

namespace dft\FoapiBundle\Controller;

class FrontEndDiscountsController extends BaseController
{
    public function listAction()
    {
        // Get the front end discounts service.
        $frontEndDiscountsService = $this->container->get('dft_foapi.front_end_discounts');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $frontEndDiscountsService->fetchAll($this->getAuthenticatedUserIdAndSubAccountIds())
        ));
    }

    public function deleteAction($discountId) {
        // Get the front end discounts service.
        $frontEndDiscountsService = $this->container->get('dft_foapi.front_end_discounts');
        $frontEndDiscountsService->deleteDiscount(
            $this->getAuthenticatedUserIdAndSubAccountIds(),
            $discountId
        );

        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }

    public function updateAction($discountId) {
        // Get the front end discounts service.
        $frontEndDiscountsService = $this->container->get('dft_foapi.front_end_discounts');

        // _POST values.
        $request = $this->container->get("request");

        $frontEndDiscountsService->updateDiscount(
            $this->getAuthenticatedUserIdAndSubAccountIds(),
            $discountId,
            $request->get('discount_type'),
            $request->get('discount_name'),
            $request->get('value'),
            $request->get('discount_item_id')
        );

        // TODO: Return proper status code if failed.
        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }

    public function createAction() {
        // Get the front end discounts service.
        $frontEndDiscountsService = $this->container->get('dft_foapi.front_end_discounts');

        // _POST values.
        $request = $this->container->get("request");

        $frontEndDiscountsService->createDiscount(
            $this->container->get('dft_foapi.login')->getAuthenticatedUserId(),
            $this->getAuthenticatedUserIdAndSubAccountIds(),
            $request->get('discount_type'),
            $request->get('discount_name'),
            $request->get('value'),
            $request->get('discount_item_id')
        );

        // TODO: Return proper status code if failed.
        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }
}