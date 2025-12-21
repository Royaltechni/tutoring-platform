<?php

namespace App\Http\Controllers\Zoom;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SignatureController extends Controller
{
    public function generate(Request $request)
    {
        try {
            $request->validate([
                'meetingNumber' => 'required',
                'role' => 'required|integer'
            ]);

            // اسحب المفاتيح من الـ .env (تأكد من وجودها)
            $sdkKey = env('ZOOM_MEETING_SDK_KEY');
            $sdkSecret = env('ZOOM_MEETING_SDK_SECRET');

            if (!$sdkKey || !$sdkSecret) {
                return response()->json(['error' => 'SDK Keys are missing in .env'], 500);
            }

            $meetingNumber = $request->meetingNumber;
            $role = $request->role;
            $iat = time() - 30;
            $exp = $iat + 60 * 60 * 2;

            $header = ['alg' => 'HS256', 'typ' => 'JWT'];
            
            $payload = [
                'sdkKey' => $sdkKey,
                'mn' => $meetingNumber,
                'role' => $role,
                'iat' => $iat,
                'exp' => $exp,
                'tokenExp' => $exp
            ];

            $base64Url = function($data) {
                return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
            };

            $segments = [
                $base64Url(json_encode($header)),
                $base64Url(json_encode($payload))
            ];

            $signingString = implode('.', $segments);
            $signature = hash_hmac('sha256', $signingString, $sdkSecret, true);
            $segments[] = $base64Url($signature);

            return response()->json([
                'signature' => implode('.', $segments),
                'sdkKey' => $sdkKey
            ]);

        } catch (\Exception $e) {
            // لو حصل أي خطأ، ابعت JSON مش HTML
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}