<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PushSubscriptionController extends Controller
{
    /**
     * Simpan atau perbarui langganan Web Push untuk perangkat yang sedang digunakan pengguna.
     * Satu akun pengguna dapat memiliki banyak langganan perangkat (HP, tablet, laptop).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'url', 'max:2048'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'content_encoding' => ['nullable', 'string', 'max:32'],
        ]);

        $endpoint = trim($validated['endpoint']);
        $endpointHash = hash('sha256', $endpoint);
        $encoding = ! empty($validated['content_encoding'])
            ? trim($validated['content_encoding'])
            : 'aes128gcm';

        $subscription = PushSubscription::updateOrCreate(
            [
                'endpoint_hash' => $endpointHash,
            ],
            [
                'user_id' => (int) $request->user()->id,
                'endpoint' => $endpoint,
                'p256dh' => trim($validated['keys']['p256dh']),
                'auth' => trim($validated['keys']['auth']),
                'content_encoding' => $encoding,
                'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
                'is_active' => true,
            ]
        );

        return response()->json([
            'status' => 'subscribed',
            'subscription_id' => $subscription->id,
            'is_active' => true,
        ]);
    }

    /**
     * Hentikan langganan Web Push untuk perangkat saat pengguna menekan tombol OFF.
     */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['nullable', 'string', 'max:2048'],
        ]);

        $userId = (int) $request->user()->id;
        $endpoint = isset($validated['endpoint']) ? trim((string) $validated['endpoint']) : '';

        if ($endpoint !== '') {
            $endpointHash = hash('sha256', $endpoint);

            PushSubscription::where('user_id', $userId)
                ->where('endpoint_hash', $endpointHash)
                ->delete();
        }

        return response()->json([
            'status' => 'unsubscribed',
            'is_active' => false,
        ]);
    }
}
