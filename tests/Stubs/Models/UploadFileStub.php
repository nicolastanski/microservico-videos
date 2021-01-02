<?php

namespace Tests\Stubs\Models;

use App\Models\Traits\UploadFiles;

class UploadFileStub
{
    use UploadFiles;

    public static $fileFields = ['file1', 'file2'];

    protected function uploadDir()
    {
        return '1';
    }
}
