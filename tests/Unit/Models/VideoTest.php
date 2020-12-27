<?php

namespace Tests\Unit\Models;

use App\Models\Traits\Uuid;
use App\Models\Video;
use Illuminate\Database\Eloquent\SoftDeletes;
use PHPUnit\Framework\TestCase;

class VideoTest extends TestCase
{
    private $video;

    protected function setUp(): void
    {
        parent::setUp();
        $this->video = new Video();
    }

    public function testIfUseTraits()
    {
        $traits = [
            SoftDeletes::class,
            Uuid::class
        ];
        $videoTraits = array_values(class_uses(Video::class));
        $this->assertEquals($traits, $videoTraits);
    }

    public function testsFillableAttribute()
    {
        $fillable = ['title', 'description', 'year_launched', 'opened', 'rating', 'duration'];
        $this->assertEquals($fillable, $this->video->getFillable());
    }

    public function testsDatesAttribute()
    {
        $dates = ['created_at', 'updated_at', 'deleted_at'];
        foreach ($dates as $date) {
            $this->assertContains($date, $this->video->getDates());
        }
        $this->assertCount(count($dates), $this->video->getDates());
    }

    public function testsCastsAttribute()
    {
        $casts = ['id' => 'string', 'opened' => 'boolean', 'year_launched' => 'integer', 'duration' => 'integer'];
        $this->assertEquals($casts, $this->video->getCasts());
    }

    public function testIncrementingAttribute()
    {
        $this->assertFalse($this->video->incrementing);
    }
}
