<?php

namespace App\Services;

use App\Models\Country;

/**
 * Mapea filas de cliente API al shape que esperan las vistas seller/* (paridad con Seller_portal.php).
 */
final class SellerPortalCustomerGridMapper
{
    /**
     * @param list<array<string, mixed>> $apiCustomers
     * @param array<int, string>         $sellerNameById
     *
     * @return list<array<string, mixed>>
     */
    public function mapMany(array $apiCustomers, int $sellerId, array $sellerNameById, ?array $countryNameToId = null): array
    {
        if ($apiCustomers === []) {
            return [];
        }

        $countryNameToId ??= $this->countryNameToIdMap();

        $upper = static function (string $s): string {
            $s = trim($s);
            if ($s === '') {
                return '';
            }

            return function_exists('mb_strtoupper') ? mb_strtoupper($s, 'UTF-8') : strtoupper($s);
        };

        $out = [];
        foreach ($apiCustomers as $c) {
            $mapped = $this->mapOne($c, $sellerId, $sellerNameById, $countryNameToId, $upper);
            if ($mapped !== null) {
                $out[] = $mapped;
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $c
     * @param array<int, string>   $sellerNameById
     * @param array<string, int>   $countryNameToId
     *
     * @return array<string, mixed>|null
     */
    private function mapOne(array $c, int $sellerId, array $sellerNameById, array $countryNameToId, callable $upper): ?array
    {
        $id = (int) ($c['customer_id'] ?? 0);
        if ($id <= 0) {
            return null;
        }

        $business = trim((string) ($c['business_name'] ?? ''));
        $contact = trim((string) ($c['name'] ?? ''));
        $email = trim((string) ($c['email'] ?? ''));
        $phone = trim((string) ($c['phone'] ?? ''));
        $mobile = trim((string) ($c['cell_phone'] ?? ''));
        $taxId = trim((string) ($c['tax_id'] ?? ''));
        $custKor = trim((string) ($c['cust_id_kor'] ?? ''));
        $idKor = (int) ($c['id_kor'] ?? 0);
        $erp = $custKor;
        if ($idKor > 0) {
            $erp = $erp !== '' ? ($erp . ' / ' . (string) $idKor) : (string) $idKor;
        }
        $st = (int) ($c['customer_status'] ?? 0);
        $status = $st === 1 ? 'active' : 'inactive';
        $joinDateRaw = trim((string) ($c['customer_created_at'] ?? ''));
        if ($joinDateRaw === '') {
            $joinDateRaw = trim((string) ($c['modified_at'] ?? ''));
        }
        $joinDate = '';
        if ($joinDateRaw !== '') {
            $ts = strtotime($joinDateRaw);
            if ($ts !== false) {
                $joinDate = date('d-M-Y', $ts);
            }
        }
        $labelParts = array_filter([$business, $contact, $email], static fn ($s) => trim((string) $s) !== '');
        $label = $labelParts !== [] ? implode(' · ', $labelParts) : ('#' . (string) $id);

        $commAddr = trim((string) ($c['commercial_address'] ?? ''));
        $commState = trim((string) ($c['commercial_state'] ?? ''));
        $commCity = trim((string) ($c['commercial_city'] ?? ''));
        $commZip = trim((string) ($c['commercial_zip_code'] ?? ''));
        $commCountry = trim((string) ($c['commercial_country'] ?? ''));
        // Orden fijo: Address, State, City, ZipCode, Country
        $addressSegments = [];
        foreach ([$commAddr, $commState, $commCity, $commZip, $commCountry] as $seg) {
            $t = trim((string) $seg);
            if ($t !== '') {
                $addressSegments[] = $upper($t);
            }
        }
        $addressFull = implode(', ', $addressSegments);
        $nameSortDisplay = $contact !== '' ? $upper($contact) : $upper($business);

        $gid = (int) ($c['group_id'] ?? 0);
        $groupName = '';
        $cg = $c['customer_group'] ?? null;
        if (is_array($cg)) {
            $groupName = trim((string) ($cg['name'] ?? ''));
        } elseif (is_object($cg)) {
            $groupName = trim((string) ($cg->name ?? ''));
        }

        $assignSid = (int) ($c['seller_assign_catalog'] ?? 0);
        $effectiveSellerId = $assignSid > 0 ? $assignSid : $sellerId;
        $assignedName = $sellerNameById[$effectiveSellerId] ?? ('#' . (string) $effectiveSellerId);
        $sendCatalog = (int) ($c['catalog_emails'] ?? 0) === 1;

        $erpIdCol = $idKor > 0 ? (string) $idKor : ($custKor !== '' ? $custKor : '');
        $termIdRow = (int) ($c['term_id'] ?? 0);
        $commDelivery = trim((string) ($c['commercial_delivery_address'] ?? ''));
        $dispDel = trim((string) ($c['dispatch_delivery_address'] ?? ''));
        $dispAddr = trim((string) ($c['dispatch_address'] ?? ''));
        $dispState = trim((string) ($c['dispatch_state'] ?? ''));
        $dispCity = trim((string) ($c['dispatch_city'] ?? ''));
        $dispZip = trim((string) ($c['dispatch_zip_code'] ?? ''));
        $dispZone = trim((string) ($c['dispatch_zone'] ?? ''));
        $dispCountryStr = trim((string) ($c['dispatch_country'] ?? ''));
        $notesVal = trim((string) ($c['notes'] ?? ''));
        $shipNotesVal = trim((string) ($c['dispatch_shipping_notes'] ?? ''));
        $discountVal = (float) ($c['discount'] ?? 0);
        $commCountryIdVal = (int) ($c['commercial_country_id'] ?? 0);
        if ($commCountryIdVal === 0 && $commCountry !== '') {
            $commCountryIdVal = $this->countryIdFromName($commCountry, $countryNameToId);
        }
        $dispCountryIdVal = (int) ($c['dispatch_country_id'] ?? 0);
        if ($dispCountryIdVal === 0 && $dispCountryStr !== '') {
            $dispCountryIdVal = $this->countryIdFromName($dispCountryStr, $countryNameToId);
        }
        $sameAsCommercial = ($commAddr === $dispAddr) && ($commState === $dispState) && ($commCity === $dispCity)
            && ($commZip === $dispZip) && ($commZone === $dispZone) && ($commCountry === $dispCountryStr)
            && ($commDelivery === $dispDel);

        $pivotSellerIds = [];
        $sellers = $c['sellers'] ?? null;
        if (is_array($sellers)) {
            foreach ($sellers as $sv) {
                $sid = is_array($sv) ? (int) ($sv['id'] ?? 0) : (int) ($sv->id ?? 0);
                if ($sid > 0) {
                    $pivotSellerIds[$sid] = true;
                }
            }
        }
        $pivotSellerIds = array_keys($pivotSellerIds);
        sort($pivotSellerIds, SORT_NUMERIC);
        if ($pivotSellerIds === []) {
            $pivotSellerIds = [$effectiveSellerId];
        }

        $formSeed = [
            'customer_id' => $id,
            'name' => $contact,
            'business_name' => $business,
            'email' => $email,
            'phone' => $phone,
            'cell_phone' => $mobile,
            'seller_ids' => $pivotSellerIds,
            'tax_id' => $taxId,
            'id_kor' => $idKor,
            'erp_code' => $custKor,
            'discount' => $discountVal,
            'catalog_emails' => $sendCatalog ? '1' : '0',
            'seller_assign_catalog' => $effectiveSellerId,
            'term_id' => $termIdRow,
            'group_id' => $gid,
            'commercial_address' => $commAddr,
            'commercial_delivery_address' => $commDelivery,
            'commercial_country_id' => $commCountryIdVal,
            'commercial_state' => $commState,
            'commercial_city' => $commCity,
            'commercial_zip_code' => $commZip,
            'commercial_zone' => $commZone,
            'dispatch_delivery_address' => $dispDel,
            'dispatch_address' => $dispAddr,
            'dispatch_country_id' => $dispCountryIdVal,
            'dispatch_state' => $dispState,
            'dispatch_city' => $dispCity,
            'dispatch_zip_code' => $dispZip,
            'dispatch_zone' => $dispZone,
            'notes' => $notesVal,
            'delivery_notes' => $shipNotesVal,
            'same_as_commercial' => $sameAsCommercial,
        ];

        return [
            'id' => $id,
            'label' => $label,
            'business' => $business,
            'contact' => $contact,
            'email' => $email,
            'phone' => $phone,
            'mobile' => $mobile,
            'tax_id' => $taxId,
            'erp' => $erp,
            'status' => $status,
            'join_date' => $joinDate,
            'address_full' => $addressFull,
            'customer_group_name' => $groupName,
            'name_sort_display' => $nameSortDisplay,
            'group_id' => $gid,
            'send_catalog' => $sendCatalog,
            'assigned_seller_id' => $effectiveSellerId,
            'assigned_seller_name' => $assignedName,
            'erp_id' => $erpIdCol,
            'term_id' => $termIdRow,
            'form' => $formSeed,
        ];
    }

    /**
     * @param array<string, int> $map
     */
    private function countryIdFromName(string $countryName, array $map): int
    {
        $key = function_exists('mb_strtolower') ? mb_strtolower(trim($countryName), 'UTF-8') : strtolower(trim($countryName));

        return (int) ($map[$key] ?? 0);
    }

    /**
     * @return array<string, int>
     */
    public function countryNameToIdMap(): array
    {
        $map = [];
        foreach (Country::query()->get(['id', 'name']) as $row) {
            $id = (int) $row->id;
            $nm = trim((string) ($row->name ?? ''));
            if ($id <= 0 || $nm === '') {
                continue;
            }
            $key = function_exists('mb_strtolower') ? mb_strtolower($nm, 'UTF-8') : strtolower($nm);
            $map[$key] = $id;
        }

        return $map;
    }
}
