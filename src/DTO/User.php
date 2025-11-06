<?php

namespace SocialLogin\DTO;

class User
{
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $avatar = null,
        public ?array $raw = null,
        public ?string $provider = null,
    ) {
    }
}
