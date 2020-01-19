<?php

namespace App\Http\Controllers;

//import auth facades
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;


class Controller extends BaseController
{
    /**
     * Override validate method use dingo validation exception
     *
     * @param Request $request
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     */
    public function validate(
        Request $request, 
        array $rules, 
        array $messages = [], 
        array $customAttributes = [])
    {
        $validator = $this->getValidationFactory()->make(
            $request->all(), 
            $rules, $messages, 
            $customAttributes
        );

        if ($validator->fails()) {
            throw new \Exception($validator->errors());
        }
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60
        ], 200);
    }
}
