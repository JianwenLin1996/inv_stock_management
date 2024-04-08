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
    
    
    /**
        * @OA\Post(
        * path="/api/signup",
        * summary="Signup a new account",
        * description="Signup a new account by providing name, email and password",
        * operationId="authSignup",
        * tags={"auth"},
        * @OA\RequestBody(
        *    required=true,
        *    description="Pass user information",
        *    @OA\JsonContent(
        *       type="object",
        *       required={"name","description"},
        *       @OA\Property(property="name", type="string", example="User ABCD"),
        *       @OA\Property(property="email", type="string", format="email", example="abcd@gmail.com"),
        *       @OA\Property(property="password", type="string", format="password", example="abcd1234")
        *    ),
        * ),
        * @OA\Response(
        *    response=200,
        *    description="Success",
        *     @OA\JsonContent(
        *       @OA\Property(property="message", type="string", example="Account updated successfully."),
        *       @OA\Property(property="status", type="boolean", example="true"),
        *       @OA\Property(property="data", type="object", example={}),
        *     )
        * )
        *)
    */
    public function signup(AuthRequest $request)
    {
        try {
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->save();
            return ResponseHelper::success('User created successfully.', data:[
                'user'=> $user,
            ]);
        }  catch (\Exception $e) {
            return ResponseHelper::error('Error in creating new user.', statusCode:500);
        }

    }

    
    /**
        * @OA\Post(
        * path="/api/login",
        * summary="Login to existing account",
        * description="Login to existing account by providing matching email and password",
        * operationId="authLogin",
        * tags={"auth"},
        * @OA\RequestBody(
        *    required=true,
        *    description="Pass user information",
        *    @OA\JsonContent(
        *       type="object",
        *       required={"name","description"},
        *       @OA\Property(property="email",  type="string", format="email", example="abcd@gmail.com"),
        *       @OA\Property(property="password",  type="string", format="password", example="abcd1234")
        *    ),
        * ),
        * @OA\Response(
        *    response=200,
        *    description="Success",
        *     @OA\JsonContent(
        *       @OA\Property(property="message", type="string", example="User login successfully."),
        *       @OA\Property(property="status", type="boolean", example="true"),
        *       @OA\Property(property="data", type="object", example={}),
        *     )
        * ),
        * @OA\Response(
        *    response=403,
        *    description="Unathenticated",
        *    @OA\JsonContent(
        *       @OA\Property(property="message", type="string", example="Unauthenticated.")
        *        )
        *     )
        * )
    */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return ResponseHelper::unauthenticated();
        }

        return $this->respondWithToken('User login successfully.', $token);
    }
    
    
    /**
        * @OA\GET(
        * path="/api/me",
        * summary="Get me",
        * description="Get information of current user.",
        * operationId="authMe",
        * tags={"auth"},
        * security={ {"bearerAuth": {} } },
        * @OA\Response(
        *    response=200,
        *    description="Success",
        *     @OA\JsonContent(
        *       @OA\Property(property="message", type="string", example="User data returned successfully."),
        *       @OA\Property(property="status", type="boolean", example="true"),
        *       @OA\Property(property="data", type="object", example={}),
        *     )
        * ),
        * @OA\Response(
        *    response=401,
        *    description="Unauthorized",
        *    @OA\JsonContent(
        *       @OA\Property(property="message", type="string", example="Unauthorized.")
        *        )
        *     )
        * )
    */
    public function me()
    {
        return ResponseHelper::success('User data returned successfully.', data: auth()->user());   
    }
    
    
    /**
        * @OA\POST(
        * path="/api/logout",
        * summary="Logout existing account",
        * description="Logout existing account.",
        * operationId="authLogout",
        * tags={"auth"},
        * security={ {"bearerAuth": {} } },
        * @OA\Response(
        *    response=200,
        *    description="Success",
        *     @OA\JsonContent(
        *       @OA\Property(property="message", type="string", example="User logged out successfully."),
        *       @OA\Property(property="status", type="boolean", example="true"),
        *       @OA\Property(property="data", type="object", example={}),
        *     )
        * ),
        * @OA\Response(
        *    response=401,
        *    description="Unauthorized",
        *    @OA\JsonContent(
        *       @OA\Property(property="message", type="string", example="Unauthorized.")
        *        )
        *     )
        * )
    */
    public function logout()
    {
        try {
            auth()->logout();
            return ResponseHelper::success('User logged out successfully.');            
        }  catch (\Exception $e) {
            return ResponseHelper::error('Error in logging out user.', statusCode:500);
        }

    }
    
    /**
        * @OA\POST(
        * path="/api/refresh",
        * summary="Refresh token",
        * description="Retrieve new token be it old token is expired or not. Old token will be revoked after refreshed.",
        * operationId="authRefresh",
        * tags={"auth"},
        * security={ {"bearerAuth": {} } },
        * @OA\Response(
        *    response=200,
        *    description="Success",
        *     @OA\JsonContent(
        *       @OA\Property(property="message", type="string", example="Token refreshed successfully."),
        *       @OA\Property(property="status", type="boolean", example="true"),
        *       @OA\Property(property="data", type="object", example={}),
        *     )
        * ),
        * @OA\Response(
        *    response=401,
        *    description="Unauthorized",
        *    @OA\JsonContent(
        *       @OA\Property(property="message", type="string", example="Unauthorized.")
        *        )
        *     )
        * )
    */
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
