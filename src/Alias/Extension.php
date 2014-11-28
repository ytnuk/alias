<?php

namespace Ytnuk\Alias;

use Nette;

/**
 * Class Extension
 *
 * @package Ytnuk\Alias
 */
final class Extension extends Nette\DI\CompilerExtension
{

	/**
	 * @var array
	 */
	private $defaults = [
		'prepend' => TRUE,
		'class' => [],
		'namespace' => [],
		'pattern' => [],
		'excluded' => []
	];

	/**
	 * @var Manager
	 */
	private $manager;

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);
		$this->manager = new Manager;
		$manager = $builder->addDefinition($this->prefix('manager'))
			->setClass(Manager::class);
		$manager->addSetup('alias', [$config['class']]);
		$this->manager->alias($config['class']);
		foreach ($config['namespace'] as $original => $alias) {
			$manager->addSetup('aliasNamespace', [
				$original,
				$alias
			]);
			$this->manager->aliasNamespace($original, $alias);
		}
		$manager->addSetup('aliasPattern', [$config['pattern']]);
		$this->manager->aliasPattern($config['pattern']);
	}

	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);
		$excluded = $config['excluded'];
		$this->manager->setResolving($excluded);
		foreach ($builder->getDefinitions() as $name => $definition) {
			if ($alias = $this->manager->resolve($definition->getClass())) {
				$excluded[] = $definition->getClass();
				$definition->setClass($alias, $definition->getFactory() ? $definition->getFactory()->arguments : []);
			}
		}
		$builder->getDefinition($this->prefix('manager'))
			->addSetup('setResolving', [array_unique($excluded)]);
	}

	/**
	 * @param Nette\PhpGenerator\ClassType $class
	 */
	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		$config = $this->getConfig($this->defaults);
		$initialize = $class->methods['initialize'];
		$initialize->addBody('$this->getByType(?)->register(?);', [
			Manager::class,
			$config['prepend']
		]);
	}
}
