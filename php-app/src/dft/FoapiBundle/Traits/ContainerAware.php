<?php
/**
 * Created by PhpStorm.
 * User: fgheorghe
 * Date: 09/11/14
 * Time: 15:50
 */

namespace dft\FoapiBundle\Traits;

trait ContainerAware {
    // Hosts the service container.
    private $container;

    /**
     * Get the service container object.
     * @return mixed
     */
    public function getContainer() {
        return $this->container;
    }

    /**
     * Sets the service container object.
     * @param $container
     * @return $this
     */
    public function setContainer($container) {
        $this->container = $container;
        return $this;
    }

    // Takes in the container.
    public function __construct($container) {
        $this->setContainer($container);
    }
}