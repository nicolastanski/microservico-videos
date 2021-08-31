<?php

namespace Tests\Prod\Models\Traits;

use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;
use Tests\Stubs\Models\UploadFileStub;
use Tests\Traits\TestProd;
use Tests\Traits\TestStorages;

class UploadFilesProdTest extends TestCase
{
    use TestStorages, TestProd;

    private $object;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skipTestIfNotProd();
        $this->object = new UploadFileStub();
        \Config::set('filesystems.default', 'gcs');
        $this->deleteAllFiles();
    }

    public function testUploadFile()
    {
        $file = UploadedFile::fake()->create('video.mp4');
        $this->object->uploadFile($file);
        \Storage::assertExists("1/{$file->hashName()}");
    }

    public function testUploadFiles()
    {
        $file1 = UploadedFile::fake()->create('video1.mp4');
        $file2 = UploadedFile::fake()->create('video2.mp4');
        $this->object->uploadFiles([$file1, $file2]);
        \Storage::assertExists("1/{$file1->hashName()}");
        \Storage::assertExists("1/{$file2->hashName()}");
    }

    public function testDeleteOldFiles()
    {
        $file1 = UploadedFile::fake()->create('video1.mp4')->size(1);
        $file2 = UploadedFile::fake()->create('video2.mp4')->size(1);
        $this->object->uploadFiles([$file1, $file2]);
        $this->object->deleteOldFiles();
        $this->assertCount(2, \Storage::allFiles());

        $this->object->oldFiles = [$file1->hashName()];
        $this->object->deleteOldFiles();
        \Storage::assertMissing("1/{$file1->hashName()}");
        \Storage::assertExists("1/{$file2->hashName()}");
    }

    public function testDeleteFile()
    {
        $file = UploadedFile::fake()->create('video.mp4');
        $this->object->uploadFile($file);
        $this->object->deleteFile($file->hashName());
        \Storage::assertMissing("1/{$file->hashName()}");

        $file = UploadedFile::fake()->create('video.mp4');
        $this->object->uploadFile($file);
        $this->object->deleteFile($file);
        \Storage::assertMissing("1/{$file->hashName()}");
    }

    public function testUDeleteFiles()
    {
        $file1 = UploadedFile::fake()->create('video1.mp4');
        $file2 = UploadedFile::fake()->create('video2.mp4');
        $this->object->uploadFiles([$file1, $file2]);
        $this->object->deleteFiles([$file1->hashName(), $file2]);
        \Storage::assertMissing("1/{$file1->hashName()}");
        \Storage::assertMissing("1/{$file2->hashName()}");
    }

}
