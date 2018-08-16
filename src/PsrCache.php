<?php

namespace TomasKarlik\PsrNetteCache;

use DateInterval;
use DateTime;
use Exception;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Psr\SimpleCache\CacheInterface;
use Traversable;


class PsrCache implements CacheInterface
{

	public const CACHE_NAMESPACE = 'PsrCache';

	/**
	 * @var Cache
	 */
	private $cache;


	public function __construct(IStorage $storage)
	{
		$this->cache = new Cache($storage, self::CACHE_NAMESPACE);
	}


	/**
	 * {@inheritdoc}
	 */
	public function get($key, $default = NULL)
	{
		$this->assertValidKey($key);

		try {
			$value = $this->cache->load($key);

		} catch (Exception $exception) {
			throw new PsrCacheException(
				sprintf('Unable load key "%s"!', $key),
				0,
				$exception
			);
		}

		if ($value === NULL) {
			return $default;
		}

		return $value;
	}


	/**
	 * {@inheritdoc}
	 */
	public function set($key, $value, $ttl = NULL)
	{
		$this->assertValidKey($key);

		try {
			$this->cache->save($key, $value, [
				Cache::EXPIRE => $this->expire($ttl),
				Cache::SLIDING => FALSE,
				Cache::TAGS => [self::CACHE_NAMESPACE]
			]);

		} catch (Exception $exception) {
			trigger_error(sprintf('PsrCache: %s.', $exception->getMessage()), E_USER_WARNING);
			return FALSE;
		}

		return TRUE;
	}


	/**
	 * {@inheritdoc}
	 */
	public function delete($key)
	{
		$this->assertValidKey($key);

		try {
			$this->cache->remove($key);

		} catch (Exception $exception) {
			trigger_error(sprintf('PsrCache: %s.', $exception->getMessage()), E_USER_WARNING);
			return FALSE;
		}

		return TRUE;
	}


	/**
	 * {@inheritdoc}
	 */
	public function clear()
	{
		try {
			$this->cache->clean([
				Cache::TAGS => [self::CACHE_NAMESPACE]
			]);

		} catch (Exception $exception) {
			trigger_error(sprintf('PsrCache: %s.', $exception->getMessage()), E_USER_WARNING);
			return FALSE;
		}

		return TRUE;
	}


	/**
	 * {@inheritdoc}
	 */
	public function getMultiple($keys, $default = NULL)
	{
		if ($keys instanceof Traversable) {
			$keys = iterator_to_array($keys, FALSE);
		}

		if ( ! is_array($keys)) {
			throw new PsrCacheInvalidArgumentException(
				'Keys should be an array or Traversable of strings.'
			);
		}

		array_map([$this, 'assertValidKey'], $keys);

		$values = $this->cache->bulkLoad($keys);
		foreach ($values as $key => &$value) {
			if ($value === NULL) {
				$value = $default;
			}
		}

		return $values;
	}


	/**
	 * {@inheritdoc}
	 */
	public function setMultiple($values, $ttl = NULL)
	{
		if ($values instanceof Traversable) {
			$values = iterator_to_array($keys, TRUE);
		}

		if ( ! is_array($values)) {
			throw new PsrCacheInvalidArgumentException(
				'Values should be an array or Traversable.'
			);
		}

		$expire = $this->expire($ttl);
		foreach ($values as $key => $value) {
			$key = (string) $key;
			if ( ! $this->set($key, $value, $expire)) {
				return FALSE;
			}
		}

		return TRUE;
	}


	/**
	 * {@inheritdoc}
	 */
	public function deleteMultiple($keys)
	{
		if ($keys instanceof Traversable) {
			$keys = iterator_to_array($keys, FALSE);
		}

		if ( ! is_array($keys)) {
			throw new PsrCacheInvalidArgumentException(
				'Keys should be an array or Traversable of strings.'
			);
		}

		foreach ($keys as $key) {
			$key = (string) $key;
			if ( ! $this->delete($key)) {
				return FALSE;
			}
		}

		return TRUE;
	}


	/**
	 * {@inheritdoc}
	 */
	public function has($key)
	{
		$this->assertValidKey($key);
		return $this->cache->load($key) !== NULL;
	}


	/**
	 * @param mixed $key
	 */
	protected function assertValidKey($key): void
	{
		if ( ! is_string($key) || empty($key)) {
			throw new PsrCacheInvalidArgumentException(
				'Invalid key! Key should be a non-empty string.'
			);
		}
	}


	/**
	 * @param DateInterval|DateTime|int|NULL $ttl
	 * @return DateTime|NULL
	 */
	protected function expire($ttl): ?DateTime
	{
		if ($ttl === NULL || $ttl === 0) {
			return NULL; // not expire

		} elseif (is_int($ttl)) {
			return new DateTime(sprintf('+%d seconds', $ttl));

		} elseif ($ttl instanceof DateInterval) {
			$datetime = new DateTime;
			$datetime->add($ttl);
			return $datetime;

		} elseif ($ttl instanceof DateTime) {
			return $ttl;
		}

		throw new PsrCacheException('Invalid TTL!');
	}

}
