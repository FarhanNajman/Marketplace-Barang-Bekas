<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Http;
use Throwable;

class WebPushService
{
    /**
     * Periksa apakah konfigurasi VAPID sudah tersedia di environment.
     */
    public function isConfigured(): bool
    {
        return $this->getPublicKey() !== '' && $this->getPrivateKey() !== '';
    }

    public function getPublicKey(): string
    {
        return trim((string) config('services.webpush.public_key', ''));
    }

    protected function getPrivateKey(): string
    {
        return trim((string) config('services.webpush.private_key', ''));
    }

    protected function getSubject(): string
    {
        $subject = trim((string) config('services.webpush.subject', ''));
        if ($subject !== '') {
            return $subject;
        }

        $appUrl = trim((string) config('app.url', ''));
        if (str_starts_with($appUrl, 'https://')) {
            return $appUrl;
        }

        return 'mailto:admin@barantau.local';
    }

    /**
     * Kirim Web Push notification ke seluruh perangkat aktif milik pengguna penerima.
     * Jangan pernah mengirim notifikasi ke pengirim pesan itu sendiri.
     *
     * @param  array<string, mixed>  $payloadData
     */
    public function sendToUser(int $recipientUserId, array $payloadData, ?int $excludeSenderId = null): int
    {
        if ($excludeSenderId !== null && $recipientUserId === $excludeSenderId) {
            return 0;
        }

        if (! $this->isConfigured()) {
            return 0;
        }

        $subscriptions = PushSubscription::where('user_id', $recipientUserId)
            ->where('is_active', true)
            ->get();

        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $jsonPayload = json_encode($payloadData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (! is_string($jsonPayload)) {
            return 0;
        }

        $sentCount = 0;

        foreach ($subscriptions as $sub) {
            if ($excludeSenderId !== null && (int) $sub->user_id === $excludeSenderId) {
                continue;
            }

            if ($this->sendToSubscription($sub, $jsonPayload)) {
                $sentCount++;
            }
        }

        return $sentCount;
    }

    /**
     * Kirim satu pesan Web Push terenkripsi (RFC 8291 aes128gcm + RFC 8292 VAPID) ke satu perangkat.
     */
    public function sendToSubscription(PushSubscription $subscription, string $jsonPayload): bool
    {
        try {
            $endpoint = trim((string) $subscription->endpoint);
            if ($endpoint === '') {
                return false;
            }

            $vapidAuth = $this->createVapidAuthorizationHeader($endpoint);
            if ($vapidAuth === null) {
                return false;
            }

            $encryptedBody = $this->encryptPayload(
                $jsonPayload,
                (string) $subscription->p256dh,
                (string) $subscription->auth
            );

            if ($encryptedBody === null) {
                return false;
            }

            $response = Http::timeout(6)
                ->withHeaders([
                    'Authorization' => $vapidAuth,
                    'Content-Encoding' => 'aes128gcm',
                    'TTL' => '86400',
                    'Urgency' => 'high',
                ])
                ->withBody($encryptedBody, 'application/octet-stream')
                ->post($endpoint);

            $status = $response->status();

            // Jika langganan perangkat sudah kedaluwarsa atau dicabut di browser (404 / 410 Gone), hapus dari database
            if ($status === 404 || $status === 410) {
                $subscription->delete();

                return false;
            }

            return $status >= 200 && $status < 300;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Buat pasangan kunci VAPID baru (P-256 / prime256v1) dalam format Base64URL.
     *
     * @return array{public_key: string, private_key: string}|null
     */
    public function generateVapidKeyPair(): ?array
    {
        $config = [
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ];

        $openSslCnf = $this->resolveOpenSslConfigPath();
        if ($openSslCnf !== null) {
            $config['config'] = $openSslCnf;
        }

        $key = @openssl_pkey_new($config);
        if ($key === false) {
            return null;
        }

        $details = @openssl_pkey_get_details($key);
        if (! is_array($details) || ! isset($details['ec']['x'], $details['ec']['y'], $details['ec']['d'])) {
            return null;
        }

        $x = str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT);
        $y = str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT);
        $d = str_pad($details['ec']['d'], 32, "\x00", STR_PAD_LEFT);

        $uncompressedPub = "\x04" . substr($x, -32) . substr($y, -32);

        return [
            'public_key' => self::base64UrlEncode($uncompressedPub),
            'private_key' => self::base64UrlEncode(substr($d, -32)),
        ];
    }

    /**
     * Susun header Authorization VAPID (RFC 8292) menggunakan tanda tangan ES256 (ECDSA P-256 SHA-256).
     */
    protected function createVapidAuthorizationHeader(string $endpoint): ?string
    {
        $parsed = parse_url($endpoint);
        if (! is_array($parsed) || empty($parsed['scheme']) || empty($parsed['host'])) {
            return null;
        }

        $audience = $parsed['scheme'] . '://' . $parsed['host'];
        if (! empty($parsed['port'])) {
            $audience .= ':' . $parsed['port'];
        }

        $publicRaw = self::base64UrlDecode($this->getPublicKey());
        $privateRaw = self::base64UrlDecode($this->getPrivateKey());

        if (strlen($publicRaw) !== 65 || $publicRaw[0] !== "\x04" || strlen($privateRaw) !== 32) {
            return null;
        }

        $headerJson = json_encode(['typ' => 'JWT', 'alg' => 'ES256']);
        $payloadJson = json_encode([
            'aud' => $audience,
            'exp' => time() + 43200,
            'sub' => $this->getSubject(),
        ], JSON_UNESCAPED_SLASHES);

        if (! is_string($headerJson) || ! is_string($payloadJson)) {
            return null;
        }

        $signingInput = self::base64UrlEncode($headerJson) . '.' . self::base64UrlEncode($payloadJson);

        $pemPrivate = self::createEcPrivateKeyPem($privateRaw, $publicRaw);
        $pkey = @openssl_pkey_get_private($pemPrivate);
        if ($pkey === false) {
            return null;
        }

        $derSignature = '';
        if (! @openssl_sign($signingInput, $derSignature, $pkey, OPENSSL_ALGO_SHA256)) {
            return null;
        }

        $rawSignature = self::derToRawSignature($derSignature);
        if ($rawSignature === null) {
            return null;
        }

        $jwt = $signingInput . '.' . self::base64UrlEncode($rawSignature);

        return 'vapid t=' . $jwt . ', k=' . $this->getPublicKey();
    }

    /**
     * Enkripsi payload Web Push sesuai RFC 8291 (Content-Encoding: aes128gcm).
     */
    protected function encryptPayload(string $plaintext, string $userPubBase64Url, string $userAuthBase64Url): ?string
    {
        $uaPublic = self::base64UrlDecode($userPubBase64Url);
        $authSecret = self::base64UrlDecode($userAuthBase64Url);

        if (strlen($uaPublic) !== 65 || $uaPublic[0] !== "\x04" || strlen($authSecret) < 12) {
            return null;
        }

        $config = [
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ];

        $openSslCnf = $this->resolveOpenSslConfigPath();
        if ($openSslCnf !== null) {
            $config['config'] = $openSslCnf;
        }

        $localKey = @openssl_pkey_new($config);
        if ($localKey === false) {
            return null;
        }

        $localDetails = @openssl_pkey_get_details($localKey);
        if (! is_array($localDetails) || ! isset($localDetails['ec']['x'], $localDetails['ec']['y'])) {
            return null;
        }

        $lx = substr(str_pad($localDetails['ec']['x'], 32, "\x00", STR_PAD_LEFT), -32);
        $ly = substr(str_pad($localDetails['ec']['y'], 32, "\x00", STR_PAD_LEFT), -32);
        $asPublic = "\x04" . $lx . $ly;

        $uaPublicPem = self::createEcPublicKeyPem($uaPublic);
        $uaKeyRes = @openssl_pkey_get_public($uaPublicPem);
        if ($uaKeyRes === false) {
            return null;
        }

        $ecdhSecret = @openssl_pkey_derive($uaKeyRes, $localKey, 32);
        if (! is_string($ecdhSecret) || strlen($ecdhSecret) === 0) {
            return null;
        }

        $ecdhSecret = substr(str_pad($ecdhSecret, 32, "\x00", STR_PAD_LEFT), -32);
        $salt = random_bytes(16);

        // RFC 8291 Section 3.3 & 3.4: HKDF-SHA256
        $keyInfo = "WebPush: info\x00" . $uaPublic . $asPublic;
        $ikm = hash_hkdf('sha256', $ecdhSecret, 32, $keyInfo, $authSecret);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\x00", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\x00", $salt);

        $tag = '';
        $paddedPlaintext = $plaintext . "\x02";
        $ciphertext = @openssl_encrypt(
            $paddedPlaintext,
            'aes-128-gcm',
            $cek,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            16
        );

        if ($ciphertext === false || strlen($tag) !== 16) {
            return null;
        }

        // Header aes128gcm (86 byte): salt (16) || rs (4, 4096) || idlen (1, 65) || keyid (65)
        $header = $salt . pack('N', 4096) . chr(strlen($asPublic)) . $asPublic;

        return $header . $ciphertext . $tag;
    }

