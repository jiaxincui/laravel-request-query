<?php
namespace Jiaxincui\RequestFilter;

use Illuminate\Database\Eloquent\Builder;

interface Filter
{
    /**
     * @param Builder $builder
     * @return Builder
     */
    public function apply(Builder $builder): Builder;
}
