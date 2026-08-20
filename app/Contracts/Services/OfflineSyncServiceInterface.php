<?php

namespace App\Contracts\Services;

use App\Models\User;

interface OfflineSyncServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function snapshot(User $user): array;

    /**
     * @param list<array<string, mixed>> $operations
     * @return list<array<string, mixed>>
     */
    public function reconcile(User $user, array $operations): array;
}
