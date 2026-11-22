<?php

declare(strict_types=1);

namespace Tests\Unit\Requests\QaBoard;

use App\Http\Requests\QaBoard\StoreThreadRequest;
use App\Models\Certification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreThreadRequestTest extends TestCase
{
    use RefreshDatabase;

    // No.4 正常なスレッド作成
    public function test_passes_with_valid_thread_data(): void
    {
        $certification = Certification::factory()->published()->create();

        $validator = Validator::make([
            'certification_id' => $certification->id,
            'title' => 'Laravelについて',
            'body' => 'Laravelについて質問があります。',
        ], (new StoreThreadRequest)->rules());

        $this->assertTrue($validator->passes());
    }

    // No.5 必須項目未入力でスレッド作成
    public function test_fails_when_required_fields_are_missing(): void
    {
        $validator = Validator::make([], (new StoreThreadRequest)->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('certification_id'));
        $this->assertTrue($validator->errors()->has('title'));
        $this->assertTrue($validator->errors()->has('body'));
    }

    // No.6 文字数超過でスレッド作成
    public function test_fails_when_title_and_body_exceed_max_length(): void
    {
        $certification = Certification::factory()->published()->create();

        $validator = Validator::make([
            'certification_id' => $certification->id,
            'title' => str_repeat('a', 201),
            'body' => str_repeat('a', 5001),
        ], (new StoreThreadRequest)->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('title'));
        $this->assertTrue($validator->errors()->has('body'));
    }
}
