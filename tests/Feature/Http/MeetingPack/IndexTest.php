<?php

declare(strict_types=1);

namespace Tests\Feature\Http\MeetingPack;

use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_meeting_pack_list(): void
    {
        $admin = User::factory()->admin()->create();

        MeetingPack::factory()->count(3)->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.meeting-packs.index'));

        $response->assertOk();
        $response->assertViewIs('meeting-pack.management.index');
        $response->assertViewHas('plans');
    }

    public function test_keyword_filter_matches_name(): void
    {
        $admin = User::factory()->admin()->create();

        MeetingPack::factory()->create([
            'name' => '検索対象パック',
            'description' => '通常の説明文',
        ]);

        MeetingPack::factory()->create([
            'name' => '別のパック',
            'description' => '通常の説明文',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.meeting-packs.index', ['keyword' => '検索対象']));

        $response->assertOk();
        $response->assertSee('検索対象パック');
        $response->assertDontSee('別のパック');
    }

    public function test_keyword_filter_matches_description(): void
    {
        $admin = User::factory()->admin()->create();

        MeetingPack::factory()->create([
            'name' => '検索対象パック',
            'description' => '特殊キーワードを含む説明文',
        ]);

        MeetingPack::factory()->create([
            'name' => '別のパック',
            'description' => '通常の説明文',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.meeting-packs.index', ['keyword' => '特殊キーワード']));

        $response->assertOk();
        $response->assertSee('検索対象パック');
        $response->assertDontSee('別のパック');
    }

    public function test_status_filter_returns_only_matching_status(): void
    {
        $admin = User::factory()->admin()->create();

        MeetingPack::factory()->create([
            'name' => 'Draft Pack',
            'status' => 'draft',
        ]);

        MeetingPack::factory()->create([
            'name' => 'Published Pack',
            'status' => 'published',
        ]);

        MeetingPack::factory()->create([
            'name' => 'Archived Pack',
            'status' => 'archived',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.meeting-packs.index', ['status' => 'published']));

        $response->assertOk();
        $response->assertSee('Published Pack');
        $response->assertDontSee('Draft Pack');
        $response->assertDontSee('Archived Pack');
    }

    public function test_paginates_20_per_page(): void
    {
        $admin = User::factory()->admin()->create();

        MeetingPack::factory()->count(22)->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.meeting-packs.index'));

        $response->assertOk();

        $plans = $response->viewData('plans');

        $this->assertSame(20, $plans->perPage());
        $this->assertSame(22, $plans->total());
        $this->assertCount(20, $plans->items());
    }

    public function test_coach_cannot_access_meeting_pack_list(): void
    {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('admin.meeting-packs.index'))
            ->assertForbidden();
    }

    public function test_student_cannot_access_meeting_pack_list(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get(route('admin.meeting-packs.index'))
            ->assertForbidden();
    }
}
