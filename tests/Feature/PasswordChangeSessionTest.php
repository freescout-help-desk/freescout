<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;

class PasswordChangeSessionTest extends TestCase
{
    /**
     * A session started before a password change must not stay valid.
     */
    public function testSessionWithStalePasswordHashIsRejected()
    {
        $user = factory(User::class)->create([
            'role' => User::ROLE_USER,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['password_hash' => 'hash-before-password-change'])
            ->get(route('users.profile', ['id' => $user->id]));

        $response->assertRedirect(route('login'));
    }

    /**
     * A session started with the current password hash stays valid.
     */
    public function testSessionWithCurrentPasswordHashIsAccepted()
    {
        $user = factory(User::class)->create([
            'role' => User::ROLE_USER,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['password_hash' => $user->getAuthPassword()])
            ->get(route('users.profile', ['id' => $user->id]));

        $response->assertStatus(200);
    }
}
