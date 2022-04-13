<?php
namespace App\Filters;

class CompositionFilter
{
    public $values;

    public function filter($builder, $value)
    {
        $this->values = explode(',', $value);

        $builder->where(function($query) {
            foreach($this->values as $i => $item){
                if($i == 0){
                    $query->where('composition', $item);
                }else{
                    $query->orWhere('composition', $item);
                }
            }
        });

        return $builder;
    }
}
