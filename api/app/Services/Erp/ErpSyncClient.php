<?php

namespace App\Services\Erp;

use App\Models\Company;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ErpSyncClient
{
    public function __construct(private Company $company) {}

    public static function forCompany(Company $company): self
    {
        return new self($company);
    }

    public function assertConfigured(): void
    {
        if (! (bool) ($this->company->erp_enabled ?? false)) {
            throw new RuntimeException('ERP connection is disabled for this company.');
        }

        $url = trim((string) ($this->company->erp_url ?? ''));
        $token = trim((string) ($this->company->erp_company_token ?? ''));
        $code = trim((string) ($this->company->erp_company_code ?? ''));
        if ($url === '' || $token === '' || $code === '') {
            throw new RuntimeException('ERP company code, URL and company token are required.');
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchAll(string $path, array $query = []): array
    {
        $this->assertConfigured();

        $page = 1;
        $perPage = 100;
        $rows = [];

        do {
            $payload = $this->get($path, array_merge($query, [
                'page' => $page,
                'per_page' => $perPage,
            ]));

            $chunk = $payload['data'] ?? [];
            if (! is_array($chunk)) {
                break;
            }

            foreach ($chunk as $item) {
                if (is_array($item)) {
                    $rows[] = $item;
                }
            }

            $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
            $lastPage = (int) ($meta['last_page'] ?? $page);
            $page++;
        } while ($page <= $lastPage && $chunk !== []);

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        $this->assertConfigured();

        $response = $this->http()->get($this->url($path), $query);
        if (! $response->successful()) {
            throw new RuntimeException($this->formatHttpError($response->status(), (string) $response->body()));
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    private function http(): PendingRequest
    {
        $headers = [
            'Accept' => 'application/json',
            'X-Api-Key' => trim((string) $this->company->erp_company_token),
        ];
        $code = trim((string) ($this->company->erp_company_code ?? ''));
        if ($code !== '') {
            $headers['X-Company-Code'] = $code;
        }

        return Http::withHeaders($headers)->timeout(60)->connectTimeout(15);
    }

    private function url(string $path): string
    {
        $base = rtrim(trim((string) $this->company->erp_url), '/');
        $path = '/'.ltrim($path, '/');

        // Normalizar base a .../api/v1 (acepta URL con o sin /api o /api/v1).
        if (preg_match('#/api/v1$#i', $base) === 1) {
            // ok
        } elseif (preg_match('#/api$#i', $base) === 1) {
            $base .= '/v1';
        } else {
            $base .= '/api/v1';
        }

        return $base.$path;
    }

    private function formatHttpError(int $status, string $body): string
    {
        $body = trim($body);
        $decoded = json_decode($body, true);
        if (is_array($decoded) && is_string($decoded['message'] ?? null) && trim($decoded['message']) !== '') {
            return 'ERP HTTP '.$status.': '.mb_substr(trim($decoded['message']), 0, 500);
        }

        return 'ERP HTTP '.$status.': '.mb_substr($body, 0, 300);
    }
}
