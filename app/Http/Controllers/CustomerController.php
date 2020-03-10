<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Customer;

class CustomerController extends Controller
{
    /**
     * Instantiate a new CustomerController instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

    /**
     * Create a new customer.
     *
     * @param Request $request
     * @return Response
     */
    public function createCustomer(Request $request)
    {
        try {
            self::_throwUnauthorizedException();

            try {

                self::_createCustomerValidation($request);

                try {
                    $customer = self::_assembleCreateCustomer($request);
                    $customer->save();

                    return response()->json(['customer' => $customer, 'message' => 'CREATED'], 201);
                } catch (\Exception $e) {
                    try {
                        if ($e->getCode() === '23000') {
                            // customer already exists, go ahead and save message
                            $deletedCustomer = Customer::withTrashed()
                                ->where('email', $customer->email)
                                ->where('phone', $customer->phone)
                                ->first();

                            self::_restoreIfTrashed($deletedCustomer);

                            $customer->save();
                        }

                        throw new \Exception($e->getMessage(), $e->getCode());
                    } catch (\Exception $e) {
                        return response()->json(['message' => 'Customer Creation Failed!'], 500);
                    }
                }
            } catch (\Exception $e) {
                return response()->json(['result' => ['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the customer data']], 400);
            }
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Contact the super admin to take this action!'], 401);
        }
    }

    /**
     * Get the authenticated Customer.
     *
     * @return Response
     */
    public function profile()
    {
        return response()->json(['customer' => Auth::customer()], 200);
    }

    /**
     * Get suspended Customers count.
     *
     * @return Response
     */
    public function suspendedCustomersCount()
    {
        //i commented the super user authorization for unsuspend cos all users should be able approve a user registration, which translates to unsuspend 
        // try {
        //     self::_throwUnauthorizedException();

        $customerModel = Customer::select(\DB::raw('COUNT(id) AS count'))
            ->where('suspended', true)
            ->first();

        return response()->json(['count' =>  $customerModel ? $customerModel->count : 0], 200);
        // } catch (AuthorizationException $e) {
        //     return response()->json(['message' => 'Contact the super admin to take this action!'], 401);
        // }
    }

    /**
     * Get all Customer.
     *
     * @param  Request $request
     * @return Response
     */
    public function allCustomers(Request $request)
    {
        try {
            self::_throwUnauthorizedException();

            $perPage = $request->input('per_page') ?? 8;
            $suspended = $request->input('suspended');

            $customerModel = new Customer;

            if (!is_null($suspended)) {
                $customerModel = $customerModel->where('suspended', !!$suspended);
            }

            return response()->json(['customers' =>  $customerModel->paginate($perPage)], 200);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Contact the super admin to take this action!'], 401);
        }
    }

    /**
     * Get one customer.
     *
     * @return Response
     */
    public function singleCustomer($id)
    {
        try {
            self::_throwUnauthorizedException();
            try {
                $customer = Customer::findOrFail($id);

                return response()->json(['customer' => $customer], 200);
            } catch (\Exception $e) {

                return response()->json(['customer' => 'customer not found!'], 404);
            }
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Contact the super admin to take this action!'], 401);
        }
    }

    /**
     * Create a new customer.
     *
     * @param Request $request
     * @return Response
     */
    public function updateCustomer(Request $request, $customerId)
    {
        try {
            self::_throwUnauthorizedException();

            try {

                self::_updateCustomerValidation($request);

                try {
                    $customer = Customer::findOrFail($customerId);

                    try {
                        $customer = self::_assembleUpdateCustomer($request, $customer);
                        $customer->save();

                        return response()->json(['customer' => $customer, 'message' => 'UPDATED'], 200);
                    } catch (\Exception $e) {
                        if ($e->getCode() === '23000') {
                            return response()->json(['message' => 'Some information were duplicates!'], 409);
                        }

                        return response()->json(['message' => 'Customer Update Failed!'], 500);
                    }
                } catch (\Exception $e) {

                    return response()->json(['message' => 'customer not found!'], 404);
                }
            } catch (\Exception $e) {
                return response()->json(['result' => ['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the customer data']], 400);
            }
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Contact the super admin to take this action!'], 401);
        }
    }

    /**
     * Delete one customer.
     *
     * @return Response
     */
    public function deleteCustomer($customerId)
    {
        try {
            self::_throwUnauthorizedException();

            try {
                $customer = Customer::findOrFail($customerId);

                try {
                    $customer->delete();

                    return response()->json(['message' => 'customer deleted!'], 200);
                } catch (\Exception $e) {

                    return response()->json(['message' => 'customer deletion failed!'], 500);
                }
            } catch (\Exception $e) {

                return response()->json(['message' => 'customer not found!'], 404);
            }
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Contact the super admin to take this action!'], 401);
        }
    }

    /**
     * Suspend one customer.
     *
     * @return Response
     */
    public function suspendCustomer($customerId)
    {
        try {
            self::_throwUnauthorizedException();

            try {
                $customer = Customer::findOrFail($customerId);

                try {
                    $customer->suspended = true;
                    $customer->save();

                    return response()->json(['message' => 'customer suspended!'], 200);
                } catch (\Exception $e) {

                    return response()->json(['message' => 'customer suspension failed!'], 500);
                }
            } catch (\Exception $e) {

                return response()->json(['message' => 'customer not found!'], 404);
            }
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Contact the super admin to take this action!'], 401);
        }
    }

