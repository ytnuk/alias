<?php
namespace Ytnuk\Alias;

use Fuel;

final class Manager
	extends Fuel\Alias\Manager
{

	public function setResolving(array $resolving) : self
	{
		$this->resolving = $resolving;

		return $this;
	}

	public function register($prepend = TRUE) : self
	{
		spl_autoload_register(
			[
				$this,
				'loader',
			],
			TRUE,
			$prepend
		);

		return $this;
	}

	public function resolve($alias) : string
	{
		if (in_array(
				$alias,
				$this->resolving
			) || ! $alias
		) {
			return (string) FALSE;
		}
		$this->resolving[] = $alias;
		if ($this->cache->has($alias) && $class = $this->cache->get($alias)) {
		} elseif ($class = $this->resolveAlias($alias)) {
		} elseif ($class = $this->resolveNamespaceAlias($alias)) {
		} elseif ( ! $class = $this->resolvePatternAlias($alias)) {
			return (string) FALSE;
		}
		array_pop($this->resolving);
		if ( ! class_exists($class)) {
			return (string) FALSE;
		}
		if ( ! $this->cache->has($alias)) {
			$this->cache->set(
				$alias,
				$class
			);
		}

		return $class;
	}

	public function loader(string $class) : string
	{
		if ($alias = $this->resolve($class)) {
			class_alias(
				$class,
				$alias
			);
		}

		return $alias;
	}
}
