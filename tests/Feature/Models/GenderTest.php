<?php

namespace Tests\Feature\Models;

use App\Models\Gender;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class GenderTest extends TestCase
{
    use DatabaseMigrations;

    public function testItShouldCreateAnUuid()
    {
        $gender = factory(Gender::class)->create();
        $this->assertEquals(36, strlen($gender->id));
    }

    public function testList()
    {
        factory(Gender::class)->create();

        $genders = Gender::all();
        $gendersKeys = array_keys($genders->first()->getAttributes());
        $this->assertEqualsCanonicalizing([
            'id',
            'name',
            'is_active',
            'created_at',
            'updated_at',
            'deleted_at'
        ], $gendersKeys);
    }

    public function testCreate()
    {
        $gender = Gender::create([
            'name' => 'Gender'
        ]);
        $gender->refresh();

        $this->assertEquals('Gender', $gender->name);
        $this->assertTrue($gender->is_active);

        $gender = Gender::create([
            'name' => 'Gender',
            'is_active' =>  false
        ]);

        $this->assertFalse($gender->is_active);
    }

    public function testUpdate()
    {
        /** @var Gender $gender */
        $gender = factory(Gender::class)->create([
            'is_active' => false
        ]);

        $data = [
            'name' => 'Gender',
            'is_active' => true
        ];
        $gender->update($data);

        foreach ($data as $key => $value) {
            $this->assertEquals($value, $gender->{$key});
        }
    }

    public function testDelete()
    {
        $gender = factory(Gender::class)->create();
        $this->assertNull($gender->deleted_at);
        $gender->delete();
        $this->assertNotNull($gender->deleted_at);
    }
}
