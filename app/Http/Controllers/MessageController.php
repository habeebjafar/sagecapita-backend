<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Message;

class MessageController extends Controller
{
    /**
     * Instantiate a new MessageController instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Store a new message.
     *
     * @param Request $request
     * @return Response
     */
    public function createMessage(Request $request)
    {
        // $this->middleware('auth');
    }

    /**
     * Get all Message.
     *
     * @return Response
     */
    public function getMessages(Request $request)
    {
        // TODO: use the query string to select the search criteria, result length, result page

        $perPage = $request->input('per_page') ?? 8;
        $nameContains = $request->input('name');
        $MAX_MESSAGE_LENGTH = 60;

        $messages = Message::join('leads', 'messages.lead_id', '=', 'leads.id')
            ->join('properties', 'messages.property_code', '=', 'properties.code')
            ->select('messages.code', \DB::raw('(select case when length(messages.message) > ' . $MAX_MESSAGE_LENGTH . ' then concat(substring(messages.message, 1, ' . $MAX_MESSAGE_LENGTH . '), \'...\') else messages.message end) as message'), 'messages.created_at', 'properties.is_exclusive', 'leads.first_name', 'leads.last_name')
            ->orderBy('messages.id', 'DESC');

        if ($nameContains) {
            $messages->whereRaw(
                "MATCH(leads.first_name,leads.last_name) AGAINST(? IN BOOLEAN MODE)",
                [$nameContains . '*']
            );
        }

        return response()->json(['messages' => $messages->paginate($perPage)], 200);
    }

    /**
     * Get all Message.
     *
     * @return Response
     */
    public function getMessage(String $code)
    {
        try {
            $message = Message::join('leads', 'messages.lead_id', '=', 'leads.id')
                ->join('properties', 'messages.property_code', '=', 'properties.code')
                ->select('messages.code AS message_code', 'messages.message', 'messages.is_done', 'properties.is_exclusive', 'properties.code AS property_code', 'properties.main_title', 'properties.country AS property_country', 'leads.first_name', 'leads.last_name', 'leads.email', 'leads.phone', 'leads.country AS lead_country')
                ->findOrFail($code);

            return response()->json(['message' => $message], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'message not found!'], 404);
        }
    }

    /**
     * Get total pending messages  .
     *
     * @return Response
     */
    public function getPendingMessages()
    {
        $messages
            = Message::select(\DB::raw('count(id) AS count'))
            ->where('is_done', 0)
            ->orWhereNull('is_done')
            ->first();

        if ($messages) {
            return response()->json(['pending_count' => $messages->count], 200);
        } else {
            return response()->json(['message' => 'No messages found!'], 404);
        }
    }

    /**
     * Mark message as pending.
     * 
     * @param String $code - message code
     * 
     * @return Response
     */
    public function markAsPending($code)
    {
        try {
            $message = Message::findOrFail($code);

            try {
                $message->is_done = false;

                $message->save();

                return response()->json(['message' => $message], 200);
            } catch (\Exception $e) {

                return response()->json(['message' => 'message update failed!'], 500);
            }
        } catch (\Exception $e) {

            return response()->json(['message' => 'message not found!'], 404);
        }
    }

    /**
     * Mark message as done.
     * 
     * @param String $code - message code
     * 
     * @return Response
     */
    public function markAsDone($code)
    {
        try {
            $message = Message::findOrFail($code);

            try {
                $message->is_done = true;

                $message->save();

                return response()->json(['message' => $message], 200);
            } catch (\Exception $e) {

                return response()->json(['message' => 'message update failed!'], 500);
            }
        } catch (\Exception $e) {

            return response()->json(['message' => 'message not found!'], 404);
        }
    }

    /**
     * Get one property.
     *
     * @return Response
     */
    public function deleteMessage($code)
    {
        try {
            $message = Message::findOrFail($code);

            try {
                $message->delete();

                return response()->json(['message' => 'message deleted!'], 200);
            } catch (\Exception $e) {

                return response()->json(['message' => 'message deletion failed!'], 500);
            }
        } catch (\Exception $e) {

            return response()->json(['message' => 'message not found!'], 404);
        }
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
