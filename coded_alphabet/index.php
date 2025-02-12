<?php

//Paralelepipedo
function coded_alphabet($word)
{
    $key = '';
    $alphabet = range('a', 'z');

    $array_word = str_split($word);

    $count_word = count($array_word);

   for($i=0; $i<$count_word; $i++){
        $key .= array_search($array_word[$i],$alphabet); 
   }

    

    return $key;

}




var_dump(coded_alphabet('paralelepipedo'));