<?php

declare(strict_types=1);

namespace Tests\Unit\Requests\QaBoard;

use App\Http\Requests\QaBoard\UpdateReplyRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateReplyRequestTest extends TestCase
{
    // No.13 正常な返信更新
    public function test_passes_with_valid_reply_body(): void
    {
        $validator = Validator::make([
            'body' => '更新後の返信本文です。',
        ], (new UpdateReplyRequest)->rules());

        $this->assertTrue($validator->passes());
    }

    // No.14 返信本文未入力で返信更新
    public function test_fails_when_reply_body_is_missing(): void
    {
        $validator = Validator::make([], (new UpdateReplyRequest)->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('body'));
    }

    // No.15 返信本文の文字数超過で返信更新
    public function test_fails_when_reply_body_exceeds_max_length(): void
    {
        $validator = Validator::make([
            'body' => str_repeat('a', 5001),
        ], (new UpdateReplyRequest)->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('body'));
    }
}
