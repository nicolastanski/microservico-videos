<?php

namespace Tests\Stubs\Controllers;

use App\Http\Controllers\Api\BasicCrudController;
use Tests\Stubs\Models\CategoryStub;

class CategoryControllerStub extends BasicCrudController
{
    protected function model()
    {
        return CategoryStub::class;
    }

    public function rulesStore()
    {
        return [
            'name' => 'required|max:255',
            'description' => 'nullable'
        ];
    }

    public function rulesUpdate()
    {
        return [
            'name' => 'required|max:255',
            'description' => 'nullable'
        ];
    }
}
