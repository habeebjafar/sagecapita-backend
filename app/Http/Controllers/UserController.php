<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\User;

class UserController extends Controller
{
    /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Create a new user.
     *
     * @param Request $request
     * @return Response
     */
    public function createUser(Request $request)
    {
        try {

            self::_createUserValidation($request);

            try {
                $user = self::_assembleCreateUser($request);
                $user->save();

                return response()->json(['user' => $user, 'message' => 'CREATED'], 201);
            } catch (\Exception $e) {
                return response()->json(['message' => 'User Creation Failed!'], 409);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => ['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the user data']], 400);
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return Response
     */
    public function profile()
    {
        return response()->json(['user' => Auth::user()], 200);
    }

    /**
     * Get all User.
     *
     * @param  Request $request
     * @return Response
     */
    public function allUsers(Request $request)
    {
        $perPage = $request->input('per_page') ?? 8;

        return response()->json(['users' =>  User::paginate($perPage)], 200);
    }

    /**
     * Get one user.
     *
     * @return Response
     */
    public function singleUser($id)
    {
        try {
            $user = User::findOrFail($id);

            return response()->json(['user' => $user], 200);
        } catch (\Exception $e) {

            return response()->json(['user' => 'user not found!'], 404);
        }
    }

    /**
     * Create a new user.
     *
     * @param Request $request
     * @return Response
     */
    public function updateUser(Request $request, $userId)
    {
        try {

            self::_updateUserValidation($request);

            try {
                $user = User::findOrFail($userId);

                try {
                    $user = self::_assembleUpdateUser($request, $user);
                    $user->save();

                    return response()->json(['user' => $user, 'message' => 'UPDATED'], 200);
                } catch (\Exception $e) {
                    return response()->json(['message' => 'User Update Failed!'], 409);
                }
            } catch (\Exception $e) {

                return response()->json(['message' => 'user not found!'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['result' => ['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the user data']], 400);
        }
    }

    /**
     * Delete one user.
     *
     * @return Response
     */
    public function deleteUser($userId)
    {
        try {
            $user = User::findOrFail($userId);

            try {
                $user->delete();

                return response()->json(['message' => 'user deleted!'], 200);
            } catch (\Exception $e) {

                return response()->json(['message' => 'user deletion failed!'], 500);
            }
        } catch (\Exception $e) {

            return response()->json(['message' => 'user not found!'], 404);
        }
    }

    /**
     * Suspend one user.
     *
     * @return Response
     */
    public function suspendUser($userId)
    {
        try {
            $user = User::findOrFail($userId);

            try {
                $user->suspended = true;
                $user->save();

                return response()->json(['message' => 'user suspended!'], 200);
            } catch (\Exception $e) {

                return response()->json(['message' => 'user suspension failed!'], 500);
            }
        } catch (\Exception $e) {

            return response()->json(['message' => 'user not found!'], 404);
        }
    }

    /**
     * Suspend one user.
     *
     * @return Response
     */
    public function unsuspendUser($userId)
    {
        try {
            $user = User::findOrFail($userId);

            try {
                $user->suspended = false;
                $user->save();

                return response()->json(['message' => 'user unsuspended!'], 200);
            } catch (\Exception $e) {

                return response()->json(['message' => 'user unsuspension failed!'], 500);
            }
        } catch (\Exception $e) {

            return response()->json(['message' => 'user not found!'], 404);
        }
    }

    /**
     * Change password one user.
     *
     * @return Response
     */
    public function changeUserPassword(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);

            try {
                $plainPassword = $request->input('password');
                $user->password = app('hash')->make($plainPassword);
                $user->save();

                return response()->json(['message' => 'User password changed!'], 200);
            } catch (\Exception $e) {

                return response()->json(['message' => 'User password change failed!'], 500);
            }
        } catch (\Exception $e) {

            return response()->json(['message' => 'User not found!'], 404);
        }
    }

    /**
     * Assemble one user.
     * 
     * @param Request $request
     * 
     * @param User $user
     * 
     * @return User $user
     */
    private function _assembleCreateUser(Request $request, User $user = null)
    {
        $user || ($user = new User);
        $user->first_name = $request->input('first_name');
        $user->last_name = $request->input('last_name');
        $user->email = $request->input('email');
        $user->phone = $request->input('phone');
        $country = $request->input('country');
        $country && ($user->country = $country);
        $plainPassword = $request->input('password');
        $user->password = app('hash')->make($plainPassword);
        $user->ip_address = self::_getIpAddress();
        $user_agent = $request->input('user_agent');
        $user_agent && ($user->user_agent = $user_agent);
        $referrer_page = $request->input('referrer_page');
        $referrer_page && ($user->referrer_page = $referrer_page);
        $lang = $request->input('lang');
        $lang && ($user->lang = $lang);
        $os = $request->input('os');
        $os && ($user->os = $os);
        $screen_width = $request->input('screen_width');
        $screen_width && ($user->screen_width = $screen_width);
        $screen_height = $request->input('screen_height');
        $screen_height && ($user->screen_height = $screen_height);
        $screen_availWidth = $request->input('screen_availWidth');
        $screen_availWidth && ($user->screen_availWidth = $screen_availWidth);
        $screen_availHeight = $request->input('screen_availHeight');
        $screen_availHeight && ($user->screen_availHeight = $screen_availHeight);
        $color_depth = $request->input('color_depth');
        $color_depth && ($user->color_depth = $color_depth);
        $pixel_depth = $request->input('pixel_depth');
        $pixel_depth && ($user->pixel_depth = $pixel_depth);

        return $user;
    }

    /**
     * Assemble one user.
     * 
     * @param Request $request
     * 
     * @param User $user
     * 
     * @return User $user
     */
    private function _assembleUpdateUser(Request $request, User $user = null)
    {
        $user || ($user = new User);
        $user->first_name = $request->input('first_name');
        $user->last_name = $request->input('last_name');
        $user->email = $request->input('email');
        $user->phone = $request->input('phone');
        $country = $request->input('country');
        $country && ($user->country = $country);

        return $user;
    }

    /**
     * Get one lead.
     * 
     * @param Request $request
     * 
     * @return void
     */
    private function _createUserValidation(Request $request)
    {
        //validate incoming request 
        $validator = $this->validate(
            $request,
            [
                'first_name' => 'required|string|max:50',
                'last_name' => 'required|string|max:60',
                'email' => 'required|email|unique:users',
                'phone' => 'required|phone:country|unique:users',
                'country' => 'required_with:phone|string:size:2',
                'password' => 'required|confirmed',
                'ip_address' => 'ip',
                'user_agent' => 'string',
                'referrer_page' => 'string',
                'lang' => 'string:size:2',
                'os' => 'string',
                'screen_width' => 'integer',
                'screen_height' => 'integer',
                'screen_availWidth' => 'integer',
                'screen_availHeight' => 'integer',
                'color_depth' => 'integer',
                'pixel_depth' => 'integer'
            ]
        );
    }

    /**
     * Get one lead.
     * 
     * @param Request $request
     * 
     * @return void
     */
    private function _updateUserValidation(Request $request)
    {
        //validate incoming request 
        $validator = $this->validate(
            $request,
            [
                'first_name' => 'required|string|max:50',
                'last_name' => 'required|string|max:60',
                'email' => 'required|email',
                'phone' => 'required|phone:country',
                'country' => 'required_with:phone|string:size:2'
            ]
        );
    }

    /**
     * Get IP Address.
     * 
     * @return string
     */
    private function _getIpAddress()
    {
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip); // just to be safe

                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
    }
}
