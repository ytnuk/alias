<?php
namespace Ytnuk\Alias;

use Fuel;

/**
 * Class Manager
 *
 * @package Ytnuk\Alias
 */
final class Manager
	extends Fuel\Alias\Manager
{

	/**
	 * @param array $resolving
	 *
	 * @return $this
	 */
	public function setResolving(array $resolving)
	{
		$this->resolving = $resolving;

		return $this;
	}

	/**
	 * @param bool $prepend
	 *
	 * @return $this
	 */
	public function register($prepend = TRUE)
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

	/**
	 * @param string $alias
	 *
	 * @return bool|string
	 */
	public function resolve($alias)
	{
		if (in_array(
				$alias,
				$this->resolving
			) || ! $alias
		) {
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
			$this->cache->set(
				$alias,
				$class
			);
		}

		return $class;
	}

	/**
	 * @param string $class
	 *
	 * @return bool|string
	 */
	public function loader($class)
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
