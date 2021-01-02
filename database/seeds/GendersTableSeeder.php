<?php

use App\Models\Category;
use App\Models\Gender;
use Illuminate\Database\Seeder;

class GenderTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = Category::all();
        factory(Gender::class, 100)
                ->create()
            ->each(function (Gender $gender) use ($categories) {
                $categoriesId = $categories->random(5)->pluck('id')->toArray();
                $gender->categories()->attach($categoriesId);
            });
    }
}
