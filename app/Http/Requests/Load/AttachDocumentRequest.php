<?php

namespace App\Http\Requests\Load;

use App\Enums\AttachmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AttachDocumentRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'attachments' => ['required', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'attachment_types' => ['required', 'array'],
            'attachment_types.*' => ['required', Rule::enum(AttachmentType::class)],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
