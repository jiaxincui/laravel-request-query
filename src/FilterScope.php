<?php

namespace Jiaxincui\RequestFilter;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Jiaxincui\RequestFilter\Filter;
trait FilterScope
{
    #[Scope]
    protected function filter(Builder $builder, Filter $filter): Builder
    {
        return $filter->apply($builder);
    }
}