<?php

namespace WebEdit\Alias;

use Fuel;
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

	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);
		foreach ($builder->getDefinitions() as $name => $definition) {
			$class = $definition->getClass();
			$result = FALSE;
			foreach ($config['class'] as $original => $alias) {
				if ($result) {
					break;
				}
				if ($class === $original) {
					$result = $alias;
				}
			}
			foreach ($config['namespace'] as $original => $alias) {
				if ($result) {
					break;
				}
				if (strpos($class, $original) === 0) {
					$result = trim(str_replace($original, $alias, $class), '\\');
				}
			}
			foreach ($config['pattern'] as $original => $alias) {
				if ($result) {
					break;
				}
				$pattern = '#^' . str_replace('\\*', '(.*)', preg_quote($original, '#')) . '$#uD';
				if ( ! preg_match($pattern, $class, $matches)) {
					continue;
				}
				$replaced = preg_replace($pattern, str_replace('\\', '\\\\', $alias), $class);
				if (class_exists($replaced)) {
					$result = $replaced;
				}
			}
			if ($result) {
				$definition->setClass($class);
			}
		}
	}

	/**
	 * @param Nette\PhpGenerator\ClassType $class
	 */
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
