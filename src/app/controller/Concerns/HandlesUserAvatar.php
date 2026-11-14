<?php

namespace Yllumi\Sayagi\app\controller\Concerns;

use Yllumi\Sayagi\libraries\AvatarUploader;
use support\Db;
use support\Request;

/**
 * Shared avatar handling for the self-service profile and the admin user
 * module, so both paths validate, store and clean up files the same way.
 */
trait HandlesUserAvatar
{
    /**
     * @return array{success:int,message:string,avatar?:string,avatar_url?:?string}
     */
    protected function storeUserAvatar(Request $request, object $user): array
    {
        $file = $request->file('avatar');
        if (! $file) {
            return ['success' => 0, 'message' => 'Pilih berkas gambar terlebih dahulu.'];
        }

        try {
            $path = (new AvatarUploader())->store($file);
        } catch (\RuntimeException $exception) {
            return ['success' => 0, 'message' => $exception->getMessage()];
        }

        AvatarUploader::delete($user->avatar);

        Db::table('mein_users')->where('id', $user->id)->update([
            'avatar'     => $path,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ((int) $user->id === $this->sessionUserId($request)) {
            $this->refreshUserSession($request, ['avatar' => $path]);
        }

        return [
            'success'    => 1,
            'message'    => 'Avatar berhasil diperbarui.',
            'avatar'     => $path,
            'avatar_url' => AvatarUploader::url($path),
        ];
    }

    /**
     * @return array{success:int,message:string}
     */
    protected function removeUserAvatar(Request $request, object $user): array
    {
        AvatarUploader::delete($user->avatar);

        Db::table('mein_users')->where('id', $user->id)->update([
            'avatar'     => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ((int) $user->id === $this->sessionUserId($request)) {
            $this->refreshUserSession($request, ['avatar' => null]);
        }

        return ['success' => 1, 'message' => 'Avatar dihapus.'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function sessionUser(Request $request): array
    {
        return (array) $request->session()->get('user', []);
    }

    protected function sessionUserId(Request $request): int
    {
        return (int) ($this->sessionUser($request)['user_id'] ?? 0);
    }

    /**
     * @param array<string, mixed> $changes
     */
    protected function refreshUserSession(Request $request, array $changes): void
    {
        $user = $this->sessionUser($request);
        if ($user === []) {
            return;
        }

        $request->session()->set('user', array_merge($user, $changes));
    }
}
