<?php
declare(strict_types=1);

namespace SGITests\Support;

class TestClient
{
    private string $baseUrl;
    private ?string $cookieFile;
    private ?string $csrfToken = null;

    public function __construct(?string $baseUrl = null, ?string $cookieFile = null)
    {
        // Permite que a suíte seja executada contra o workspace (por exemplo,
        // um servidor PHP embutido), sem depender de uma implantação Apache.
        // O padrão aponta para o servidor embutido de testes. Assim, executar
        // a suíte sem configurar um ambiente nunca atinge o Apache de
        // desenvolvimento por acidente.
        $configuredBaseUrl = $baseUrl ?? getenv('SGI_TEST_BASE_URL') ?: 'http://127.0.0.1:8083';
        $this->baseUrl = rtrim($configuredBaseUrl, '/');
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

        $methodUpper = strtoupper($method);
        if (in_array($methodUpper, ['POST', 'PUT', 'PATCH', 'DELETE'], true) && $this->csrfToken !== null) {
            $hasCsrfHeader = false;
            foreach (array_keys($headers) as $headerName) {
                if (!is_int($headerName) && strcasecmp((string) $headerName, 'X-SGI-CSRF') === 0) {
                    $hasCsrfHeader = true;
                    break;
                }
            }
            if (!$hasCsrfHeader) {
                $headers['X-SGI-CSRF'] = $this->csrfToken;
            }
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
        $response = $this->postJson('api/v1/login', [
            'matricula' => $matricula,
            'senha' => $senha
        ]);
        $token = $response['json']['csrf_token'] ?? null;
        $this->csrfToken = is_string($token) && $token !== '' ? $token : null;

        return $response;
    }
}
