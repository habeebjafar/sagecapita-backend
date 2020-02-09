<?php

namespace App\Http\Controllers;

//import auth facades
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use App\Customer;

class AuthCustomerController extends Controller
{
    /**
     * Store a new customer.
     *
     * @param  Request  $request
     * @return Response
     */
    public function register(Request $request)
    {
        try {
            //validate incoming request 
            $this->validate(
                $request, [
                    'first_name' => 'required|string|max:50',
                    'last_name' => 'required|string|max:60',
                    'email' => 'required|email|unique:customers',
                    'phone' => 'required|phone:country|unique:customers',
                    'country' => 'required_with:phone|string:size:2',
                    'language' => 'required|string:size:2',
                    'city' => 'required|string:max:255',
                    'profession' => 'required|string:max:255',
                    'password' => 'required|confirmed',
                    'ip_address' => 'ip',
                    'user_agent' => 'string',
                    'referrer_page' => 'string',
                    'lang' => 'string:size:5',
                    'os' => 'string',
                    'screen_width' => 'integer',
                    'screen_height' => 'integer',
                    'screen_availWidth' => 'integer',
                    'screen_availHeight' => 'integer',
                    'color_depth' => 'integer',
                    'pixel_depth' => 'integer'
                ]
            );

            try {
                $customer = new Customer;
                $customer->first_name = $request->input('first_name');
                $customer->last_name = $request->input('last_name');
                $customer->email = $request->input('email');
                $customer->phone = $request->input('phone');
                $customer->country = $request->input('country');
                $customer->language = $request->input('language');
                $customer->city = $request->input('city');
                $customer->profession = $request->input('profession');
                $plainPassword = $request->input('password');
                $customer->password = app('hash')->make($plainPassword);
                $customer->ip_address = $request->input('ip_address');
                $customer->user_agent = $request->input('user_agent');
                $customer->referrer_page = $request->input('referrer_page');
                $customer->lang = $request->input('lang');
                $customer->os = $request->input('os');
                $customer->screen_width = $request->input('screen_width');
                $customer->screen_height = $request->input('screen_height');
                $customer->screen_availWidth = $request->input('screen_availWidth');
                $customer->screen_availHeight = $request->input('screen_availHeight');
                $customer->color_depth = $request->input('color_depth');
                $customer->pixel_depth = $request->input('pixel_depth');
                $customer->suspended = true;
    
                $customer->save();
    
                //return successful response
                return response()->json(['customer' => $customer, 'message' => 'CREATED'], 201);
            } catch (\Exception $e) {
                //return error message
                return response()->json(['message' => 'Customer Registration Failed!'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the customer data'], 400);
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
        try{
            //validate incoming request 
            $this->validate(
                $request, [
                'email' => 'required|string',
                'password' => 'required|string',
                ]
            );

            try{
                $credentials = array_merge(
                    $request->only(['email', 'password']),
                    ['suspended' => null]
                );

                $customersAuth = Auth::guard('customers');
        
                if (!$token = $customersAuth->attempt($credentials)) {
                    return response()->json(['message' => 'Unauthorized'], 401);
                }
        
                //send customer details
                $customer = $customersAuth->user();
                
                //update last_login_time, number of logins
                $customerUpdate = Customer::find($customer->id);
                $customerUpdate->last_login = gmdate("Y-m-d H:i:s");
                $customerUpdate->number_of_logins += 1;
                $customerUpdate->save();
                
                return $this->respondWithTokenAndUser($token, $customer);
            } catch (\Exception $e) { echo $e;
                //return error message
                return response()->json(['message' => 'Customer Login Failed!'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the customer data'], 400);
        }
    }
}