    /**
     * Suspend one customer.
     *
     * @return Response
     */
    public function unsuspendCustomer($customerId)
    {
        //i commented the super user authorization for unsuspend cos all users should be able approve a user registration, which translates to unsuspend 
        // try {
        //     self::_throwUnauthorizedException();

        try {
            $customer = Customer::findOrFail($customerId);

            try {
                $customer->suspended = null;
                $customer->save();

                return response()->json(['message' => 'customer unsuspended!'], 200);
            } catch (\Exception $e) {

                return response()->json(['message' => 'customer unsuspension failed!'], 500);
            }
        } catch (\Exception $e) {

            return response()->json(['message' => 'customer not found!'], 404);
        }
        // } catch (AuthorizationException $e) {
        //     return response()->json(['message' => 'Contact the super admin to take this action!'], 401);
        // }
    }

    /**
     * Change password one customer.
     *
     * @return Response
     */
    public function changeCustomerPassword(Request $request, $customerId)
    {
        try {
            self::_throwUnauthorizedException();

            try {
                $customer = Customer::findOrFail($customerId);

                try {
                    $plainPassword = $request->input('password');
                    $customer->password = app('hash')->make($plainPassword);
                    $customer->save();

                    return response()->json(['message' => 'Customer password changed!'], 200);
                } catch (\Exception $e) {

                    return response()->json(['message' => 'Customer password change failed!'], 500);
                }
            } catch (\Exception $e) {

                return response()->json(['message' => 'Customer not found!'], 404);
            }
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Contact the super admin to take this action!'], 401);
        }
    }

    /**
     * Customer subject
     * 
     * The customer $customer 
     * 
     * @param Customer $customer
     * 
     * @return void
     */

    private function _restoreIfTrashed(Customer $customer)
    {
        if ($customer->trashed()) {
            $customer->restore();
        }
    }

    /**
     * Assemble one customer.
     * 
     * @param Request $request
     * 
     * @param Customer $customer
     * 
     * @return Customer $customer
     */
    private function _assembleCreateCustomer(Request $request, Customer $customer = null)
    {
        $customer || ($customer = new Customer);
        $photo = $request->input('photo');
        $photo && ($customer->photo = $photo);
        $customer->first_name = $request->input('first_name');
        $customer->last_name = $request->input('last_name');
        $customer->email = $request->input('email');
        $customer->phone = $request->input('phone');
        $country = $request->input('country');
        $country && ($customer->country = $country);
        $plainPassword = $request->input('password');
        $customer->password = app('hash')->make($plainPassword);
        $customer->ip_address = self::_getIpAddress();
        $user_agent = $request->input('user_agent');
        $user_agent && ($customer->user_agent = $user_agent);
        $referrer_page = $request->input('referrer_page');
        $referrer_page && ($customer->referrer_page = $referrer_page);
        $lang = $request->input('lang');
        $lang && ($customer->lang = $lang);
        $os = $request->input('os');
        $os && ($customer->os = $os);
        $screen_width = $request->input('screen_width');
        $screen_width && ($customer->screen_width = $screen_width);
        $screen_height = $request->input('screen_height');
        $screen_height && ($customer->screen_height = $screen_height);
        $screen_availWidth = $request->input('screen_availWidth');
        $screen_availWidth && ($customer->screen_availWidth = $screen_availWidth);
        $screen_availHeight = $request->input('screen_availHeight');
        $screen_availHeight && ($customer->screen_availHeight = $screen_availHeight);
        $color_depth = $request->input('color_depth');
        $color_depth && ($customer->color_depth = $color_depth);
        $pixel_depth = $request->input('pixel_depth');
        $pixel_depth && ($customer->pixel_depth = $pixel_depth);

        return $customer;
    }

    /**
     * Assemble one customer.
     * 
     * @param Request $request
     * 
     * @param Customer $customer
     * 
     * @return Customer $customer
     */
    private function _assembleUpdateCustomer(Request $request, Customer $customer = null)
    {
        $customer || ($customer = new Customer);
        $photo = $request->input('photo');
        $photo && ($customer->photo = $photo);
        $customer->first_name = $request->input('first_name');
        $customer->last_name = $request->input('last_name');
        $customer->email = $request->input('email');
        $customer->phone = $request->input('phone');
        $country = $request->input('country');
        $country && ($customer->country = $country);

        return $customer;
    }

    /**
     * Get one customer.
     * 
     * @param Request $request
     * 
     * @return void
     */
    private function _createCustomerValidation(Request $request)
    {
        //validate incoming request 
        $validator = $this->validate(
            $request,
            [
                'photo' => 'string|max:100',
                'first_name' => 'required|string|max:50',
                'last_name' => 'required|string|max:60',
                'email' => 'required|email|unique:customers',
                'phone' => 'required|phone:country|unique:customers',
                'country' => 'required_with:phone|string:size:2',
                'password' => 'required|min:8|confirmed',
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
    }

    /**
     * Get one customer.
     * 
     * @param Request $request
     * 
     * @return void
     */
    private function _updateCustomerValidation(Request $request)
    {
        //validate incoming request 
        $validator = $this->validate(
            $request,
            [
                'photo' => 'string|max:100',
                'first_name' => 'required|string|max:50',
                'last_name' => 'required|string|max:60',
                'email' => 'required|email',
                'phone' => 'required|phone:country',
                'country' => 'required_with:phone|string:size:2'
            ]
        );
    }

    private function _throwUnauthorizedException()
    {
        $customer = Auth::guard('users')->user();

        if ($customer->perms !== 0) {
            throw new AuthorizationException();
        }
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
