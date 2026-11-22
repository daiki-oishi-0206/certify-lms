<?php

declare(strict_types=1);

namespace App\Http\Requests\QaBoard;

use App\Enums\QaThreadStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Q&Aスレッドのステータス変更を検証するリクエスト。
 */
class UpdateThreadStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::enum(QaThreadStatus::class),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'ステータス',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'ステータスを選択してください',
            'status.enum' => '指定されたステータスは無効です',
        ];
    }
}
