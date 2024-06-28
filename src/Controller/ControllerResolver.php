<?php

use Symfony\Component\HttpKernel\Controller\ControllerResolver;

class MyControllerResolver extends ControllerResolver
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
        parent::__construct();
    }

    protected function instantiateController($class): object
    {
        if ($this->container && $this->container->offsetExists($class)) {
            return $this->container[$class];
        }

        return parent::instantiateController($class);
    }
}
