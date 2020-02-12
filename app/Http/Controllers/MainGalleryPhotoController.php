<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\MainGalleryPhoto;

class MainGalleryPhotoController extends Controller
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
     * Store a new MainGalleryPhoto.
     *
     * @param Request $request
     * @return Response
     */
    public function createMainGalleryPhoto(Request $request)
    {
        try {
            //validate incoming request 
            self::_mainGalleryPhotoValidation($request);

            try {
                // $mainGalleryPhoto = MainGalleryPhoto::create($request->all());
                $mainGalleryPhoto = self::_assembleMainGalleryPhoto($request);

                $mainGalleryPhoto->save();

                //return successful response
                return response()->json(['main_gallery_photo' => $mainGalleryPhoto, 'message' => 'CREATED'], 201);
            } catch (\Exception $e) {
                //return error message
                return response()->json(['message' => 'Home carousel Creation Failed!'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the home carousel data'], 400);
        }
    }

    /**
     * Get all MainGalleryPhoto.
     *
     * @return Response
     */
    public function getMainGalleryPhotos()
    {
        // TODO: use the query string to select the search criteria, result lenght, result page
        return response()->json(['main_gallery_photos' =>  self::_mainPhotoGalleryPropertiesJoin()->get()], 200);
    }

    /**
     * Get one MainGalleryPhoto.
     *
     * @return Response
     */
    public function getMainGalleryPhoto()
    {
        return response()->json(['main_gallery_photo' =>  self::_mainPhotoGalleryPropertiesJoin()->first()], 200);
    }

    /**
     * Update MainGalleryPhoto.
     *
     * @param Request $request
     * 
     * @return Response
     */
    public function updateMainGalleryPhoto(Request $request)
    {
        try {
            self::_mainGalleryPhotoValidation($request);

            $propertyCode = $request->input('property_code');

            $mainGalleryPhoto = MainGalleryPhoto::where('property_code', $propertyCode)->first();

            if ($mainGalleryPhoto) {
                try {
                    $mainGalleryPhoto = self::_assembleMainGalleryPhoto($request, $mainGalleryPhoto);

                    $mainGalleryPhoto->save();

                    return response()->json(['main_gallery_photo' => $mainGalleryPhoto], 200);
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
     * Get one MainGalleryPhoto.
     *
     * @return Response
     */
    public function deleteProperty(Request $request)
    {
        $propertyCode = $request->input('property_code');

        $mainGalleryPhoto = MainGalleryPhoto::where('property_code', $propertyCode)->first();

        if ($mainGalleryPhoto) {
            try {
                $mainGalleryPhoto->delete();

                return response()->json(['message' => 'home carousel deleted!'], 200);
            } catch (\Exception $e) {

                return response()->json(['message' => 'home carousel deletion failed!'], 500);
            }
        } else {
            return response()->json(['message' => 'home carousel not found!'], 404);
        }
    }

    /**
     * Get one MainGalleryPhoto.
     * 
     * @param Request $request
     * 
     * @param MainGalleryPhoto $mainGalleryPhoto
     * 
     * @return MainGalleryPhoto $mainGalleryPhoto
     */
    private function _assembleMainGalleryPhoto(Request $request, MainGalleryPhoto $mainGalleryPhoto = null)
    {
        $mainGalleryPhoto || ($mainGalleryPhoto = new MainGalleryPhoto);
        $mainGalleryPhoto->property_code = $request->input('property_code');

        return $mainGalleryPhoto;
    }

    /**
     * Get one MainGalleryPhoto.
     * 
     * @param Request $request
     * 
     * @return void
     */
    private function _mainGalleryPhotoValidation(Request $request)
    {
        //validate incoming request 
        $validator = $this->validate(
            $request,
            [
                'property_code' => 'required|string|max:255',
            ]
        );
    }

    private function _mainPhotoGalleryPropertiesJoin()
    {
        $MAX_MESSAGE_LENGTH = 60;

        return MainGalleryPhoto::join('properties', 'main_gallery_photo.property_code', '=', 'properties.code')
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
