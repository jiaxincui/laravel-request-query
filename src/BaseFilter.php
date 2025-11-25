<?php

namespace Jiaxincui\RequestFilter;

use Closure;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseFilter implements Filter
{
    protected static array $requestQuery;
    protected Builder $builder;
    protected bool $trashed = false;
    protected array $dontCallMethods = [
        'applyWhere',
        'parseWhere',
        'whereQuery',
        'getFieldsQueryable',
        'getReleasable',
        'getSortable'
    ];

    /**
     * getFieldsQueryable
     *
     * @return array|string[]
     */
    abstract protected function getFieldsQueryable(): array;

    /**
     * getReleasable
     *
     * @return array|string[]
     */
    abstract protected function getReleasable(): array;

    /**
     * getSortable
     *
     * @return array|string[]
     */
    abstract protected function getSortable(): array;

    public static function setQuery(array $requestQuery): void
    {
        static::$requestQuery = $requestQuery;
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    protected function addBaseScope(Builder $builder): Builder
    {
        return $builder;
    }

    public function apply(Builder $builder): Builder
    {
        $this->builder = $this->addBaseScope($builder);

        $query = static::$requestQuery;

        foreach ($query as $name => $value) {
            if ($value && !in_array($name, $this->dontCallMethods) && method_exists($this, $name)) {
                call_user_func_array([$this, $name], array_filter([$value]));
            }
        }

        return $this->builder;
    }

    public function trashed(string $trashed): void
    {
        if ($this->trashed) {
            if ($trashed === 'only') {
                $this->builder->onlyTrashed();
            }
            if ($trashed === 'with') {
                $this->builder->withTrashed();
            }
        }
    }

    public function orderBy(string $orderBy): void
    {
        if ($orderBy) {
            $arr = explode(',', $orderBy);
            if (in_array($by = $arr[0], $this->getSortable())) {
                $this->builder->orderBy($by, $arr[1] ?? 'asc');
            }
        }
    }

    public function slice(string $slice): void
    {
        if (count($arr = explode(',', $slice)) >= 2) {
            $offset = (int)($arr[0] ?? 0);
            $limit = (int)($arr[1] ?? 0);
            $this->builder->offset(max($offset, 0))->limit(max($limit, 0));
        }
    }

    public function with(string $with): void
    {
        $with = explode(',', $with);
        $with = array_filter($with, function ($v) {
            return in_array($v, $this->getReleasable());
        });

        if (count($with) > 0) {
            $this->builder->with($with);
        }
    }

    public function where(string|array $where): void
    {
        if (is_array($where)) {
            foreach ($where as $v) {
                $this->applyWhere($v);
            }
        }
        if (is_string($where)) {
            $this->applyWhere($where);
        }
    }

    protected function applyWhere(string $where): void
    {
        $this->builder->where(function ($query) use ($where) {
            $parseWhere = $this->parseWhere($where);
            $first = true;
            foreach ($parseWhere as $or) {
                $relation = null;
                $relation_field = null;
                if (stripos($or[0], '.')) {
                    $explode = explode('.', $or[0]);
                    $relation_field = array_pop($explode);
                    $relation = implode('.', $explode);
                }
                if ($first) {
                    if (!is_null($relation)) {
                        $func = $this->whereQuery($relation_field, $or[1] ?? null, $or[2] ?? null);
                        $query->whereHas($relation, $func);
                        $first = false;
                    } else {
                        $func = $this->whereQuery($or[0], $or[1] ?? null, $or[2] ?? null);
                        $func($query);
                        $first = false;
                    }
                } else {
                    if (!is_null($relation)) {
                        $func = $this->whereQuery($relation_field, $or[1] ?? null, $or[2] ?? null);
                        $query->orWhereHas($relation, $func);
                    } else {
                        $func = $this->whereQuery($or[0], $or[1] ?? null, $or[2] ?? null, 'or');
                        $func($query);
                    }
                }
            }
        });
    }

    /**
     * @param string $data
     * @return array
     */
    protected function parseWhere(string $data): array
    {
        $result = [];
        foreach (explode(';', $data) as $v) {
            $item = explode(':', $v, 3);
            if (count($item) < 2 || !in_array($item[0], $this->getFieldsQueryable())) {
                continue;
            }
            if (count($item) === 2 && !in_array(strtolower($item[1]), ['null', 'notnull'])) {
                $result[] = [$item[0], 'eq', $item[1]];
            } else {
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * @param string $field
     * @param string $separator
     * @param string|null $value
     * @param string $and
     * @return Closure|null
     */
    protected function whereQuery(string $field, string $separator, ?string $value, string $and = 'and'): ?Closure
    {
        $operator = OperatorEnum::tryFrom(strtolower($separator));
        if (is_null($operator)) {
            return fn($query) => $query;
        }
        return match ($operator) {
            OperatorEnum::In => fn($query) => $query->whereIn($field, explode(',', $value), $and),
            OperatorEnum::NotIn => fn($query) => $query->whereNotIn($field, explode(',', $value), $and),
            OperatorEnum::Null => fn($query) => $query->whereNull($field, $and),
            OperatorEnum::NotNull => fn($query) => $query->whereNotNull($field, $and),
            OperatorEnum::Between  => fn($query) => $query->whereBetween($field, explode(',', $value, 2), $and),
            OperatorEnum::NotBetween => fn($query) => $query->whereNotBetween($field, explode(',', $value, 2), $and),
            OperatorEnum::Like => fn($query) => $query->where($field, 'like', "%{$value}%", $and),
            OperatorEnum::GreaterThan => fn($query) => $query->where($field, '>', $value, $and),
            OperatorEnum::GreaterThanOrEqual => fn($query) => $query->where($field, '>=', $value, $and),
            OperatorEnum::LessThan => fn($query) => $query->where($field, '<', $value, $and),
            OperatorEnum::LessThanOrEqual => fn($query) => $query->where($field, '<=', $value, $and),
            OperatorEnum::Equal => fn($query) => $query->where($field, '=', $value, $and),
            OperatorEnum::NotEqual => fn($query) => $query->where($field, '<>', $value, $and),
        };
    }
}