<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;

class AuthController extends Controller
{
    public function signup(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $newUser = new User();
        $newUser['email'] = $request['email'];
        $newUser['name'] = $request['name'] ?? $request['email'];
        $newUser['password'] = Hash::make($request['password']);
        $newUser->save();

        return response()->json($newUser);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request['email'])->firstOrFail();
        if (!Hash::check($request['password'], $user['password'])) {
            abort(401);
        }

        // /* @var NewAccessToken */
        $token = $user->createToken('token');

        return response()->json(['token' => $token->plainTextToken]);
    }
}
