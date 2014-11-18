<?php

namespace WebEdit\Alias;

use Nette;

/**
 * Class Extension
 *
 * @package WebEdit\Alias
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
		'pattern' => []
	];

	/**
	 * @var Manager
	 */
	private $manager;

	public function loadConfiguration()
	{
		$this->manager = new Manager;
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);
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
		$resolving = [];
		foreach ($builder->getDefinitions() as $name => $definition) {
			if ($alias = $this->manager->resolve($definition->getClass())) {
				$resolving[] = $definition->getClass();
				$definition->setClass($alias, $definition->getFactory() ? $definition->getFactory()->arguments : []);
			}
		}
		$builder->getDefinition($this->prefix('manager'))
			->addSetup('setResolving', [$resolving]);
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
