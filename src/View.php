<?php
namespace Xaamin\Blade;

use Illuminate\View\Factory;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\View\FileViewFinder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Compilers\BladeCompiler;

class View
{
    /**
    * Array containing paths where to look for blade files
    * @var array
    */
    public $viewPaths;

    /**
    * Location where to store cached views
    * @var string
    */
    public $cachePath;

    /**
    * @var \Illuminate\Container\Container
    */
    protected $container;

    /**
    * @var \Illuminate\View\Factory
    */
    protected $instance;

    /**
    * Initialize class
    *
    * @param array  							$viewPaths
    * @param string 							$cachePath
    * @param \Illuminate\Events\Dispatcher 		$events
    * @param \Illuminate\Container\Container 	$container
    */
    function __construct($viewPaths, $cachePath, Dispatcher $events = null, Container $container = null) {
        $this->container = $container ? : new Container;
        $this->viewPaths = (array) $viewPaths;
        $this->cachePath = $cachePath;

        $this->registerFilesystem();
        $this->registerEvents($events ?: new Dispatcher);
        $this->registerEngineResolver();
        $this->registerViewFinder();
        $this->createFactory();
    }

    protected function registerFilesystem()
    {
        $this->container->singleton('filesystem', function() {
            return new Filesystem;
        });
    }

    protected function registerEvents(Dispatcher $events)
    {
        $this->container->singleton('events', function() use ($events) {
            return $events;
        });
    }

    /**
    * Register the engine resolver instance.
    *
    * @return void
    */
    protected function registerEngineResolver()
    {
        $that = $this;

        $this->container->singleton('view.engine.resolver', function($app) use ($that) {
            $resolver = new EngineResolver;
            // Next we will register the various engines with the resolver so that the
            // environment can resolve the engines it needs for various views based
            // on the extension of view files. We call a method for each engines.
            foreach (array('php', 'blade') as $engine) {
                $method = 'register' . ucfirst($engine) . 'Engine';

                $that->{$method}($resolver);
            }

            return $resolver;
        });
    }

    /**
    * Register the PHP engine implementation.
    *
    * @param  \Illuminate\View\Engines\EngineResolver  $resolver
    * @return void
    */
    protected function registerPhpEngine($resolver)
    {
        $resolver->register('php', function() { return new PhpEngine; });
    }

    /**
    * Register the Blade engine implementation.
    *
    * @param  \Illuminate\View\Engines\EngineResolver  $resolver
    * @return void
    */
    protected function registerBladeEngine($resolver)
    {
        $that = $this;
        $app = $this->container;

        // The Compiler engine requires an instance of the CompilerInterface, which in
        // this case will be the Blade compiler, so we'll first create the compiler
        // instance to pass into the engine so it can compile the views properly.
        $this->container->singleton('blade.compiler', function($app) use ($that) {
            $cache = $that->cachePath;

            return new BladeCompiler($app['filesystem'], $cache);
        });

        $resolver->register('blade', function() use ($app) {
            return new CompilerEngine($app['blade.compiler'], $app['filesystem']);
        });
    }

    /**
    * Register the view finder implementation.
    *
    * @return void
    */
    protected function registerViewFinder()
    {
        $that = $this;

        $this->container->singleton('view.finder', function($app) use ($that) {
            $paths = $that->viewPaths;
            return new FileViewFinder($app['filesystem'], $paths);
        });
    }

    /**
    * Register the view environment.
    *
    * @return void
    */
    protected function createFactory()
    {
        // Grab the engine resolver instance that will be used by the environment.
        // The resolver will be used by an environment to get each of
        // the various engine implementations such as plain PHP or Blade engine.
        $resolver = $this->container['view.engine.resolver'];
        $finder = $this->container['view.finder'];
        $factory = new Factory($resolver, $finder, $this->container['events']);

        $factory->setContainer($this->container);

        $this->instance = $factory;
    }

    public function compiler()
    {
        return $this->container['blade.compiler'];
    }

    /**
    * Pass any method to the view factory instance.
    *
    * @param  string $method
    * @param  array  $params
    * @return mixed
    */
    public function __call($method, $params)
    {
        return call_user_func_array([$this->instance, $method], $params);
    }
}