<?php

namespace App\Http\Controllers;

use App\Models\Format;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Formatos de impresión/descarga de catálogos. Scoping por company_id recibido del cliente.
 */
class FormatController extends Controller
{
    protected $resourceRoute = 'format';

    public function byCompany($companyId)
    {
        return Format::query()
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->orderBy('order', 'ASC')
            ->orderBy('format_name', 'ASC')
            ->get();
    }

    public function show(Format $format)
    {
        return $format;
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $format = Format::create($data);

        if ((int) ($data['is_default'] ?? 0) === 1) {
            $this->resetDefaults((int) $data['company_id'], (int) $format->format_id);
        }

        return response()->json([
            'message' => 'Resource created successfully',
            'data' => $format,
        ], Response::HTTP_OK);
    }

    public function update(Request $request, Format $format)
    {
        $data = $this->validateData($request);

        $format->update($data);

        if ((int) ($data['is_default'] ?? 0) === 1) {
            $this->resetDefaults((int) $data['company_id'], (int) $format->format_id);
        }

        return response()->json([
            'message' => 'Resource updated successfully',
            'data' => $format->fresh(),
        ], Response::HTTP_OK);
    }

    public function destroy(Format $format): ?Response
    {
        $format->delete();
        return response()->noContent();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request): array
    {
        return $request->validate([
            'format_name' => 'required|string|max:255',
            'format_description' => 'nullable|string',
            'template' => 'nullable|string|max:10000',
            'order' => 'nullable|integer',
            'is_default' => 'nullable|integer',
            'company_id' => 'required|integer',
            'format_type' => 'nullable|string|max:50',
            'related_id' => 'nullable|integer',
        ]);
    }

    private function resetDefaults(int $companyId, int $keepFormatId): void
    {
        Format::query()
            ->where('company_id', $companyId)
            ->where('format_id', '!=', $keepFormatId)
            ->whereNull('deleted_at')
            ->update(['is_default' => 0]);
    }
}
