<?php

declare(strict_types=1);

namespace App\Http\Requests\QaBoard;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;

class StoreThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Student
            && $this->user()?->status === UserStatus::InProgress;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'certification_id' => [
                'required',
                'ulid',
                'exists:certifications,id',
            ],
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'certification_id' => '資格',
            'title' => 'タイトル',
            'body' => '本文',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'certification_id.required' => '資格を選択してください',
            'certification_id.ulid' => '選択した資格が存在しません',
            'certification_id.exists' => '選択した資格が存在しません',
            'title.required' => 'タイトルを入力してください',
            'title.max' => 'タイトルは200文字以内で入力してください',
            'body.required' => '本文を入力してください',
            'body.max' => '本文は2000文字以内で入力してください',
        ];
    }
}
