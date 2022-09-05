<?php

namespace App\Filters;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class AbstractFilter
{
    protected $request;

    protected $filters = [];

    const SELECTED_FIELDS = [
        'products.*',
        'products_images.image as image',
    ];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function without_filter(Builder $builder)
    {
        $builder
            ->select('products.*')
            ->selectRaw('MIN(`offers`.`price`) as `price`')
            ->where('products.active', true)
            ->leftJoin('offers', 'products.id', '=', 'offers.product_id');

            $builder->where(function($query) {
                $query->whereNotNull('offers.product_id');
                $query->where('offers.active', true);
            });

            foreach($this->getFilters() as $filter => $value)
            {
                if($filter == 'section'){
                    $this->resolveFilter($filter)->filter($builder, $value);
                }
            }

            $builder->groupBy('products.id');

        return $builder;
    }

    public function filter(Builder $builder)
    {
        $builder
            ->select(self::SELECTED_FIELDS)
            ->selectRaw('MIN(`offers`.`price`) as `price`')
            ->where('products.active', true)

            ->where(function ($query) {
                $query
                    ->whereNull('products_images.sort')
                    ->orWhere('products_images.sort', 0)
                    ->orWhere('products_images.sort', 500);
            })

            ->leftJoin('offers', 'products.id', '=', 'offers.product_id')
            ->leftJoin('products_images', 'products.id', '=', 'products_images.product_id');

            //Выбираем только те товары у которых есть торговые предложения
            //торговые предложения должны быть активны
            $builder->where(function($query) {
                $query->whereNotNull('offers.product_id');
                $query->where('offers.active', true);
            });

        //Возможно эти orders будут очищены в фильтре SortFilter
        $builder->orderBy('products.sort', 'asc');

        foreach($this->getFilters() as $filter => $value)
        {
            $this->resolveFilter($filter)->filter($builder, $value);
        }

        $builder->groupBy('products.id');

        return $builder;
    }

    protected function getFilters()
    {
        return array_filter($this->request->only(array_keys($this->filters)));
    }

    protected function resolveFilter($filter)
    {
        return new $this->filters[$filter];
    }
}
