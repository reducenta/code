<?php

namespace App;

class Parser
{
    public function parse($html)
    {
        preg_match_all('/<([^\/!][a-z1-9]*)/i',$html,$matches);
        return array_count_values($matches[1]);
    }
}