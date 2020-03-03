<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Property;
use App\PropertyGroup;
use App\Favorite;
use App\View;
use Illuminate\Support\Facades\Auth;

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
        try {
            //validate incoming request 
            self::createPropertyValidation($request);

            try {
                // $property = Property::create($request->all());
                $property = self::assembleProperty($request);

                $property->save();

                //return successful response
                return response()->json(['property' => $property, 'message' => 'CREATED'], 201);
            } catch (\Exception $e) {
                //return error message
                return response()->json(['message' => 'Property Creation Failed!'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the property data'], 400);
        }
    }

    /**
     * Get all Property.
     * 
     * @param  Request  $request
     *
     * @return Response
     */
    public function getProperties(Request $request)
    {
        try {
            // \DB::enableQueryLog(); // Enable query log

            //page is already taken care of
            $perPage = $request->input('per_page') ?? 4;
            $orderByCol = $request->input('order_by_col');
            $orderByDir = $request->input('order_by_dir');
            $isExclusive = $request->input('is_exclusive');
            $whereCity = $request->input('city');
            $whereState = $request->input('state');
            $whereSuburb = $request->input('suburb');
            $whereType = $request->input('type');

            $wherePrice = null;

            $video = !!$request->input('video');
            $sold = $request->input('sold');

            $_price = $request->input('price');

            if ($_price) {
                $_price_tokens = explode(' ', $_price);

                switch (count($_price_tokens)) {
                    case 2:
                        $wherePrice = [
                            [
                                self::_expandOperator($_price_tokens[0]),
                                $_price_tokens[1]
                            ]
                        ];
                        break;
                    case 3:
                        $doubleOperators
                            = self::_doubleExpandOperator($_price_tokens[1]);

                        $wherePrice = [
                            [
                                $doubleOperators[0],
                                $_price_tokens[0]
                            ], [
                                $doubleOperators[1],
                                $_price_tokens[2]
                            ]
                        ];
                        break;
                    default:
                        throw new \Exception('Problem with price input');
                        break;
                }
            }

            $properties = self::selectPropertyLargeThumbnailFields();

            $usersGuard = Auth::guard('users');

            if ($usersGuard->check()) {
                $user = $usersGuard->user();

                if ($user->perms !== 0) {
                    $agentId = $user->id;

                    $properties
                        ->where('user_id', $agentId);
                }
            }

            if ($orderByCol && $orderByDir) {
                if ($orderByCol === 'price') {
                    $properties
                        ->orderBy('max_price', $orderByDir);
                } else {
                    $properties->orderBy($orderByCol, $orderByDir);
                }
            }

            $properties->orderBy('id', 'DESC');

            if ($isExclusive) {
                switch ($isExclusive) {
                    case '1':
                        $properties->whereNotNull('is_exclusive');
                        break;
                    case '2':
                        $properties->whereNull('is_exclusive');
                        break;
                }
            }

            if ($video) {
                $properties->whereNotNull('video');
            }


            if ($sold) {
                switch ($sold) {
                    case '1':
                        $properties->whereNotNull('sold_at');
                        break;
                    case '2':
                        $properties->whereNull('sold_at');
                        break;
                }
            }

            $whereOrArray = [];

            if ($whereCity) {
                $whereOrArray['city'] = $whereCity;
            }

            if ($whereState) {
                $whereOrArray['state'] = $whereState;
            }

            if ($whereSuburb) {
                $whereOrArray['suburb'] = $whereSuburb;
            }

            if ($whereType) {
                $whereOrArray['type'] = $whereType;
            }

            if (count($whereOrArray)) {
                $properties->where(
                    function ($query) use ($whereOrArray) {
                        foreach ($whereOrArray as $field => $fieldValue) {
                            $query->orWhere($field, $fieldValue);
                        }
                    }
                );
            }

            if ($wherePrice) {
                foreach ($wherePrice as $wherePriceRow) {
                    $properties->whereRaw(
                        \DB::raw(
                            '(' .
                                'CASE WHEN price IS NOT NULL ' .
                                'THEN price ' . $wherePriceRow[0] . ' ' . $wherePriceRow[1] . ' ' .
                                'WHEN price_lower_range IS NOT NULL AND price_upper_range IS NOT NULL ' .
                                'THEN price_lower_range ' . $wherePriceRow[0] . ' ' . $wherePriceRow[1] . ' OR ' .
                                'price_upper_range ' . $wherePriceRow[0] . ' ' . $wherePriceRow[1] . ' ' .
                                'END)'
                        )
                    );
                }

                // $properties->where(
                //     function ($query) use ($wherePrice) {
                //         foreach ($wherePrice as $wherePriceRow) {
                //             $query->orWhere(
                //                 function ($query) use ($wherePriceRow) {
                //                     $query
                //                         ->whereNotNull('price')
                //                         ->where('price', $wherePriceRow[0], $wherePriceRow[1]);
                //                 }
                //             )->orWhere(
                //                 function ($query) use ($wherePriceRow) {
                //                     $fields = [
                //                         'price_lower_range',
                //                         'price_upper_range'
                //                     ];

                //                     foreach ($fields as $field) {
                //                         $query
                //                             ->whereNotNull($field);
                //                     }

                //                     $query
                //                         ->where(
                //                             function ($query) use ($fields, $wherePriceRow) {
                //                                 foreach ($fields as $field) {
                //                                     $query->orWhere(
                //                                         function ($query) use ($wherePriceRow, $field) {
                //                                             $query
                //                                                 ->where($field, $wherePriceRow[0], $wherePriceRow[1]);
                //                                         }
                //                                     );
                //                                 }
                //                             }
                //                         );
                //                 }
                //             );
                //         }
                //     }
                // );
            }

            // $properties->get();
            // dd(\DB::getQueryLog()); // Show results of log

            return response()->json(['properties' =>  $properties->paginate($perPage)], 200);
        } catch (\Exception $e) {

            return response()->json(['message' =>  'There\'s is a problem with the input'], 400);
        }
    }

    /**
     * Get all Favorited Properties.
     * 
     * @param  Request  $request
     *
     * @return Response
     */
    public function getFavorites(Request $request)
    {
        try {
            // \DB::enableQueryLog(); // Enable query log

            //page is already taken care of
            $perPage = $request->input('per_page') ?? 4;
            $orderByCol = $request->input('order_by_col');
            $orderByDir = $request->input('order_by_dir');
            $whereCity = $request->input('city');
            $whereState = $request->input('state');
            $whereSuburb = $request->input('suburb');
            $whereType = $request->input('type');

            $wherePrice = null;

            // $video = !!$request->input('video');

            $_price = $request->input('price');

            if ($_price) {
                $_price_tokens = explode(' ', $_price);

                switch (count($_price_tokens)) {
                    case 2:
                        $wherePrice = [
                            [
                                self::_expandOperator($_price_tokens[0]),
                                $_price_tokens[1]
                            ]
                        ];
                        break;
                    case 3:
                        $doubleOperators
                            = self::_doubleExpandOperator($_price_tokens[1]);

                        $wherePrice = [
                            [
                                $doubleOperators[0],
                                $_price_tokens[0]
                            ], [
                                $doubleOperators[1],
                                $_price_tokens[2]
                            ]
                        ];
                        break;
                    default:
                        throw new \Exception('Problem with price input');
                        break;
                }
            }

            $customersAuth = Auth::guard('customers');

            $properties =
                Favorite::join('properties', 'favorites.property_code', '=', 'properties.code')
                ->select(
                    'properties.code AS code',
                    'properties.video AS video',
                    'properties.interior_surface AS interior_surface',
                    'properties.photo AS photo',
                    'properties.main_title AS main_title',
                    'properties.price AS price',
                    'properties.price_upper_range AS price_upper_range',
                    'properties.price_lower_range AS price_lower_range',
                    'properties.is_exclusive AS is_exclusive',
                    \DB::raw('CASE WHEN properties.price IS NOT NULL THEN properties.price ELSE properties.price_upper_range END AS max_price'),
                    'properties.city AS city',
                    'properties.updated_at AS updated_at',
                    'properties.type AS type',
                    \DB::raw('true AS is_favorite')
                )
                ->where('customer_id', $customersAuth->user()->id)
                ->whereNull('sold_at');

            if ($orderByCol && $orderByDir) {
                if ($orderByCol === 'price') {
                    $properties
                        ->orderBy('max_price', $orderByDir);
                } else {
                    $properties->orderBy($orderByCol, $orderByDir);
                }
            }

            $whereOrArray = [];

            if ($whereCity) {
                $whereOrArray['city'] = $whereCity;
            }

            if ($whereState) {
                $whereOrArray['state'] = $whereState;
            }

            if ($whereSuburb) {
                $whereOrArray['suburb'] = $whereSuburb;
            }

            if ($whereType) {
                $whereOrArray['type'] = $whereType;
            }

            if (count($whereOrArray)) {
                $properties->where(
                    function ($query) use ($whereOrArray) {
                        foreach ($whereOrArray as $field => $fieldValue) {
                            $query->orWhere($field, $fieldValue);
                        }
                    }
                );
            }

            if ($wherePrice) {
                foreach ($wherePrice as $wherePriceRow) {
                    $properties->whereRaw(
                        \DB::raw(
                            '(' .
                                'CASE WHEN price IS NOT NULL ' .
                                'THEN price ' . $wherePriceRow[0] . ' ' . $wherePriceRow[1] . ' ' .
                                'WHEN price_lower_range IS NOT NULL AND price_upper_range IS NOT NULL ' .
                                'THEN price_lower_range ' . $wherePriceRow[0] . ' ' . $wherePriceRow[1] . ' OR ' .
                                'price_upper_range ' . $wherePriceRow[0] . ' ' . $wherePriceRow[1] . ' ' .
                                'END)'
                        )
                    );
                }
            }

            // $properties->get();
            // dd(\DB::getQueryLog()); // Show results of log

            return response()->json(['properties' =>  $properties->paginate($perPage)], 200);
        } catch (\Exception $e) {
            echo $e;
            return response()->json(['message' =>  'There\'s is a problem with the input'], 400);
        }
    }

    /**
     * Check if property exists.
     *
     * @return Response
     */
    public function propertyExists($code)
    {
        if (Property::where('code', $code)->exists()) {
            return response()->json(['message' => 'Property exists'], 200);
        } else {
            return response()->json(['message' => 'Property not found'], 404);
        }
    }

    /**
     * Get properties top stats.
     *
     * @return Response
     */
    public function getPropertiesTopStats()
    {
        try {
            $propertyTopStats = Property::select(
                \DB::raw('SUM(CASE WHEN sold_at IS NOT NULL THEN 1 ELSE 0 END) AS sold'),
                \DB::raw('SUM(COALESCE(is_exclusive, 0)) AS exclusive')
            );

            $todayDate = (new \DateTime('now',  new \DateTimeZone('UTC')))->format('Y-m-d');
            $thisMonthsFirstDate
                = (new \DateTime('first day of this month', new \DateTimeZone('UTC')))->format('Y-m-d');

            $totalTransactions = Property::select(
                \DB::raw('SUM(COALESCE(NULLIF(price, 0), price_lower_range, price_upper_range, 0)) AS transactions')
            )
                ->whereNotNull('sold_at');

            $monthsViews = View::select(
                \DB::raw('COUNT(views.id) AS views')
            )
                // ->whereRaw('MONTH(views.created_at) = ?', [date('n')])
                // ->whereRaw('YEAR(views.created_at) = ?', [date('Y')]);
                ->whereBetween('views.created_at', [$thisMonthsFirstDate, $todayDate]);

            $daysTransactions = Property::select(
                \DB::raw('SUM(COALESCE(NULLIF(price, 0), price_lower_range, price_upper_range, 0)) AS transaction')
            )
                //->whereNotNull('sold_at')
                // ->whereRaw('DAY(created_at) = ?', [date('j')])
                // ->whereRaw('MONTH(created_at) = ?', [date('n')])
                // ->whereRaw('YEAR(created_at) = ?', [date('Y')]);
                ->whereBetween('sold_at', [$todayDate . ' 00:00:00', $todayDate . ' 23:59:59']);

            $user = Auth::guard('users')->user();

            if ($user->perms !== 0) {
                $agentId = $user->id;

                $propertyTopStats
                    ->where('user_id', $agentId);

                $totalTransactions
                    ->where('user_id', $agentId);

                $monthsViews
                    ->join(
                        'properties',
                        function ($join) use ($agentId) {
                            $join->on('views.property_code', '=', 'properties.code')
                                ->where('properties.user_id', $agentId);
                        }
                    );

                $daysTransactions
                    ->where('user_id', $agentId);
            }

            $propertyTopStats = $propertyTopStats->first();
            $totalTransactions = $totalTransactions->first();
            $monthsViews = $monthsViews->first();
            $daysTransactions = $daysTransactions->first();

            return response()->json(['sold' => +$propertyTopStats->sold, 'exclusive' => +$propertyTopStats->exclusive, 'transactions' => +$totalTransactions->transactions, 'months_views' => $monthsViews->views, 'days_transactions' => +$daysTransactions->transaction], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Problem getting stats'], 500);
        }
    }

    /**
     * Get properties top stats.
     *
     * @return Response
     */
    public function get30DaysPerfomance()
    {
        try {
            $unsoldProperties = Property::select(
                \DB::raw('COUNT(id) AS unsold')
            )->whereNull('sold_at');

            $nowdate = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
            $last30daysDate
                = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('-30 days')->format('Y-m-d ') . '00:00:00';

            $last30daysViews = View::select(
                \DB::raw('COUNT(views.id) AS views')
            )->whereBetween('views.created_at', [$last30daysDate, $nowdate]);

            $user = Auth::guard('users')->user();

            if ($user->perms !== 0) {
                $agentId = $user->id;

                $unsoldProperties
                    ->where('user_id', $agentId);

                $last30daysViews
                    ->join(
                        'properties',
                        function ($join) use ($agentId) {
                            $join->on('views.property_code', '=', 'properties.code')
                                ->where('properties.user_id', $agentId);
                        }
                    );
            }

            $unsoldProperties = $unsoldProperties->first();
            $last30daysViews = $last30daysViews->first();

            if (!$last30daysViews->views) {
                $perf = 0;
            } else if (!$unsoldProperties->unsold) {
                $perf = 15;
            } else {
                $perf = $last30daysViews->views / $unsoldProperties->unsold;
            }

            return response()->json(['perf' => $perf > 7.5 ? $perf > 15 ? 2 : 1 : 0], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Problem getting info'], 500);
        }
    }

    /**
     * Get properties stats.
     *
     * @return Response
     */
    public function getPropertiesStats()
    {
        try {
            $propertyStats = Property::select(
                \DB::raw('COUNT(id) AS properties'),
                \DB::raw('SUM(COALESCE(views, 0)) AS views'),
                \DB::raw('SUM(COALESCE(inquiries, 0)) AS requests')
            );

            $favorites
                = Favorite::select(\DB::raw('count(favorites.id) AS favorites'));

            $user = Auth::guard('users')->user();

            if ($user->perms !== 0) {
                $agentId = $user->id;

                $propertyStats
                    ->where('user_id', $agentId);

                $favorites
                    ->join(
                        'properties',
                        function ($join) use ($agentId) {
                            $join->on('favorites.property_code', '=', 'properties.code')
                                ->where('properties.user_id', $agentId);
                        }
                    );
            }

            $propertyStats = $propertyStats->first();
            $favorites = $favorites->first();

            return response()->json(['properties' => $propertyStats->properties, 'views' => +$propertyStats->views, 'requests' => +$propertyStats->requests, 'favorites' => $favorites->favorites], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Problem getting stats'], 500);
        }
    }

    /**
     * get sold properties.
     * 
     */
    public function getSoldProperties()
    {
        $soldProperties
            = self::selectPropertyGalleryThumbnailFields()
            ->whereNotNull('sold_at')
            ->latest('id')
            ->paginate(3);

        if ($soldProperties) {
            return response()->json(['properties' => $soldProperties], 200);
        } else {
            return response()->json(['message' => 'No sold properties yet'], 404);
        }
    }

    /**
     * get recently uploaded properties.
     * 
     */
    public function getRecentlyUploadedProperties()
    {
        $recentlyUploadedProperties
            = self::selectPropertyGalleryThumbnailFields()
            ->whereNull('sold_at')
            ->latest('id')
            ->paginate(3);

        if ($recentlyUploadedProperties) {
            return response()->json(['properties' => $recentlyUploadedProperties], 200);
        } else {
            return response()->json(['message' => 'Property not found'], 404);
        }
    }

    /**
     * get most seen properties.
     * 
     */
    public function getMostSeenProperties()
    {
        $mostSeenProperties
            = self::selectPropertyGalleryThumbnailFields()
            ->whereNull('sold_at')->latest('views')->paginate(3);

        if ($mostSeenProperties) {
            return response()->json(['properties' => $mostSeenProperties], 200);
        } else {
            return response()->json(['message' => 'Property not found'], 404);
        }
    }

    /**
     * get viewed properties.
     * 
     */
    public function getViewedProperties()
    {
        $viewedProperties
            = self::selectPropertyThumbnailFields()->whereNull('sold_at')
            ->latest('views')->paginate(4);

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
            = self::selectPropertyThumbnailFields()
            ->whereNull('sold_at')->latest('id')->paginate(4);

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
            = self::selectPropertyThumbnailFields()
            ->whereNull('sold_at')->where('is_exclusive', '!=', null)->paginate(4);

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
            = self::selectPropertyThumbnailFields()->whereNull('sold_at')
            ->latest('views')->latest('inquiries')->paginate(3);

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
    public function getProperty(string $code)
    {
        $property = Property::join('users', 'properties.user_id', '=', 'users.id')
            ->select('properties.*', 'users.first_name')
            ->find($code);

        if ($property) {
            //We should increment property count regardless of whether the customer viewing is logged in or not
            //if (Auth::guard('customers')->check()) {
            self::incrementViews($property);
            //}

            return response()->json(['property' => $property], 200);
        } else {
            return response()->json(['message' => 'property not found!'], 404);
        }
    }

    /**
     * Get total properties  .
     *
     * @param Request $request
     * @return Response
     */
    public function getTotalProperties(Request $request)
    {
        $properties
            = Property::select(\DB::raw('count(id) AS count'))
            ->whereNull('sold_at')
            ->first();

        if ($properties) {
            return response()->json(['properties_count' => $properties->count], 200);
        } else {
            return response()->json(['message' => 'No properties found!'], 404);
        }
    }

    /**
     * Update property.
     * 
     * @param String  $code    - property code
     *
     * @param Request $request - hyyyyy
     * 
     * @return Response
     */
    public function updateProperty($code, Request $request)
    {
        try {
            self::updatePropertyValidation($request);

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
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the property data'], 400);
        }
    }

    /**
     * Update property video.
     * 
     * @param String  $code    - property code
     *
     * @param Request $request - hyyyyy
     * 
     * @return Response
     */
    public function updatePropertyVideo($code, Request $request)
    {
        try {
            self::updatePropertyVideoValidation($request);

            try {
                $property = Property::findOrFail($code);

                try {
                    $property = self::assemblePropertyVideo($request, $property);

                    $property->save();

                    return response()->json(['property' => $property], 200);
                } catch (\Exception $e) {

                    return response()->json(['message' => 'property video update failed!'], 500);
                }
            } catch (\Exception $e) {

                return response()->json(['message' => 'property video not found!'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the property video data'], 400);
        }
    }

    /**
     * Update property brochure.
     * 
     * @param String  $code    - property code
     *
     * @param Request $request - hyyyyy
     * 
     * @return Response
     */
    public function updatePropertyBrochure($code, Request $request)
    {
        try {
            self::updatePropertyBrochureValidation($request);

            try {
                $property = Property::findOrFail($code);

                try {
                    $property = self::assemblePropertyBrochure($request, $property);

                    $property->save();

                    return response()->json(['property' => $property], 200);
                } catch (\Exception $e) {

                    return response()->json(['message' => 'property brochure update failed!'], 500);
                }
            } catch (\Exception $e) {

                return response()->json(['message' => 'property brochure not found!'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the property brochure data'], 400);
        }
    }

    /**
     * Update property sold.
     * 
     * @param String  $code    - property code
     * 
     * @return Response
     */
    public function updatePropertySold($code)
    {
        try {
            $property = Property::findOrFail($code);

            try {
                $property->sold_at = gmdate("Y-m-d H:i:s");

                $property->save();

                return response()->json(['property' => $property], 200);
            } catch (\Exception $e) {

                return response()->json(['message' => 'property sold update failed!'], 500);
            }
        } catch (\Exception $e) {

            return response()->json(['message' => 'property sold not found!'], 404);
        }
    }

    /**
     * Update property unsold.
     * 
     * @param String  $code    - property code
     * 
     * @return Response
     */
    public function updatePropertyUnsold($code)
    {
        try {
            $property = Property::findOrFail($code);

            try {
                $property->sold_at = null;

                $property->save();

                return response()->json(['property' => $property], 200);
            } catch (\Exception $e) {

                return response()->json(['message' => 'property unsold update failed!'], 500);
            }
        } catch (\Exception $e) {

            return response()->json(['message' => 'property unsold not found!'], 404);
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

            try {
                $property->delete();

                return response()->json(['message' => 'property deleted!'], 200);
            } catch (\Exception $e) {

                return response()->json(['message' => 'property deletion failed!'], 500);
            }
        } catch (\Exception $e) {

            return response()->json(['message' => 'property not found!'], 404);
        }
    }

    private function _expandOperator($operator)
    {
        switch ($operator) {
            case 'LT':
                return '<';
            case 'LTE':
                return '<=';
            case 'GT':
                return '>';
            case 'GTE':
                return '>=';
            case 'EQ':
                return '=';
            case 'NEQ':
                return '!=';
            default:
                throw new \Exception('Unknown operator');
        }
    }

    private function _doubleExpandOperator($operator)
    {
        switch ($operator) {
            case 'GTE':
                return ['>', '<='];
            case 'GT':
                return ['>', '<'];
            default:
                throw new \Exception('Unknown doubleable operator');
        }
    }

    private function topPropertiesPagination($column, $perPage, $routeName)
    {

        $page = LengthAwarePaginator::resolveCurrentPage();
        $options = ['path' => url('api/' . $routeName)];
        $topProperties
            = Property::select($column . ' AS name', \DB::raw('(SUM(COALESCE(views, 0)) + SUM(COALESCE(inquiries, 0))) AS sumOfRequests'), \DB::raw('count(id) AS count'))
            ->whereNull('sold_at')
            ->orderBy('sumOfRequests', 'DESC')
            ->groupBy('name')
            ->get();

        $slicedTopProperties = $topProperties
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

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
        \DB::transaction(
            function () use ($property) {
                $view = new View;
                $view->property_code = $property->code;
                $view->save();

                $property->views += 1;

                $property->save();
            }
        );
    }

    private function incrementInquiries(Property $property)
    {
        $property->inquiries += 1;

        $property->save();
    }

    private function selectPropertyLargeThumbnailFields()
    {
        $MAX_MESSAGE_LENGTH = 60;

        return Property::select(
            'code',
            'video',
            'interior_surface',
            'photo',
            'main_title',
            \DB::raw('(select case when length(description_text) > ' . $MAX_MESSAGE_LENGTH . ' then concat(substring(description_text, 1, ' . $MAX_MESSAGE_LENGTH . '), \'...\') else description_text end) as description_text'),
            'price',
            'price_upper_range',
            'price_lower_range',
            'is_exclusive',
            \DB::raw('CASE WHEN price IS NOT NULL THEN price ELSE price_upper_range END AS max_price'),
            'city',
            'updated_at',
            'sold_at',
            'type'
        );
    }

    private function selectPropertyGalleryThumbnailFields()
    {
        return Property::select('code', 'photo', 'suburb', 'city', 'state', 'views', 'created_at', 'price', 'price_upper_range', 'price_lower_range', 'is_exclusive');
    }

    private function selectPropertyThumbnailFields()
    {
        return Property::select('code', 'photo', 'main_title', 'price', 'price_upper_range', 'price_lower_range', 'is_exclusive', 'suburb', 'city', 'state');
    }

    private function assembleProperty(Request $request, Property $property = null)
    {
        $property || ($property = new Property);
        $photo = $request->input('photo');
        $photo && ($property->photo = $photo);
        $photos = $request->input('photos');
        $photos && ($property->photos = $request->input('photos'));
        $video = $request->input('video');
        $video && ($property->video = $request->input('video'));
        $brochure = $request->input('brochure');
        $brochure && ($property->brochure = $request->input('brochure'));
        $property->main_title = $request->input('main_title');
        $property->side_title = $request->input('side_title');
        $property->heading_title = $request->input('heading_title');
        $property->description_text = $request->input('description_text');
        $property->country = strtoupper($request->input('country'));
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
        $price_lower_range = $request->input('price_lower_range');
        $price_upper_range = $request->input('price_upper_range');

        if ($price) {
            $property->price = $price;
            $property->price_lower_range = null;
            $property->price_upper_range = null;
        } else {
            $property->price = null;
            $property->price_lower_range = $price_lower_range;
            $property->price_upper_range = $price_upper_range;
        }

        return $property;
    }

    private function assemblePropertyVideo(Request $request, Property $property = null)
    {
        $property || ($property = new Property);
        $property->video = $request->input('video');

        return $property;
    }

    private function assemblePropertyBrochure(Request $request, Property $property = null)
    {
        $property || ($property = new Property);
        $property->brochure = $request->input('brochure');

        return $property;
    }

    private function createPropertyValidation(Request $request)
    {
        //validate incoming request 
        $validator = $this->validate($request, [
            'photo' => 'required|string|max:100',
            'photos' => 'required|json',
            'video' => 'string|max:100',
            'brochure' => 'string|max:100',
            'main_title' => 'required|string|max:150',
            'side_title' => 'required|string|max:150',
            'heading_title' => 'required|string|max:150',
            'description_text' => 'required|string|max:1000',
            'country' => 'required|string|size:2',
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

    private function updatePropertyVideoValidation(Request $request)
    {
        //validate incoming request 
        $validator = $this->validate($request, [
            'video' => 'string|max:100'
        ]);
    }

    private function updatePropertyBrochureValidation(Request $request)
    {
        //validate incoming request 
        $validator = $this->validate($request, [
            'brochure' => 'string|max:100'
        ]);
    }

    private function updatePropertyValidation(Request $request)
    {
        //validate incoming request 
        $validator = $this->validate($request, [
            'photo' => 'string|max:100',
            'photos' => 'json',
            'video' => 'string|max:100',
            'brochure' => 'string|max:100',
            'main_title' => 'required|string|max:150',
            'side_title' => 'required|string|max:150',
            'heading_title' => 'required|string|max:150',
            'description_text' => 'required|string|max:1000',
            'country' => 'required|string|size:2',
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
