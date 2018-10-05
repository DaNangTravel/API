<?php

namespace App\Http\Controllers\Admin\Auth;

use Hash;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Requests\Admin\ProfileRequest;
use App\Http\Requests\Admin\ChangePasswordRequest;
use App\Repositories\Eloquents\UserRepositoryEloquent;

class AuthController extends BaseController
{
    protected $user;

    public function __construct(UserRepositoryEloquent $user)
    {
        $this->user = $user;
    }
    // public function login(LoginRequest $request)
    // {
    //     $credentials = $request->all();

    //     if (!Auth::attempt($credentials))
    //     {
    //         return responses('Unauthorized', Response::HTTP_UNAUTHORIZED);
    //     }

    //     $user   = $request->user();
    //     $accessToken = $user->createToken('Laravel Password Grant Client');
    //     $token  = $accessToken->token;

    //     $token->save();

    //     $expires_at = Carbon::parse(
    //         $token->expires_at
    //     )->toDateTimeString();

    //     $data = [
    //         'access_token' => 'Bearer ' . $accessToken->accessToken,
    //         'token_type' => 'Bearer',
    //         'expires_at' => $expires_at,
    //     ];

    //     return responses('login successfully', Response::HTTP_OK, $data);
    // }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return $this->responses('Successfully logged out', Response::HTTP_OK);
    }

    public function user(Request $request)
    {
        $profile = $this->user->with(['roles'])
            ->withCount('posts')
            ->find($request->user()->id);

        return $this->responses(trans('notication.load.success'), Response::HTTP_OK, compact('profile'));
    }

    public function update(ProfileRequest $request)
    {
        $this->user->skipPresenter();

        $user = $this->user->find($request->user()->id);
        if(!empty($request->avatar)) {
            $user->avatar     = $request->avatar;
        }

        $user->first_name = $request->first_name;
        $user->last_name  = $request->last_name;
        $user->phone      = $request->phone;
        $user->gender     = $request->gender;
        $user->birthday   = $request->birthday;

        $user->save();

        return $this->responses(trans('notication.edit.success'), Response::HTTP_OK);
    }

    public function changePassword(ChangePasswordRequest $request)
    {

        $user = $request->user();

        $old_password = $user->password;

        if(!Hash::check($request->old_password, $old_password)) {
            return $this->responseErrors('password', 'Mật khẩu hiện tại không khớp.');
        }

        $user->password = bcrypt($request->new_password);
        $user->token()->revoke();
        $token = $user->createToken('newToken');

        $accessToken = $token->accessToken;
        $expires_at  = Carbon::parse($token->token->expires_at)->toDateTimeString();

        $user->save();

        $data = [
            "token_type"   => "Bearer",
            'access_token' => $accessToken,
            'expires_at'   => $expires_at
        ];

        return $this->responses(trans('notication.edit.change'), Response::HTTP_OK, $data);
    }
}
