<?php
namespace App\Filters;

class SectionFilter
{
    public $values;

    public function filter($builder, $value)
    {
        $builder->whereIn('section_id', explode(',', $value));
        return $builder;
    }
}
