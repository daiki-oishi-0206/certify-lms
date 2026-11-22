<?php

declare(strict_types=1);

namespace Tests\Unit\Requests\QaBoard;

use App\Enums\QaThreadStatus;
use App\Http\Requests\QaBoard\IndexThreadRequest;
use App\Models\Certification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexThreadRequestTest extends TestCase
{
    use RefreshDatabase;

    // No.1 検索条件未指定で一覧表示
    public function test_passes_when_no_search_conditions_are_given(): void
    {
        $validator = Validator::make(
            [],
            (new IndexThreadRequest)->rules(),
        );

        $this->assertTrue($validator->passes());
    }

    // No.2 全検索条件を指定して一覧表示
    public function test_passes_with_all_search_conditions(): void
    {
        $certification = Certification::factory()->published()->create();

        $validator = Validator::make([
            'keyword' => 'Laravel',
            'certification_id' => $certification->id,
            'status' => QaThreadStatus::Unresolved->value,
            'page' => 2,
        ], (new IndexThreadRequest)->rules());

        $this->assertTrue($validator->passes());
    }

    // No.3 不正な検索条件で一覧表示
    public function test_fails_with_invalid_search_conditions(): void
    {
        $validator = Validator::make([
            'certification_id' => 'invalid-id',
            'status' => 'invalid-status',
            'page' => 0,
        ], (new IndexThreadRequest)->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('certification_id'));
        $this->assertTrue($validator->errors()->has('status'));
        $this->assertTrue($validator->errors()->has('page'));
    }
}