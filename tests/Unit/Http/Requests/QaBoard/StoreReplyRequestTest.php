<?php

declare(strict_types=1);

namespace Tests\Unit\Requests\QaBoard;

use App\Http\Requests\QaBoard\StoreReplyRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreReplyRequestTest extends TestCase
{
    // No.10 正常な返信投稿
    public function test_passes_with_valid_reply_body(): void
    {
        $validator = Validator::make([
            'body' => '返信本文です。',
        ], (new StoreReplyRequest)->rules());

        $this->assertTrue($validator->passes());
    }

    // No.11 返信本文未入力で返信投稿
    public function test_fails_when_reply_body_is_missing(): void
    {
        $validator = Validator::make([], (new StoreReplyRequest)->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('body'));
    }

    // No.12 返信本文の文字数超過で返信投稿
    public function test_fails_when_reply_body_exceeds_max_length(): void
    {
        $validator = Validator::make([
            'body' => str_repeat('a', 5001),
        ], (new StoreReplyRequest)->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('body'));
    }
}
