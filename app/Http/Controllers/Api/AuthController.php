<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\ApiToken;
use App\Models\Customer;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $this->findPortalUserByPhone((string) $data['phone']);

        if (! $user instanceof User || ! Hash::check((string) $data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'phone' => 'The provided credentials are incorrect.',
            ]);
        }

        if (! $user->hasAnyRole([User::ROLE_CUSTOMER, User::ROLE_TECHNICIAN])) {
            throw ValidationException::withMessages([
                'phone' => 'This account cannot access the portal API.',
            ]);
        }

        if ($user->isCustomer() && $user->customer()->doesntExist()) {
            throw ValidationException::withMessages([
                'phone' => 'This customer account is not linked to a customer profile.',
            ]);
        }

        if ($user->isTechnician() && $user->technician()->doesntExist()) {
            throw ValidationException::withMessages([
                'phone' => 'This technician account is not linked to a technician profile.',
            ]);
        }

        $token = $user->issueApiToken((string) ($data['device_name'] ?? 'portal'));

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token,
            'user' => new UserResource($user->load(['customer', 'technician'])),
        ]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load(['customer', 'technician']));
    }

    public function logout(Request $request): Response
    {
        $plainTextToken = $request->bearerToken();

        if (filled($plainTextToken)) {
            ApiToken::query()
                ->where('user_id', $request->user()->getKey())
                ->where('token_hash', hash('sha256', $plainTextToken))
                ->delete();
        }

        Auth::guard('api')->forgetUser();

        return response()->noContent();
    }

    protected function findPortalUserByPhone(string $phone): ?User
    {
        $normalizedPhone = preg_replace('/\s+/', '', $phone) ?? $phone;

        $customer = Customer::query()
            ->with('user')
            ->where('phone', $normalizedPhone)
            ->first();

        if ($customer instanceof Customer && $customer->user instanceof User) {
            return $customer->user;
        }

        $technician = Technician::query()
            ->with('user')
            ->where('phone', $normalizedPhone)
            ->first();

        if ($technician instanceof Technician && $technician->user instanceof User) {
            return $technician->user;
        }

        return null;
    }
}
