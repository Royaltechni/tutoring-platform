<?php

namespace App\Services\Zoom;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ZoomS2STokenService
{
    public function getAccessToken(): string
    {
        return Cache::remember('zoom_s2s_access_token', 50 * 60, function () {
            $accountId = config('services.zoom.account_id');
            $clientId  = config('services.zoom.client_id');
            $secret    = config('services.zoom.client_secret');

            $res = Http::asForm()
                ->withBasicAuth($clientId, $secret)
                ->post('https://zoom.us/oauth/token', [
                    'grant_type' => 'account_credentials',
                    'account_id' => $accountId,
                ]);

            if (!$res->ok()) {
                throw new \RuntimeException('Zoom token failed: ' . $res->body());
            }

            return (string) $res->json('access_token');
        });
    }

    /**
     * ZAK Token للـ Host (حساب المنصّة).
     * مهم: الـ ZAK بيتغير، فبنخزّنه فترة قصيرة.
     */
    public function getZakTokenForHostEmail(string $hostEmail): string
    {
        $hostEmail = trim(strtolower($hostEmail));
        if (!$hostEmail) {
            throw new \RuntimeException('Host email is missing');
        }

        $cacheKey = 'zoom_zak_' . md5($hostEmail);

        // نخزّن 10 دقائق احتياطياً (ZAK ممكن يخلص أسرع/أطول حسب Zoom)
        return Cache::remember($cacheKey, 10 * 60, function () use ($hostEmail) {
            $accessToken = $this->getAccessToken();

            // 1) نجيب user id من email
            $u = Http::withToken($accessToken)
                ->acceptJson()
                ->get('https://api.zoom.us/v2/users/' . urlencode($hostEmail));

            if (!$u->ok()) {
                throw new \RuntimeException('Zoom get user failed: ' . $u->body());
            }

            $userId = (string) $u->json('id');
            if (!$userId) {
                throw new \RuntimeException('Zoom user id missing for host');
            }

            // 2) نجيب ZAK
            $zakRes = Http::withToken($accessToken)
                ->acceptJson()
                ->get("https://api.zoom.us/v2/users/{$userId}/token", [
                    'type' => 'zak',
                ]);

            if (!$zakRes->ok()) {
                throw new \RuntimeException('Zoom get ZAK failed: ' . $zakRes->body());
            }

            $zak = (string) ($zakRes->json('token') ?? '');
            if (!$zak) {
                throw new \RuntimeException('ZAK token missing from Zoom response');
            }

            return $zak;
        });
    }
}
