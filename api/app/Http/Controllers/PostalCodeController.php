<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\PostalCode;
use App\Models\State;
use Illuminate\Http\Request;

class PostalCodeController extends Controller
{
    public function search(Request $request)
    {
        $cityId = (int) $request->query('city_id', 0);
        $stateId = (int) $request->query('state_id', 0);
        $countryId = (int) $request->query('country_id', 0);
        $term = trim((string) $request->query('q', $request->query('term', '')));

        if ($cityId <= 0 && $stateId <= 0 && $countryId <= 0) {
            return response()->json(['data' => []]);
        }

        $q = PostalCode::query()->select(['id', 'postal_code', 'place_name', 'city_id', 'state_id', 'country_id']);

        if ($cityId > 0) {
            $city = City::query()->find($cityId);
            $q->where(function ($w) use ($cityId, $city) {
                $w->where('city_id', $cityId);
                if ($city) {
                    $state = State::query()->find($city->state_id);
                    $w->orWhere(function ($w2) use ($city, $state) {
                        $w2->whereRaw('UPPER(place_name) = ?', [mb_strtoupper(trim((string) $city->name))]);
                        if ($state) {
                            $w2->whereRaw('UPPER(admin_name1) = ?', [mb_strtoupper(trim((string) $state->name))]);
                            $country = Country::query()->find($state->country_id);
                            if ($country) {
                                $w2->where('country_iso2', strtoupper(substr((string) $country->sortname, 0, 2)));
                            }
                        }
                    });
                }
            });
        } elseif ($stateId > 0) {
            $state = State::query()->find($stateId);
            $q->where(function ($w) use ($stateId, $state) {
                $w->where('state_id', $stateId);
                if ($state) {
                    $w->orWhere(function ($w2) use ($state) {
                        $w2->whereRaw('UPPER(admin_name1) = ?', [mb_strtoupper(trim((string) $state->name))]);
                        $country = Country::query()->find($state->country_id);
                        if ($country) {
                            $w2->where('country_iso2', strtoupper(substr((string) $country->sortname, 0, 2)));
                        }
                    });
                }
            });
        } else {
            $country = Country::query()->find($countryId);
            $q->where(function ($w) use ($countryId, $country) {
                $w->where('country_id', $countryId);
                if ($country) {
                    $w->orWhere('country_iso2', strtoupper(substr((string) $country->sortname, 0, 2)));
                }
            });
        }

        if ($term !== '') {
            $q->where('postal_code', 'like', $term . '%');
        }

        $rows = $q->orderBy('postal_code')->limit(500)->get();

        $seen = [];
        $data = [];
        foreach ($rows as $row) {
            $code = trim((string) $row->postal_code);
            if ($code === '' || isset($seen[$code])) {
                continue;
            }
            $seen[$code] = true;
            $data[] = [
                'id' => $code,
                'postal_code' => $code,
                'name' => $code,
                'place_name' => (string) $row->place_name,
            ];
        }

        return response()->json(['data' => array_values($data)]);
    }
}
