<?php

namespace App\Services\Stripe;

use App\Models\StripeDocument;
use Illuminate\Support\Facades\Schema;

/**
 * CRUD local stripe_documents (sin SDK; enlaces/importes gestionados en BD).
 */
class StripeDocumentService
{
    public function list(int $limit = 100): array
    {
        if (! Schema::hasTable('stripe_documents')) {
            throw new \RuntimeException('stripe_table_documents_missing');
        }

        return StripeDocument::query()
            ->orderByDesc('doc_id')
            ->limit(min(max($limit, 1), 500))
            ->get()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): StripeDocument
    {
        if (! Schema::hasTable('stripe_documents')) {
            throw new \RuntimeException('stripe_table_documents_missing');
        }

        return StripeDocument::query()->create($this->filterFillable($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $docId, array $data): StripeDocument
    {
        $doc = StripeDocument::query()->findOrFail($docId);
        $doc->fill($this->filterFillable($data));
        $doc->save();

        return $doc;
    }

    public function delete(int $docId): void
    {
        StripeDocument::query()->where('doc_id', $docId)->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @return array<string, mixed>
     */
    private function filterFillable(array $data): array
    {
        $allowed = ['urlStripe', 'amount', 'pagado', 'montopagado', 'fecha', 'fechapagado'];
        $out = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $out[$key] = $data[$key];
            }
        }

        return $out;
    }
}
