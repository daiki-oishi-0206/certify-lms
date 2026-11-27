<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Models\MeetingPack;
use App\UseCases\MeetingPack\IndexAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_meeting_packs_in_order(): void
    {
        $later = MeetingPack::factory()->create(['sort_order' => 2]);
        $earlier = MeetingPack::factory()->create(['sort_order' => 1]);

        $result = app(IndexAction::class)(null, null);

        $this->assertSame(
            [$earlier->id, $later->id],
            $result->getCollection()->modelKeys(),
        );
    }

    public function test_filters_by_name_keyword(): void
    {
        $matched = MeetingPack::factory()->create([
            'name' => 'プレミアム面談パック',
        ]);

        MeetingPack::factory()->create([
            'name' => '通常面談パック',
        ]);

        $result = app(IndexAction::class)('プレミアム', null);

        $this->assertSame(
            [$matched->id],
            $result->getCollection()->modelKeys(),
        );
    }

    public function test_filters_by_description_keyword(): void
    {
        $matched = MeetingPack::factory()->create([
            'description' => '特別コーチングプラン',
        ]);

        MeetingPack::factory()->create([
            'description' => '通常プラン',
        ]);

        $result = app(IndexAction::class)('特別コーチング', null);

        $this->assertSame(
            [$matched->id],
            $result->getCollection()->modelKeys(),
        );
    }

    public function test_filters_by_status(): void
    {
        $published = MeetingPack::factory()->published()->create();

        MeetingPack::factory()->draft()->create();
        MeetingPack::factory()->archived()->create();

        $result = app(IndexAction::class)(
            null,
            MeetingPackStatus::Published,
        );

        $this->assertSame(
            [$published->id],
            $result->getCollection()->modelKeys(),
        );
    }

    public function test_filters_by_keyword_and_status_together(): void
    {
        $matched = MeetingPack::factory()->published()->create([
            'name' => 'プレミアム面談パック',
        ]);

        MeetingPack::factory()->draft()->create([
            'name' => 'プレミアム面談パック',
        ]);

        MeetingPack::factory()->published()->create([
            'name' => '通常面談パック',
        ]);

        $result = app(IndexAction::class)(
            'プレミアム',
            MeetingPackStatus::Published,
        );

        $this->assertSame(
            [$matched->id],
            $result->getCollection()->modelKeys(),
        );
    }

    public function test_paginates_with_specified_per_page(): void
    {
        MeetingPack::factory()->count(5)->create();

        $result = app(IndexAction::class)(null, null, 2);

        $this->assertSame(2, $result->perPage());
        $this->assertSame(5, $result->total());
        $this->assertCount(2, $result->items());
        $this->assertSame(3, $result->lastPage());
    }
}
