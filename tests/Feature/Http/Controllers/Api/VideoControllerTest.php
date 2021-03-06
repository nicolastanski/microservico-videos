<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Category;
use App\Models\Gender;
use App\Models\Video;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Tests\Traits\TestSaves;
use Tests\Traits\TestValidations;

class VideoControllerTest extends TestCase
{
    use DatabaseMigrations, TestValidations, TestSaves;

    private $video;
    private $sendData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->video = factory(Video::class)->create([
            'opened' => false
        ]);
        $this->sendData = [
            'title' => 'title',
            'description' => 'description',
            'year_launched' => 2010,
            'rating' => Video::RATING_LIST[0],
            'duration' => 90
        ];
    }

    public function testIndex()
    {
        $response = $this->get(route('videos.index'));

        $response
            ->assertStatus(200)
            ->assertJson([$this->video->toArray()]);
    }

    public function testShow()
    {
        $response = $this->get(route('videos.show', ['video' => $this->video->id]));

        $response
            ->assertStatus(200)
            ->assertJson($this->video->toArray());
    }

    public function testInvalidationRequired()
    {
        $data = [
            'title' => '',
            'description' => '',
            'year_launched' => '',
            'rating' => '',
            'duration' => '',
            'categories_id' => '',
            'genders_id' => '',
        ];
        $this->assertInvalidationInStoreAction($data, 'required');
        $this->assertInvalidationInUpdateAction($data, 'required');
    }

    public function testInvalidationMax()
    {
        $data = [
            'title' => str_repeat('a', 256)
        ];
        $this->assertInvalidationInStoreAction($data, 'max.string', ['max' => 255]);
        $this->assertInvalidationInUpdateAction($data, 'max.string', ['max' => 255]);
    }

    public function testInvalidationInteger()
    {
        $data = [
            'duration' => 's'
        ];
        $this->assertInvalidationInStoreAction($data, 'integer');
        $this->assertInvalidationInUpdateAction($data, 'integer');
    }

    public function testInvalidationYearLaunchedField()
    {
        $data = [
            'year_launched' => 'a'
        ];
        $this->assertInvalidationInStoreAction($data, 'date_format', ['format' => 'Y']);
        $this->assertInvalidationInUpdateAction($data, 'date_format', ['format' => 'Y']);
    }

    public function testInvalidationOpenedField()
    {
        $data = [
            'opened' => 'a'
        ];
        $this->assertInvalidationInStoreAction($data, 'boolean');
        $this->assertInvalidationInUpdateAction($data, 'boolean');
    }

    public function testInvalidationRatingField()
    {
        $data = [
            'rating' => 0
        ];
        $this->assertInvalidationInStoreAction($data, 'in');
        $this->assertInvalidationInUpdateAction($data, 'in');
    }

    public function testInvalidationCategoriesIdField()
    {
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

    public function testInvalidationGendersIdField()
    {
        $data = [
            'genders_id' => 'a'
        ];
        $this->assertInvalidationInStoreAction($data, 'array');
        $this->assertInvalidationInUpdateAction($data, 'array');

        $data = [
            'genders_id' => [100]
        ];
        $this->assertInvalidationInStoreAction($data, 'exists');
        $this->assertInvalidationInUpdateAction($data, 'exists');

        $gender = factory(Gender::class)->create();
        $gender->delete();
        $data = [
            'genders_id' => [$gender->id]
        ];
        $this->assertInvalidationInStoreAction($data, 'exists');
        $this->assertInvalidationInUpdateAction($data, 'exists');
    }

    public function testInvalidationVideoFileField()
    {
        $data = [
            'video_file' => 'a'
        ];
        $this->assertInvalidationInStoreAction($data, 'mimetypes', ['values' => 'video/mp4']);
        $this->assertInvalidationInUpdateAction($data, 'mimetypes', ['values' => 'video/mp4']);

        $data = [
            'video_file' => UploadedFile::fake()->create('video.mp4', 4096)
        ];
        $this->assertInvalidationInStoreAction($data, 'max.file', ['max' => 1024]);
        $this->assertInvalidationInUpdateAction($data, 'max.file', ['max' => 1024]);
    }

    public function testSave()
    {
        $category = factory(Category::class)->create();
        $gender = factory(Gender::class)->create();
        $gender->categories()->sync($category->id);

        $data = [
            [
                'send_data' => $this->sendData + ['categories_id' => [$category->id]] + ['genders_id' => [$gender->id]],
                'test_data' => $this->sendData + ['opened' => false]
            ],
            [
                'send_data' => $this->sendData + [
                    'opened' => true,
                    'categories_id' => [$category->id],
                    'genders_id' => [$gender->id]
                ],
                'test_data' => $this->sendData + ['opened' => true]
            ],
            [
                'send_data' => $this->sendData + [
                    'rating' => Video::RATING_LIST[1],
                    'categories_id' => [$category->id],
                    'genders_id' => [$gender->id]
                ],
                'test_data' => $this->sendData + ['rating' => Video::RATING_LIST[1]]
            ],
            [
                'send_data' => $this->sendData + [
                        'rating' => Video::RATING_LIST[1],
                        'categories_id' => [$category->id],
                        'genders_id' => [$gender->id]
                    ],
                'test_data' => $this->sendData + ['rating' => Video::RATING_LIST[1]]
            ],
        ];

        foreach ($data as $key => $value) {
            $response = $this->assertStore(
                $value['send_data'],
                $value['test_data'] + ['deleted_at' => null]
            );
            $response->assertJsonStructure([
                'created_at',
                'updated_at'
            ]);
            $this->assertDatabaseHas('category_video', [
                'category_id' => $value['send_data']['categories_id'][0],
                'video_id' => $response->json('id')
            ]);
            $this->assertDatabaseHas('gender_video', [
                'gender_id' => $value['send_data']['genders_id'][0],
                'video_id' => $response->json('id')
            ]);

            $response = $this->assertUpdate(
                $value['send_data'],
                $value['test_data'] + ['deleted_at' => null]
            );
            $response->assertJsonStructure([
                'created_at',
                'updated_at'
            ]);
            $this->assertDatabaseHas('category_video', [
                'category_id' => $value['send_data']['categories_id'][0],
                'video_id' => $response->json('id')
            ]);
            $this->assertDatabaseHas('gender_video', [
                'gender_id' => $value['send_data']['genders_id'][0],
                'video_id' => $response->json('id')
            ]);

        }
    }

//    public function testRollbackStore()
//    {
//        $controller = \Mockery::mock(VideoController::class)
//            ->makePartial()
//            ->shouldAllowMockingProtectedMethods();
//
//        $controller->shouldReceive('validate')
//            ->withAnyArgs()
//            ->andReturn($this->sendData);
//
//        $controller->shouldReceive('rulesStore')
//            ->withAnyArgs()
//            ->andReturn([]);
//
//        $request = \Mockery::mock(Request::class);
//        $request->shouldReceive('get')
//            ->withAnyArgs()
//            ->andReturnNull();
//
//        $controller->shouldReceive('handleRelations')
//            ->once()
//            ->andThrow(new TestException());
//
//        $hasError = false;
//        try {
//            $controller->store($request);
//        } catch (TestException $exception) {
//            $this->assertCount(1, Video::all());
//            $hasError = true;
//        }
//
//        $this->assertTrue($hasError);
//    }
//
//    public function testRollbackUpdate()
//    {
//        $controller = \Mockery::mock(VideoController::class)
//            ->makePartial()
//            ->shouldAllowMockingProtectedMethods();
//
//        $controller->shouldReceive('findOrFail')
//            ->withAnyArgs()
//            ->andReturn($this->video);
//
//        $controller->shouldReceive('validate')
//            ->withAnyArgs()
//            ->andReturn($this->sendData);
//
//        $controller->shouldReceive('rulesUpdate')
//            ->withAnyArgs()
//            ->andReturn([]);
//
//        $request = \Mockery::mock(Request::class);
//        $request->shouldReceive('get')
//            ->withAnyArgs()
//            ->andReturnNull();
//
//        $controller->shouldReceive('handleRelations')
//            ->once()
//            ->andThrow(new TestException());
//
//        $hasError = false;
//        try {
//            $controller->update($request, 1);
//        } catch (TestException $exception) {
//            $this->assertCount(1, Video::all());
//            $hasError = true;
//        }
//
//        $this->assertTrue($hasError);
//    }

//    public function testSyncCategories()
//    {
//        $categoriesId = factory(Category::class, 3)->create()->pluck('id')->toArray();
//        $gender = factory(Gender::class)->create();
//        $gender->categories()->sync($categoriesId);
//        $genderId = $gender->id;
//
//        $response = $this->json(
//            'POST',
//            $this->routeStore(),
//            $this->sendData + ['genders_id' => [$genderId], 'categories_id' => [$categoriesId[0]]]
//        );
//
//        $this->assertDatabaseHas('category_video', [
//            'category_id' => $categoriesId[0],
//            'video_id' => $response->json('id')
//        ]);
//
//        $response = $this->json(
//            'PUT',
//            route('videos.update', ['video' => $response->json('id')]),
//            $this->sendData + ['genders_id' => [$genderId], 'categories_id' => [$categoriesId[1], $categoriesId[2]]]
//        );
//
//        $this->assertDatabaseMissing('category_video', [
//            'category_id' => $categoriesId[0],
//            'video_id' => $response->json('id')
//        ]);
//        $this->assertDatabaseHas('category_video', [
//            'category_id' => $categoriesId[1],
//            'video_id' => $response->json('id')
//        ]);
//        $this->assertDatabaseHas('category_video', [
//            'category_id' => $categoriesId[2],
//            'video_id' => $response->json('id')
//        ]);
//    }

//    public function testSyncGenders()
//    {
//        $genders = factory(Gender::class, 3)->create();
//        $gendersId = $genders->pluck('id')->toArray();
//        $categoryId = factory(Category::class)->create()->id;
//        $genders->each(function ($gender) use ($categoryId) {
//            $gender->categories()->sync($categoryId);
//        });
//
//        $response = $this->json(
//            'POST',
//            $this->routeStore(),
//            $this->sendData + ['categories_id' => [$categoryId], 'genders_id' => [$gendersId[0]]]
//        );
//
//        $this->assertDatabaseHas('gender_video', [
//            'gender_id' => $gendersId[0],
//            'video_id' => $response->json('id')
//        ]);
//
//        $response = $this->json(
//            'PUT',
//            route('videos.update', ['video' => $response->json('id')]),
//            $this->sendData + ['categories_id' => [$categoryId], 'genders_id' => [$gendersId[1], $gendersId[2]]]
//        );
//
//        $this->assertDatabaseMissing('gender_video', [
//            'gender_id' => $gendersId[0],
//            'video_id' => $response->json('id')
//        ]);
//        $this->assertDatabaseHas('gender_video', [
//            'gender_id' => $gendersId[1],
//            'video_id' => $response->json('id')
//        ]);
//        $this->assertDatabaseHas('gender_video', [
//            'gender_id' => $gendersId[2],
//            'video_id' => $response->json('id')
//        ]);
//    }

    public function testUploadFile()
    {
        \Storage::fake();

        $category = factory(Category::class)->create();
        $gender = factory(Gender::class)->create();
        $gender->categories()->sync($category->id);

        $file = UploadedFile::fake()->create('video.mp4', 512, 'video/mp4');

        $response = $this->json(
            'POST',
            $this->routeStore(),
            $this->sendData + [
                'categories_id' => [$category->id],
                'genders_id' => [$gender->id],
                'video_file' => $file
            ]
        );

        $response->assertStatus(201);
        $this->assertEquals($file->hashName(), $response->json('video_file'));
        \Storage::assertExists("{$response->json('id')}/{$file->hashName()}");

        $file1 = UploadedFile::fake()->create('video1.mp4', 512, 'video/mp4');
        $response = $this->json(
            'PUT',
            $this->routeUpdate(),
            $this->sendData + [
                'categories_id' => [$category->id],
                'genders_id' => [$gender->id],
                'video_file' => $file1
            ]
        );

        $response->assertStatus(200);
        $this->assertEquals($file1->hashName(), $response->json('video_file'));
        \Storage::assertExists("{$response->json('id')}/{$file1->hashName()}");
    }

    public function testDestroy()
    {
        $response = $this->json(
            'DELETE',
            route('videos.destroy', ['video' => $this->video->id])
        );

        $response->assertStatus(204)->assertNoContent();
        $this->assertNull(Video::find($this->video->id));
        $this->assertNotNull(video::withTrashed()->find($this->video->id));
    }

    protected function routeStore()
    {
        return route('videos.store');
    }

    protected function routeUpdate()
    {
        return route('videos.update', ['video' => $this->video->id]);
    }

    protected function model()
    {
        return Video::class;
    }
}
