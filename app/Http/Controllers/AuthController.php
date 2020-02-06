<?php

namespace App\Http\Controllers;

//import auth facades
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use App\User;

class AuthController extends Controller
{
    /**
     * Store a new user.
     *
     * @param  Request  $request
     * @return Response
     */
    public function register(Request $request)
    {
        //validate incoming request 
        $this->validate($request, [
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:60',
            'email' => 'required|email|unique:users',
            'phone' => 'required|phone:country|unique:users',
            'country' => 'required_with:phone',
            'password' => 'required|confirmed',
            'ip_address' => 'ip',
        ]);

        try {
            $user = new User;
            $user->first_name = $request->input('first_name');
            $user->last_name = $request->input('last_name');
            $user->email = $request->input('email');
            $user->phone = $request->input('phone');
            $plainPassword = $request->input('password');
            $user->password = app('hash')->make($plainPassword);
            $user->ip_address = $request->input('ip_address');
            $user->user_agent = $request->input('user_agent');
            $user->referrer_page = $request->input('referrer_page');
            $user->lang = $request->input('lang');
            $user->os = $request->input('os');
            $user->screen_width = $request->input('screen_width');
            $user->screen_height = $request->input('screen_height');
            $user->screen_availWidth = $request->input('screen_availWidth');
            $user->screen_availHeight = $request->input('screen_availHeight');
            $user->color_depth = $request->input('color_depth');
            $user->pixel_depth = $request->input('pixel_depth');
            $user->secs_to_submit = $request->input('secs_to_submit');

            $user->save();

            //return successful response
            return response()->json(['user' => $user, 'message' => 'CREATED'], 201);
        } catch (\Exception $e) {
            //return error message
            return response()->json(['message' => 'User Registration Failed!'], 409);
        }
    }

    /**
     * Get a JWT via given credentials.
     *
     * @param  Request  $request
     * @return Response
     */
    public function login(Request $request)
    {
        //validate incoming request 
        $this->validate(
            $request, [
            'email' => 'required|string',
            'password' => 'required|string',
            ]
        );

        $credentials = array_merge(
            $request->only(['email', 'password']),
            ['suspended' => null]
        );

        if (!$token = Auth::attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        //send user details
        $user = Auth::user();

        //update last_login_time, number of logins
        $userUpdate = User::find($user->id);
        $userUpdate->last_login = gmdate("Y-m-d H:i:s");
        $userUpdate->number_of_logins += 1;
        $userUpdate->save();

        return $this->respondWithTokenAndUser($token, $user);
    }
}
