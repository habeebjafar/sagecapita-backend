<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lead;
use App\Message;
use App\Property;

class LeadController extends Controller
{
    /**
     * Instantiate a new LeadController instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Store a new lead.
     *
     * @param Request $request
     * @return Response
     */
    public function createLead(Request $request)
    {
        try {
            //validate incoming request 
            self::_createLeadValidation($request);

            try {
                \DB::beginTransaction();

                $lead = self::_assembleLead($request);
                $lead->save();

                try {
                    self::_createMessage($request, $lead);

                    \DB::commit();

                    //return successful response
                    return response()->json(['lead' => $lead, 'message' => 'CREATED'], 201);
                } catch (\Exception $e) {
                    $message = json_decode($e->getMessage(), true);

                    \DB::rollBack();

                    return response()->json($message['result'], $message['status']);
                }
            } catch (\Exception $e) {
                try {

                    if ($e->getCode() === '23000') {
                        // lead already exists, go ahead and save message
                        $lead = Lead::withTrashed()
                            ->where('email', $lead->email)
                            ->where('phone', $lead->phone)
                            ->where('country', $lead->country)
                            ->first();

                        self::_restoreIfTrashed($lead);

                        try {
                            self::_createMessage($request, $lead);

                            \DB::commit();

                            //return successful response
                            return response()->json(['lead' => $lead, 'message' => 'UPDATED'], 200);
                        } catch (\Exception $e) {
                            $message = json_decode($e->getMessage(), true);

                            \DB::rollBack();

                            return response()->json($message['result'], $message['status']);
                        }
                    }

                    throw new \Exception($e->getMessage(), $e->getCode());
                } catch (\Exception $e) {
                    \DB::rollBack();

                    //return error message
                    return response()->json(['message' => 'Lead Creation Failed!'], 409);
                }
            }
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the lead data'], 400);
        }
    }

    /**
     * Get all Leads.
     *
     * @return Response
     */
    public function getLeads(Request $request)
    {
        // $this->middleware('auth');
        // TODO: use the query string to select the search criteria, result length, result page

        $perPage = $request->input('per_page') ?? 8;
        $nameContains = $request->input('name');

        $leads = Lead::orderBy('id', 'DESC');

        if ($nameContains) {
            $leads->whereRaw(
                "MATCH(first_name,last_name) AGAINST(? IN BOOLEAN MODE)",
                [$nameContains . '*']
            );
        }

        return response()->json(['leads' => $leads->paginate($perPage)], 200);
    }

    /**
     * Check if lead exists.
     * 
     * @param Request $request
     *
     * @return Response
     */
    public function leadExists(Request $request)
    {
        if (self::_getLeadByEmailPhoneAndCountry($request)->exists()) {
            return response()->json(['message' => 'Lead exists'], 200);
        } else {
            return response()->json(['message' => 'Lead not found'], 404);
        }
    }

    /**
     * Get total leads  .
     *
     * @param Request $request
     * @return Response
     */
    public function getTotalLeads(Request $request)
    {
        $leads
            = Lead::select(\DB::raw('count(id) AS count'))
            ->first();

        if ($leads) {
            return response()->json(['leads_count' => $leads->count], 200);
        } else {
            return response()->json(['message' => 'No leads found!'], 404);
        }
    }

    /**
     * Get one lead.
     *
     * @return Response
     */
    public function getLead(Request $request)
    {
        $lead = self::_getLeadByEmailPhoneAndCountry($request)->first();

        if ($lead) {
            return response()->json(['lead' => $lead], 200);
        } else {
            return response()->json(['message' => 'lead not found!'], 404);
        }
    }

