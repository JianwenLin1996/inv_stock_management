<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequest;
use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'signup']]);
    }
    
    public function signup(AuthRequest $request)
    {
        try {
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->save();
            return ResponseHelper::success('User created successfully.');
        }  catch (\Exception $e) {
            return ResponseHelper::error('Error in creating new user.', statusCode:500);
        }

    }

    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return ResponseHelper::unauthenticated();
        }

        return $this->respondWithToken('User login successfully.', $token);
    }
    
    public function me()
    {
        return ResponseHelper::success('User data returned successfully.', data: auth()->user());   
    }
    
    public function logout()
    {
        try {
            auth()->logout();
            return ResponseHelper::success('User logged out successfully.');            
        }  catch (\Exception $e) {
            return ResponseHelper::error('Error in logging out user.', statusCode:500);
        }

    }
    
    public function refresh()
    {
        return $this->respondWithToken( 'Token refreshed successfully.', auth()->refresh());
    }
    
    
    protected function respondWithToken($message, $token)
    {
        return ResponseHelper::success($message, data: [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

}
