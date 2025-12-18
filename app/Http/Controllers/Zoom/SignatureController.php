<?php

namespace App\Http\Controllers\Zoom;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SignatureController extends Controller
{
    /**
     * توليد Signature للـ Web SDK باستخدام S2S OAuth (Access Token)
     */
    public function generate(Request $request)
    {
        $request->validate([
            'meetingNumber' => ['required'],
            'role' => ['required', 'integer', 'in:0,1'],
        ]);

        $meetingNumber = preg_replace('/\D+/', '', (string)$request->meetingNumber);
        $role = (int) $request->role;

        if (!$meetingNumber) {
            return response()->json(['message' => 'Invalid meetingNumber. Digits only are allowed.'], 422);
        }

        // Access Token من S2S OAuth
        $token = $this->getAccessToken();
        if (!$token) {
            return response()->json(['message' => 'Unable to get Zoom Access Token'], 500);
        }

        // Signature توليد باستخدام JWT حسب Web SDK Docs
        $signature = $this->generateSignature($meetingNumber, $role, $token);

        return response()->json(['signature' => $signature, 'access_token' => $token]);
    }

    private function getAccessToken(): ?string
    {
        try {
            $response = Http::asForm()->post('https://zoom.us/oauth/token', [
                'grant_type' => 'account_credentials',
                'account_id' => env('ZOOM_S2S_ACCOUNT_ID'),
                'client_id' => env('ZOOM_S2S_CLIENT_ID'),
                'client_secret' => env('ZOOM_S2S_CLIENT_SECRET'),
            ]);

            if ($response->successful()) {
                return $response->json('access_token');
            } else {
                Log::error('Zoom Access Token error', ['response' => $response->body()]);
            }
        } catch (\Exception $e) {
            Log::error('Zoom Access Token exception', ['message' => $e->getMessage()]);
        }

        return null;
    }

    private function generateSignature(string $meetingNumber, int $role, string $token): string
    {
        $header = ['alg'=>'HS256','typ'=>'JWT'];
        $iat = time();
        $exp = $iat + 2*60*60;

        $payload = [
            'sdkKey' => env('ZOOM_S2S_CLIENT_ID'), // بدل SDK Key
            'mn' => $meetingNumber,
            'role' => $role,
            'iat' => $iat,
            'exp' => $exp,
            'token' => $token
        ];

        $base64Url = fn($data) => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');

        $segments = [
            $base64Url(json_encode($header)),
            $base64Url(json_encode($payload))
        ];

        $signature = hash_hmac('sha256', implode('.', $segments), env('ZOOM_S2S_CLIENT_SECRET'), true);
        $segments[] = $base64Url($signature);

        return implode('.', $segments);
    }
}