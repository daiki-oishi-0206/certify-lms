<?php

declare(strict_types=1);

namespace App\Http\Requests\QaBoard;

use App\Enums\QaThreadStatus;
use App\Models\QaThread;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Q&Aスレッド一覧の検索条件を検証するリクエスト。
 */
class IndexThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', QaThread::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'certification_id' => [
                'nullable',
                'ulid',
                'exists:certifications,id',
            ],
            'status' => [
                'nullable',
                Rule::enum(QaThreadStatus::class),
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
            'certification_id.ulid' => '選択した資格が存在しません',
            'certification_id.exists' => '選択した資格が存在しません',
            'status.enum' => '指定されたステータスは無効です',
            'page.integer' => 'ページ番号は整数で入力してください',
            'page.min' => 'ページ番号は1以上で入力してください',
        ];
    }

    /**
     * @return array{
     *     keyword: ?string,
     *     certification_id: ?string,
     *     status: ?string
     * }
     */
    public function filters(): array
    {
        return [
            'keyword' => $this->input('keyword'),
            'certification_id' => $this->input('certification_id'),
            'status' => $this->input('status'),
        ];
    }
}
