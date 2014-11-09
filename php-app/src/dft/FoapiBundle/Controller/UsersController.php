<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class UsersController extends Controller
{
    public function listAction()
    {
        // TODO: Implement.
        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }
}
