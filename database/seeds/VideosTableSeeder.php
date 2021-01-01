<?php

use App\Models\Gender;
use App\Models\Video;
use Illuminate\Database\Seeder;

class VideoTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $genders = Gender::all();
        factory(Video::class, 100)
            ->create()
            ->each(function(Video $video) use ($genders) {
                $subGenders = $genders->random(5)->load('categories');
                $categoriesId = [];
                foreach ($subGenders as $gender) {
                    array_push($categoriesId, ...$gender->categories->pluck('id')->toArray());
                }
                $categoriesId = array_unique($categoriesId);
                $video->categories()->attach($categoriesId);
                $video->genders()->attach($subGenders->pluck('id')->toArray());
            });
    }
}
