<?php

function fatorial(int $number): int
{
    if($number <= 1){
        return $number;
    }else{
        return $number *= fatorial($number -1);
    }
}


echo fatorial(4);
