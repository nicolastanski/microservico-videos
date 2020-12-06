<?php

namespace Tests\Unit\Models;

use App\Models\CastMember;
use App\Models\Traits\Uuid;
use Illuminate\Database\Eloquent\SoftDeletes;
use PHPUnit\Framework\TestCase;

class CastMemberTest extends TestCase
{
    private $castMember;

    protected function setUp(): void
    {
        parent::setUp();
        $this->castMember = new CastMember();
    }

    public function testIfUseTraits()
    {
        $traits = [
            SoftDeletes::class,
            Uuid::class
        ];
        $castMemberTraits = array_values(class_uses(CastMember::class));
        $this->assertEquals($traits, $castMemberTraits);
    }

    public function testsFillableAttribute()
    {
        $fillable = ['name', 'type'];
        $this->assertEquals($fillable, $this->castMember->getFillable());
    }

    public function testsDatesAttribute()
    {
        $dates = ['created_at', 'updated_at', 'deleted_at'];
        foreach ($dates as $date) {
            $this->assertContains($date, $this->castMember->getDates());
        }
        $this->assertCount(count($dates), $this->castMember->getDates());
    }

    public function testsCastsAttribute()
    {
        $casts = ['id' => 'string', 'type' => 'integer'];
        $this->assertEquals($casts, $this->castMember->getCasts());
    }

    public function testIncrementingAttribute()
    {
        $this->assertFalse($this->castMember->incrementing);
    }
}
