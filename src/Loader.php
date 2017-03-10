<?php

/**
 * Bricks Framework & Bricks CMS
 * http://bricks-cms.org
 *
 * The MIT License (MIT)
 * Copyright (c) 2015 bricks-cms.org
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Bricks\Loader;

use Bricks\Config\Config;
use Bricks\Loader\Exception\CouldNotInstantiate;
use Bricks\Loader\Factory\FactoryInterface;
use Bricks\Loader\Factory\DefaultFactory;
use Zend\ServiceManager\ServiceManager;

class Loader implements LoaderInterface {
	
	const ARG_INSTANCE = 'argInstance';
	const ARG_BUILD = 'argBuild';
	const ARG_INITIALIZE = 'argInitialize';

    /**
     * @var Config
     */
	protected $config;

    /**
     * @var array
     */
	protected $factories = array();

    /**
     * @var array
     */
	protected $instances = array();

    /**
     * @var array
     */
	protected $arguments = array();

    /**
     * @var ServiceManager
     */
	protected $serviceManager;

    /**
     * Loader constructor.
     * @param Config $config
     */
	public function __construct(Config $config,ServiceManager $sm){
		$this->config = $config;
		$this->serviceManager = $sm;
	}

    /**
     * @return Config
     */
	public function getConfig(){
		return $this->config;
	}

    /**
     * @return ServiceManager
     */
	public function getServiceManager(){
	    return $this->serviceManager;
    }

    /**
     * @param string $class
     * @param FactoryInterface $factory
     */
	public function addFactory($class,FactoryInterface $factory){
		$this->factories[$class][] = $factory;
	}

    /**
     * @param string $class
     * @return FactoryInterface
     */
	public function getFactories($class){
		if(isset($this->factories[$class])){
			return $this->factories[$class];
		}
	}

    /**
     * @param $class
     * @param FactoryInterface $factory
     */
	public function removeFactory($class,FactoryInterface $factory){
		if(isset($this->factories[$class])){
			while($key = array_search($factory,$this->factories[$class])){
				unset($this->factories[$class][$key]);
			}
		}
	}

    /**
     * @return DefaultFactory
     */
	public function getDefaultFactory(){
		return new DefaultFactory;
	}

    /**
     * @param string $class
     * @param string $namespace
     * @return string
     */
	public function solveClass($class,$namespace=null){
		$namespace = $namespace?:$this->getConfig()->getDefaultNamespace();
		$class = $this->getConfig()->get('bricks-loader.alias.'.$class,$namespace)?:$class;
		$class = $this->getConfig()->get('bricks-loader.classmap.'.$class,$namespace)?:$class;
		return $class;		
	}

    /**
     * @param string $type
     * @param string $class
     * @param mixed $argument
     * @param string $name
     */
	public function setArgument($type,$class,$argument,$name=null){
		if(isset($this->arguments[$class][$type]) && null == $order){
			$order = count($this->arguments[$class][$type]);
		}
		$order = $order?:0;
		$name = $name?:$order;
		$this->arguments[$class][$type][$name] = $argument;
	}

    /**
     * @param string $type
     * @param string $class
     * @param string $name
     * @return mixed
     */
	public function getArgument($type,$class,$name){
		if(isset($this->arguments[$class][$type][$name])){
			return $this->arguments[$class][$type][$name];
		}
	}

    /**
     * @param string $type
     * @param string $class
     * @return array
     */
	public function getArguments($type,$class){
		$arguments = array();
		if(isset($this->arguments[$class][$type])){
			$arguments = $this->arguments[$class][$type];
		}
	}

    /**
     * @param string $type
     * @param string $class
     * @param integer $order
     */
	public function removeArgumentByOrder($type,$class,$order=null){
		if(isset($this->arguments[$class][$type][$order])){
			unset($this->arguments[$class][$type][$order]);
		}		
	}

    /**
     * @param string $type
     * @param string $class
     * @param string $argument
     */
	public function removeArgument($type,$class,$argument){
		if(isset($this->arguments[$class][$type])){
			while($key = array_search($argument,$this->arguments[$class][$type])){
				unset($this->arguments[$class][$type][$key]);
			}
		}
	}

    /**
     * @param string $class
     * @param array $arguments
     * @param string $namespace
     * @return object
     */
	public function get($class,$arguments=array(),$namespace=null){
		$namespace = $namespace?:$this->getConfig()->getDefaultNamespace();
		$class = $this->solveClass($class,$namespace);		
		$arguments = count($arguments)?$arguments:$this->getArguments(self::ARG_INSTANCE,$class);
		if($this->getServiceManager()->has($class)){
		    return $this->getServiceManager()->get($class);
        }
		return $this->getInstance($class,$arguments,$namespace);
	}

    /**
     * @param string $class
     * @param array $arguments
     * @param string $namespace
     * @return object
     */
	public function singleton($class,$arguments=array(),$namespace=null){
		$namespace = $namespace?:$this->getConfig()->getDefaultNamespace();
		if(!isset($this->instances[$namespace][$class])){
		    $this->instances[$namespace][$class] = $this->get($class,$arguments,$namespace);
		}
		return $this->instances[$namespace][$class];
	}

    /**
     * @param string $class
     * @param array $arguments
     * @return object
     */
	protected function getInstance($class,$arguments=array()){
		$factories = $this->getFactories($class);
		if(!count($factories)){
			$factories = array($this->getDefaultFactory());
		}		
		$instance = null;
		foreach ($factories AS $factory) {
            $instance = $factory->instantiate($class, $arguments, $instance);
        }
        foreach ($factories AS $factory) {
            $factory->build($instance, $arguments);
        }
        foreach ($factories AS $factory) {
            $factory->initialize($instance, $arguments);
        }
        if(!$instance){
		    throw new CouldNotInstantiate('could not instantiate class '.$class);
        }
		return $instance;
	}
	
}