<?php

namespace SocialLogin;

use SocialLogin\Contracts\ProviderDriver;
use SocialLogin\Exceptions\SocialLoginException;
use SocialLogin\Providers\GithubProvider;
use SocialLogin\Providers\GoogleProvider;
use SocialLogin\Providers;

class Manager
{
	/**
	 * Built-in provider class map.
	 * Maps provider name => fully qualified class name.
	 */
	private static array $builtInProviders = [
		'google' => GoogleProvider::class,
		'github' => GithubProvider::class,
		'facebook' => Providers\FacebookProvider::class,
		'linkedin' => Providers\LinkedInProvider::class,
		'microsoft' => Providers\MicrosoftProvider::class,
		'twitter' => Providers\TwitterProvider::class,
	];

	/**
	 * Custom provider registry.
	 * Allows runtime registration of additional providers.
	 */
	private static array $customProviders = [];

	public function __construct(private array $config)
	{
	}

	/**
	 * Register a custom provider globally.
	 * 
	 * Example:
	 *   Manager::extend('facebook', FacebookProvider::class);
	 *   Manager::extend('linkedin', MyCustomLinkedInProvider::class);
	 * 
	 * @param string $name Provider name (lowercase recommended)
	 * @param class-string<ProviderDriver> $providerClass Fully qualified class implementing ProviderDriver
	 */
	public static function extend(string $name, string $providerClass): void
	{
		if (!is_subclass_of($providerClass, ProviderDriver::class)) {
			throw new SocialLoginException(
				"Provider class {$providerClass} must implement " . ProviderDriver::class
			);
		}
		self::$customProviders[strtolower($name)] = $providerClass;
	}

	/**
	 * Get a provider driver instance by name.
	 * 
	 * Resolution order:
	 * 1. Check if config specifies a custom 'driver' class
	 * 2. Check custom registered providers via extend()
	 * 3. Check built-in providers
	 * 4. Throw exception if not found
	 * 
	 * @param string $name Provider name
	 * @return ProviderDriver
	 * @throws SocialLoginException
	 */
	public function driver(string $name): ProviderDriver
	{
		$key = strtolower($name);
		$providers = $this->config['providers'] ?? [];
		$cfg = $providers[$key] ?? null;
		
		if (!$cfg) {
			throw new SocialLoginException("Provider config missing: {$key}");
		}

		// Option 1: Custom driver class specified in config
		if (isset($cfg['driver']) && is_string($cfg['driver'])) {
			$providerClass = $cfg['driver'];
			if (!is_subclass_of($providerClass, ProviderDriver::class)) {
				throw new SocialLoginException(
					"Custom driver class {$providerClass} must implement " . ProviderDriver::class
				);
			}
			return $providerClass::fromConfig($cfg);
		}

		// Option 2: Check custom registered providers
		if (isset(self::$customProviders[$key])) {
			$providerClass = self::$customProviders[$key];
			return $providerClass::fromConfig($cfg);
		}

		// Option 3: Check built-in providers
		if (isset(self::$builtInProviders[$key])) {
			$providerClass = self::$builtInProviders[$key];
			return $providerClass::fromConfig($cfg);
		}

		// Not found
		throw new SocialLoginException(
			"Unsupported provider: {$key}. " .
			"Register it via Manager::extend('{$key}', YourProviderClass::class) " .
			"or specify 'driver' => YourProviderClass::class in config."
		);
	}

	/**
	 * Get list of all available provider names (built-in + custom).
	 * 
	 * @return array<string>
	 */
	public static function availableProviders(): array
	{
		return array_unique(array_merge(
			array_keys(self::$builtInProviders),
			array_keys(self::$customProviders)
		));
	}
}
