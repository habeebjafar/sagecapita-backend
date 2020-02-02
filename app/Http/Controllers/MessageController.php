<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Lead;
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
        $this->middleware('auth');

        try{
            //validate incoming request 
            self::_messageValidation($request);

            try {
                $message = self::_assembleMessage($request);                
                $message->save();

                $message = self::_createMessage($request, $message);
                $message->save();

                //return successful response
                return response()->json(['message' => $message, 'message' => 'CREATED'], 201);
            } catch (\Exception $e) {
                try {
                    self::_trashedRestore($message);

                    $message = self::_createMessage($request, $message);
                    $message->save();

                    return response()->json(['message' => $message, 'message' => 'UPDATED'], 200);
                } catch (\Exception $e) {
                    //return error message
                    return response()->json(['message' => 'Message Creation Failed!'], 409);
                }
            }

        } catch(\Exception $e) {
            return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the message data'], 400);
        }
    }

    /**
     * Get messages by customer and property .
     *
     * @return Response
     */
    public function getCustomerAndPropertyMessages(Request $request) 
    {
        try{
            self::_customerAndPropertyMessageValidation($request);

            try {
                $propertyIds = \json_decode($request->input('property_ids'));
                $customerId = $request->input('customer_id');

                $messageProperties = Message::select('property_id')
                    ->where('customer_id', $column)
                    ->whereIn('property_id', $propertyIds)
                    ->get();

                    return response()->json(['message_properties' => $messageProperties], 200);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Customer Message Properties Fetch Failed!'], 500);
            }
        } catch(\Exception $e) {
            return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the message data'], 400);
        }
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

        $messages = Lead::select('first_name', 'last_name', 'phone', 'email', 'country', 'language', \DB::raw('(SELECT message from messages WHERE lead_id = id)'))->orderBy('id', 'DESC');

        if ($nameContains) {
            $messages->whereRaw(
                "MATCH(first_name,last_name) AGAINST(? IN BOOLEAN MODE)",
                [$nameContains]
            );
        }
        
         return response()->json(['messages' => $messages->paginate($perPage)], 200);
    }

    /**
     * Check if message exists.
     * 
     * @param Request $request
     *
     * @return Response
     */
    public function messageExists(Request $request)
    {
        if (self::_getMessageByPropertyAndCustomerId($request)->exists() ) {
            return response()->json(['message' => 'Message exists'], 200);
        } else {
            return response()->json(['message' => 'Message not found'], 404);
        }

    }

    /**
     * Get total messages  .
     *
     * @param Request $request
     * @return Response
     */
    public function getTotalMessages(Request $request)
    {
        $messages 
            = Message::select(\DB::raw('count(id) AS count'))
            ->first();

        if ($messages) {
            return response()->json(['messages_count' => $messages->count], 200);
        } else {
            return response()->json(['message' => 'No messages found!'], 404);
        }
    }

    /**
     * Get one message.
     *
     * @return Response
     */
    public function getMessage(Request $request)
    {
        $message = self::_getMessageByPropertyAndCustomerId($request)->first();

        if ($message) {
            return response()->json(['message' => $message], 200);
        } else {
            return response()->json(['message' => 'message not found!'], 404);
        }

    }

    /**
     * Get one message.
     *
     * @return Response
     */
    public function deleteMessage(Request $request)
    {
        $this->middleware('auth');

        $message = self::_getMessageByPropertyAndCustomerId($request)->first();

        if ($message) {
            try{
                $message->delete();

                return response()->json(['message' => 'message deleted!'], 200);
            } catch (\Exception $e) {

                return response()->json(['message' => 'message deletion failed!'], 500);
            }
        } else {
            return response()->json(['message' => 'message not found!'], 404);
        }

    }

    /**
     * Message subject
     * 
     * The message $message 
     * 
     * @param Message $message
     * 
     * @return void
     */

    private function _trashedRestore(Message $message) 
    {
        if ($message->trashed()) {
            $message->restore();
        } else {
            throw new \Exception('The message wasnt trashed so, error...');
        }        
    }

    /**
     * Message subject
     * 
     * The subject $subject 
     * 
     * @param string $subject
     * 
     * @return Message
     */

    private function _getMessageByPropertyAndCustomerId(Request $request) 
    {
        $propertyId = $request->input('property_id');
        $customerId = $request->input('customer_id');

        return Message::where('property_id', $propertyId)
            ->where('customer_id', $customerId);
    }

    /**
     * Get one message.
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
        $message->first_name = $request->input('first_name');
        $message->last_name = $request->input('last_name');
        $message->email = $request->input('email');
        $message->phone = $request->input('phone');
        $message->country = $request->input('country');
        $ip_address = $request->input('ip_address');
        $ip_address && ($message->ip_address = $ip_address);
        $user_agent = $request->input('user_agent');
        $user_agent && ($message->user_agent = $user_agent);
        $referrer_page = $request->input('referrer_page');
        $referrer_page && ($message->referrer_page = $referrer_page);
        $language = $request->input('language');
        $language && ($message->language = $language);
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
     * Get one message.
     * 
     * @param Request $request
     * 
     * @return void
     */
    private function _messageValidation(Request $request) 
    {
        //validate incoming request 
        $validator = $this->validate(
            $request, [
                'first_name' => 'required|string|max:50',
                'last_name' => 'required|string|max:60',
                'email' => 'required|email',
                'phone' => 'required|phone:country',
                'country' => 'required_with:phone',
                'ip_address' => 'ip',
                'user_agent'=>'string',
                'referrer_page'=>'string',
                'language'=>'string:size:2',
                'os'=>'string',
                'screen_width'=>'integer',
                'screen_height'=>'integer',
                'screen_availWidth'=>'integer',
                'screen_availHeight'=>'integer',
                'color_depth'=>'integer',
                'pixel_depth'=>'integer',

                'message'=>'string',
            ]
        );
    }

    /**
     * Get one message.
     * 
     * @param Request $request
     * 
     * @return void
     */
    private function _customerAndPropertyMessageValidation(Request $request) 
    {
        //validate incoming request 
        $validator = $this->validate(
            $request, [
                'first_name' => 'required|string|max:50',
                'last_name' => 'required|string|max:60',
                'email' => 'required|email',
                'phone' => 'required|phone:country',
                'country' => 'required_with:phone',
                'ip_address' => 'ip',
                'user_agent'=>'string',
                'referrer_page'=>'string',
                'language'=>'string:size:2',
                'os'=>'string',
                'screen_width'=>'integer',
                'screen_height'=>'integer',
                'screen_availWidth'=>'integer',
                'screen_availHeight'=>'integer',
                'color_depth'=>'integer',
                'pixel_depth'=>'integer',
            ]
        );
    }

    /**
     * Get create message.
     * 
     * @param Request $request
     * @param Message $message
     * 
     * @return void
     */
    private function _createMessage(Request $request, Message $message) 
    {
        $message = new Message;
        $message->message_id = $message->id;
        $message->message = $request->input('message');

        return $message;
    }

}