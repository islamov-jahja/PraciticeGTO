<?php

namespace Tests\tests\_data;
use DI\ContainerBuilder;

class Container
{
    public static function get():\DI\Container
    {
        $containerBuilder = new ContainerBuilder();
        $settings = require __DIR__ . '/../../../app/settings.php';
        $settings($containerBuilder);

        $dependencies = require __DIR__ . '/../../../app/dependencies.php';
        $dependencies($containerBuilder);

        $container = $containerBuilder->build();
        return $container;
    }
}