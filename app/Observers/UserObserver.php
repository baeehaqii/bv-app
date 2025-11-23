<?php

namespace App\Observers;

use App\Models\User;
use LaraZeus\Boredom\BoringAvatar;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $this->generateAvatarUrl($user);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Generate avatar jika avatar_url kosong
        if (empty($user->avatar_url)) {
            $this->generateAvatarUrl($user);
        }
    }

    /**
     * Generate dan save avatar URL untuk user
     */
    private function generateAvatarUrl(User $user): void
    {
        try {
            $boringAvatar = app()->make(BoringAvatar::class);
            $avatarUrl = $boringAvatar->get(name: $user->name);

            if ($avatarUrl) {
                $user->updateQuietly([
                    'avatar_url' => $avatarUrl,
                ]);
            }
        } catch (\Exception $e) {
            // Silently fail if avatar generation fails
            \Log::warning('Failed to generate boring avatar for user ' . $user->id . ': ' . $e->getMessage());
        }
    }
}
