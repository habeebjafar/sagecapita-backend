<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\MediaRequest;
use App\Mail\MediaRequestResponse;
use App\Mail\Joinus;
use App\Mail\JoinusResponse;
use App\Mail\NewsletterSignup;
use App\Mail\NewsletterSignupResponse;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /**
     * Instantiate a new ContactController instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth:customers');
    }

    /**
     * Create a new joinus request.
     *
     * @param Request $request
     * @return Response
     */
    public function createJoinus(Request $request)
    {
        try {
            self::_joinusValidation($request);

            try {
                $file = $request->file('cv');
                $email = $request->input('email');

                $joinus = new \stdClass();
                $joinus->fullName = $request->input('full_name');
                $joinus->email = $email;
                $joinus->phone = $request->input('phone');
                $joinus->role = $request->input('role');
                $joinus->country = $request->input('country');
                $joinus->language = $request->input('language');
                $joinus->message = $request->input('message');
                $joinus->cv = $file->getRealPath();
                $joinus->cvMime = $file->getMimeType();

                Mail::to("sendmail@sagecapita.com")->send(new Joinus($joinus));

                Mail::to($email)->send(new JoinusResponse($joinus));

                return response()->json(['message' => 'Your request has been received, we would get back to you soon!'], 200);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Problem sending join request'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the lead data'], 400);
        }
    }

    /**
     * Create a new media request.
     *
     * @param Request $request
     * @return Response
     */
    public function createMediaRequest(Request $request)
    {
        try {
            self::_mediaRequestValidation($request);

            try {
                $email = $request->input('email');

                $mediaRequest = new \stdClass();
                $mediaRequest->fullName = $request->input('full_name');
                $mediaRequest->email = $email;
                $mediaRequest->headline = $request->input('headline');
                $mediaRequest->country = $request->input('country');

                Mail::to("sendmail@sagecapita.com")->send(new MediaRequest($mediaRequest));

                Mail::to($email)->send(new MediaRequestResponse($mediaRequest));

                return response()->json(['message' => 'Your request has been received, we would get back to you soon!'], 200);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Problem sending join request'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the lead data'], 400);
        }
    }

    /**
     * Create a new newsletter.
     *
     * @param Request $request
     * @return Response
     */
    public function createNewsletterSignup(Request $request)
    {
        try {
            self::_newsletterSignupValidation($request);

            try {
                $email = $request->input('email');

                $newsletterSignup = new \stdClass();
                $newsletterSignup->email = $email;

                Mail::to("sendmail@sagecapita.com")
                    ->send(new NewsletterSignup($newsletterSignup));

                Mail::to($email)->send(new NewsletterSignupResponse());

                return response()->json(['message' => 'Thanks for signing up!'], 200);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Problem sending join request'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the lead data'], 400);
        }
    }

    /**
     * Validate media request form.
     * 
     * @param Request $request
     * 
     * @return void
     */
    private function _mediaRequestValidation(Request $request)
    {
        //validate incoming request 
        $validator = $this->validate(
            $request,
            [
                'full_name' => 'string:max:255',
                'email' => 'required|email',
                'country' => 'required_with:phone|string:size:2',
                'headline' => 'string:max:1024',
                'privacy_policy_check' => 'accepted'
            ]
        );
    }

    /**
     * Validate join us form.
     * 
     * @param Request $request
     * 
     * @return void
     */
    private function _joinusValidation(Request $request)
    {
        //validate incoming request 
        $validator = $this->validate(
            $request,
            [
                'cv' => 'required|file|mimetypes:application/pdf|max:2048',
                'full_name' => 'string:max:255',
                'email' => 'required|email',
                'phone' => 'required|phone:country',
                'country' => 'required_with:phone|string:size:2',
                'language' => 'string:size:2',
                'role' => 'string:max:35',
                'message' => 'required|string',
                'privacy_policy_check' => 'accepted'
            ]
        );
    }

        /**
     * Validate newsletter form.
     * 
     * @param Request $request
     * 
     * @return void
     */
    private function _newsletterSignupValidation(Request $request)
    {
        //validate incoming request 
        $validator = $this->validate(
            $request,
            [
                'email' => 'required|email',
                'privacy_policy_check' => 'accepted'
            ]
        );
    }
}
