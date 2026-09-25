<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminRoleId = Role::where('name', 'ADMIN')->value('id');

        if (!$adminRoleId) {
            return;
        }

        User::updateOrCreate(
            ['email' => 'admin1@example.com'],
            [
                'role_id' => $adminRoleId,
                'name' => 'Admin One',
                'username' => 'admin1',
                'password' => 'password',
            ]
        );

        User::updateOrCreate(
            ['email' => 'aleanaamurao12@gmail.com'],
            [
                'role_id' => $adminRoleId,
                'name' => 'Admin Amurao',
                'username' => 'amurao',
                'password' => 'Password1!',
            ]
        );

        User::updateOrCreate(
            ['email' => 'caspher207@gmail.com'],
            [
                'role_id' => $adminRoleId,
                'name' => 'Admin Caspher',
                'username' => 'caspher',
                'password' => 'Password1!',
            ]
        );

        User::updateOrCreate(
            ['email' => 'deborah3915@gmail.com'],
            [
                'role_id' => $adminRoleId,
                'name' => 'Admin Deborah',
                'username' => '',
                'password' => 'Password1!',
            ]
        );

        User::updateOrCreate(
            ['email' => '⁠Comidahealth77@gmail.com'],
            [
                'role_id' => $adminRoleId,
                'name' => 'Admin Comidahealth',
                'username' => 'Comidahealth',
                'password' => 'Password1!',
            ]
        );

        User::updateOrCreate(
            ['email' => 'Victorlyp@hotmail.com'],
            [
                'role_id' => $adminRoleId,
                'name' => 'Admin Victorlyp',
                'username' => 'Victorlyp',
                'password' => 'Password1!',
            ]
        );
    }
}
