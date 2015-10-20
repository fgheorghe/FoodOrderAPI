<?php

namespace dft\FoapiBundle\Controller;

use dft\FoapiBundle\Services\User;

class UserController extends BaseController
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
                        $this->getAuthenticatedUserIdAndSubAccountIds(),
                        array(
                            "start" => $query->get('start'),
                            "limit" => $query->get('limit'),
                            // Force the role id to be an IN value for Administrator, Chef and Delivery.
                            "role_id" => array(
                                    User::ROLE_TYPE_CHEF,
                                    User::ROLE_TYPE_DELIVERY,
                                    User::ROLE_TYPE_ADMINISTRATOR
                                )
                        )
                    )
            )
        );
    }

    public function getAction($userId)
    {
        // Get the user service.
        $userService = $this->container->get('dft_foapi.user');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $userService->fetchOne(
                    $this->getAuthenticatedUserIdAndSubAccountIds(),
                    $userId
                )
            )
        );
    }

    public function activateAction($userId)
    {
        // Get the user service.
        $userService = $this->container->get('dft_foapi.user');

        $userService->activateUser(
            $this->getAuthenticatedUserIdAndSubAccountIds(),
            $userId
        );

        // TODO: Return proper status code and message if failed.
        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }

    public function deactivateAction($userId)
    {
        // Get the user service.
        $userService = $this->container->get('dft_foapi.user');

        $userService->deactivateUser(
            $this->getAuthenticatedUserIdAndSubAccountIds(),
            $userId
        );

        // TODO: Return proper status code and message if failed.
        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }

    public function changePasswordAction($userId)
    {
        // _POST values.
        $request = $this->container->get("request");

        // Get the user service.
        $userService = $this->container->get('dft_foapi.user');

        $userService->changeUserPassword(
            $this->getAuthenticatedUserIdAndSubAccountIds(),
            $userId,
            $request->get('password')
        );

        // TODO: Return proper status code and message if failed.
        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }

    public function changeOwnPasswordAction()
    {
        // _POST values.
        $request = $this->container->get("request");

        // Get the user service.
        $userService = $this->container->get('dft_foapi.user');

        $success = $userService->changeOwnPassword(
            $this->container->get('dft_foapi.login')->getAuthenticatedUserId(),
            $request->get('new_password'),
            $request->get('current_password')
        );

        return $this->render('dftFoapiBundle:Common:' . ($success ? 'success' : 'failure') . '.json.twig');
    }

    public function changeRoleAction($userId)
    {
        // _POST values.
        $request = $this->container->get("request");

        // Get the user service.
        $userService = $this->container->get('dft_foapi.user');

        $userService->changeUserRole(
            $this->getAuthenticatedUserIdAndSubAccountIds(),
            $userId,
            $request->get('role_id')
        );

        // TODO: Return proper status code and message if failed.
        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }
}
