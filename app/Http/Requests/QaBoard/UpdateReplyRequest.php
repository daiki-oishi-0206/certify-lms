<?php

declare(strict_types=1);

namespace App\Http\Requests\QaBoard;

use App\Models\QaReply;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reply = $this->route('reply');

        return $reply instanceof QaReply
            && ($this->user()?->can('update', $reply) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'body' => '回答本文',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => '回答本文を入力してください',
            'body.max' => '回答本文は5000文字以内で入力してください',
        ];
    }
}
