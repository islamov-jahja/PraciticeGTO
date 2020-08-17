<?php


namespace App\Persistance\ModelsEloquant;

use Illuminate\Database\Capsule\Manager as Capsule;

class DataBase
{
    private $capsule;
    public function __construct($options)
    {
        $this->capsule = new Capsule();
        $this->capsule->addConnection([
            'driver'    => $options['DBDriver'],
            'host'      => $options['DBHost'],
            'database'  => $options['DBName'],
            'username'  => $options['DBUser'],
            'password'  => $options['DBPass'],
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix'    => '',
        ]);

        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();
    }

    public function getCapsule():Capsule{
        return $this->capsule;
    }
}