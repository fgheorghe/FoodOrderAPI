<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        // TODO: Move version to a config variable.
        return $this->render('dftFoapiBundle:Default:index.json.twig', array( "version" => "0.1" ));
    }

    public function ehloAction() {
        // Tells the user they are logged in.
        return $this->render('dftFoapiBundle:Default:ehlo.json.twig');
    }
}
