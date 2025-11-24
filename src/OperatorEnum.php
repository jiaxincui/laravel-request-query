<?php

namespace Jiaxincui\RequestFilter;

enum OperatorEnum: string
{
    case In = 'in';
    case NotIn = 'notin';
    case Between = 'between';
    case NotBetween = 'notbetween';
    case Null = 'null';
    case NotNull = 'notnull';
    case Like = 'like';
    case LessThan = 'lt';
    case LessThanOrEqual = 'lte';
    case Equal = 'eq';
    case GreaterThan = 'gt';
    case GreaterThanOrEqual = 'gte';
    case NotEqual = 'neq';
}
