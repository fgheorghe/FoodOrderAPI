<?php
/**
 * Created by PhpStorm.
 * User: fgheorghe
 * Date: 22/11/14
 * Time: 09:30
 */

namespace dft\FoapiBundle\Traits;

/**
 * Class Logger. Standard Symfony2 logger wrapper.
 * @package dft\FoapiBundle\Traits
 */
trait Logger {
    /**
     * Method used for returning the logger, via the injected container.
     * @return \Symfony\Bridge\Monolog\Logger
     */
    public function getLogger() {
        return $this->getContainer()->get('logger');
    }
}