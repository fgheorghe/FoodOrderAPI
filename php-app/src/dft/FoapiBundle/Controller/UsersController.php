<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class UsersController extends Controller
{
    public function listAction()
    {
        // _GET values.
        $query = $this->container->get("request")->query;
        // Get the user service.
        $userService = $this->container->get('dft_foapi.user');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $userService->fetchAll(
                        $this->container->get('dft_foapi.login')->getAuthenticatedUserId(),
                        array(
                            "start" => $query->get('start'),
                            "limit" => $query->get('limit')
                        )
                    )
            )
        );
    }
}
