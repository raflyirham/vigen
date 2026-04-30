<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVideoGenerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'video_type' => ['required', 'string', 'max:80'],
            'topic' => ['required', 'string', 'max:220'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'target_audience' => ['required', 'string', 'max:160'],
            'tone' => ['required', 'string', 'max:80'],
            'aspect_ratio' => ['required', 'string', Rule::in(['2:3', '3:2', '1:1', '9:16', '16:9'])],
            'duration' => ['required', 'integer', Rule::in([30, 60])],
            'template_key' => ['nullable', 'string', 'max:80'],
        ];
    }
}
