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

namespace Bricks\Loader\Factory;

use Bricks\Loader\Loader;

class DefaultFactory implements FactoryInterface {
	
	public function instantiate($class,$arguments=array(),$instance=null){
		if($instance){
			return $instance;
		}

		$arguments = array_values($arguments?:array());
		
		switch(count($arguments)){
			case 0: return new $class;
			case 1: return new $class($arguments[0]);
			case 2: return new $class($arguments[0],$arguments[1]);
			case 3: return new $class($arguments[0],$arguments[1],$arguments[2]);
			case 4: return new $class(
				$arguments[0],
				$arguments[1],
				$arguments[2],
				$arguments[3]
			);
			case 5: return new $class(
				$arguments[0],
				$arguments[1],
				$arguments[2],
				$arguments[3],
				$arguments[4]
			);
			case 6: return new $class(
				$arguments[0],
				$arguments[1],
				$arguments[2],
				$arguments[3],
				$arguments[4],
				$arguments[5]
			);
			case 7: return new $class(
				$arguments[0],
				$arguments[1],
				$arguments[2],
				$arguments[3],
				$arguments[4],
				$arguments[5],
				$arguments[6]
			);
			case 8: return new $class(
				$arguments[0],
				$arguments[1],
				$arguments[2],
				$arguments[3],
				$arguments[4],
				$arguments[5],
				$arguments[6],
				$arguments[7]
			);
		}
	
		$reflection = new \ReflectionClass($class);
		if($reflection->isInstantiable()){
			return $reflection->newInstanceArgs($arguments);
		}
	
	}
	
	public function build($instance,$arguments=array()){
		return null;
	}
	
	public function initialize($instance,$arguments=array()){
		if(method_exists($instance,'initilize')){
			return $instance->initilize($arguments);
		}
	}
	
}