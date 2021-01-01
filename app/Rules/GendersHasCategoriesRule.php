<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Collection;

class GendersHasCategoriesRule implements Rule
{
    private $categoriesId;

    private $gendersId;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(array $categoriesId)
    {
        $this->categoriesId = array_unique($categoriesId);
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if(!is_array($value)) {
            $value = [];
        }

        $this->gendersId = array_unique($value);
        if(!count($this->gendersId) || !count($this->categoriesId)) {
            return  false;
        }

        $categoriesFound = [];
        foreach ($this->gendersId as  $genderId) {
            $rows = $this->getRows($genderId);
            if (!$rows->count()) {
                return false;
            }
            array_push($categoriesFound, ...$rows->pluck('category_id')->toArray());
        }

        $categoriesFound = array_unique($categoriesFound);

        if (count($categoriesFound) !== count($this->categoriesId)) {
            return false;
        }
        return true;
    }

    public function getRows($genderId): Collection
    {
        return \DB
            ::table('category_gender')
            ->where('gender_id', $genderId)
            ->whereIn('category_id', $this->categoriesId)
            ->get();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('validation.genres_has_categories');
    }
}
