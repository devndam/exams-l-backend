<?php

namespace App\Support;

use InvalidArgumentException;

class Similarity
{
    /**
     * @param  array<float>  $a
     * @param  array<float>  $b
     */
    public static function euclideanDistance(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            throw new InvalidArgumentException('Embedding dimension mismatch: '.count($a).' vs '.count($b));
        }

        $sum = 0.0;
        foreach ($a as $i => $value) {
            $d = $value - $b[$i];
            $sum += $d * $d;
        }

        return sqrt($sum);
    }
}
