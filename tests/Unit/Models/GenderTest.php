<?php

namespace Tests\Unit\Models;

use App\Models\Gender;
use App\Models\Traits\Uuid;
use Illuminate\Database\Eloquent\SoftDeletes;
use PHPUnit\Framework\TestCase;

class GenderTest extends TestCase
{
    private $gender;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gender = new Gender();
    }

    public function testIfUseTraits()
    {
        $traits = [
            SoftDeletes::class,
            Uuid::class
        ];
        $genderTraits = array_values(class_uses(Gender::class));
        $this->assertEquals($traits, $genderTraits);
    }

    public function testsFillableAttribute()
    {
        $fillable = ['name', 'is_active'];
        $this->assertEquals($fillable, $this->gender->getFillable());
    }

    public function testsDatesAttribute()
    {
        $dates = ['created_at', 'updated_at', 'deleted_at'];
        foreach ($dates as $date) {
            $this->assertContains($date, $this->gender->getDates());
        }
        $this->assertCount(count($dates), $this->gender->getDates());
    }

    public function testsCastsAttribute()
    {
        $casts = ['id' => 'string', 'is_active' => 'boolean'];
        $this->assertEquals($casts, $this->gender->getCasts());
    }

    public function testIncrementingAttribute()
    {
        $this->assertFalse($this->gender->incrementing);
    }
}
