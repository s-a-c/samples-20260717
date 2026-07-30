<?php

declare(strict_types=1);

use App\Http\Responses\LoginResponse;
use App\Http\Responses\RegisterResponse;
use App\Http\Responses\VerifyEmailResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;

covers(
    LoginResponse::class,
    RegisterResponse::class,
    VerifyEmailResponse::class,
);

function jsonRequest(?User $user = null): Request
{
    $request = Request::create('/', 'GET', server: ['HTTP_ACCEPT' => 'application/json']);
    $request->setUserResolver(fn () => $user);

    return $request;
}

test('login response returns json when the client wants json', function () {
    $user = User::factory()->create();

    $response = app(LoginResponseContract::class)->toResponse(jsonRequest($user));

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(200);
});

test('register response returns json when the client wants json', function () {
    $user = User::factory()->create();

    $response = app(RegisterResponseContract::class)->toResponse(jsonRequest($user));

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(201);
});

test('verify email response returns json when the client wants json', function () {
    $user = User::factory()->create();

    $response = app(VerifyEmailResponseContract::class)->toResponse(jsonRequest($user));

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(204);
});
