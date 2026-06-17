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

test('public form order preflight allows any origin before laravel cors handles it', function () {
    $response = $this->withHeaders([
        'Origin' => 'https://external-example.test',
        'Access-Control-Request-Method' => 'POST',
        'Access-Control-Request-Headers' => 'content-type, authorization',
    ])->options('/api/public/form-order');

    $response->assertNoContent();
    $response->assertHeader('Access-Control-Allow-Origin', '*');
    $response->assertHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
    $response->assertHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
});
