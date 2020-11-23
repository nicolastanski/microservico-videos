<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use DatabaseMigrations;

    public function testItShouldCreateAnUuid()
    {
        $category = factory(Category::class)->create();
        $this->assertEquals(36, strlen($category->id));
    }

    public function testList()
    {
        factory(Category::class)->create();

        $categories = Category::all();
        $categoryKeys = array_keys($categories->first()->getAttributes());
        $this->assertEqualsCanonicalizing([
            'id',
            'name',
            'description',
            'is_active',
            'created_at',
            'updated_at',
            'deleted_at'
        ], $categoryKeys);
    }

    public function testCreate()
    {
        $category = Category::create([
            'name' => 'Category One'
        ]);
        $category->refresh();

        $this->assertEquals('Category One', $category->name);
        $this->assertNull($category->description);
        $this->assertTrue($category->is_active);

        $category = Category::create([
            'name' => 'Category One',
            'description' =>  null
        ]);

        $this->assertNull($category->description);

        $category = Category::create([
            'name' => 'Category One',
            'description' =>  'Description'
        ]);

        $this->assertEquals('Description', $category->description);

        $category = Category::create([
            'name' => 'Category One',
            'is_active' =>  false
        ]);

        $this->assertFalse($category->is_active);

        $category = Category::create([
            'name' => 'Category One',
            'is_active' =>  false
        ]);

        $this->assertFalse($category->is_active);
    }

    public function testUpdate()
    {
        /** @var Category $category */
        $category = factory(Category::class)->create([
            'description' => 'Test description',
            'is_active' => false
        ]);

        $data = [
            'name' => 'Category updated',
            'description' => 'Description updated',
            'is_active' => true
        ];
        $category->update($data);

        foreach ($data as $key => $value) {
            $this->assertEquals($value, $category->{$key});
        }
    }

    public function testDelete()
    {
        $category = factory(Category::class)->create();
        $this->assertNull($category->deleted_at);
        $category->delete();
        $this->assertNotNull($category->deleted_at);
    }
}
