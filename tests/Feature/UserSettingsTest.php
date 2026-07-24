<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_authenticated_user_can_open_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('settings/index')
                ->where('auth.user.email', $user->email)
                ->where('auth.user.avatar', null));
    }

    public function test_user_can_upload_and_view_a_private_tenant_avatar(): void
    {
        $user = User::factory()->create();
        tenancy()->initialize($user->tenant);
        Storage::fake('local');

        $this->actingAs($user)
            ->post(route('settings.avatar.update'), [
                'avatar' => UploadedFile::fake()->create('avatar.png', 100, 'image/png'),
            ])
            ->assertRedirect(route('settings.edit'));

        $user->refresh();

        $this->assertNotNull($user->avatar_path);
        Storage::disk('local')->assertExists($user->avatar_path);

        $this->actingAs($user)
            ->get(route('settings.avatar.show'))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $this->actingAs($user)
            ->get(route('settings.edit'))
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.avatar', fn ($avatar) => is_string($avatar)
                    && str_starts_with($avatar, '/settings/avatar?v=')));
    }

    public function test_replacing_avatar_deletes_the_previous_file(): void
    {
        $user = User::factory()->create();
        tenancy()->initialize($user->tenant);
        Storage::fake('local');
        Storage::disk('local')->put('avatars/old.png', 'old');
        $user->forceFill(['avatar_path' => 'avatars/old.png'])->save();

        $this->actingAs($user)
            ->post(route('settings.avatar.update'), [
                'avatar' => UploadedFile::fake()->create('new.jpg', 100, 'image/jpeg'),
            ])
            ->assertRedirect(route('settings.edit'));

        $user->refresh();

        Storage::disk('local')->assertMissing('avatars/old.png');
        Storage::disk('local')->assertExists($user->avatar_path);
    }

    public function test_user_can_remove_the_avatar(): void
    {
        $user = User::factory()->create();
        tenancy()->initialize($user->tenant);
        Storage::fake('local');
        Storage::disk('local')->put('avatars/current.webp', 'avatar');
        $user->forceFill(['avatar_path' => 'avatars/current.webp'])->save();

        $this->actingAs($user)
            ->delete(route('settings.avatar.destroy'))
            ->assertRedirect(route('settings.edit'));

        $this->assertNull($user->fresh()->avatar_path);
        Storage::disk('local')->assertMissing('avatars/current.webp');
    }

    public function test_avatar_validation_rejects_unsupported_files(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.avatar.update'), [
                'avatar' => UploadedFile::fake()->create('script.php', 10, 'text/x-php'),
            ])
            ->assertSessionHasErrors('avatar');
    }
}
