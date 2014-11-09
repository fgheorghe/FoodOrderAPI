<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MenuItemController extends Controller
{
    public function indexAction()
    {
        // TODO: Implement.
        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }
}
