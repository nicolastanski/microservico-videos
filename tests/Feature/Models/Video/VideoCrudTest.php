<?php


namespace Tests\Feature\Models\Video;


use App\Models\Category;
use App\Models\Gender;
use App\Models\Video;
use Illuminate\Database\QueryException;

class VideoCrudTest extends BaseVideoTestCase
{
    private $fileFields = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (Video::$fileFields as $field) {
            $fileFields[$field] = "$field.text";
        }
    }

    public function testList()
    {
        factory(Video::class)->create();
        $videos = Video::all();
        $this->assertCount(1, $videos);
        $videoKeys = array_keys($videos->first()->getAttributes());

        $this->assertEqualsCanonicalizing([
            'id',
            'title',
            'description',
            'year_launched',
            'opened',
            'rating',
            'duration',
            'created_at',
            'updated_at',
            'deleted_at',
            'thumb_file',
            'banner_file',
            'trailer_file',
            'video_file'
        ],
            $videoKeys
        );
    }

    public function testCreateWithBasicFields()
    {
        $video = Video::create($this->sendData + $this->fileFields);
        $video->refresh();

        $this->assertEquals(36, strlen($video->id));
        $this->assertFalse($video->opened);
        $this->assertDatabaseHas('videos', $this->sendData + $this->fileFields + ['opened' => false]);

        $video = Video::create($this->sendData + ['opened' => true]);
        $this->assertTrue($video->opened);
        $this->assertDatabaseHas('videos', ['opened' => true]);
    }

    public function testCreateWithRelations()
    {
        $category = factory(Category::class)->create();
        $gender = factory(Gender::class)->create();
        $video = Video::create($this->sendData + [
                'categories_id' => [$category->id],
                'genders_id' => [$gender->id]
            ]);

        $this->assertHasCategory($video->id, $category->id);
        $this->assertHasGender($video->id, $gender->id);
    }

    public function testUpdateWithBasicFields()
    {
        $video = factory(Video::class)->create([
            'opened' => false
        ]);
        $video->update($this->sendData +  $this->fileFields);
        $this->assertFalse($video->opened);
        $this->assertDatabaseHas('videos', $this->sendData +  $this->fileFields + ['opened' => false]);

        $video = factory(Video::class)->create([
            'opened' => false
        ]);
        $video->update($this->sendData + ['opened' => true]);
        $this->assertTrue($video->opened);
        $this->assertDatabaseHas('videos', $this->sendData + ['opened' => true]);
    }

    public function testUpdateWithRelations()
    {
        $category = factory(Category::class)->create();
        $gender = factory(Gender::class)->create();
        $video = factory(Video::class)->create();
        $video->update($this->sendData + [
                'categories_id' => [$category->id],
                'genders_id' => [$gender->id]
            ]);

        $this->assertHasCategory($video->id, $category->id);
        $this->assertHasGender($video->id, $gender->id);
    }

    protected function assertHasCategory($videoId, $categoryId)
    {
        $this->assertDatabaseHas('category_video', [
            'video_id' => $videoId,
            'category_id' => $categoryId
        ]);
    }

    protected function assertHasGender($videoId, $genderId)
    {
        $this->assertDatabaseHas('gender_video', [
            'video_id' => $videoId,
            'gender_id' => $genderId
        ]);
    }

    public function testRollbackCreate()
    {
        $hasError = false;
        try {
            Video::create([
                'title' => 'title',
                'description' => 'description',
                'year_launched' => 2010,
                'rating' => Video::RATING_LIST[0],
                'duration' => 90,
                'categories_id' => [0, 1, 2]
            ]);
        } catch (\Exception $exception) {
            $this->assertCount(0, Video::all());
            $hasError = true;
        }

        $this->assertTrue($hasError);
    }

    public function testRollbackUpdate()
    {
        $video = factory(Video::class)->create();
        $hasError = false;
        $oldTitle = $video->title;
        try {
            $video->update([
                'title' => 'title',
                'description' => 'description',
                'year_launched' => 2010,
                'rating' => Video::RATING_LIST[0],
                'duration' => 90,
                'categories_id' => [0, 1, 2]
            ]);
        } catch (QueryException $exception) {
            $this->assertDatabaseHas('videos', [
                'title' => $oldTitle
            ]);
            $hasError = true;
        }
        $this->assertTrue($hasError);
    }

    public function testHandleRelations()
    {
        $video = factory(Video::class)->create();
        Video::handleRelations($video, []);
        $this->assertCount(0, $video->categories);
        $this->assertCount(0, $video->genders);

        $category = factory(Category::class)->create();
        Video::handleRelations($video, [
            'categories_id' => [$category->id]
        ]);
        $video->refresh();
        $this->assertCount(1, $video->categories);

        $gender = factory(Gender::class)->create();
        Video::handleRelations($video, [
            'genders_id' => [$gender->id]
        ]);
        $video->refresh();
        $this->assertCount(1, $video->genders);

        $video->categories()->delete();
        $video->genders()->delete();

        Video::handleRelations($video, [
            'categories_id' => [$category->id],
            'genders_id' => [$gender->id]
        ]);
        $video->refresh();
        $this->assertCount(1, $video->categories);
        $this->assertCount(1, $video->genders);
    }

    public function testSyncCategories()
    {
        $categoriesId = factory(Category::class, 3)->create()->pluck('id')->toArray();
        $video = factory(Video::class)->create();
        Video::handleRelations($video, [
            'categories_id' => [$categoriesId[0]]
        ]);
        $this->assertDatabaseHas('category_video', [
            'category_id' => $categoriesId[0],
            'video_id' => $video->id
        ]);

        Video::handleRelations($video, [
            'categories_id' => [$categoriesId[1], $categoriesId[2]]
        ]);

        $this->assertDatabaseMissing('category_video', [
            'category_id' => $categoriesId[0],
            'video_id' => $video->id
        ]);

        $this->assertDatabaseHas('category_video', [
            'category_id' => $categoriesId[1],
            'video_id' => $video->id
        ]);
        $this->assertDatabaseHas('category_video', [
            'category_id' => $categoriesId[2],
            'video_id' => $video->id
        ]);
    }

    public function testSyncGenders()
    {
        $gendersId = factory(Gender::class, 3)->create()->pluck('id')->toArray();
        $video = factory(Video::class)->create();
        Video::handleRelations($video, [
            'genders_id' => [$gendersId[0]]
        ]);
        $this->assertDatabaseHas('gender_video', [
            'gender_id' => $gendersId[0],
            'video_id' => $video->id
        ]);

        Video::handleRelations($video, [
            'genders_id' => [$gendersId[1], $gendersId[2]]
        ]);
        $this->assertDatabaseMissing('gender_video', [
            'gender_id' => $gendersId[0],
            'video_id' => $video->id
        ]);
        $this->assertDatabaseHas('gender_video', [
            'gender_id' => $gendersId[1],
            'video_id' => $video->id
        ]);
        $this->assertDatabaseHas('gender_video', [
            'gender_id' => $gendersId[2],
            'video_id' => $video->id
        ]);
    }

    public function testDelete()
    {
        $video = factory(Video::class)->create();
        $video->delete();
        $this->assertNull(Video::find($video->id));

        $video->restore();
        $this->assertNotNull(Video::find($video->id));
    }
}
