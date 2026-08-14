<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirestoreRestClient
{
    private ?string $accessToken = null;

    public function setDocument(string $collection, string $documentId, array $payload): void
    {
        $projectId = config('services.firebase.project_id');
        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/{$collection}/{$documentId}";
        $response = Http::withToken($this->token())->patch($url, ['fields' => $this->fields($payload)]);
        if (! $response->successful()) {
            throw new RuntimeException("Firestore {$response->status()}: ".$response->body());
        }
    }

    private function token(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }
        $path = config('services.firebase.credentials');
        if (! $path || ! is_file($path)) {
            throw new RuntimeException('FIREBASE_CREDENTIALS no apunta a una cuenta de servicio válida.');
        }
        $credentials = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $now = time();
        $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = $this->base64Url(json_encode([
            'iss' => $credentials['client_email'], 'scope' => 'https://www.googleapis.com/auth/datastore',
            'aud' => $credentials['token_uri'], 'iat' => $now, 'exp' => $now + 3600,
        ]));
        $unsigned = "{$header}.{$claims}";
        openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);
        $jwt = $unsigned.'.'.$this->base64Url($signature);
        $response = Http::asForm()->post($credentials['token_uri'], [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt,
        ]);
        if (! $response->successful()) {
            throw new RuntimeException('No se pudo obtener token de servicio Firebase.');
        }

        return $this->accessToken = $response->json('access_token');
    }

    private function fields(array $payload): array
    {
        return collect($payload)->mapWithKeys(fn ($value, $key) => [$key => $this->value($value)])->all();
    }

    private function value(mixed $value): array
    {
        return match (true) {
            is_null($value) => ['nullValue' => null],
            is_bool($value) => ['booleanValue' => $value],
            is_int($value) => ['integerValue' => (string) $value],
            is_float($value) => ['doubleValue' => $value],
            is_array($value) && array_is_list($value) => ['arrayValue' => ['values' => array_map(fn ($v) => $this->value($v), $value)]],
            is_array($value) => ['mapValue' => ['fields' => $this->fields($value)]],
            default => ['stringValue' => (string) $value],
        };
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
