<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Favorite;

class FavoriteController extends Controller
{
     /**
      * Instantiate a new FavoriteController instance.
      *
      * @return void
      */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Store a new favorite.
     *
     * @param Request $request
     * @return Response
     */
    public function createFavorite(Request $request)
    {   
        $this->middleware('auth');

        try{
            //validate incoming request 
            self::_favoriteValidation($request);

            try {
                $favorite = self::_assembleFavorite($request);
                
                $favorite->save();

                //return successful response
                return response()->json(['favorite' => $favorite, 'message' => 'CREATED'], 201);
            } catch (\Exception $e) {
                try {
                    self::_trashedRestore($favorite);

                    return response()->json(['favorite' => $favorite, 'message' => 'UPDATED'], 200);
                } catch (\Exception $e) {
                    //return error message
                    return response()->json(['message' => 'Favorite Creation Failed!'], 409);
                }
            }

        } catch(\Exception $e) {
            return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the favorite data'], 400);
        }
    }

    /**
     * Get favorites by customer and property .
     *
     * @return Response
     */
    public function getCustomerAndPropertyFavorites(Request $request) 
    {
        try{
            self::_customerAndPropertyFavoriteValidation($request);

            try {
                $propertyIds = \json_decode($request->input('property_ids'));
                $customerId = $request->input('customer_id');

                $favoriteProperties = Favorite::select('property_id')
                    ->where('customer_id', $column)
                    ->whereIn('property_id', $propertyIds)
                    ->get();

                    return response()->json(['favorite_properties' => $favoriteProperties], 200);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Customer Favorite Properties Fetch Failed!'], 500);
            }
        } catch(\Exception $e) {
            return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the favorite data'], 400);
        }
    }

    /**
     * Get all Favorite.
     *
     * @return Response
     */
    public function getFavorites()
    {
        // TODO: use the query string to select the search criteria, result length, result page
         return response()->json(['favorites' =>  Favorite::all()], 200);
    }

    /**
     * Check if favorite exists.
     * 
     * @param Request $request
     *
     * @return Response
     */
    public function favoriteExists(Request $request)
    {
        if (self::_getFavoriteByPropertyAndCustomerId($request)->exists() ) {
            return response()->json(['message' => 'Favorite exists'], 200);
        } else {
            return response()->json(['message' => 'Favorite not found'], 404);
        }

    }

    /**
     * Get total favorites  .
     *
     * @param Request $request
     * @return Response
     */
    public function getTotalFavorites(Request $request)
    {
        $favorites 
            = Favorite::select(\DB::raw('count(id) AS count'))
            ->first();

        if ($favorites) {
            return response()->json(['favorites_count' => $favorites->count], 200);
        } else {
            return response()->json(['message' => 'No favorites found!'], 404);
        }
    }

    /**
     * Get one favorite.
     *
     * @return Response
     */
    public function getFavorite(Request $request)
    {
        $favorite = self::_getFavoriteByPropertyAndCustomerId($request)->first();

        if ($favorite) {
            return response()->json(['favorite' => $favorite], 200);
        } else {
            return response()->json(['message' => 'favorite not found!'], 404);
        }

    }

    /**
     * Get one favorite.
     *
     * @return Response
     */
    public function deleteFavorite(Request $request)
    {
        $this->middleware('auth');

        $favorite = self::_getFavoriteByPropertyAndCustomerId($request)->first();

        if ($favorite) {
            try{
                $favorite->delete();

                return response()->json(['message' => 'favorite deleted!'], 200);
            } catch (\Exception $e) {

                return response()->json(['message' => 'favorite deletion failed!'], 500);
            }
        } else {
            return response()->json(['message' => 'favorite not found!'], 404);
        }

    }

    /**
     * Favorite subject
     * 
     * The favorite $favorite 
     * 
     * @param Favorite $favorite
     * 
     * @return void
     */

    private function _trashedRestore(Favorite $favorite) 
    {
        if ($favorite->trashed()) {
            $favorite->restore();
        } else {
            throw new \Exception('The favorite wasnt trashed so, error...');
        }        
    }

    /**
     * Favorite subject
     * 
     * The subject $subject 
     * 
     * @param string $subject
     * 
     * @return Favorite
     */

    private function _getFavoriteByPropertyAndCustomerId(Request $request) 
    {
        $propertyId = $request->input('property_id');
        $customerId = $request->input('customer_id');

        return Favorite::where('property_id', $propertyId)
            ->where('customer_id', $customerId);
    }

    /**
     * Get one favorite.
     * 
     * @param Request $request
     * 
     * @param Favorite $favorite
     * 
     * @return Favorite $favorite
     */
    private function _assembleFavorite(Request $request, Favorite $favorite = null) 
    {
        $favorite || ($favorite = new Favorite);
        $favorite->property_id = $request->input('property_id');
        $favorite->customer_id = $request->input('customer_id');

        return $favorite;
    }

    /**
     * Get one favorite.
     * 
     * @param Request $request
     * 
     * @return void
     */
    private function _favoriteValidation(Request $request) 
    {
        //validate incoming request 
        $validator = $this->validate(
            $request, [
            'property_id' => 'required|integer',
            'customer_id' => 'required|integer'
            ]
        );
    }

    /**
     * Get one favorite.
     * 
     * @param Request $request
     * 
     * @return void
     */
    private function _customerAndPropertyFavoriteValidation(Request $request) 
    {
        //validate incoming request 
        $validator = $this->validate(
            $request, [
            'property_ids' => 'required|json',
            'customer_id' => 'required|integer'
            ]
        );
    }

}