<?php

namespace App\Http\Controllers;

use App\Models\Currencybycountry;
use Illuminate\Support\Facades\Schema;

class CurrencybycountryController extends Controller
{
    /**
     * Catálogo legacy agrupado por GroupID (tabla currencybycountries).
     *
     * @return array<int, object{GroupID: string, currencies: array<int, object>}>
     */
    public function grouped(): array
    {
        if (! Schema::hasTable('currencybycountries')) {
            return [];
        }

        $rows = Currencybycountry::query()
            ->orderBy('GroupID')
            ->orderBy('id')
            ->get();

        $groups = [];
        $groupIndex = [];

        foreach ($rows as $row) {
            $groupId = trim((string) ($row->GroupID ?? ''));
            if ($groupId === '') {
                $groupId = 'Other';
            }

            if (! isset($groupIndex[$groupId])) {
                $groupIndex[$groupId] = count($groups);
                $groups[] = (object) [
                    'GroupID' => $groupId,
                    'currencies' => [],
                ];
            }

            $idx = $groupIndex[$groupId];
            $groups[$idx]->currencies[] = (object) [
                'id' => (int) $row->id,
                'GroupID' => $groupId,
                'Contry' => (string) ($row->Contry ?? ''),
                'Name' => (string) ($row->Name ?? ''),
                'Code' => (string) ($row->Code ?? ''),
                'Symbol' => (string) ($row->Symbol ?? ''),
                'default' => (string) ($row->default ?? '') === 'selected' ? 'selected' : '',
            ];
        }

        return $groups;
    }
}
