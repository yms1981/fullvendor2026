<?php

namespace App\Services;

/**
 * Parsea catalog.attach (definición de Catalog Studio) y resuelve layout/cols.
 *
 * Formato attach: template_key::show_prices::banner_bg::banner_text::footer_bg::footer_text::accent
 */
final class CatalogStudioDefinitionService
{
    /**
     * Layout/cols alineados con mvc/companyasset/company/catalog/templates/manifest.json
     *
     * @var array<string, array{layout: string, cols: int}>
     */
    private const TEMPLATE_META = [
        'classic-grid' => ['layout' => 'grid', 'cols' => 3],
        'photo-gallery' => ['layout' => 'grid', 'cols' => 4],
        'boutique' => ['layout' => 'grid', 'cols' => 2],
        'editorial' => ['layout' => 'magazine', 'cols' => 1],
        'compact-list' => ['layout' => 'list', 'cols' => 1],
        'price-sheet' => ['layout' => 'table', 'cols' => 1],
        'marketplace' => ['layout' => 'grid', 'cols' => 3],
        'luxury-gold' => ['layout' => 'grid', 'cols' => 2],
        'ocean-breeze' => ['layout' => 'grid', 'cols' => 4],
        'forest-wholesale' => ['layout' => 'grid', 'cols' => 3],
        'midnight-noir' => ['layout' => 'grid', 'cols' => 3],
        'coral-retail' => ['layout' => 'grid', 'cols' => 3],
        'slate-b2b' => ['layout' => 'list', 'cols' => 1],
        'amber-trade' => ['layout' => 'grid', 'cols' => 3],
        'violet-studio' => ['layout' => 'grid', 'cols' => 2],
        'emerald-shop' => ['layout' => 'grid', 'cols' => 3],
        'rose-collection' => ['layout' => 'magazine', 'cols' => 1],
        'indigo-tech' => ['layout' => 'table', 'cols' => 1],
        'sand-minimal' => ['layout' => 'grid', 'cols' => 3],
        'poster-hero' => ['layout' => 'grid', 'cols' => 3],
        'stripe-rows' => ['layout' => 'list', 'cols' => 1],
        'cards-shadow' => ['layout' => 'grid', 'cols' => 3],
        'wide-gallery' => ['layout' => 'grid', 'cols' => 4],
        'compact-trade' => ['layout' => 'table', 'cols' => 1],
    ];

    /**
     * @return array{
     *   template_key: string,
     *   show_prices: bool,
     *   layout: string,
     *   cols: int,
     *   shop_view: string,
     *   theme: array{banner_bg: string, banner_text: string, footer_bg: string, footer_text: string, accent: string}
     * }
     */
    public function parseAttach(?string $attach): array
    {
        $attach = trim((string) $attach);
        $defaults = $this->defaultTheme('classic-grid');
        $key = 'classic-grid';
        $showPrices = true;
        $parts = $attach !== '' ? explode('::', $attach) : [];

        if ($parts !== []) {
            $candidate = preg_replace('/[^a-zA-Z0-9\-]/', '', (string) ($parts[0] ?? '')) ?: 'classic-grid';
            if (isset(self::TEMPLATE_META[$candidate])) {
                $key = $candidate;
            }
            $showPrices = ! isset($parts[1]) || (string) $parts[1] !== '0';
            $defaults = $this->defaultTheme($key);
        }

        $meta = self::TEMPLATE_META[$key] ?? self::TEMPLATE_META['classic-grid'];
        $layout = $meta['layout'];
        $cols = (int) $meta['cols'];

        return [
            'template_key' => $key,
            'show_prices' => $showPrices,
            'layout' => $layout,
            'cols' => $cols,
            'shop_view' => $this->shopViewForLayout($layout, $cols),
            'theme' => [
                'banner_bg' => $this->sanitizeHex($parts[2] ?? null, $defaults['banner_bg']),
                'banner_text' => $this->sanitizeHex($parts[3] ?? null, $defaults['banner_text']),
                'footer_bg' => $this->sanitizeHex($parts[4] ?? null, $defaults['footer_bg']),
                'footer_text' => $this->sanitizeHex($parts[5] ?? null, $defaults['footer_text']),
                'accent' => $this->sanitizeHex($parts[6] ?? null, $defaults['accent']),
            ],
        ];
    }

