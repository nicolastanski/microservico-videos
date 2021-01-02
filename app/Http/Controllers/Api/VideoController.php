<?php

namespace App\Http\Controllers\Api;

use App\Models\Video;
use App\Rules\GendersHasCategoriesRule;
use Illuminate\Http\Request;

class VideoController extends BasicCrudController
{
    private $rules = [];

    public function __construct()
    {
        $this->rules = [
            'title' => 'required|max:255',
            'description' => 'required',
            'year_launched' => 'required|date_format:Y',
            'opened' => 'boolean',
            'rating' => 'required|in:' . implode(',', Video::RATING_LIST),
            'duration' => 'required|integer',
            'categories_id' => 'required|array|exists:categories,id,deleted_at,NULL',
            'genders_id' => [
                'required',
                'array',
                'exists:genders,id,deleted_at,NULL'
            ],
            'video_file' => 'mimetypes:video/mp4|max:1024'
        ];
    }

    public function store(Request $request)
    {
        $this->addRuleIfGenderHasCategories($request);
        $validatedData = $this->validate($request, $this->rulesStore());
        $obj = $this->model()::create($validatedData);
        $obj->refresh();
        return $obj;
    }

    public function update(Request $request, $id)
    {
        $obj = $this->findOrFail($id);
        $this->addRuleIfGenderHasCategories($request);
        $validatedData = $this->validate($request, $this->rulesUpdate());
        $obj->update($validatedData);
        return $obj;
    }

    protected function addRuleIfGenderHasCategories(Request $request)
    {
        $categoriesId = $request->get('categories_id');
        $categoriesId = is_array($categoriesId) ? $categoriesId : [];
        $this->rules['genders_id'][] = new GendersHasCategoriesRule(
            $categoriesId
        );
    }

    protected function model()
    {
        return Video::class;
    }

    protected function rulesStore()
    {
        return $this->rules;
    }

    protected function rulesUpdate()
    {
        return $this->rules;
    }
}
