<?php

declare(strict_types=1);

namespace App\Http\Requests\MeetingPack;

use App\Models\MeetingPack;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $meetingPack = $this->route('plan');

        return $meetingPack instanceof MeetingPack
            && ($this->user()?->can('update', $meetingPack) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'meeting_count' => ['required', 'integer', 'min:1', 'max:100'],
            'price' => ['required', 'integer', 'min:0', 'max:1000000'],
            'stripe_price_id' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => '面談パック名',
            'description' => '説明',
            'meeting_count' => '面談回数',
            'price' => '価格',
            'stripe_price_id' => 'Stripe Price ID',
            'sort_order' => '表示順',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => '面談パック名を入力してください',
            'name.max' => '面談パック名は100文字以内で入力してください',
            'description.max' => '説明は2000文字以内で入力してください',
            'meeting_count.required' => '面談回数を入力してください',
            'meeting_count.integer' => '面談回数は整数で入力してください',
            'meeting_count.min' => '面談回数は1以上で入力してください',
            'meeting_count.max' => '面談回数は100以下で入力してください',
            'price.required' => '価格を入力してください',
            'price.integer' => '価格は整数で入力してください',
            'price.min' => '価格は0以上で入力してください',
            'price.max' => '価格は1000000以下で入力してください',
            'stripe_price_id.max' => 'Stripe Price IDは255文字以内で入力してください',
            'sort_order.integer' => '表示順は整数で入力してください',
            'sort_order.min' => '表示順は0以上で入力してください',
        ];
    }
}
