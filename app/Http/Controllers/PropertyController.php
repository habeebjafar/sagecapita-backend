<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Property;
use App\PropertyGroup;

class PropertyController extends Controller
{
     /**
      * Instantiate a new PropertyController instance.
      *
      * @return void
      */
    public function __construct()
    {
        //$this->middleware('auth');
    }

        /**
     * Store a new property.
     *
     * @param  Request  $request
     * @return Response
     */
    public function createProperty(Request $request)
    {   
        $this->middleware('auth');

        try{
            //validate incoming request 
            self::propertyValidation($request);

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

        } catch(\Exception $e) {
            return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the property data'], 400);
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
     * Check if property exists.
     *
     * @return Response
     */
    public function propertyExists($code)
    {
        if (Property::where('code', $code)->exists() ) {
            return response()->json(['message' => 'Property exists'], 200);
        } else {
            return response()->json(['message' => 'Property not found'], 404);
        }

    }

    /**
     * get top selection properties.
     * 
     */
    public function getViewedProperties()
    {
        $viewedProperties
            = self::selectPropertyThumbnailFields()->where('sold', null)->latest('views')->paginate(4);

        if ($viewedProperties) {
            return response()->json(['properties' => $viewedProperties], 200);
        } else {
            return response()->json(['message' => 'Property not found'], 404);
        }

    }

    /**
     * Get top cities
     *
     * @param  Request  $request
     * @return Response
     */
    public function getTopTypes(Request $request)
    {
        $paginatedTopTypes = self::topPropertiesPagination('type', 6, 'top_types');

        if ($paginatedTopTypes) {
            return response()->json(['property_groups' => $paginatedTopTypes], 200);
        } else {
            return response()->json(['message' => 'Property not found'], 404);
        }
    }

    /**
     * Get top cities
     *
     * @param  Request  $request
     * @return Response
     */
    public function getTopStates(Request $request)
    {
        $paginatedTopStates = self::topPropertiesPagination('state', 6, 'top_states');

        if ($paginatedTopStates) {
            return response()->json(['property_groups' => $paginatedTopStates], 200);
        } else {
            return response()->json(['message' => 'Property not found'], 404);
        }
    }

    /**
     * Get top cities
     *
     * @param  Request  $request
     * @return Response
     */
    public function getTopCities(Request $request)
    {
        $paginatedTopCities = self::topPropertiesPagination('city', 6, 'top_cities');

        if ($paginatedTopCities) {
            return response()->json(['property_groups' => $paginatedTopCities], 200);
        } else {
            return response()->json(['message' => 'Property not found'], 404);
        }
    } 

    /**
     * Check latest acquisition properties.
     * 
     */
    public function getLatestAcquisitions()
    {
        $latestAcquisitions
            = self::selectPropertyThumbnailFields()->where('sold', null)->latest('id')->paginate(4);

        if ($latestAcquisitions) {
            return response()->json(['properties' => $latestAcquisitions], 200);
        } else {
            return response()->json(['message' => 'Property not found'], 404);
        }

    }    

    /**
     * Check exclusive properties.
     * 
     */
    public function getExclusiveProperties()
    {
        $exclusiveProperties
            = self::selectPropertyThumbnailFields()->where('sold', null)->where('is_exclusive', '!=', null)->paginate(4);

        if ($exclusiveProperties) {
            return response()->json(['properties' => $exclusiveProperties], 200);
        } else {
            return response()->json(['message' => 'Property not found'], 404);
        }

    }

    /**
     * get top selection properties.
     * 
     */
    public function getTopSelections()
    {
        $topSelections
            = self::selectPropertyThumbnailFields()->where('sold', null)->latest('views')->latest('inquiries')->paginate(3);

        if ($topSelections) {
            return response()->json(['properties' => $topSelections], 200);
        } else {
            return response()->json(['message' => 'Property not found'], 404);
        }

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

            self::incrementViews($property);

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
        $this->middleware('auth');

        try{
            self::propertyValidation($request);

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
        } catch(\Exception $e) {
            return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the property data'], 400);
        }

    }

     /**
     * Get one property.
     *
     * @return Response
     */
    public function deleteProperty($code)
    {
        $this->middleware('auth');

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

    private function topPropertiesPagination($column, $perPage, $routeName)
    {

        $page = LengthAwarePaginator::resolveCurrentPage();
        $options = ['path' => url('api/' . $routeName)];
        $topProperties 
            = Property::select($column . ' AS name', \DB::raw('(sum(views) + sum(inquiries)) AS sumOfRequests'), \DB::raw('count(id) AS count'))
            ->where('sold', null)
            ->orderBy('sumOfRequests', 'DESC')
            ->groupBy($column)
            ->get();

        $slicedTopProperties = $topProperties->slice(($page - 1) * $perPage, $perPage)->values();

        $slicedTopPropertiesName = $slicedTopProperties->map( 
            function ($propertyGroup) {
                return $propertyGroup->name;
            }
        );

        $getPhotos = PropertyGroup::select('photo')
            ->where('class', $column)
            ->whereIn('name', $slicedTopPropertiesName->toArray());

        if ($getPhotos->count() === $slicedTopPropertiesName->count()) {
            $getPhotosArray = $getPhotos->get()->toArray();
            $slicedTopPropertiesArray = $slicedTopProperties->toArray();

            $slicedTopPropertiesPhotoAdded = [];

            foreach ($slicedTopPropertiesArray as $key => $value) {
                $slicedTopPropertiesPhotoAdded[$key] 
                    = array_merge($value, $getPhotosArray[$key]);
            }

            return new LengthAwarePaginator($slicedTopPropertiesPhotoAdded, $topProperties->count(), $perPage, $page, $options);
        } else {
            throw new \Exception('Some property classes do not exist in the property groups table');
        } 
    }

    private function incrementViews(Property $property) 
    {
        $property->views += 1;

        $property->save();
    }

    private function incrementInquiries(Property $property) 
    {
        $property->inquiries += 1;

        $property->save();
    }
    
    private function selectPropertyThumbnailFields()
    {
        return Property::select('code', 'photo', 'main_title', 'price', 'price_upper_range', 'price_lower_range', 'suburb', 'city', 'state');
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

    private function propertyValidation (Request $request) {
        //validate incoming request 
        $validator = $this->validate($request, [
            'photo' => 'required|string|max:100',
            'photos' => 'required|json',
            'video' => 'string|max:100',
            'main_title' => 'required|string|max:150',
            'side_title' => 'required|string|max:150',
            'heading_title' => 'required|string|max:150',
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
    }

}