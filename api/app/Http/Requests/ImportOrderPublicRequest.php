<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportOrderPublicRequest extends FormRequest
{
    /**
     * Campos canónicos del cabezal (order/import y order/import-bulk).
     * Contrato oficial: prefijos customer_* y direcciones commercial_ / dispatch_.
     *
     * @return list<string>
     */
    public static function cabezalFieldNames(): array
    {
        return [
            'iderp', 'fecha', 'norden', 'notas', 'descuento', 'subtotal', 'total',
            'codigo_estado', 'numero_estado', 'nombre_estado',
            'seller_id', 'seller_name', 'seller_email',
            'warehouse_user_id', 'warehouse_name', 'warehouse_email',
            'customer_business_name', 'customer_name', 'customer_email', 'customer_phone',
            'customer_cell_phone', 'customer_tax_id', 'customer_notes',
            'customer_group_id', 'customer_term_id',
            'commercial_address', 'commercial_country', 'commercial_state',
            'commercial_city', 'commercial_zip_code',
            'dispatch_address', 'dispatch_country', 'dispatch_state', 'dispatch_city',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalized = self::normalizeOrderPayload([
            'company_id' => $this->input('company_id'),
            'cabezal' => $this->input('cabezal', $this->input('header', [])),
            'customer' => $this->input('customer'),
            'items' => $this->input('items', $this->input('productos', $this->input('lines', []))),
            'seller_id' => $this->input('seller_id'),
            'seller_name' => $this->input('seller_name'),
            'seller_email' => $this->input('seller_email'),
            'warehouse_user_id' => $this->input('warehouse_user_id'),
            'almacenero_id' => $this->input('almacenero_id'),
            'warehouse_name' => $this->input('warehouse_name'),
            'warehouse_email' => $this->input('warehouse_email'),
        ]);

        $this->merge($normalized);
    }

    /**
     * Normaliza una orden (cabezal + items) al contrato canónico customer_*.
     *
     * @param  array<string, mixed>  $input
     * @return array{company_id:mixed,cabezal:array,items:array}
     */
    public static function normalizeOrderPayload(array $input): array
    {
        $cabezal = $input['cabezal'] ?? $input['header'] ?? [];
        if (! is_array($cabezal)) {
            $cabezal = [];
        }

        $customerBlock = $cabezal['customer'] ?? $cabezal['cliente'] ?? $input['customer'] ?? [];
        if (! is_array($customerBlock)) {
            $customerBlock = [];
        }

        $items = $input['items'] ?? $input['productos'] ?? $input['lines'] ?? [];
        if (! is_array($items)) {
            $items = [];
        }

        $pick = static function (array ...$sources) {
            foreach ($sources as $source) {
                foreach ($source as $value) {
                    if ($value !== null && $value !== '') {
                        return $value;
                    }
                }
            }

            return null;
        };

        $map = [
            'iderp' => $pick(
                [$cabezal['iderp'] ?? null, $cabezal['cust_id_kor'] ?? null, $cabezal['erp_id'] ?? null],
                [$customerBlock['iderp'] ?? null, $customerBlock['cust_id_kor'] ?? null]
            ),
            'fecha' => $cabezal['fecha'] ?? $cabezal['date'] ?? $cabezal['created'] ?? null,
            'norden' => $cabezal['norden'] ?? $cabezal['order_number'] ?? $cabezal['numero_orden'] ?? null,
            'notas' => $cabezal['notas'] ?? $cabezal['order_notes'] ?? $cabezal['nota'] ?? null,
            'descuento' => $cabezal['descuento'] ?? $cabezal['order_discount'] ?? 0,
            'subtotal' => $cabezal['subtotal'] ?? $cabezal['amount'] ?? null,
            'total' => $cabezal['total'] ?? $cabezal['total_amount'] ?? null,
            'codigo_estado' => $cabezal['codigo_estado'] ?? $cabezal['cod_status'] ?? $cabezal['status_code'] ?? null,
            'numero_estado' => $cabezal['numero_estado'] ?? $cabezal['id_status'] ?? $cabezal['status_id'] ?? null,
            'nombre_estado' => $cabezal['nombre_estado'] ?? $cabezal['status_name'] ?? $cabezal['estado'] ?? null,
            'company_id' => $cabezal['company_id'] ?? $input['company_id'] ?? null,
            'seller_id' => $cabezal['seller_id']
                ?? $cabezal['vendedor_id']
                ?? $cabezal['seller']
                ?? $input['seller_id'] ?? null,
            'seller_name' => $cabezal['seller_name']
                ?? $cabezal['vendedor_nombre']
                ?? $cabezal['nombre_vendedor']
                ?? $input['seller_name'] ?? null,
            'seller_email' => $cabezal['seller_email']
                ?? $cabezal['vendedor_email']
                ?? $cabezal['email_vendedor']
                ?? $input['seller_email'] ?? null,
            'warehouse_user_id' => $cabezal['warehouse_user_id']
                ?? $cabezal['almacenero_id']
                ?? $cabezal['almacenero']
                ?? $input['warehouse_user_id']
                ?? $input['almacenero_id'] ?? null,
            'warehouse_name' => $cabezal['warehouse_name']
                ?? $cabezal['almacenero_nombre']
                ?? $cabezal['nombre_almacenero']
                ?? $input['warehouse_name'] ?? null,
            'warehouse_email' => $cabezal['warehouse_email']
                ?? $cabezal['almacenero_email']
                ?? $cabezal['email_almacenero']
                ?? $input['warehouse_email'] ?? null,

            'customer_business_name' => $pick(
                [$cabezal['customer_business_name'] ?? null, $cabezal['business_name'] ?? null, $cabezal['razon_social'] ?? null],
                [$customerBlock['customer_business_name'] ?? null, $customerBlock['business_name'] ?? null, $customerBlock['razon_social'] ?? null]
            ),
            'customer_name' => $pick(
                [$cabezal['customer_name'] ?? null, $cabezal['name'] ?? null, $cabezal['nombre_cliente'] ?? null],
                [$customerBlock['customer_name'] ?? null, $customerBlock['name'] ?? null, $customerBlock['nombre'] ?? null]
            ),
            'customer_email' => $pick(
                [$cabezal['customer_email'] ?? null, $cabezal['email'] ?? null, $cabezal['email_cliente'] ?? null],
                [$customerBlock['customer_email'] ?? null, $customerBlock['email'] ?? null]
            ),
            'customer_phone' => $pick(
                [$cabezal['customer_phone'] ?? null, $cabezal['phone'] ?? null, $cabezal['telefono'] ?? null],
                [$customerBlock['customer_phone'] ?? null, $customerBlock['phone'] ?? null, $customerBlock['telefono'] ?? null]
            ),
            'customer_cell_phone' => $pick(
                [$cabezal['customer_cell_phone'] ?? null, $cabezal['cell_phone'] ?? null, $cabezal['celular'] ?? null],
                [$customerBlock['customer_cell_phone'] ?? null, $customerBlock['cell_phone'] ?? null, $customerBlock['celular'] ?? null]
            ),
            'customer_tax_id' => $pick(
                [$cabezal['customer_tax_id'] ?? null, $cabezal['tax_id'] ?? null, $cabezal['rfc'] ?? null, $cabezal['nit'] ?? null],
                [$customerBlock['customer_tax_id'] ?? null, $customerBlock['tax_id'] ?? null, $customerBlock['rfc'] ?? null]
            ),
            'customer_notes' => $pick(
                [$cabezal['customer_notes'] ?? null, $cabezal['notes'] ?? null, $cabezal['notas_cliente'] ?? null],
                [$customerBlock['customer_notes'] ?? null, $customerBlock['notes'] ?? null, $customerBlock['notas'] ?? null]
            ),
            'customer_group_id' => $pick(
                [$cabezal['customer_group_id'] ?? null, $cabezal['group_id'] ?? null],
                [$customerBlock['customer_group_id'] ?? null, $customerBlock['group_id'] ?? null]
            ),
            'customer_term_id' => $pick(
                [$cabezal['customer_term_id'] ?? null, $cabezal['term_id'] ?? null],
                [$customerBlock['customer_term_id'] ?? null, $customerBlock['term_id'] ?? null]
            ),
            'commercial_address' => $pick([$cabezal['commercial_address'] ?? null], [$customerBlock['commercial_address'] ?? null]),
            'commercial_country' => $pick([$cabezal['commercial_country'] ?? null], [$customerBlock['commercial_country'] ?? null]),
            'commercial_state' => $pick([$cabezal['commercial_state'] ?? null], [$customerBlock['commercial_state'] ?? null]),
            'commercial_city' => $pick([$cabezal['commercial_city'] ?? null], [$customerBlock['commercial_city'] ?? null]),
            'commercial_zip_code' => $pick([$cabezal['commercial_zip_code'] ?? null], [$customerBlock['commercial_zip_code'] ?? null]),
            'dispatch_address' => $pick([$cabezal['dispatch_address'] ?? null], [$customerBlock['dispatch_address'] ?? null]),
            'dispatch_country' => $pick([$cabezal['dispatch_country'] ?? null], [$customerBlock['dispatch_country'] ?? null]),
            'dispatch_state' => $pick([$cabezal['dispatch_state'] ?? null], [$customerBlock['dispatch_state'] ?? null]),
            'dispatch_city' => $pick([$cabezal['dispatch_city'] ?? null], [$customerBlock['dispatch_city'] ?? null]),
        ];

        // Ítems con cantidad=0 o precio=0 se omiten.
        // Datos de producto (nombres, no IDs) para alta si el SKU no existe.
        $normalizedItems = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $normalized = self::normalizeOrderItem($item);
            if ($normalized === null) {
                continue;
            }
            $normalizedItems[] = $normalized;
        }

        $canonicalCabezal = [];
        foreach (self::cabezalFieldNames() as $field) {
            if (array_key_exists($field, $map)) {
                $canonicalCabezal[$field] = $map[$field];
            }
        }
        if (isset($map['company_id'])) {
            $canonicalCabezal['company_id'] = $map['company_id'];
        }

        return [
            'company_id' => $map['company_id'],
            'cabezal' => $canonicalCabezal,
            'items' => $normalizedItems,
        ];
    }

    /**
     * Normaliza un ítem de orden + campos de producto (alta si SKU no existe).
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null null si se omite (cantidad/precio 0)
     */
    public static function normalizeOrderItem(array $item): ?array
    {
        $cantidad = $item['cantidad'] ?? $item['qty'] ?? $item['quantity'] ?? null;
        $precio = $item['precio'] ?? $item['price'] ?? $item['sale_price'] ?? null;
        if (is_numeric($cantidad) && (float) $cantidad == 0.0) {
            return null;
        }
        if (is_numeric($precio) && (float) $precio == 0.0) {
            return null;
        }

        $stock = $item['stock'] ?? null;
        if (is_numeric($stock) && (float) $stock < 0) {
            $stock = 0;
        }

        $status = self::normalizeFlag01($item['status'] ?? null);
        $notifyMoq = self::normalizeFlag01(
            $item['notify_minimum_stock'] ?? $item['notify_moq'] ?? $item['notifymoq'] ?? null
        );
        $sellWithInventory = self::normalizeFlag01(
            $item['sell_with_inventory'] ?? $item['vender_con_inventario'] ?? null
        );

        $tags = $item['tags'] ?? $item['etiquetas'] ?? null;
        if (is_array($tags)) {
            $tags = implode(',', array_filter(array_map(
                static fn ($t) => trim((string) (is_array($t) ? ($t['name'] ?? $t['tag'] ?? '') : $t)),
                $tags
            )));
        }

        $locations = $item['locations'] ?? $item['location'] ?? $item['ubicaciones'] ?? null;
        if (is_array($locations)) {
            $locations = implode(',', array_filter(array_map(
                static fn ($l) => trim((string) (is_array($l) ? ($l['name'] ?? $l['location'] ?? '') : $l)),
                $locations
            )));
        }

        return [
            // Línea de orden
            'sku' => $item['sku'] ?? $item['SKU'] ?? null,
            'cantidad' => $cantidad,
            'precio' => $precio,
            // Producto (todos los campos fillable útiles; categorías/grupos por NOMBRE)
            'name' => $item['name'] ?? $item['product_name'] ?? $item['nombre'] ?? $item['nombre_producto'] ?? null,
            'sale_price' => $item['sale_price'] ?? $precio,
            'categories' => self::normalizeNameList(
                $item['categories'] ?? $item['categorias'] ?? $item['category'] ?? $item['category_names'] ?? []
            ),
            'category_groups' => self::normalizeNameList(
                $item['category_groups'] ?? $item['grupos'] ?? $item['category_group'] ?? $item['group_names'] ?? []
            ),
            'category_subgroups' => self::normalizeNameList(
                $item['category_subgroups'] ?? $item['subgrupos'] ?? $item['category_subgroup'] ?? $item['subgroup_names'] ?? []
            ),
            'barcode' => $item['barcode'] ?? $item['codigo_barras'] ?? null,
            'barcode_images' => $item['barcode_images'] ?? null,
            'tags' => $tags,
            'descriptions' => $item['descriptions'] ?? $item['description'] ?? $item['descripcion'] ?? null,
            'stock' => $stock,
            'minimum_stock' => $item['minimum_stock'] ?? $item['min_stock'] ?? $item['stock_minimo'] ?? null,
            'notify_minimum_stock' => $notifyMoq,
            'currency_type' => $item['currency_type'] ?? $item['currency'] ?? null,
            'fob_price' => $item['fob_price'] ?? $item['fob'] ?? null,
            'purchase_price' => $item['purchase_price'] ?? $item['purchase'] ?? $item['cost'] ?? null,
            'locations' => $locations,
            'status' => $status,
            'sell_with_inventory' => $sellWithInventory,
            'price_reduced' => $item['price_reduced'] ?? $item['precio_reducido'] ?? null,
            'sem_red_hasta' => $item['sem_red_hasta'] ?? null,
            'sem_amarillo_desde' => $item['sem_amarillo_desde'] ?? null,
            'sem_amarillo_hasta' => $item['sem_amarillo_hasta'] ?? null,
            'sem_verde_desde' => $item['sem_verde_desde'] ?? null,
            'sem_verde_hasta' => $item['sem_verde_hasta'] ?? null,
            'unit' => $item['unit'] ?? $item['unidad'] ?? $item['unit_name'] ?? null,
        ];
    }

    /**
     * Normaliza flags 0/1 (bool, string o número). Null si ausente.
     */
    public static function normalizeFlag01(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if (is_string($value) && ! is_numeric($value)) {
            $lower = mb_strtolower(trim($value), 'UTF-8');

            return in_array($lower, ['1', 'true', 'yes', 'si', 'sí', 'active', 'activo', 'on'], true) ? 1 : 0;
        }

        return (int) $value ? 1 : 0;
    }

    /**
     * @param  mixed  $value
     * @return list<string>
     */
    public static function normalizeNameList(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[,;|]/', $value) ?: [];
        }
        if (! is_array($value)) {
            return [];
        }
        $names = [];
        foreach ($value as $entry) {
            if (is_array($entry)) {
                $entry = $entry['name'] ?? $entry['category_name'] ?? $entry['group_name']
                    ?? $entry['subgroup_name'] ?? $entry['nombre'] ?? null;
            }
            $name = trim((string) $entry);
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * @return array<string, mixed>
     */
    public static function orderItemProductRules(string $itemPrefix): array
    {
        return [
            $itemPrefix.'sku' => ['required', 'string', 'max:255'],
            $itemPrefix.'cantidad' => ['required', 'numeric', 'gt:0'],
            $itemPrefix.'precio' => ['required', 'numeric', 'gt:0'],
            $itemPrefix.'name' => ['nullable', 'string', 'max:255'],
            $itemPrefix.'sale_price' => ['nullable', 'numeric', 'min:0'],
            $itemPrefix.'categories' => ['nullable', 'array'],
            $itemPrefix.'categories.*' => ['nullable', 'string', 'max:255'],
            $itemPrefix.'category_groups' => ['nullable', 'array'],
            $itemPrefix.'category_groups.*' => ['nullable', 'string', 'max:255'],
            $itemPrefix.'category_subgroups' => ['nullable', 'array'],
            $itemPrefix.'category_subgroups.*' => ['nullable', 'string', 'max:255'],
            $itemPrefix.'barcode' => ['nullable', 'string', 'max:255'],
            $itemPrefix.'barcode_images' => ['nullable', 'string', 'max:2000'],
            $itemPrefix.'tags' => ['nullable', 'string', 'max:2000'],
            $itemPrefix.'descriptions' => ['nullable', 'string', 'max:10000'],
            $itemPrefix.'stock' => ['nullable', 'numeric', 'min:0'],
            $itemPrefix.'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            $itemPrefix.'notify_minimum_stock' => ['nullable', 'integer', 'min:0', 'max:1'],
            $itemPrefix.'currency_type' => ['nullable', 'string', 'max:50'],
            $itemPrefix.'fob_price' => ['nullable', 'numeric', 'min:0'],
            $itemPrefix.'purchase_price' => ['nullable', 'numeric', 'min:0'],
            $itemPrefix.'locations' => ['nullable', 'string', 'max:2000'],
            $itemPrefix.'status' => ['nullable', 'integer', 'in:0,1'],
            $itemPrefix.'sell_with_inventory' => ['nullable', 'integer', 'in:0,1'],
            $itemPrefix.'price_reduced' => ['nullable', 'numeric', 'min:0'],
            $itemPrefix.'sem_red_hasta' => ['nullable', 'integer', 'min:0'],
            $itemPrefix.'sem_amarillo_desde' => ['nullable', 'integer', 'min:0'],
            $itemPrefix.'sem_amarillo_hasta' => ['nullable', 'integer', 'min:0'],
            $itemPrefix.'sem_verde_desde' => ['nullable', 'integer', 'min:0'],
            $itemPrefix.'sem_verde_hasta' => ['nullable', 'integer', 'min:0'],
            $itemPrefix.'unit' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function orderPayloadRules(string $prefix = ''): array
    {
        $p = $prefix === '' ? '' : rtrim($prefix, '.').'.';

        return array_merge([
            $p.'cabezal' => ['required', 'array'],
            $p.'cabezal.iderp' => ['required', 'string', 'max:50'],
            $p.'cabezal.fecha' => ['required', 'date'],
            $p.'cabezal.norden' => ['required', 'string', 'max:255'],
            $p.'cabezal.notas' => ['nullable', 'string', 'max:5000'],
            $p.'cabezal.descuento' => ['nullable', 'numeric', 'min:0'],
            $p.'cabezal.subtotal' => ['nullable', 'numeric', 'min:0'],
            $p.'cabezal.total' => ['nullable', 'numeric', 'min:0'],
            $p.'cabezal.codigo_estado' => ['nullable'],
            $p.'cabezal.numero_estado' => ['nullable'],
            $p.'cabezal.nombre_estado' => ['nullable', 'string', 'max:255'],
            $p.'cabezal.seller_id' => ['nullable', 'integer'],
            $p.'cabezal.seller_name' => ['nullable', 'string', 'max:255'],
            $p.'cabezal.seller_email' => ['nullable', 'string', 'max:255'],
            $p.'cabezal.warehouse_user_id' => ['nullable', 'integer'],
            $p.'cabezal.warehouse_name' => ['nullable', 'string', 'max:255'],
            $p.'cabezal.warehouse_email' => ['nullable', 'string', 'max:255'],
            $p.'cabezal.customer_business_name' => ['nullable', 'string', 'max:255'],
            $p.'cabezal.customer_name' => ['nullable', 'string', 'max:255'],
            $p.'cabezal.customer_email' => ['nullable', 'string', 'max:255'],
            $p.'cabezal.customer_phone' => ['nullable', 'string', 'max:50'],
            $p.'cabezal.customer_cell_phone' => ['nullable', 'string', 'max:50'],
            $p.'cabezal.customer_tax_id' => ['nullable', 'string', 'max:255'],
            $p.'cabezal.customer_notes' => ['nullable', 'string', 'max:5000'],
            $p.'cabezal.customer_group_id' => ['nullable', 'integer'],
            $p.'cabezal.customer_term_id' => ['nullable', 'integer'],
            $p.'cabezal.commercial_address' => ['nullable', 'string', 'max:255'],
            $p.'cabezal.commercial_country' => ['nullable', 'string', 'max:255'],
            $p.'cabezal.commercial_state' => ['nullable', 'string', 'max:255'],
            $p.'cabezal.commercial_city' => ['nullable', 'string', 'max:255'],
            $p.'cabezal.commercial_zip_code' => ['nullable', 'string', 'max:20'],
            $p.'cabezal.dispatch_address' => ['nullable', 'string', 'max:255'],
            $p.'cabezal.dispatch_country' => ['nullable', 'string', 'max:255'],
            $p.'cabezal.dispatch_state' => ['nullable', 'string', 'max:255'],
            $p.'cabezal.dispatch_city' => ['nullable', 'string', 'max:255'],
            $p.'items' => ['nullable', 'array'],
        ], self::orderItemProductRules($p.'items.*.'));
    }

    /**
     * Valida un payload de una sola orden (tras normalizeOrderPayload).
     *
     * @param  array<string, mixed>  $payload
     * @return array{ok:true,data:array}|array{ok:false,errors:array}
     */
    public static function validateNormalizedOrder(array $payload): array
    {
        $validator = \Illuminate\Support\Facades\Validator::make(
            $payload,
            array_merge(
                ['company_id' => ['required', 'integer', 'exists:companies,company_id']],
                self::orderPayloadRules()
            )
        );

        if ($validator->fails()) {
            return [
                'ok' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        $data = $validator->validated();
        // Conservar ítems normalizados completos (campos de producto).
        $data['items'] = $payload['items'] ?? ($data['items'] ?? []);
        $data['cabezal'] = $payload['cabezal'] ?? ($data['cabezal'] ?? []);

        return [
            'ok' => true,
            'data' => $data,
        ];
    }

    public function rules(): array
    {
        return array_merge(
            ['company_id' => ['required', 'integer', 'exists:companies,company_id']],
            self::orderPayloadRules()
        );
    }

    /**
     * Conservar ítems/cabezal normalizados completos (campos de producto no se pierden).
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        if ($key !== null) {
            return $data;
        }

        $data['items'] = $this->input('items', $data['items'] ?? []);
        $data['cabezal'] = $this->input('cabezal', $data['cabezal'] ?? []);

        return $data;
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'company_id es obligatorio en el cabezal o en la raíz del JSON.',
            'cabezal.iderp.required' => 'El iderp del cliente es obligatorio.',
            'cabezal.fecha.required' => 'La fecha de la orden es obligatoria.',
            'cabezal.norden.required' => 'El número de orden (norden) es obligatorio.',
            'items.required' => 'Debe enviar al menos un ítem.',
            'items.*.sku.required' => 'Cada ítem debe incluir sku.',
            'items.*.cantidad.required' => 'Cada ítem debe incluir cantidad.',
            'items.*.precio.required' => 'Cada ítem debe incluir precio.',
        ];
    }
}
