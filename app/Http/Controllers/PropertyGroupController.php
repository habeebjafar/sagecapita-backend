<?php

namespace App\Http\Controllers;

use App\Favorite;
use App\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use App\PropertyGroup;
use App\Property;

class PropertyGroupController extends Controller
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
     * Store a new propertyGroup.
     *
     * @param Request $request
     * @return Response
     */
    public function createPropertyGroup(Request $request)
    {
        try {
            //validate incoming request 
            self::_propertyGroupValidation($request);

            try {
                // $propertyGroup = PropertyGroup::create($request->all());
                $propertyGroup = self::assemblePropertyGroup($request);

                $propertyGroup->save();

                //return successful response
                return response()->json(['property_group' => $propertyGroup, 'message' => 'CREATED'], 201);
            } catch (\Exception $e) {
                //return error message
                return response()->json(['message' => 'Property group Creation Failed!'], 409);
            }
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the property group data'], 400);
        }
    }

    /**
     * Get 6 months sold property data.
     *
     * @return Response
     */

    public function get6MonthsSoldPropertiesData()
    {
        $nowdate = (new \DateTime())->format('Y-m-d H:i:s');
        $last6Monthsdate
            = (new \DateTime('first day of this month - 6 months'))->format('Y-m-d ') . '00:00:00';

        $soldProperties = Property::select(\DB::raw('DATE_FORMAT(created_at, \'%b\') AS month'), \DB::raw('SUM(COALESCE(price, 0)) + SUM(COALESCE(price_lower_range, 0)) AS total'))
            ->whereNotNull('sold')
            ->whereBetween('created_at', [$last6Monthsdate, $nowdate]);

        $soldExclusiveProperties = Property::select(\DB::raw('DATE_FORMAT(created_at, \'%b\') AS month'), \DB::raw('SUM(COALESCE(price, 0)) + SUM(COALESCE(price_lower_range, 0)) AS total'))
            ->whereNotNull('is_exclusive')
            ->whereNotNull('sold')
            ->whereBetween('created_at', [$last6Monthsdate, $nowdate]);

        $user = Auth::guard('users')->user();

        if ($user->perms !== 0) {
            $agentId = $user->id;

            $soldProperties
                ->where('user_id', $agentId);

            $soldExclusiveProperties
                ->where('user_id', $agentId);
        }

        $soldProperties = $soldProperties->orderBy('created_at', 'ASC')
            ->groupBy('month')
            ->get();

        $soldExclusiveProperties = $soldExclusiveProperties->orderBy('created_at', 'ASC')
            ->groupBy('month')
            ->get();

        $exclusivePropertiesIndex = 0;
        $soldPropertiesMap = $soldProperties->mapWithKeys(
            function ($item) use (&$soldExclusiveProperties, &$exclusivePropertiesIndex) {
                if ($soldExclusiveProperties[$exclusivePropertiesIndex]->month === $item['month']) {
                    $exclusiveMonth
                        = $soldExclusiveProperties[$exclusivePropertiesIndex]->total;
                    $exclusivePropertiesIndex += 1;
                } else {
                    $exclusiveMonth = 0;
                }

                return [$item['month'] => [
                    +$item['total'],
                    +$exclusiveMonth
                ]];
            }
        );

        return response()->json(['sold_properties' => $soldPropertiesMap], 200);
    }

    /**
     * Get all city PropertyGroup.
     *
     * @return Response
     */
    public function getPropertyCityGroupListWithCount()
    {
        $cityGroup = self::_groupPropertySubject('city');

        $cityGroupMap = $cityGroup->mapWithKeys(
            function ($item) {
                return [$item['name'] => $item['count']];
            }
        );

        return response()->json(['cities_list' => $cityGroupMap], 200);
    }

    /**
     * Get property new months favorites and change.
     *
     * @return Response
     */
    public function getNewMonthsFavoritesAndChange()
    {
        $nowdate = (new \DateTime())->format('Y-m-d H:i:s');
        $thisMonthsFirstdate = (new \DateTime('first day of this month'))
            ->format('Y-m-d') . ' 00:00:00';
        $lastMonthsFirstdate = (new \DateTime('first day of last month'))
            ->format('Y-m-d') . ' 00:00:00';
        $lastMonthsLastdate = (new \DateTime('last day of last month'))
            ->format('Y-m-d') . ' 23:59:59';

        $monthsFavCount
            = Favorite::select(\DB::raw('COUNT(favorites.id) AS total'))
            ->whereBetween('favorites.created_at', [$thisMonthsFirstdate, $nowdate]);

        $lastMonthsFavCount
            = Favorite::select(\DB::raw('COUNT(favorites.id) AS total'))
            ->whereBetween('favorites.created_at', [$lastMonthsFirstdate, $lastMonthsLastdate]);

        $user = Auth::guard('users')->user();

        if ($user->perms !== 0) {
            $agentId = $user->id;

            $monthsFavCount
                ->join(
                    'properties',
                    function ($join) use ($agentId) {
                        $join->on('favorites.property_code', '=', 'properties.code')
                            ->where('properties.user_id', $agentId);
                    }
                );

            $lastMonthsFavCount
                ->join(
                    'properties',
                    function ($join) use ($agentId) {
                        $join->on('favorites.property_code', '=', 'properties.code')
                            ->where('properties.user_id', $agentId);
                    }
                );
        }

        $monthsFavTotal = $monthsFavCount->first()->total;
        $lastMonthsFavTotal = $lastMonthsFavCount->first()->total;

        return response()->json(['total' => $monthsFavTotal, 'change' => $lastMonthsFavTotal ? ((($monthsFavTotal - $lastMonthsFavTotal) / $lastMonthsFavTotal) * 100) : 100], 200);
    }

    /**
     * Get property new months favorites and change.
     *
     * @return Response
     */
    public function getNewMonthsMessagesAndChange()
    {
        $nowdate = (new \DateTime())->format('Y-m-d H:i:s');
        $thisMonthsFirstdate = (new \DateTime('first day of this month'))
            ->format('Y-m-d') . ' 00:00:00';
        $lastMonthsFirstdate = (new \DateTime('first day of last month'))
            ->format('Y-m-d') . ' 00:00:00';
        $lastMonthsLastdate = (new \DateTime('last day of last month'))
            ->format('Y-m-d') . ' 23:59:59';

        $monthsMsgCount
            = Message::select(\DB::raw('COUNT(messages.id) AS total'))
            ->whereBetween('messages.created_at', [$thisMonthsFirstdate, $nowdate]);

        $lastMonthsMsgCount
            = Message::select(\DB::raw('COUNT(messages.id) AS total'))
            ->whereBetween('messages.created_at', [$lastMonthsFirstdate, $lastMonthsLastdate]);

        $user = Auth::guard('users')->user();

        if ($user->perms !== 0) {
            $agentId = $user->id;

            $monthsMsgCount
                ->join(
                    'properties',
                    function ($join) use ($agentId) {
                        $join->on('messages.property_code', '=', 'properties.code')
                            ->where('properties.user_id', $agentId);
                    }
                );

            $lastMonthsMsgCount
                ->join(
                    'properties',
                    function ($join) use ($agentId) {
                        $join->on('messages.property_code', '=', 'properties.code')
                            ->where('properties.user_id', $agentId);
                    }
                );
        }

        $monthsMsgTotal = $monthsMsgCount->first()->total;
        $lastMonthsMsgTotal = $lastMonthsMsgCount->first()->total;

        return response()->json(['total' => $monthsMsgTotal, 'change' => $lastMonthsMsgTotal ? ((($monthsMsgTotal - $lastMonthsMsgTotal) / $lastMonthsMsgTotal) * 100) : 100], 200);
    }

    /**
     * Get property sold total ratio.
     *
     * @return Response
     */
    public function getPropertySoldTotalRatio()
    {
        $soldPropertiesTotalRatio
            = Property::select(\DB::raw('COUNT(id) AS total'), \DB::raw('SUM(COALESCE(sold, 0)) AS sold'));

        $user = Auth::guard('users')->user();

        if ($user->perms !== 0) {
            $agentId = $user->id;

            $soldPropertiesTotalRatio
                ->where('user_id', $agentId);
        }

        $soldPropertiesTotalRatio = $soldPropertiesTotalRatio->first();

        return response()->json(['sold' => +$soldPropertiesTotalRatio->sold, 'total' => $soldPropertiesTotalRatio->total], 200);
    }

    /**
     * Get all PropertyGroup.
     *
     * @return Response
     */
    public function getPropertyGroupsListWithCount()
    {
        //suburb
        $suburbGroup = self::_groupPropertySubject('suburb');

        //city
        $cityGroup = self::_groupPropertySubject('city');

        //state
        $stateGroup = self::_groupPropertySubject('state');

        //type
        $typeGroup = self::_groupPropertySubject('type');

        //merge
        $groupCollection = new Collection();
        $groupCollection->put('suburb', $suburbGroup);
        $groupCollection->put('city', $cityGroup);
        $groupCollection->put('state', $stateGroup);
        $groupCollection->put('type', $typeGroup);

        //merge group count
        $groupMergeCount = $groupCollection->map(
            function ($groupItem, $key) {
                return $groupItem->mapWithKeys(
                    function ($item) {
                        return [$item['name'] => $item['count']];
                    }
                );
            }
        );

        return response()->json(['groups_list' => $groupMergeCount], 200);
    }

    /**
     * Get all PropertyGroup.
     *
     * @return Response
     */
    public function getPropertyGroupsList()
    {
        $propertyGroups = PropertyGroup::all();

        if ($propertyGroups->count()) {
            $video_count
                = Property::select(\DB::raw('count(*) AS count'))
                ->whereNotNull('video')
                ->first();

            $groupedPropertyGroups = $propertyGroups->mapToGroups(
                function ($item, $key) {
                    return [$item['class'] => $item['name']];
                }
            );

            return response()->json(['property_groups_list' => $groupedPropertyGroups, 'video_count' => $video_count->count], 200);
        } else {
            return response()->json(['message' => 'No property group was found!'], 404);
        }
    }

    /**
     * Get all PropertyGroup.
     *
     * @return Response
     */
    public function getPropertyGroups()
    {
        // TODO: use the query string to select the search criteria, result lenght, result page
        return response()->json(['property_groups' =>  PropertyGroup::all()], 200);
    }

    /**
     * Check if propertyGroup exists.
     * 
     * @param Request $request
     *
     * @return Response
     */
    public function propertyGroupExists(Request $request)
    {
        $name = $request->input('name');
        $class = $request->input('class');

        if (PropertyGroup::where('name', $name)->where('class', $class)->exists()) {
            return response()->json(['message' => 'Property group exists'], 200);
        } else {
            return response()->json(['message' => 'Property group not found'], 404);
        }
    }

    /**
     * Get one propertyGroup.
     *
     * @return Response
     */
    public function getProperty(Request $request)
    {
        $name = $request->input('name');
        $class = $request->input('class');

        $propertyGroup
            = PropertyGroup::where('name', $name)
            ->where('class', $class)
            ->first();

        if ($propertyGroup) {
            return response()->json(['property_group' => $propertyGroup], 200);
        } else {
            return response()->json(['message' => 'propertyGroup not found!'], 404);
        }
    }

    /**
     * Update propertyGroup.
     *
     * @param Request $request
     * 
     * @return Response
     */
    public function updatePropertyGroup(Request $request)
    {
        try {
            self::_propertyGroupValidation($request);

            $name = $request->input('name');
            $class = $request->input('class');

            $propertyGroup = PropertyGroup::where('name', $name)->where('class', $class)->first();

            if ($propertyGroup) {
                try {
                    $propertyGroup = self::_assembleGroupProperty($request, $propertyGroup);

                    $propertyGroup->save();

                    return response()->json(['propertyGroup' => $propertyGroup], 200);
                } catch (\Exception $e) {

                    return response()->json(['message' => 'propertyGroup update failed!'], 500);
                }
            } else {
                return response()->json(['message' => 'propertyGroup not found!'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the propertyGroup data'], 400);
        }
    }

    /**
     * Get one propertyGroup.
     *
     * @return Response
     */
    public function deleteProperty(Request $request)
    {
        $name = $request->input('name');
        $class = $request->input('class');

        $propertyGroup = PropertyGroup::where('name', $name)->where('class', $class)->first();

        if ($propertyGroup) {
            try {
                $propertyGroup->delete();

                return response()->json(['message' => 'property group deleted!'], 200);
            } catch (\Exception $e) {

                return response()->json(['message' => 'property group deletion failed!'], 500);
            }
        } else {
            return response()->json(['message' => 'property group not found!'], 404);
        }
    }

    /**
     * Group property subject
     * 
     * The subject $subject 
     * 
     * @param string $subject
     * 
     * @return Property
     */

    private function _groupPropertySubject(string $subject)
    {
        $propertySubjectGroup = Property
            ::select($subject . ' AS name', \DB::raw('count(id) AS count'))
            ->whereNull('sold');


        $usersGuard = Auth::guard('users');

        if ($usersGuard->check()) {
            $user = $usersGuard->user();

            if ($user->perms !== 0) {
                $agentId = $user->id;

                $propertySubjectGroup
                    ->where('user_id', $agentId);
            }
        }

        return $propertySubjectGroup->orderBy('count', 'DESC')
            ->groupBy('name')
            ->get();
    }

    /**
     * Get one propertyGroup.
     * 
     * @param Request $request
     * 
     * @param PropertyGroup $propertyGroup
     * 
     * @return PropertyGroup $propertyGroup
     */
    private function _assembleGroupProperty(Request $request, PropertyGroup $propertyGroup = null)
    {
        $propertyGroup || ($propertyGroup = new PropertyGroup);
        $propertyGroup->photo = $request->input('photo');
        $propertyGroup->photos = $request->input('photos');
        $propertyGroup->video = $request->input('video');
        $propertyGroup->main_title = $request->input('main_title');
        $propertyGroup->side_title = $request->input('side_title');
        $propertyGroup->heading_title = $request->input('heading_title');
        $propertyGroup->description_text = $request->input('description_text');
        $propertyGroup->state = $request->input('state');
        $propertyGroup->city = $request->input('city');
        $propertyGroup->suburb = $request->input('suburb');
        $propertyGroup->type = $request->input('type');
        $propertyGroup->interior_surface = $request->input('interior_surface');
        $propertyGroup->exterior_surface = $request->input('exterior_surface');
        $propertyGroup->features = $request->input('features');
        $is_exclusive = $request->input('is_exclusive');
        $is_exclusive && ($propertyGroup->is_exclusive = $is_exclusive);
        $price = $request->input('price');
        $price && ($propertyGroup->price = $price);
        $price_lower_range = $request->input('price_lower_range');
        $price_lower_range && ($propertyGroup->price_lower_range = $price_lower_range);
        $price_upper_range = $request->input('price_upper_range');
        $price_upper_range && ($propertyGroup->price_upper_range = $price_upper_range);

        return $propertyGroup;
    }

    /**
     * Get one propertyGroup.
     * 
     * @param Request $request
     * 
     * @return void
     */
    private function _propertyGroupValidation(Request $request)
    {
        //validate incoming request 
        $validator = $this->validate(
            $request,
            [
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
            ]
        );
    }
}
