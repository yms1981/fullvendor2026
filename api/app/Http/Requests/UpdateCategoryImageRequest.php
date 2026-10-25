<?php

namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCategoryImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        \Log::info('UpdateCategoryImageRequest::authorize - Verificando autorización');
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        \Log::info('UpdateCategoryImageRequest::rules - Aplicando reglas de validación');
        \Log::info('UpdateCategoryImageRequest::rules - Request all: ' . json_encode($this->all()));
        \Log::info('UpdateCategoryImageRequest::rules - Request hasFile image: ' . ($this->hasFile('image') ? 'yes' : 'no'));
        \Log::info('UpdateCategoryImageRequest::rules - Request input company_id: ' . ($this->input('company_id') ?? 'null'));
        \Log::info('UpdateCategoryImageRequest::rules - Request allFiles: ' . json_encode($this->allFiles()));
        
        $rules = [
            'company_id' => 'required|numeric|exists:companies,company_id',
        ];
        
        // Solo validar imagen si está presente
        if ($this->hasFile('image')) {
            \Log::info('UpdateCategoryImageRequest::rules - Imagen presente, aplicando validaciones de imagen');
            $rules['image'] = [
                'image',
                'mimes:jpeg,png,jpg',
                'max:10240',
                'dimensions:min_width=100,min_height=100',
            ];
        } else {
            \Log::info('UpdateCategoryImageRequest::rules - No hay imagen, solo validando company_id');
        }
        
        return $rules;
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        \Log::error('UpdateCategoryImageRequest::failedValidation - Validación falló');
        \Log::error('UpdateCategoryImageRequest::failedValidation - Errores: ' . json_encode($validator->errors()->all()));
        \Log::error('UpdateCategoryImageRequest::failedValidation - Request all: ' . json_encode($this->all()));
        
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
