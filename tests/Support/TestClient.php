<?php
declare(strict_types=1);

namespace SGITests\Support;

class TestClient
{
    private string $baseUrl;
    private ?string $cookieFile;

    public function __construct(string $baseUrl = 'http://localhost/SGI', ?string $cookieFile = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->cookieFile = $cookieFile ?? (sys_get_temp_dir() . '/sgi_cookie_' . uniqid() . '.txt');
    }

    public function __destruct()
    {
        if ($this->cookieFile && file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }

    public function clearCookies(): void
    {
        if ($this->cookieFile && file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }

    public function getCookieFile(): string
    {
        return $this->cookieFile;
    }

    public function request(string $endpoint, string $method = 'GET', $data = null, array $headers = []): array
    {
        $url = str_starts_with($endpoint, 'http') ? $endpoint : ($this->baseUrl . '/' . ltrim($endpoint, '/'));
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        if ($this->cookieFile) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        }

        $formattedHeaders = [];
        $hasContentType = false;
        foreach ($headers as $k => $v) {
            if (is_int($k)) {
                $formattedHeaders[] = $v;
                if (stripos($v, 'Content-Type:') === 0) $hasContentType = true;
            } else {
                $formattedHeaders[] = "$k: $v";
                if (stripos($k, 'Content-Type') === 0) $hasContentType = true;
            }
        }

        $methodUpper = strtoupper($method);
        if ($methodUpper === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (is_array($data) && $hasContentType) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data ?? []);
            }
        } elseif (in_array($methodUpper, ['PUT', 'DELETE', 'PATCH'], true)) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $methodUpper);
            if ($data !== null) {
                $body = (is_array($data) || is_object($data)) ? json_encode($data) : $data;
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                if (!$hasContentType) {
                    $formattedHeaders[] = 'Content-Type: application/json';
                }
            }
        }

        if (!empty($formattedHeaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
        }

        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $json = null;
        if (is_string($raw) && $raw !== '') {
            $json = json_decode($raw, true);
        }

        return [
            'code' => $code,
            'body' => $raw,
            'json' => $json,
            'error' => $err
        ];
    }

    public function get(string $endpoint, array $headers = []): array
    {
        return $this->request($endpoint, 'GET', null, $headers);
    }

    public function postJson(string $endpoint, array $data, array $headers = []): array
    {
        $headers['Content-Type'] = 'application/json';
        return $this->request($endpoint, 'POST', $data, $headers);
    }

    public function postForm(string $endpoint, array $data, array $headers = []): array
    {
        return $this->request($endpoint, 'POST', $data, $headers);
    }

    public function putJson(string $endpoint, array $data, array $headers = []): array
    {
        $headers['Content-Type'] = 'application/json';
        return $this->request($endpoint, 'PUT', $data, $headers);
    }

    public function deleteJson(string $endpoint, array $data = [], array $headers = []): array
    {
        $headers['Content-Type'] = 'application/json';
        return $this->request($endpoint, 'DELETE', $data, $headers);
    }

    public function login(string $matricula, string $senha): array
    {
        return $this->postJson('api/login.php', [
            'matricula' => $matricula,
            'senha' => $senha
        ]);
    }
}
