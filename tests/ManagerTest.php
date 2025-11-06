<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SocialLogin\Manager;
use SocialLogin\Contracts\ProviderDriver;

final class ManagerTest extends TestCase
{
    private function config(): array
    {
        return [
            'providers' => [
                'google' => [
                    'client_id' => 'id',
                    'client_secret' => 'secret',
                    'redirect_uri' => 'https://example.com/callback?provider=google',
                ],
                'github' => [
                    'client_id' => 'id',
                    'client_secret' => 'secret',
                    'redirect_uri' => 'https://example.com/callback?provider=github',
                ],
            ],
        ];
    }

    public function test_it_builds_google_driver(): void
    {
        $m = new Manager($this->config());
        $driver = $m->driver('google');
        $this->assertInstanceOf(ProviderDriver::class, $driver);
        $url = $driver->getAuthorizationUrl();
        $this->assertIsString($url);
        $this->assertNotEmpty($driver->getState());
    }

    public function test_it_builds_github_driver(): void
    {
        $m = new Manager($this->config());
        $driver = $m->driver('github');
        $this->assertInstanceOf(ProviderDriver::class, $driver);
        $url = $driver->getAuthorizationUrl();
        $this->assertIsString($url);
        $this->assertNotEmpty($driver->getState());
    }

    public function test_missing_config_throws(): void
    {
        $m = new Manager(['providers' => []]);
        $this->expectException(\SocialLogin\Exceptions\SocialLoginException::class);
        $m->driver('google');
    }
}
