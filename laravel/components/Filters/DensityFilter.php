<?php
namespace App\Filters;

class DensityFilter
{
    public $values;

    public function filter($builder, $value)
    {
        $this->values = explode(',', $value);

        $builder->where(function($query) {
            foreach($this->values as $i => $item){
                if($i == 0){
                    $query->where('density', $item);
                }else{
                    $query->orWhere('density', $item);
                }
            }
        });

        return $builder;
    }
}
