<?php

namespace App\Services;

use App\Models\TeacherDashboardLayout;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class TeacherDashboardLayoutService
{
    public const DEFAULT_CARD_ORDER = ['attendance', 'exam_performance', 'payments', 'learning_flow'];

    /** @param array<int, mixed>|null $cardOrder
     *  @return array<int, string>
     */
    public static function normalize(?array $cardOrder): array
    {
        if ($cardOrder === null || count($cardOrder) !== count(self::DEFAULT_CARD_ORDER)) {
            return self::DEFAULT_CARD_ORDER;
        }

        $order = array_values(array_filter($cardOrder, 'is_string'));

        return count($order) === count(self::DEFAULT_CARD_ORDER)
            && count(array_unique($order)) === count(self::DEFAULT_CARD_ORDER)
            && !array_diff(self::DEFAULT_CARD_ORDER, $order)
            ? $order
            : self::DEFAULT_CARD_ORDER;
    }

    /** @return array<int, string> */
    public function for(User $teacher): array
    {
        $this->guardTeacher($teacher);
        $layout = TeacherDashboardLayout::query()->where('user_id', $teacher->id)->first();

        $cardOrder = $layout?->getAttribute('card_order');

        return self::normalize(is_array($cardOrder) ? $cardOrder : null);
    }

    /** @param array<int, string> $cardOrder */
    public function save(User $teacher, array $cardOrder): TeacherDashboardLayout
    {
        $this->guardTeacher($teacher);
        $normalized = self::normalize($cardOrder);

        return DB::transaction(function () use ($teacher, $normalized): TeacherDashboardLayout {
            TeacherDashboardLayout::query()->upsert([
                [
                    'user_id' => $teacher->id,
                    'card_order' => json_encode($normalized, JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ], ['user_id'], ['card_order', 'updated_at']);

            return TeacherDashboardLayout::query()->where('user_id', $teacher->id)->firstOrFail();
        }, attempts: 3);
    }

    public function reset(User $teacher): void
    {
        $this->guardTeacher($teacher);

        DB::transaction(function () use ($teacher): void {
            TeacherDashboardLayout::query()->where('user_id', $teacher->id)->delete();
        }, attempts: 3);
    }

    private function guardTeacher(User $teacher): void
    {
        if ($teacher->role !== 'teacher') {
            throw new AuthorizationException('ترتيب اللوحة متاح للمعلم فقط.');
        }
    }
}
