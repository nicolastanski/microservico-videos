<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Http\Controllers\Api\GenderController;
use App\Models\Category;
use App\Models\Gender;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Request;
use Tests\Exceptions\TestException;
use Tests\TestCase;
use Tests\Traits\TestSaves;
use Tests\Traits\TestValidations;

class GenderControllerTest extends TestCase
{
    use DatabaseMigrations, TestValidations, TestSaves;

    private $gender;
    private $sendData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gender = factory(Gender::class)->create();
        $this->sendData = [
            'name' => 'test'
        ];
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
            'name' => '',
            'categories_id' => ''
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

        $data = [
            'categories_id' => 'a'
        ];
        $this->assertInvalidationInStoreAction($data, 'array');
        $this->assertInvalidationInUpdateAction($data, 'array');

        $data = [
            'categories_id' => [100]
        ];
        $this->assertInvalidationInStoreAction($data, 'exists');
        $this->assertInvalidationInUpdateAction($data, 'exists');

        $category = factory(Category::class)->create();
        $category->delete();
        $data = [
            'categories_id' => [$category->id]
        ];
        $this->assertInvalidationInStoreAction($data, 'exists');
        $this->assertInvalidationInUpdateAction($data, 'exists');
    }

    public function testStore()
    {
        $categoryId = factory(Category::class)->create()->id;
        $data = [
            'name' => 'test'
        ];
        $response = $this->assertStore(
            $data + ['categories_id' => [$categoryId]],
        $data + ['is_active' =>  true, 'deleted_at' => null]
        );
        $response->assertJsonStructure([
            'created_at',
            'updated_at'
        ]);

        $this->assertDatabaseHas('category_gender', [
            'gender_id' => $response->json('id'),
            'category_id' => $categoryId
        ]);

        $data = [
            'name' => 'Test',
            'is_active' => false
        ];
        $this->assertStore(
            $data + ['categories_id' => [$categoryId]],
        $data + ['is_active' =>  false]
        );
    }

    public function testUpdate()
    {
        $categoryId = factory(Category::class)->create()->id;
        $this->gender = factory(Gender::class)->create([
            'is_active' => false
        ]);
        $data = [
            'name' => 'New Test',
            'is_active' => true
        ];
        $response = $this->assertUpdate($data + ['categories_id' => [$categoryId]], $data + ['deleted_at' => null]);
        $response->assertJsonStructure([
            'created_at',
            'updated_at'
        ]);
        $this->assertDatabaseHas('category_gender', [
            'gender_id' => $response->json('id'),
            'category_id' => $categoryId
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

    public function testRollbackStore()
    {
        $controller = \Mockery::mock(GenderController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $controller->shouldReceive('validate')
            ->withAnyArgs()
            ->andReturn($this->sendData);

        $controller->shouldReceive('rulesStore')
            ->withAnyArgs()
            ->andReturn([]);

        $request = \Mockery::mock(Request::class);

        $controller->shouldReceive('handleRelations')
            ->once()
            ->andThrow(new TestException());

        $hasError = false;
        try {
            $controller->store($request);
        } catch (TestException $exception) {
            $this->assertCount(1, Gender::all());
            $hasError = true;
        }

        $this->assertTrue($hasError);
    }

    public function testRollbackUpdate()
    {
        $controller = \Mockery::mock(GenderController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $controller->shouldReceive('findOrFail')
            ->withAnyArgs()
            ->andReturn($this->gender);

        $controller->shouldReceive('validate')
            ->withAnyArgs()
            ->andReturn($this->sendData);

        $controller->shouldReceive('rulesUpdate')
            ->withAnyArgs()
            ->andReturn([]);

        $request = \Mockery::mock(Request::class);

        $controller->shouldReceive('handleRelations')
            ->once()
            ->andThrow(new TestException());

        $hasError = false;
        try {
            $controller->update($request, 1);
        } catch (TestException $exception) {
            $this->assertCount(1, Gender::all());
            $hasError = true;
        }

        $this->assertTrue($hasError);
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
