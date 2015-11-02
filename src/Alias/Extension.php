<?php
namespace Ytnuk\Alias;

use Nette;

final class Extension
	extends Nette\DI\CompilerExtension
{

	/**
	 * @var array
	 */
	private $defaults = [
		'prepend' => TRUE,
		'class' => [],
		'namespace' => [],
		'pattern' => [],
		'excluded' => [],
	];

	/**
	 * @var Manager
	 */
	private $manager;

	public function __construct()
	{
		$this->manager = new Manager;
	}

	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		$methods = $class->getMethods();
		$methods['initialize']->addBody(
			'$this->getByType(?)->register(?);',
			[
				Manager::class,
				$this->config['prepend'],
			]
		);
	}

	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$this->manager->setResolving($excluded = $this->config['excluded']);
		foreach (
			$builder->getDefinitions() as $name => $definition
		) {
			$class = $definition->getClass();
			if ($alias = $this->manager->resolve($class)) {
				$excluded[$alias] = $class;
				$definition->setClass($alias);
			}
			$class = $definition->getFactory() ? $definition->getFactory()->getEntity() : NULL;
			if ($alias = $this->manager->resolve($class)) {
				$excluded[$alias] = $class;
				$definition->setFactory(
					$alias,
					$definition->getFactory() ? $definition->getFactory()->arguments : []
				);
			}
			$class = $definition->getImplement();
			if ($alias = $this->manager->resolve($class)) {
				$excluded[$alias] = $class;
				$definition->setImplement($alias);
			}
		}
		$builder->getDefinition($this->prefix('manager'))->addSetup(
			'setResolving',
			[array_unique($excluded)]
		);
	}

	public function loadConfiguration()
	{
		$this->validateConfig($this->defaults);
		$providers = $this->compiler->getExtensions(Provider::class);
		array_walk(
			$providers,
			function (Provider $provider) {
				$this->config = $this->validateConfig(
					$this->config,
					$provider->getAliasResources()
				);
			}
		);
		$builder = $this->getContainerBuilder();
		$manager = $builder->addDefinition($this->prefix('manager'))->setClass(Manager::class);
		$manager->addSetup(
			'alias',
			[$this->config['class']]
		);
		$this->manager->alias($this->config['class']);
		foreach (
			$this->config['namespace'] as $original => $alias
		) {
			$manager->addSetup(
				'aliasNamespace',
				[
					$original,
					$alias,
				]
			);
			$this->manager->aliasNamespace(
				$original,
				$alias
			);
		}
		$manager->addSetup(
			'aliasPattern',
			[$this->config['pattern']]
		);
		$this->manager->aliasPattern($this->config['pattern']);
	}
}
