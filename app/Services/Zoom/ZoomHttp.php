<?php

namespace App\Services\Zoom;

use Illuminate\Support\Facades\Http;

class ZoomHttp
{
    public function __construct(private ZoomS2STokenService $tokens) {}

    public function get(string $url, array $query = [])
    {
        return Http::withToken($this->tokens->getAccessToken())
            ->acceptJson()
            ->get($url, $query);
    }

    public function post(string $url, array $payload = [])
    {
        return Http::withToken($this->tokens->getAccessToken())
            ->acceptJson()
            ->post($url, $payload);
    }

    public function patch(string $url, array $payload = [])
    {
        return Http::withToken($this->tokens->getAccessToken())
            ->acceptJson()
            ->patch($url, $payload);
    }

    public function put(string $url, array $payload = [])
    {
        return Http::withToken($this->tokens->getAccessToken())
            ->acceptJson()
            ->put($url, $payload);
    }
}
