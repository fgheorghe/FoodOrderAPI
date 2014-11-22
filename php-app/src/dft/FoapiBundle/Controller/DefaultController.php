<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        // TODO: Move version to a config variable.
        return $this->render('dftFoapiBundle:Default:index.html.twig', array( "version" => "0.1" ));
    }
}
