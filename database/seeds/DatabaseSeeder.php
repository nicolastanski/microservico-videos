<?php

use App\Models\CastMember;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(CategoriesTableSeeder::class);
        $this->call(GenderTableSeeder::class);
        $this->call(CastMember::class);
    }
}
