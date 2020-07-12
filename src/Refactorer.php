<?php

namespace Signifly\DatabaseRefactors;

use ReflectionClass;

class Refactorer {

    /**
     * Methods required on the refactor class to throw an exception if missing.
     *
     * @var string[]
     */
    protected $required_methods = ['up', 'down'];

    protected $repository;

    public function __construct(Repositories\DatabaseRefactorRepository $repository) {
        $this->repository = $repository;
    }

    /**
     * Execute refactor class methods.
     *
     * @param $class
     * @param $method
     * @param Repositories\DatabaseRefactorRepository $repository
     * @return mixed
     * @throws \ReflectionException
     */
    public function execute($class, $method, $migration = null)
    {
        if(!$this->repository->repositoryExists()) {
            $this->repository->createRepository();
        }

        $reflection  = new ReflectionClass($class);

        if($method === 'up' && !$reflection->hasMethod('up')) {
            throw new Exception('Method up does not exist on class: '.$class);
        }
        elseif($method === 'down' && !$reflection->hasMethod('down')) {
            throw new Exception('Method down does not exist on class: '.$class);
        }

        $has_run = $this->repository->hasRun($class);
        if($method === 'up' && $has_run) {
            // return $this->error("Refactor class {$class} has already run.");
            throw new \Exception("Refactor class {$class} has already run.");
        }
        elseif($method === 'down' && !$has_run) {
            // return $this->error("Unable to rollback refactor class {$class}, has not yet run.");
            throw new \Exception("Unable to rollback refactor class {$class}, has not yet run.");
        }

        $time = microtime(true);
        (new $class)->{$method}();
        $time = microtime(true) - $time;

        if(in_array($method, $this->required_methods)) {
            $this->repository->{$method === 'down' ? 'delete' : 'log'}($class, $migration);
        }
        if(app()->runningInConsole()) {
            $this->repository->setConsoleOutput($class, $method, $time);
        }
    }

}
