<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class UserController extends Controller
{
    public function createAction()
    {
        // _POST values.
        $request = $this->container->get("request");

        // Get the user service.
        $userService = $this->container->get('dft_foapi.user');

        $userService->createUser(
            $this->container->get('dft_foapi.login')->getAuthenticatedUserId(),
            $request->get('name'),
            $request->get('role_id'),
            $request->get('email'),
            $request->get('password')
        );

        // TODO: Return proper status code and message if failed.
        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }

    public function updateAction($userId)
    {
        // _POST values.
        $request = $this->container->get("request");

        // Get the user service.
        $userService = $this->container->get('dft_foapi.user');

        $userService->updateUser(
            $this->container->get('dft_foapi.login')->getAuthenticatedUserId(),
            $userId,
            $request->get('name'),
            $request->get('role_id'),
            $request->get('email'),
            $request->get('password')
        );

        // TODO: Return proper status code and message if failed.
        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }

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
