<?php

namespace App\Services;

use App\Models\Language;
use App\Support\Languages\SupportedLanguages;
use Illuminate\Support\Carbon;

class LanguageSyncService
{
    /**
     * @return array{
     *     supported: int,
     *     existing: int,
     *     missing: list<int>,
     *     outdated: list<int>,
     *     soft_deleted: list<int>,
     *     extra_in_database: list<int>,
     *     missing_json_files: list<string>,
     *     orphan_json_files: list<string>
     * }
     */
    public function audit(): array
    {
        $definitions = SupportedLanguages::definitions();
        $existing = Language::withTrashed()
            ->whereIn('language_id', SupportedLanguages::ids())
            ->get()
            ->keyBy('language_id');

        $missing = [];
        $outdated = [];
        $softDeleted = [];

        foreach ($definitions as $languageId => $definition) {
            $row = $existing->get($languageId);
            if ($row === null) {
                $missing[] = $languageId;

                continue;
            }

            if ($row->trashed()) {
                $softDeleted[] = $languageId;
            }

            if ($this->needsUpdate($row, $definition)) {
                $outdated[] = $languageId;
            }
        }

        $knownIds = SupportedLanguages::ids();
        $extraInDatabase = Language::query()
            ->whereNotIn('language_id', $knownIds)
            ->pluck('language_id')
            ->map(static fn ($id) => (int) $id)
            ->values()
            ->all();

        return [
            'supported' => count($definitions),
            'existing' => $existing->filter(static fn (Language $language) => ! $language->trashed())->count(),
            'missing' => $missing,
            'outdated' => $outdated,
            'soft_deleted' => $softDeleted,
            'extra_in_database' => $extraInDatabase,
            'missing_json_files' => SupportedLanguages::missingJsonFiles(),
            'orphan_json_files' => SupportedLanguages::orphanJsonFiles(),
        ];
    }

    /**
     * @return array{
     *     created: list<int>,
     *     updated: list<int>,
     *     restored: list<int>,
     *     unchanged: list<int>
     * }
     */
    public function sync(bool $updateExisting = true): array
    {
        $result = [
            'created' => [],
            'updated' => [],
            'restored' => [],
            'unchanged' => [],
        ];

        $now = Carbon::now();

        foreach (SupportedLanguages::recordsForDatabase() as $record) {
            $languageId = (int) $record['language_id'];
            $definition = SupportedLanguages::definitions()[$languageId];
            $language = Language::withTrashed()->find($languageId);

            if ($language === null) {
                $language = new Language();
                $language->language_id = $languageId;
                $language->name = $record['name'];
                $language->iso_639_1 = $record['iso_639_1'];
                $language->type = $record['type'];
                $language->status = $record['status'];
                $language->created_at = $now;
                $language->updated_at = $now;
                $language->save();
                $result['created'][] = $languageId;

                continue;
            }

            $restored = false;
            if ($language->trashed()) {
                $language->restore();
                $restored = true;
                $result['restored'][] = $languageId;
            }

            if ($updateExisting && $this->needsUpdate($language, $definition)) {
                $language->name = $record['name'];
                $language->iso_639_1 = $record['iso_639_1'];
                $language->type = $record['type'];
                $language->status = $record['status'];
                $language->updated_at = $now;
                $language->save();
                $result['updated'][] = $languageId;

                continue;
            }

            if (! $restored) {
                $result['unchanged'][] = $languageId;
            }
        }

        return $result;
    }

    /**
     * @param  array{name: string, iso_639_1: string, type: string, status: int}  $definition
     */
    private function needsUpdate(Language $language, array $definition): bool
    {
        return (string) ($language->name ?? '') !== $definition['name']
            || (string) ($language->iso_639_1 ?? '') !== $definition['iso_639_1']
            || (string) ($language->type ?? '') !== $definition['type']
            || (int) ($language->status ?? 0) !== (int) $definition['status'];
    }
}
