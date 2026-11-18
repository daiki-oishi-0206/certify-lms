<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CertificationStatus;
use App\Enums\QaThreadStatus;
use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Database\Seeder;

class QaSeeder extends Seeder
{
    public function run(): void
    {
        $student = User::query()
            ->where('email', 'student@certify-lms.test')
            ->first();

        $coach = User::query()
            ->where('email', 'coach@certify-lms.test')
            ->first();

        if ($student === null || $coach === null) {
            $this->command?->warn(
                'QaSeeder: 固定の受講生またはコーチが存在しません。'
            );

            return;
        }

        $certifications = Certification::query()
            ->where('status', CertificationStatus::Published->value)
            ->orderBy('created_at')
            ->get();

        if ($certifications->isEmpty()) {
            $this->command?->warn(
                'QaSeeder: 公開済み資格が存在しません。'
            );

            return;
        }

        // 既存データがある場合は重複作成しない
        if (QaThread::query()->exists()) {
            $this->command?->info(
                'QaSeeder: Q&Aデータが既に存在するためスキップします。'
            );

            return;
        }

        $threadData = [
            [
                'title' => 'アルゴリズムの学習方法について',
                'body' => 'アルゴリズムの問題を解くとき、どこから考え始めればよいか分かりません。',
                'resolved' => false,
                'replies' => [
                    ['user' => $coach, 'body' => 'まずは問題文から入力と出力を整理してみましょう。'],
                    ['user' => $student, 'body' => 'ありがとうございます。入力と出力を整理するところから始めます。'],
                ],
            ],
            [
                'title' => '試験範囲の優先順位について',
                'body' => '試験範囲が広く、どの分野から勉強すればよいか迷っています。',
                'resolved' => true,
                'replies' => [
                    ['user' => $coach, 'body' => 'まずは出題頻度の高い分野から取り組むのがおすすめです。'],
                ],
            ],
            [
                'title' => 'リスニングの勉強方法について',
                'body' => '音声を聞いても内容を理解できないことがあります。効果的な練習方法を知りたいです。',
                'resolved' => false,
                'replies' => [
                    ['user' => $coach, 'body' => '短い音声を繰り返し聞き、スクリプトと照らし合わせてみましょう。'],
                    ['user' => $student, 'body' => 'シャドーイングも試してみます。'],
                    ['user' => $coach, 'body' => '最初はゆっくりした音声から始めるとよいですよ。'],
                ],
            ],
            [
                'title' => '仕訳の覚え方について',
                'body' => '仕訳のパターンがなかなか覚えられません。効率的な覚え方はありますか？',
                'resolved' => true,
                'replies' => [],
            ],
            [
                'title' => '試験直前の復習について',
                'body' => '試験直前は新しい問題を解くべきか、今までの復習を優先すべきか悩んでいます。',
                'resolved' => false,
                'replies' => [
                    ['user' => $coach, 'body' => '直前期は間違えた問題の復習を優先しましょう。'],
                ],
            ],
        ];

        foreach ($threadData as $index => $data) {
            $certification = $certifications->get($index);

            if ($certification === null) {
                break;
            }

            $createdAt = now()->subDays(10 - $index * 2);

            $thread = QaThread::factory()->create([
                'certification_id' => $certification->id,
                'user_id' => $student->id,
                'title' => $data['title'],
                'body' => $data['body'],
                'status' => $data['resolved']
                    ? QaThreadStatus::Resolved->value
                    : QaThreadStatus::Unresolved->value,
                'resolved_at' => $data['resolved']
                    ? $createdAt->copy()->addHours(2)
                    : null,
            ]);

            $thread->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->save();

            foreach ($data['replies'] as $replyIndex => $replyData) {
                $replyCreatedAt = $createdAt->copy()
                    ->addHours($replyIndex + 1);

                $reply = QaReply::factory()->create([
                    'qa_thread_id' => $thread->id,
                    'user_id' => $replyData['user']->id,
                    'body' => $replyData['body'],
                ]);

                $reply->forceFill([
                    'created_at' => $replyCreatedAt,
                    'updated_at' => $replyCreatedAt,
                ])->save();
            }
        }
    }
}
