<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Nette\Bridges\ApplicationDI;

use Nette;


/**
 * Routing extension for Nette DI.
 *
 * @author     David Grudl
 */
class RoutingExtension extends Nette\DI\CompilerExtension
{
	public $defaults = array(
		'debugger' => TRUE,
		'routes' => array(), // of [mask => action]
	);

	/** @var bool */
	private $debugMode;


	public function __construct($debugMode = FALSE)
	{
		$this->debugMode = $debugMode;
	}


	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();

		$config = $this->compiler->getConfig();
		if ($old = !isset($config[$this->name]) && isset($config['nette']['routing'])) {
			$config = Nette\DI\Config\Helpers::merge($config['nette']['routing'], $this->defaults);
			// trigger_error("Configuration section 'nette.routing' is deprecated, use section '$this->name' instead.", E_USER_DEPRECATED);
		} else {
			$config = $this->getConfig($this->defaults);
		}

		$this->validateConfig($this->defaults, $config, $old ? 'nette.routing' : $this->name);

		$router = $container->addDefinition('router') // no namespace for back compatibility
			->setClass('Nette\Application\IRouter')
			->setFactory('Nette\Application\Routers\RouteList');

		foreach ($config['routes'] as $mask => $action) {
			$router->addSetup('$service[] = new Nette\Application\Routers\Route(?, ?);', array($mask, $action));
		}

		if ($this->debugMode && $config['debugger']) {
			$container->getDefinition('application')->addSetup('@Tracy\Bar::addPanel', array(
				new Nette\DI\Statement('Nette\Bridges\ApplicationTracy\RoutingPanel')
			));
		}
	}

}
