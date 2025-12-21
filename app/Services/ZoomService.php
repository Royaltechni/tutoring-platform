<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ZoomService
{
    protected $accountId, $clientId, $clientSecret;
    protected $baseUrl = 'https://api.zoom.us/v2';

    public function __construct()
    {
        $this->accountId = config('services.zoom.account_id');
        $this->clientId = config('services.zoom.client_id');
        $this->clientSecret = config('services.zoom.client_secret');
    }

    protected function getToken()
    {
        return Cache::remember('zoom_access_token', 3500, function () {
            $response = Http::asForm()->withBasicAuth($this->clientId, $this->clientSecret)
                ->post("https://zoom.us/oauth/token", [
                    'grant_type' => 'account_credentials',
                    'account_id' => $this->accountId,
                ]);
            return $response->json()['access_token'] ?? null;
        });
    }

    public function createMeeting($data)
    {
        $token = $this->getToken();
        $response = Http::withToken($token)->post("{$this->baseUrl}/users/me/meetings", [
            'topic'      => $data['topic'],
            'type'       => 2,
            'start_time' => date('Y-m-d\TH:i:s', strtotime($data['start_time'])),
            'duration'   => $data['duration'],
            'timezone'   => 'Africa/Cairo', // اختر توقيتك
            'settings'   => [
                'host_video' => true,
                'participant_video' => true,
                'waiting_room' => true,
            ],
        ]);

        return $response->json();
    }
}