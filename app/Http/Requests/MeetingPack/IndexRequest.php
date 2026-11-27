<?php

declare(strict_types=1);

namespace App\Http\Requests\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Models\MeetingPack;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 面談パック一覧の検索条件を検証するリクエスト。
 */
class IndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', MeetingPack::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:100'],
            'status' => [
                'nullable',
                Rule::enum(MeetingPackStatus::class),
            ],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'keyword.max' => 'キーワードは100文字以内で入力してください',
            'status.enum' => '指定されたステータスは無効です',
            'page.integer' => 'ページ番号は整数で入力してください',
            'page.min' => 'ページ番号は1以上で入力してください',
        ];
    }
}