    /**
     * Bungkus 65-byte uncompressed P-256 public key menjadi PEM SubjectPublicKeyInfo.
     */
    protected static function createEcPublicKeyPem(string $uncompressedPublicKey): string
    {
        // ASN.1 prefix untuk SubjectPublicKeyInfo P-256 (id-ecPublicKey + prime256v1)
        $derPrefix = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200');
        $der = $derPrefix . $uncompressedPublicKey;

        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    /**
     * Bungkus 32-byte raw P-256 private key dan 65-byte uncompressed public key menjadi PEM SEC1 EC PRIVATE KEY.
     */
    protected static function createEcPrivateKeyPem(string $privateKey32, string $uncompressedPublicKey65): string
    {
        // SEC1 ECPrivateKey: SEQUENCE { version INTEGER(1), privateKey OCTET STRING(32), [0] OID prime256v1, [1] BIT STRING(65) }
        $der = hex2bin('30770201010420')
            . $privateKey32
            . hex2bin('a00a06082a8648ce3d030107a144034200')
            . $uncompressedPublicKey65;

        return "-----BEGIN EC PRIVATE KEY-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END EC PRIVATE KEY-----\n";
    }

    /**
     * Konversi tanda tangan ASN.1 DER ECDSA menjadi 64-byte raw (R || S) untuk JWT ES256.
     */
    protected static function derToRawSignature(string $der): ?string
    {
        if (strlen($der) < 8 || ord($der[0]) !== 0x30) {
            return null;
        }

        $offset = 2;
        if ((ord($der[1]) & 0x80) !== 0) {
            $offset += (ord($der[1]) & 0x7F);
        }

        if (! isset($der[$offset]) || ord($der[$offset]) !== 0x02) {
            return null;
        }

        $rLen = ord($der[$offset + 1]);
        $r = substr($der, $offset + 2, $rLen);
        $offset += 2 + $rLen;

        if (! isset($der[$offset]) || ord($der[$offset]) !== 0x02) {
            return null;
        }

        $sLen = ord($der[$offset + 1]);
        $s = substr($der, $offset + 2, $sLen);

        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");

        if (strlen($r) > 32 || strlen($s) > 32) {
            return null;
        }

        return str_pad($r, 32, "\x00", STR_PAD_LEFT) . str_pad($s, 32, "\x00", STR_PAD_LEFT);
    }

    protected function resolveOpenSslConfigPath(): ?string
    {
        $envConf = getenv('OPENSSL_CONF');
        if (is_string($envConf) && $envConf !== '' && is_file($envConf)) {
            return $envConf;
        }

        $phpDir = dirname(PHP_BINARY);
        $candidates = [
            $phpDir . DIRECTORY_SEPARATOR . 'extras' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'openssl.cnf',
            '/etc/ssl/openssl.cnf',
            '/usr/lib/ssl/openssl.cnf',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $data): string
    {
        $clean = strtr(trim($data), '-_', '+/');
        $pad = strlen($clean) % 4;
        if ($pad > 0) {
            $clean .= str_repeat('=', 4 - $pad);
        }

        $decoded = base64_decode($clean, true);

        return is_string($decoded) ? $decoded : '';
    }
}
