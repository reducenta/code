<?php
namespace App\Filters;

class SortFilter
{

    public $values;

    public function filter($builder, $value)
    {
        $builder->getQuery()->orders = null;

        switch ($value){
            case 'name_asc':
                $builder->orderBy('products.name', 'asc');
                break;
            case 'name_desc':
                $builder->orderBy('products.name', 'desc');
                break;
            case 'price_asc':
                $builder->orderBy('offers.price', 'asc');
                break;
            case 'price_desc':
                $builder->orderBy('offers.price', 'desc');
                break;
        }

        return $builder;
    }
}
