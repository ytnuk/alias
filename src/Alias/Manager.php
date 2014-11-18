<?php

namespace WebEdit\Alias;

use Fuel;

final class Manager extends Fuel\Alias\Manager
{

	public function setResolving(array $resolving)
	{
		$this->resolving = $resolving;

		return $this;
	}

	public function register($prepend = TRUE)
	{
		spl_autoload_register([
			$this,
			'createAlias'
		], TRUE, $prepend);

		return $this;
	}

	public function createAlias($alias)
	{
		if ($class = $this->resolve($alias)) {
			class_alias($alias, $class);
		}

		return $class;
	}

	public function resolve($alias)
	{
		if (in_array($alias, $this->resolving)) {
			return FALSE;
		}
		$this->resolving[] = $alias;
		if ($this->cache->has($alias) && $class = $this->cache->get($alias)) {
		} elseif ($class = $this->resolveAlias($alias)) {
		} elseif ($class = $this->resolveNamespaceAlias($alias)) {
		} elseif ( ! $class = $this->resolvePatternAlias($alias)) {
			return FALSE;
		}
		array_pop($this->resolving);
		if ( ! class_exists($class)) {
			return FALSE;
		}
		if ( ! $this->cache->has($alias)) {
			$this->cache->set($alias, $class);
		}

		return $class;
	}
}
