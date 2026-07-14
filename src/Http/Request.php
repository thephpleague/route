<?php

declare(strict_types=1);

namespace League\Route\Http;

final class Request
{
    public const string METHOD_GET = 'GET';
    public const string METHOD_POST = 'POST';
    public const string METHOD_PUT = 'PUT';
    public const string METHOD_PATCH = 'PATCH';
    public const string METHOD_DELETE = 'DELETE';
    public const string METHOD_HEAD = 'HEAD';
    public const string METHOD_OPTIONS = 'OPTIONS';
    public const string METHOD_QUERY = 'QUERY';
}
