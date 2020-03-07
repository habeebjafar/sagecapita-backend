<?php

namespace App\Http\Controllers;

use App\Favorite;
use App\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use App\PropertyGroup;
use App\Property;
use Illuminate\Auth\Access\AuthorizationException;
use App\Helpers\UnauthorizedHelper;

class PropertyGroupController extends Controller
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
     * Store a new propertyGroup.
     *
     * @param Request $request
     * @return Response
     */
    public function createPropertyGroup(Request $request)
    {
        try {
            UnauthorizedHelper::throwUnauthorizedException();

            try {
                //validate incoming request 
                self::_addPropertyGroupValidation($request);

                try {
                    $propertyGroup = self::_assembleAddPropertyGroup($request);

                    $propertyGroup->save();

                    //return successful response
                    return response()->json(['property_group' => $propertyGroup, 'message' => 'CREATED'], 201);
                } catch (\Exception $e) {
                    try {
                        if ($e->getCode() === '23000') {
                            $propertyGroup = PropertyGroup::withTrashed()
                                ->where('name', $propertyGroup->name)
                                ->where('class', $propertyGroup->class)
                                ->first();

                            self::_restoreIfTrashed($propertyGroup);

                            return response()->json(['property_group' => $propertyGroup, 'message' => 'UPDATED'], 200);
                        }

                        throw new \Exception($e->getMessage(), $e->getCode());
                    } catch (\Exception $e) {
                        //return error message
                        return response()->json(['message' => 'Property group Creation Failed!'], 500);
                    }
                }
            } catch (\Exception $e) {
                return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the property group data'], 400);
            }
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Contact the super admin to take this action!'], 401);
        }
    }

    /**
     * Get all PropertyGroup.
     *
     * @return Response
     */
    public function getPropertyGroups(Request $request)
    {
        // TODO: use the query string to select the search criteria, result lenght, result page

        $perPage = $request->input('per_page') ?? 8;
        $nameContains = $request->input('name');

        $PropertyGroups = PropertyGroup::orderBy('property_groups.id', 'DESC');

        if ($nameContains) {
            $PropertyGroups->whereRaw(
                "MATCH(name,class) AGAINST(? IN BOOLEAN MODE)",
                [$nameContains . '*']
            );
        }

        return response()->json(['property_groups' => $PropertyGroups->paginate($perPage)], 200);
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
    public function getPropertyGroup(Request $request)
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
            UnauthorizedHelper::throwUnauthorizedException();

            try {
                self::_updatePropertyGroupValidation($request);

                $name = $request->input('_name') ?? $request->input('name');
                $class = $request->input('_class') ?? $request->input('class');

                $propertyGroup = PropertyGroup::where('name', $name)
                    ->where('class', $class)
                    ->first();

                if ($propertyGroup) {
                    try {
                        $propertyGroup
                            = self::_assembleUpdatePropertyGroup($request, $propertyGroup);

                        $propertyGroup->save();

                        return response()->json(['property_group' => $propertyGroup], 200);
                    } catch (\Exception $e) {

                        return response()->json(['message' => 'property group update failed!'], 500);
                    }
                } else {
                    return response()->json(['message' => 'property group not found!'], 404);
                }
            } catch (\Exception $e) {
                return response()->json(['errors' => $e->getMessage(), 'message' => 'There\'s a problem with the propertyGroup data'], 400);
            }
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Contact the super admin to take this action!'], 401);
        }
    }

    /**
     * Get one propertyGroup.
     *
     * @return Response
     */
    public function deletePropertyGroup(Request $request)
    {
        try {
            UnauthorizedHelper::throwUnauthorizedException();

            $name = $request->input('name');
            $class = $request->input('class');

            $propertyGroup = PropertyGroup::where('name', $name)
                ->where('class', $class)
                ->first();

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
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Contact the super admin to take this action!'], 401);
        }
    }

    /**
     * Get 6 months sold property data.
     *
     * @return Response
     */

    public function get6MonthsSoldPropertiesData()
    {
        $nowdate = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $last6Monthsdate
            = (new \DateTime('first day of this month - 6 months', new \DateTimeZone('UTC')))->format('Y-m-d ') . '00:00:00';

        $soldProperties = Property::select(\DB::raw('DATE_FORMAT(created_at, \'%b\') AS month'), \DB::raw('SUM(COALESCE(NULLIF(price, 0), price_lower_range, price_upper_range, 0)) AS total'))
            ->whereNotNull('sold_at')
            ->whereBetween('created_at', [$last6Monthsdate, $nowdate]);

        $soldExclusiveProperties = Property::select(\DB::raw('DATE_FORMAT(created_at, \'%b\') AS month'), \DB::raw('SUM(COALESCE(NULLIF(price, 0), price_lower_range, price_upper_range, 0)) AS total'))
            ->whereNotNull('is_exclusive')
            ->whereNotNull('sold_at')
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
                if (isset($soldExclusiveProperties[$exclusivePropertiesIndex]) && $soldExclusiveProperties[$exclusivePropertiesIndex]->month === $item['month']) {
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
        $nowdate = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $thisMonthsFirstdate = (new \DateTime('first day of this month', new \DateTimeZone('UTC')))
            ->format('Y-m-d') . ' 00:00:00';
        $lastMonthsFirstdate = (new \DateTime('first day of last month', new \DateTimeZone('UTC')))
            ->format('Y-m-d') . ' 00:00:00';
        $lastMonthsLastdate = (new \DateTime('last day of last month', new \DateTimeZone('UTC')))
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
        $nowdate = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $thisMonthsFirstdate = (new \DateTime('first day of this month', new \DateTimeZone('UTC')))
            ->format('Y-m-d') . ' 00:00:00';
        $lastMonthsFirstdate = (new \DateTime('first day of last month', new \DateTimeZone('UTC')))
            ->format('Y-m-d') . ' 00:00:00';
        $lastMonthsLastdate = (new \DateTime('last day of last month', new \DateTimeZone('UTC')))
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
            = Property::select(\DB::raw('COUNT(id) AS total'), \DB::raw('SUM(CASE WHEN sold_at IS NOT NULL THEN 1 ELSE 0 END) AS sold'));

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
            ->whereNull('sold_at');


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
     * PropertyGroup subject
     * 
     * The propertyGroup $propertyGroup
     * 
     * @param PropertyGroup $propertyGroup
     * 
     * @return void
     */

    private function _restoreIfTrashed(PropertyGroup $propertyGroup)
    {
        if ($propertyGroup->trashed()) {
            $propertyGroup->restore();
        }
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
    private function _assembleAddPropertyGroup(Request $request, PropertyGroup $propertyGroup = null)
    {
        $propertyGroup || ($propertyGroup = new PropertyGroup);
        $propertyGroup->photo = $request->input('photo');
        $propertyGroup->name = strtolower($request->input('name'));
        $propertyGroup->class = strtolower($request->input('class'));

        return $propertyGroup;
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
    private function _assembleUpdatePropertyGroup(Request $request, PropertyGroup $propertyGroup = null)
    {
        $propertyGroup || ($propertyGroup = new PropertyGroup);
        $photo = $request->input('photo');
        $photo && ($propertyGroup->photo = $photo);
        $propertyGroup->name = strtolower($request->input('name'));
        $propertyGroup->class = strtolower($request->input('class'));

        return $propertyGroup;
    }

    /**
     * Get add one propertyGroup.
     * 
     * @param Request $request
     * 
     * @return void
     */
    private function _addPropertyGroupValidation(Request $request)
    {
        //validate incoming request 
        $validator = $this->validate(
            $request,
            [
                'photo' => 'required|string|max:100',
                'name' => 'required|string|max:25',
                'class' => 'required|string|max:20'
            ]
        );
    }

    /**
     * Get update one propertyGroup.
     * 
     * @param Request $request
     * 
     * @return void
     */
    private function _updatePropertyGroupValidation(Request $request)
    {
        //validate incoming request 
        $validator = $this->validate(
            $request,
            [
                'photo' => 'string|max:100',
                'name' => 'required|string|max:25',
                'class' => 'required|string|max:20'
            ]
        );
    }
}
