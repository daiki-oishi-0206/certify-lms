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

        if ($student === null) {
            $this->command?->warn(
                'QaSeeder: 固定の受講生が存在しません。'
            );

            return;
        }

        $certifications = Certification::query()
            ->where('status', CertificationStatus::Published->value)
            ->with('coaches')
            ->orderBy('created_at')
            ->get();

        if ($certifications->isEmpty()) {
            $this->command?->warn(
                'QaSeeder: 公開済み資格が存在しません。'
            );

            return;
        }

        if ($certifications->contains(
            fn(Certification $certification) => $certification->coaches->isEmpty()
        )) {
            $this->command?->warn(
                'QaSeeder: 担当コーチが設定されていない資格があります。'
            );

            return;
        }

        // 既存データがある場合は重複作成しない
        if (
            QaThread::query()->exists()
            || QaReply::query()->exists()
        ) {
            $this->command?->info(
                'QaSeeder: Q&Aデータが既に存在するためスキップします。'
            );

            return;
        }

        $topics = [
            'アルゴリズムの学習方法について',
            '試験範囲の優先順位について',
            'リスニングの勉強方法について',
            '仕訳の覚え方について',
            '試験直前の復習について',
            '過去問題の取り組み方について',
            '苦手分野の克服方法について',
            '模擬試験の活用方法について',
            '学習時間の確保について',
            '試験当日の注意点について',
        ];

        $bodies = [
            '効率よく学習を進めるための方法について教えてください。',
            'どのような順番で勉強を進めるのがおすすめでしょうか。',
            '理解が難しい部分があり、具体的な勉強方法を知りたいです。',
            '繰り返し学習していますが、なかなか定着しません。',
            '試験に向けて、今のうちに確認しておくべきことはありますか。',
        ];

        // 0件・1件・2件・3件の回答を繰り返し設定
        $replyCounts = [0, 1, 2, 3, 0, 2, 1, 3, 0, 1];

        for ($index = 0; $index < 40; $index++) {
            // 公開済み資格5件に各8件ずつ質問を割り当てる
            $certification = $certifications->get(
                intdiv($index, 8) % $certifications->count()
            );

            // 資格の担当コーチを取得
            $assignedCoach = $certification->coaches->first();

            // 作成日時にばらつきを持たせる
            $createdAt = now()
                ->subDays(($index * 7) % 30)
                ->subHours(($index * 5) % 24);

            // 未解決・解決済みを交互に設定
            $resolved = $index % 2 === 0;

            $thread = QaThread::factory()->create([
                'certification_id' => $certification->id,
                'user_id' => $student->id,
                'title' => $topics[$index % count($topics)]
                    . '（確認用 ' . ($index + 1) . '）',
                'body' => $bodies[$index % count($bodies)],
                'status' => $resolved
                    ? QaThreadStatus::Resolved->value
                    : QaThreadStatus::Unresolved->value,
                'resolved_at' => $resolved
                    ? $createdAt->copy()->addHours(2)
                    : null,
            ]);

            $thread->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->save();

            $replyCount = $replyCounts[$index % count($replyCounts)];

            for ($replyIndex = 0; $replyIndex < $replyCount; $replyIndex++) {
                // 担当コーチ・受講生の回答を交互に作成
                $replyUser = $replyIndex % 2 === 0
                    ? $assignedCoach
                    : $student;

                $replyCreatedAt = $createdAt->copy()
                    ->addHours($replyIndex + 1);

                $reply = QaReply::factory()->create([
                    'qa_thread_id' => $thread->id,
                    'user_id' => $replyUser->id,
                    'body' => $replyIndex % 2 === 0
                        ? '具体的な学習方法を確認しながら進めてみましょう。'
                        : 'ありがとうございます。参考にして取り組んでみます。',
                ]);

                $reply->forceFill([
                    'created_at' => $replyCreatedAt,
                    'updated_at' => $replyCreatedAt,
                ])->save();
            }
        }

        $this->command?->info(
            'QaSeeder: Q&A質問40件を作成しました。'
        );
    }
}