    /**
     * @return array{banner_bg: string, banner_text: string, footer_bg: string, footer_text: string, accent: string}
     */
    private function defaultTheme(string $templateKey): array
    {
        // Defaults alineados con manifest.json (solo colores; layout en TEMPLATE_META).
        $map = [
            'classic-grid' => ['#111827', '#ffffff', '#111827', '#e5e7eb', '#b91c1c'],
            'photo-gallery' => ['#0f766e', '#ecfdf5', '#134e4a', '#99f6e4', '#0f766e'],
            'boutique' => ['#1c1917', '#fafaf9', '#292524', '#d6d3d1', '#9a3412'],
            'editorial' => ['#1e3a8a', '#dbeafe', '#1e3a8a', '#bfdbfe', '#1d4ed8'],
            'compact-list' => ['#dc2626', '#ffffff', '#7f1d1d', '#fecaca', '#dc2626'],
            'price-sheet' => ['#0f172a', '#f8fafc', '#0f172a', '#94a3b8', '#b91c1c'],
            'marketplace' => ['#ea580c', '#fff7ed', '#9a3412', '#ffedd5', '#ea580c'],
            'luxury-gold' => ['#1c1917', '#fde68a', '#0c0a09', '#a8a29e', '#d4a017'],
            'ocean-breeze' => ['#0369a1', '#e0f2fe', '#0c4a6e', '#7dd3fc', '#0284c7'],
            'forest-wholesale' => ['#166534', '#dcfce7', '#14532d', '#86efac', '#15803d'],
            'midnight-noir' => ['#18181b', '#fda4af', '#09090b', '#71717a', '#f43f5e'],
            'coral-retail' => ['#e11d48', '#fff1f2', '#9f1239', '#fecdd3', '#e11d48'],
            'slate-b2b' => ['#334155', '#f1f5f9', '#0f172a', '#94a3b8', '#334155'],
            'amber-trade' => ['#b45309', '#fffbeb', '#78350f', '#fcd34d', '#d97706'],
            'violet-studio' => ['#6d28d9', '#f3e8ff', '#4c1d95', '#c4b5fd', '#7c3aed'],
            'emerald-shop' => ['#047857', '#d1fae5', '#064e3b', '#6ee7b7', '#059669'],
            'rose-collection' => ['#be185d', '#fce7f3', '#9f1239', '#f9a8d4', '#db2777'],
            'indigo-tech' => ['#4338ca', '#e0e7ff', '#312e81', '#a5b4fc', '#4f46e5'],
            'sand-minimal' => ['#57534e', '#fafaf9', '#44403c', '#d6d3d1', '#a8a29e'],
            'poster-hero' => ['#dc2626', '#ffffff', '#111827', '#d1d5db', '#dc2626'],
            'stripe-rows' => ['#0ea5e9', '#f0f9ff', '#0369a1', '#bae6fd', '#0ea5e9'],
            'cards-shadow' => ['#0f172a', '#f8fafc', '#1e293b', '#94a3b8', '#ef4444'],
            'wide-gallery' => ['#18181b', '#fafafa', '#27272a', '#a1a1aa', '#18181b'],
            'compact-trade' => ['#15803d', '#dcfce7', '#14532d', '#86efac', '#16a34a'],
        ];

        $tuple = $map[$templateKey] ?? $map['classic-grid'];

        return [
            'banner_bg' => $tuple[0],
            'banner_text' => $tuple[1],
            'footer_bg' => $tuple[2],
            'footer_text' => $tuple[3],
            'accent' => $tuple[4],
        ];
    }

    private function shopViewForLayout(string $layout, int $cols): string
    {
        if (in_array($layout, ['list', 'table', 'magazine'], true) || $cols <= 1) {
            return 'grid_list';
        }
        if ($cols === 2) {
            return 'grid_3'; // closest existing shop view (still overridden via CSS cols)
        }
        if ($cols >= 4) {
            return 'grid_4';
        }

        return 'grid_3';
    }

    private function sanitizeHex(?string $value, string $fallback): string
    {
        $raw = strtoupper(ltrim(trim((string) $value), '#'));
        if ($raw !== '' && preg_match('/^[0-9A-F]{6}$/', $raw)) {
            return '#' . $raw;
        }

        $fb = strtoupper(ltrim(trim($fallback), '#'));
        if ($fb !== '' && preg_match('/^[0-9A-F]{6}$/', $fb)) {
            return '#' . $fb;
        }

        return '#B91C1C';
    }
}
