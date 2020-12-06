<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Gender;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Tests\Traits\TestSaves;
use Tests\Traits\TestValidations;

class GenderControllerTest extends TestCase
{
    use DatabaseMigrations, TestValidations, TestSaves;

    private $gender;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gender = factory(Gender::class)->create();
    }

    public function testIndex()
    {
        $response = $this->get(route('genders.index'));

        $response
            ->assertStatus(200)
            ->assertJson([$this->gender->toArray()]);
    }

    public function testShow()
    {
        $response = $this->get(route('genders.show', ['gender' => $this->gender->id]));

        $response
            ->assertStatus(200)
            ->assertJson($this->gender->toArray());
    }

    public function testInvalidationData()
    {
        $data = [
            'name' => ''
        ];
        $this->assertInvalidationInStoreAction($data, 'required');
        $this->assertInvalidationInUpdateAction($data, 'required');

        $data = [
            'name' => str_repeat('a', 256),
        ];
        $this->assertInvalidationInStoreAction($data, 'max.string', ['max' => 255]);
        $this->assertInvalidationInUpdateAction($data, 'max.string', ['max' => 255]);

        $data = [
            'is_active' => 'a'
        ];
        $this->assertInvalidationInStoreAction($data, 'boolean');
        $this->assertInvalidationInUpdateAction($data, 'boolean');
    }

    public function testStore()
    {
        $data = [
            'name' => 'test'
        ];
        $response = $this->assertStore(
            $data,
        $data + ['is_active' =>  true, 'deleted_at' => null]
        );
        $response->assertJsonStructure([
            'created_at',
            'updated_at'
        ]);

        $data = [
            'name' => 'Test',
            'is_active' => false
        ];
        $this->assertStore(
            $data,
        $data + ['is_active' =>  false]
        );
    }

    public function testUpdate()
    {
        $this->gender = factory(Gender::class)->create([
            'is_active' => false
        ]);
        $data = [
            'name' => 'New Test',
            'is_active' => true
        ];
        $response = $this->assertUpdate($data, $data + ['deleted_at' => null]);
        $response->assertJsonStructure([
            'created_at',
            'updated_at'
        ]);
    }

    public function testDestroy()
    {
        $response = $this->json(
            'DELETE',
            route('genders.destroy', ['gender' => $this->gender->id])
        );

        $response->assertStatus(204)->assertNoContent();
        $this->assertNull(Gender::find($this->gender->id));
        $this->assertNotNull(Gender::withTrashed()->find($this->gender->id));
    }

    protected function routeStore()
    {
        return route('genders.store');
    }

    protected function routeUpdate()
    {
        return route('genders.update', ['gender' => $this->gender->id]);
    }

    protected function model()
    {
        return Gender::class;
    }
}
