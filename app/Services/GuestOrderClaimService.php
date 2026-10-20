<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;

class GuestOrderClaimService
{
    public function claim(User $user): int
    {
        return Order::query()
            ->whereNull('user_id')
            ->whereRaw('LOWER(email) = ?', [strtolower($user->email)])
            ->update(['user_id' => $user->id]);
    }
}
