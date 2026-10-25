<?php

namespace App\Http\Requests;

use App\Models\Banner;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBannerRequest extends UpdateModelRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $banner = Banner::where('bannerId', $this->route('banner'))->first();
        return \Auth::user()->can('update', $banner);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }

    public function getResourceRoute(): string
    {
        return 'banner';
    }
}