    /**
     * Store a new lead.
     *
     * @param Request $request
     * @return Response
     */
    public function updateLead(Request $request)
    {
        try {
            //validate incoming request 
            self::_updateLeadValidation($request);

            $lead = self::_getLeadByEmailPhoneAndCountry($request)->first();

            if ($lead) {
                try {
                    $lead = self::_assembleLead($request, $lead);

                    $lead->save();

                    return response()->json(['property' => $lead], 200);
                } catch (\Exception $e) {

                    return response()->json(['message' => 'lead update failed!'], 500);
                }
            } else {
                return response()->json(['message' => 'lead not found!'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the lead data'], 400);
        }
    }

    /**
     * Get one lead.
     *
     * @return Response
     */
    public function deleteLead(Request $request)
    {
        $lead = self::_getLeadByEmailPhoneAndCountry($request)->first();

        if ($lead) {
            try {
                $lead->delete();

                return response()->json(['message' => 'lead deleted!'], 200);
            } catch (\Exception $e) {

                return response()->json(['message' => 'lead deletion failed!'], 500);
            }
        } else {
            return response()->json(['message' => 'lead not found!'], 404);
        }
    }

    /**
     * Lead subject
     * 
     * The lead $lead 
     * 
     * @param Lead $lead
     * 
     * @return void
     */

    private function _restoreIfTrashed(Lead $lead)
    {
        if ($lead->trashed()) {
            $lead->restore();
        }
    }

    //     /**
    //  * Lead subject
    //  * 
    //  * The lead $lead 
    //  * 
    //  * @param Lead $lead
    //  * 
    //  * @return void
    //  */

    // private function _trashedRestore(Lead $lead)
    // {
    //     if ($lead->trashed()) {
    //         $lead->restore();
    //     } else {
    //         throw new \Exception('The lead wasnt trashed so, error...');
    //     }
    // }

    /**
     * Lead subject
     * 
     * The subject $subject 
     * 
     * @param string $subject
     * 
     * @return Lead
     */

    private function _getLeadByEmailPhoneAndCountry(Request $request)
    {
        $email = $request->input('_email') ?? $request->input('email');
        $phone = $request->input('_phone') ?? $request->input('phone');
        $country = $request->input('_country') ?? $request->input('country');

        return Lead::where('email', $email)
            ->where('phone', $phone)
            ->where('country', $country);
    }

    /**
     * Assemble one lead.
     * 
     * @param Request $request
     * 
     * @param Lead $lead
     * 
     * @return Lead $lead
     */
    private function _assembleLead(Request $request, Lead $lead = null)
    {
        $lead || ($lead = new Lead);
        $lead->first_name = $request->input('first_name');
        $lead->last_name = $request->input('last_name');
        $lead->email = $request->input('email');
        $lead->phone = $request->input('phone');
        $lead->country = $request->input('country');
        $lead->language = $request->input('language');

        return $lead;
    }

    /**
     * Assemble one message.
     * 
     * @param Request $request
     * 
     * @param Message $message
     * 
     * @return Message $message
     */
    private function _assembleMessage(Request $request, Message $message = null)
    {
        $message || ($message = new Message);
        $message->lead_id = $request->input('lead_id');
        $message->message = $request->input('message');
        $property_code = $request->input('property_code');
        $property_code && ($message->property_code = $property_code);
        $message->ip_address = self::_getIpAddress();
        $user_agent = $request->input('user_agent');
        $user_agent && ($message->user_agent = $user_agent);
        $referrer_page = $request->input('referrer_page');
        $referrer_page && ($message->referrer_page = $referrer_page);
        $lang = $request->input('lang');
        $lang && ($message->lang = $lang);
        $os = $request->input('os');
        $os && ($message->os = $os);
        $screen_width = $request->input('screen_width');
        $screen_width && ($message->screen_width = $screen_width);
        $screen_height = $request->input('screen_height');
        $screen_height && ($message->screen_height = $screen_height);
        $screen_availWidth = $request->input('screen_availWidth');
        $screen_availWidth && ($message->screen_availWidth = $screen_availWidth);
        $screen_availHeight = $request->input('screen_availHeight');
        $screen_availHeight && ($message->screen_availHeight = $screen_availHeight);
        $color_depth = $request->input('color_depth');
        $color_depth && ($message->color_depth = $color_depth);
        $pixel_depth = $request->input('pixel_depth');
        $pixel_depth && ($message->pixel_depth = $pixel_depth);

        return $message;
    }

    /**
     * Get one lead.
     * 
     * @param Request $request
     * 
     * @return void
     */
    private function _createLeadValidation(Request $request)
    {
        //validate incoming request 
        $validator = $this->validate(
            $request,
            [
                'first_name' => 'required|string|max:50',
                'last_name' => 'required|string|max:60',
                'email' => 'required|email',
                'phone' => 'required|phone:country',
                'message' => 'required|string',
                'country' => 'required_with:phone',
                'language' => 'string:size:2',
                'privacy_policy_check' => 'accepted'
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
    private function _updateLeadValidation(Request $request)
    {
        //validate incoming request 
        $validator = $this->validate(
            $request,
            [
                'first_name' => 'required|string|max:50',
                'last_name' => 'required|string|max:60',
                'email' => 'required|email',
                'phone' => 'required|phone:country',
                'country' => 'required_with:phone',
                'language' => 'string:size:2'
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
    private function _messageValidation(Request $request)
    {
        //validate incoming request 
        $validator = $this->validate(
            $request,
            [
                'message' => 'required|string',
                'lead_id' => 'required|integer',
                'property_code' => 'string',
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
     * Get create message.
     * 
     * @param Request $request
     * @param Response $response
     * 
     * @return void
     */
    private function _createMessage(Request $request, Lead $lead)
    {
        try {
            // $request->lead_id = $lead->id;
            $request->request->add(['lead_id' => $lead->id]);

            self::_messageValidation($request);

            try {
                $message = self::_assembleMessage($request);
                $message->save();

                if ($message->property_code) {
                    self::_incrementInquiries($message->property_code);
                }
            } catch (\Exception $e) {
                throw new \Exception(json_encode(['result' => ['message' => 'Message Creation Failed!'], 'status' => 409]));
            }
        } catch (\Exception $e) {
            throw new \Exception(json_encode(['result' => ['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the lead data'], 'status' => 400]));
        }
    }

    /**
     * Increment property inquiries.
     * 
     * @param String $propertyCode
     * 
     * @return void
     */
    private function _incrementInquiries(String $propertyCode)
    {
        $property = Property::findOrFail($propertyCode);
        $property->inquiries += 1;

        $property->save();
    }

    /**
     * Get create message.
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
