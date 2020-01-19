<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Property;

class PropertyController extends Controller
{
     /**
     * Instantiate a new PropertyController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

        /**
     * Store a new property.
     *
     * @param  Request  $request
     * @return Response
     */
    public function createProperty(Request $request)
    {
        //validate incoming request 
        $this->validate($request, [
            'photo' => 'required|string|max:32',
            'photos' => 'required|json',
            'video' => 'required|string|max:32',
            'main_title' => 'required|string|max:80',
            'side_title' => 'required|string|max:80',
            'heading_title' => 'required|string|max:80',
            'description_text' => 'required|string|max:1000',
            'state' => 'required|string|max:25',
            'city' => 'required|string|max:35',
            'suburb' => 'required|string|max:45',
            'type' => 'required|string|max:25',
            'interior_surface' => 'required|integer',
            'exterior_surface' => 'required|integer',
            'features' => 'required|json',
            'is_exclusive' => 'boolean',
            'price' => 'integer',
            'price_lower_range' => 'integer',
            'price_upper_range' => 'integer'
        ]);

        try {
            // $property = Property::create($request->all());
            $property = self::assembleProperty($request);
            
            $property->save();

            //return successful response
            return response()->json(['property' => $property, 'message' => 'CREATED'], 201);
        } catch (\Exception $e) {
            //return error message
            return response()->json(['message' => 'Property Creation Failed!'], 409);
        }
    }

    /**
     * Get all Property.
     *
     * @return Response
     */
    public function getProperties()
    {
        // TODO: use the query string to select the search criteria, result lenght, result page
         return response()->json(['properties' =>  Property::all()], 200);
    }

    /**
     * Get one property.
     *
     * @return Response
     */
    public function getProperty($code)
    {
        try {
            $property = Property::findOrFail($code);

            return response()->json(['property' => $property], 200);

        } catch (\Exception $e) {

            return response()->json(['message' => 'property not found!'], 404);
        }

    }

     /**
     * Update property.
     *
     * @param  Request  $request
     * 
     * @return Response
     */
    public function updateProperty($code, Request $request)
    {
        try {
            $property = Property::findOrFail($code);
            
            try { 
                $property = self::assembleProperty($request, $property);

                $property->save();
    
                return response()->json(['property' => $property], 200);
            } catch (\Exception $e) {

                return response()->json(['message' => 'property update failed!'], 500);
            }

        } catch (\Exception $e) {

            return response()->json(['message' => 'property not found!'], 404);
        }

    }

     /**
     * Get one property.
     *
     * @return Response
     */
    public function deleteProperty($code)
    {
        try {
            $property = Property::findOrFail($code);

            try{
                $property->delete();

                return response()->json(['message' => 'property deleted!'], 200);
            } catch (\Exception $e) {

                return response()->json(['message' => 'property deletion failed!'], 500);
            }

        } catch (\Exception $e) {

            return response()->json(['message' => 'property not found!'], 404);
        }

    }

    private function assembleProperty(Request $request, Property $property = null) {
        $property || ($property = new Property);
        $property->photo = $request->input('photo');
        $property->photos = $request->input('photos');
        $property->video = $request->input('video');
        $property->main_title = $request->input('main_title');
        $property->side_title = $request->input('side_title');
        $property->heading_title = $request->input('heading_title');
        $property->description_text = $request->input('description_text');
        $property->state = $request->input('state');
        $property->city = $request->input('city');
        $property->suburb = $request->input('suburb');
        $property->type = $request->input('type');
        $property->interior_surface = $request->input('interior_surface');
        $property->exterior_surface = $request->input('exterior_surface');
        $property->features = $request->input('features');
        $is_exclusive = $request->input('is_exclusive');
        $is_exclusive && ($property->is_exclusive = $is_exclusive);
        $price = $request->input('price');
        $price && ($property->price = $price);
        $price_lower_range = $request->input('price_lower_range');
        $price_lower_range && ($property->price_lower_range = $price_lower_range);
        $price_upper_range = $request->input('price_upper_range');
        $price_upper_range && ($property->price_upper_range = $price_upper_range);

        return $property;
    }

}