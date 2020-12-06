<?php

namespace Tests\Stub\Controllers;

use App\Http\Controllers\Api\BasicCrudController;
use Tests\Stubs\Modes\CategoryStub;

class CategoryControllerStub extends BasicCrudController
{
    protected function model()
    {
        return CategoryStub::class();
    }
}
