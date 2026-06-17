<?php

use App\Http\Middleware\AllowAnyOrigin;
use Illuminate\Http\Request;

uses(Tests\TestCase::class);

test('allow any origin middleware adds cors headers', function () {
    $middleware = new AllowAnyOrigin;
    $request = Request::create('/api/public/form-order', 'POST');

    $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

    expect($response->headers->get('Access-Control-Allow-Origin'))->toBe('*')
        ->and($response->headers->get('Access-Control-Allow-Methods'))->toContain('POST')
        ->and($response->headers->get('Access-Control-Allow-Headers'))->toContain('Content-Type');
});

test('allow any origin middleware handles preflight requests', function () {
    $middleware = new AllowAnyOrigin;
    $request = Request::create('/api/public/form-order', 'OPTIONS');

    $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

    expect($response->getStatusCode())->toBe(204)
        ->and($response->headers->get('Access-Control-Allow-Origin'))->toBe('*');
});
