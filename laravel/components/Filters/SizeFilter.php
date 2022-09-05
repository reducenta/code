<?php
namespace App\Filters;

class SizeFilter
{

    public $values;

    public function filter($builder, $value)
    {
        $this->values = explode(',', $value);

        $builder->where(function($query) {
            foreach($this->values as $i => $item){
                if($i == 0){
                    $query->where('offers.size', $item);
                }else{
                    $query->orWhere('offers.size', $item);
                }
            }
        });

        return $builder;
    }
}
