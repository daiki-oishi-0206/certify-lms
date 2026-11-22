<?php

declare(strict_types=1);

namespace Tests\Unit\Requests\QaBoard;

use App\Http\Requests\QaBoard\UpdateThreadRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateThreadRequestTest extends TestCase
{
    // No.7 正常なスレッド更新
    public function test_passes_with_valid_thread_data(): void
    {
        $validator = Validator::make([
            'title' => '更新後のタイトル',
            'body' => '更新後の本文です。',
        ], (new UpdateThreadRequest)->rules());

        $this->assertTrue($validator->passes());
    }

    // No.8 必須項目未入力でスレッド更新
    public function test_fails_when_required_fields_are_missing(): void
    {
        $validator = Validator::make([], (new UpdateThreadRequest)->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('title'));
        $this->assertTrue($validator->errors()->has('body'));
    }

    // No.9 文字数超過でスレッド更新
    public function test_fails_when_title_and_body_exceed_max_length(): void
    {
        $validator = Validator::make([
            'title' => str_repeat('a', 201),
            'body' => str_repeat('a', 5001),
        ], (new UpdateThreadRequest)->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('title'));
        $this->assertTrue($validator->errors()->has('body'));
    }
}
