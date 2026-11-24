<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use App\Models\Invitation;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ViewErrorBag;

/**
 * オンボーディングフォーム(初回パスワード設定 + プロフィール入力)のリクエスト。
 *
 * 認可は署名付き URL に委ねるため `authorize()` は常に true。
 * コーチ宛て招待の場合は固定面談 URL(`meeting_url`)の入力を必須化し、受講生宛てでは表示しない。
 */
class OnboardingRequest extends FormRequest
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
        $rules = [
            'name' => ['required', 'string', 'max:50'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];

        if ($this->invitedRole() === UserRole::Coach) {
            $rules['meeting_url'] = ['required', 'string', 'url', 'max:500'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'お名前',
            'bio' => '自己紹介',
            'password' => 'パスワード',
            'password_confirmation' => 'パスワード（確認）',
            'meeting_url' => 'ミーティング URL',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'meeting_url.required' => 'ミーティング URL を入力してください。',
            'meeting_url.url' => 'ミーティング URL を正しい形式で入力してください。',
            'password.confirmed' => 'パスワードと確認用パスワードが一致しません。',
        ];
    }

    /**
     * 招待トークンに紐付くロールを取り出す。Route Model Binding 経由で Invitation を解決する。
     */
    private function invitedRole(): ?UserRole
    {
        $invitation = $this->route('invitation');

        return $invitation instanceof Invitation ? $invitation->role : null;
    }

    /**
     * バリデーション失敗時は入力画面を再表示し、HTTP 422を返す。
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors();

        session()->flashInput(
            $this->except('password', 'password_confirmation')
        );

        $errorBag = (new ViewErrorBag())->put('default', $errors);

        session()->flash('errors', $errorBag);

        /** @var Invitation $invitation */
        $invitation = $this->route('invitation');

        $postUrl = URL::temporarySignedRoute(
            'onboarding.store',
            $invitation->expires_at,
            ['invitation' => $invitation->id],
        );

        throw new HttpResponseException(
            response()->view('auth.onboarding', [
                'invitation' => $invitation,
                'postUrl' => $postUrl,
                'errors' => $errorBag,
            ], 422)
        );
    }
}
