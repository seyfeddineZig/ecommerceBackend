<?php

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(Faker $facker)
    {

        $faker = Faker::create('fr_FR');

        DB::table('user_groups')->insert([
            'id'            => 1,
            'name'          => 'administrateur',
            'description'   => 'Les utilisateurs de ce groupe ont tous les privilèges, ce groupe ne peut pas ètre supprimé ou modifié',
        ]);

        DB::table('user_roles')->insert([
            'id'            => 1,
            'name'          => 'OPEN_SESSION',
            'description'   => 'Ouvrir une session',
            'group'         => 'Authentification',
        ]);

        DB::table('user_group_roles')->insert([
            'user_group_id' => 1,
            'user_role_id'  => 1
        ]);

        DB::table('users')->insert([
            'last_name'         => $faker->name,
            'first_name'        => $faker->name,
            'email'             => 'admin@admin.com',
            'password'          => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'user_group_id'     => 1
        ]);

        DB::table('langs')->insert([
            ['full_name' => 'Français', 'short_name' => 'Fr'],
            ['full_name' => 'العربية', 'short_name' => 'Ar']
        ]);
    }
}
