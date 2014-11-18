<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class RestaurantSettingsController extends Controller
{
    public function getAction()
    {
        // Get the restaurant service.
        $restaurantSettingsService = $this->container->get('dft_foapi.restaurant_settings');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $restaurantSettingsService->fetchOne(
                    $this->container->get('dft_foapi.login')->getAuthenticatedUserId()
                )
            )
        );
    }

    // TODO: Return proper status code if failed.
    // TODO: Allow for multiple branches.
    // TODO: Verify delivery range data type.
    public function updateAction()
    {
        // _POST values.
        $request = $this->container->get("request");

        // Get the restaurant service.
        $restaurantSettingsService = $this->container->get('dft_foapi.restaurant_settings');

        // Update settings.
        $restaurantSettingsService->updateRestaurantSettings(
            $this->container->get('dft_foapi.login')->getAuthenticatedUserId(),
            $request->get('restaurant_name'),
            $request->get('restaurant_post_code'),
            $request->get('delivery_range'),
            $request->get('opening_time'),
            $request->get('closing_time'),
            $request->get('open_all_day')
        );

        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }
}
