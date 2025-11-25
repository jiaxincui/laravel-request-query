## About

Eloquent request filter for laravel

## 使用示例
请求时使用对应的参数返回过滤后的数据

`/api/users?where=age:gt:18`

`/api/users?where=name:like:foo;email:like:bar`

`/api/users?where=age:gt:18&slice=5,10&orderBy=id,desc`

`/api/users?where[]=age:gt:18&where[]=name:foo&slice=5,10&orderBy=id,desc`

## 使用步骤
1. 在 `Model` 里使用 `trait` 类

`use Jiaxincui\RequestFilter\FilterScope;`

2. 创建 `Filter` 类

`php artisan make:filter User`

将生成一个 filter 类 `App\Filters\UserFilter`

你还需要显式定义可用于过滤的字段，见后续章节。

3. 在查询时添加 `Filter` 类

`User::fitler(new UserFilter)->get()`

4. 查询请求

`/api/users?where=age:gt:18;name:foo`

## 默认可用的方法

### with
`with` 示例：`with=userinfo`

### orderBy
`orderBy` (大小写敏感)， 示例：`orderBy=id,desc`

### slice
`slice` 示例： `slice=5,10`

### trashed
`trashed` 示例 `trashed=only`、`trashed=with`

### where
`where` 和 `where[]`
示例：`where=age:gt:18`、`where[]=name:like:foo&where[]=age:gt:18`

**操作符**
参数值分3段，并且使用`:` (冒号) 分割，分别是字段、操作符、值

支持的操作符如下：

`eq` `lt` `gt` `lte` `gte` `neq` `in` `notin` `between` `notbetween` `null` `notnull`

**注意**

其中 `null` `notnull` 参数为2段

示例：`where=card_number:null`

## 自定义方法

除了使用默认方法，你还可以在生成的类里自定义方法

```php
// App\Filters\UserFilter

public function onlyVip($arg){
    $this->builder->where('vip', '=', $arg)
}
```
使用： `/api/users?onlyVip=1`

## 分组
对于 `where` 查询需要区分 `and` 和 `or` , 以下示例可以告诉你答案 

**示例1**

`/api/users?where=age:gt:18;name:foo` 

SQL `age > 18 or neme = 'foo'`

**示例2**

`/api/users?where[]=age:gt:18&where[]=name:foo`

SQL `age > 18 and neme = 'foo'`

**示例3**

`/api/users?where[]=age:lte:18;vip:eq:1&where[]=name:foo`

SQL `(age > 18 or vip = 1) and neme = 'foo'`

## 关联查询

基本

`/api/users?with=userinfo` 等同于 `User::with('userinfo')->get();`

带条件的关联查询

`/api/users?with=userinfo&where=userinfo.age:gt:18` 等同于 `User::whereHas('userinfo', fn ($query) => $query->where('age', '>', 18)->get();`

## 定义字段

安全起见，过滤器使用字段白名单机制，每个过滤器都需要显式定义可查询的列、可关联的表、可用于排序的列。
（如果没有定义，过滤器将不起任何作用）

你需要显式实现如下方法：

```php
// App\Filters\UserFilter

// 定义可用于条件查询的列名
protected function getFieldsQueryable(): array {
    return [
        'name',
        'email',
        'userinfo.age' // 关联查询
    ]
}

// 定义可用于关联查询的表
protected function getReleasable(): array {
    return [
        'userinfo',
        'addresses'
    ]
}

//定义可用于排序的列
protected function getSortable(): array {
    return [
        'id',
        'created_at'
    ]
}
```

## License

[MIT](https://github.com/jiaxincui/laravel-query-filter/blob/master/LICENSE.md) © [JiaxinCui](https://github.com/jiaxincui)
