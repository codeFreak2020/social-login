<?php

namespace SocialLogin;

use SocialLogin\Contracts\ProviderDriver;
use SocialLogin\Exceptions\SocialLoginException;
use SocialLogin\Providers\GithubProvider;
use SocialLogin\Providers\GoogleProvider;

class Manager
{
	public function __construct(private array $config)
	{
	}

	public function driver(string $name): ProviderDriver
	{
		$key = strtolower($name);
		$providers = $this->config['providers'] ?? [];
		$cfg = $providers[$key] ?? null;
		if (!$cfg) {
			throw new SocialLoginException("Provider config missing: {$key}");
		}

		return match ($key) {
			'google' => GoogleProvider::fromConfig($cfg),
			'github' => GithubProvider::fromConfig($cfg),
			default => throw new SocialLoginException("Unsupported provider: {$key}"),
		};
	}
}
