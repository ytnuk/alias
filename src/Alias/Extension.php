<?php

namespace WebEdit\Alias;

use Nette;
use Fuel;
use WebEdit;

final class Extension extends Nette\DI\CompilerExtension
{

	private $defaults = [
		'prepend' => TRUE,
		'class' => [],
		'namespace' => [],
		'pattern' => []
	];

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);
		$manager = $builder->addDefinition($this->prefix('manager'))
			->setClass(Fuel\Alias\Manager::class);
		foreach ($config['class'] as $original => $alias) {
			$manager->addSetup('alias', [
				$original,
				$alias
			]);
		}
		foreach ($config['namespace'] as $original => $alias) {
			$manager->addSetup('aliasNamespace', [
				$original,
				$alias
			]);
		}
		foreach ($config['pattern'] as $original => $alias) {
			$manager->addSetup('aliasPattern', [
				$original,
				$alias
			]);
		}
	}

	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		$config = $this->getConfig($this->defaults);
		$initialize = $class->methods['initialize'];
		$initialize->addBody('$this->getByType(?)->register(?);', [
			Fuel\Alias\Manager::class,
			$config['prepend'] ? 'prepend' : 'append'
		]);
	}
}
