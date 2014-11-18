<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class RestaurantSettingsController extends Controller
{
    public function getAction()
    {
        // Get the order service.
        $restaurantSettingsService = $this->container->get('dft_foapi.restaurant_settings');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $restaurantSettingsService->fetchOne(
                    $this->container->get('dft_foapi.login')->getAuthenticatedUserId()
                )
            )
        );
    }
}
