<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class LoginController extends Controller
{
    public function loginAction()
    {
        // Get the login service.
        $loginService = $this->container->get('dft_foapi.login');

        // Authenticate the user.
        $success = $loginService->login(
            $this->getRequest()->get("username", null),
            $this->getRequest()->get("password", null)
        );

        return $this->render('dftFoapiBundle:Common:' . ($success ? 'success' : 'failure') . '.json.twig');
    }
}
