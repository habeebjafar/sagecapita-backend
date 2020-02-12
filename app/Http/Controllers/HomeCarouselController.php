<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\HomeCarousel;

class HomeCarouselController extends Controller
{
    /**
     * Instantiate a new PropertyController instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Store a new HomeCarousel.
     *
     * @param Request $request
     * @return Response
     */
    public function createHomeCarousel(Request $request)
    {
        try {
            //validate incoming request 
            self::_homeCarouselValidation($request);

            try {
                // $homeCarousel = HomeCarousel::create($request->all());
                $homeCarousel = self::_assembleHomeCarousel($request);

                $homeCarousel->save();

                //return successful response
                return response()->json(['home_carousel' => $homeCarousel, 'message' => 'CREATED'], 201);
            } catch (\Exception $e) {
                //return error message
                return response()->json(['message' => 'Home carousel Creation Failed!'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the home carousel data'], 400);
        }
    }

    /**
     * Get all HomeCarousel.
     *
     * @return Response
     */
    public function getHomeCarousels()
    {
        // TODO: use the query string to select the search criteria, result lenght, result page
        return response()->json(['home_carousels' =>  self::_homeCarouselPropertiesJoin()->get()], 200);
    }

    /**
     * Update HomeCarousel.
     *
     * @param Request $request
     * 
     * @return Response
     */
    public function updateHomeCarousel(Request $request)
    {
        try {
            self::_homeCarouselValidation($request);

            $propertyCode = $request->input('property_code');

            $homeCarousel = HomeCarousel::where('property_code', $propertyCode)->first();

            if ($homeCarousel) {
                try {
                    $homeCarousel = self::_assembleHomeCarousel($request, $homeCarousel);

                    $homeCarousel->save();

                    return response()->json(['home_carousel' => $homeCarousel], 200);
                } catch (\Exception $e) {

                    return response()->json(['message' => 'Home carousel update failed!'], 500);
                }
            } else {
                return response()->json(['message' => 'Home carousel not found!'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the Home carousel data'], 400);
        }
    }

    /**
     * Get one HomeCarousel.
     *
     * @return Response
     */
    public function deleteProperty(Request $request)
    {
        $propertyCode = $request->input('property_code');

        $homeCarousel = HomeCarousel::where('property_code', $propertyCode)->first();

        if ($homeCarousel) {
            try {
                $homeCarousel->delete();

                return response()->json(['message' => 'home carousel deleted!'], 200);
            } catch (\Exception $e) {

                return response()->json(['message' => 'home carousel deletion failed!'], 500);
            }
        } else {
            return response()->json(['message' => 'home carousel not found!'], 404);
        }
    }

    /**
     * Get one HomeCarousel.
     * 
     * @param Request $request
     * 
     * @param HomeCarousel $homeCarousel
     * 
     * @return HomeCarousel $homeCarousel
     */
    private function _assembleHomeCarousel(Request $request, HomeCarousel $homeCarousel = null)
    {
        $homeCarousel || ($homeCarousel = new HomeCarousel);
        $homeCarousel->property_code = $request->input('property_code');

        return $homeCarousel;
    }

    /**
     * Get one HomeCarousel.
     * 
     * @param Request $request
     * 
     * @return void
     */
    private function _homeCarouselValidation(Request $request)
    {
        //validate incoming request 
        $validator = $this->validate(
            $request,
            [
                'property_code' => 'required|string|max:255',
            ]
        );
    }

    private function _homeCarouselPropertiesJoin()
    {
        $MAX_MESSAGE_LENGTH = 60;

        return HomeCarousel::join('properties', 'home_carousel.property_code', '=', 'properties.code')
        ->select(
            'properties.code AS code',
            'properties.video AS video',
            'properties.interior_surface AS interior_surface',
            'properties.photo AS photo',
            'properties.main_title AS main_title',
            \DB::raw('(select case when length(properties.description_text) > ' . $MAX_MESSAGE_LENGTH . ' then concat(substring(properties.description_text, 1, ' . $MAX_MESSAGE_LENGTH . '), \'...\') else properties.description_text end) as description_text'),
            'properties.price AS price',
            'properties.price_upper_range AS price_upper_range',
            'properties.price_lower_range AS price_lower_range',
            'properties.is_exclusive AS is_exclusive',
            'properties.city AS city',
            'properties.type AS type'
        );
    }
}
