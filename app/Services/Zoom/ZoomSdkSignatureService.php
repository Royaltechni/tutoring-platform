<?php

namespace App\Services\Zoom;

class ZoomSdkSignatureService
{
    public function makeSignature(string $meetingNumber, int $role = 0): string
    {
        $sdkKey    = (string) config('services.zoom_meeting_sdk.key');
        $sdkSecret = (string) config('services.zoom_meeting_sdk.secret');

        if (!$sdkKey || !$sdkSecret) {
            throw new \RuntimeException('Zoom Meeting SDK key/secret missing');
        }

        $iat = time();
        $exp = $iat + 2 * 60 * 60; // ساعتين

        // ✅ v5 payload الصحيح
        $payload = [
            'appKey'   => $sdkKey,
            'sdkKey'   => $sdkKey,
            'mn'       => (string) $meetingNumber,
            'role'     => (int) $role,
            'iat'      => (int) $iat,
            'exp'      => (int) $exp,
            'tokenExp' => (int) $exp,
        ];

        return $this->jwtEncodeHS256($payload, $sdkSecret);
    }

    private function jwtEncodeHS256(array $payload, string $secret): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];

        $segments = [];
        $segments[] = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $segments[] = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));

        $signingInput = implode('.', $segments);

        // ✅ لازم secret raw زي ما هو
        $signature = hash_hmac('sha256', $signingInput, $secret, true);

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
