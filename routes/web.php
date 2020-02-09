<?php

/** 
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */

$router->get(
    '/',
    function () use ($router) {
        return $router->app->version();
    }
);

// API route group
$router->group(
    ['prefix' => 'api'],
    function () use ($router) {
        // Matches "/api/user_register
        $router->post('user_register', 'AuthUserController@register');

        // Matches "/api/user_login
        $router->post('user_login', 'AuthUserController@login');

        // Matches "/api/profile
        $router->get('user_profile', 'UserController@profile');

        // Matches "/api/users
        $router->post('users', 'UserController@createUser');

        // Matches "/api/users/1 
        //get one user by id
        $router->get('users/{id}', 'UserController@singleUser');

        // Matches "/api/users
        $router->get('users', 'UserController@allUsers');

        // Matches "/api/users/1
        $router->put('users/{id}', 'UserController@updateUser');

        // Matches "/api/users/1
        $router->put('suspend_user/{id}', 'UserController@suspendUser');

        // Matches "/api/users/1
        $router->put('unsuspend_user/{id}', 'UserController@unsuspendUser');

        // Matches "/api/users/1
        $router->put('change_password/{id}', 'UserController@changeUserPassword');

        // Matches "/api/users/1
        $router->delete('users/{id}', 'UserController@deleteUser');

        // Matches "/api/customer_register
        $router->post('customer_register', 'AuthCustomerController@register');

        // Matches "/api/customer_login
        $router->post('customer_login', 'AuthCustomerController@login');

        // Matches "/api/property
        $router->post(
            'property',
            [
                'middleware' => 'auth',
                'uses' => 'PropertyController@createProperty'
            ]
        );

        // Matches "/api/property
        $router->get('property', 'PropertyController@getProperties');

        // Matches "/api/property/1
        $router->get('property/{code}', 'PropertyController@getProperty');

        // Matches "/api/property_exists/1
        $router->get('property_exists/{code}', 'PropertyController@propertyExists');

        // Matches "/api/property_count
        $router->get('property_count', 'PropertyController@getTotalProperties');

        // Matches "/api/favorite_properties
        $router->get(
            'favorite_properties',
            [
                'middleware' => 'auth:customers',
                'uses' => 'PropertyController@getFavorites'
            ]
        );

        // Matches "/api/top_types
        $router->get('top_types', 'PropertyController@getTopTypes');

        // Matches "/api/top_cities
        $router->get('top_states', 'PropertyController@getTopStates');

        // Matches "/api/top_cities
        $router->get('top_cities', 'PropertyController@getTopCities');

        // Matches "/api/latest_acquisitions
        $router->get(
            'latest_acquisitions',
            'PropertyController@getLatestAcquisitions'
        );

        // Matches "/api/viewed_properties
        $router->get(
            'recently_uploaded',
            'PropertyController@getRecentlyUploadedProperties'
        );

        // Matches "/api/sold_properties
        $router->get('sold_properties', 'PropertyController@getSoldProperties');

        // Matches "/api/most_seen
        $router->get('most_seen', 'PropertyController@getMostSeenProperties');

        // Matches "/api/viewed_properties
        $router->get('viewed_properties', 'PropertyController@getViewedProperties');

        // Matches "/api/top_properties
        $router->get('top_selections', 'PropertyController@getTopSelections');

        // Matches "/api/exlusive_properties
        $router->get(
            'exclusive_properties',
            'PropertyController@getExclusiveProperties'
        );

        // Matches "/api/properties_stats
        $router->get(
            'properties_stats',
            [
                'middleware' => 'auth',
                'uses' => 'PropertyController@getPropertiesStats'
            ]
        );

        // Matches "/api/property/1
        $router->put(
            'property/{code}',
            [
                'middleware' => 'auth',
                'uses' => 'PropertyController@updateProperty'
            ]
        );

        // Matches "/api/property/1
        $router->delete(
            'property/{code}',
            [
                'middleware' => 'auth',
                'uses' => 'PropertyController@deleteProperty'
            ]
        );

        // Matches "/api/get_property_groups_list
        $router->get(
            'get_property_groups_list',
            'PropertyGroupController@getPropertyGroupsList'
        );

        // Matches "/api/get_property_groups_list
        $router->get(
            'get_property_groups_list_with_count',
            'PropertyGroupController@getPropertyGroupsListWithCount'
        );

        // Matches "/api/favorites
        $router->post(
            'favorites',
            [
                'middleware' => 'auth:customers',
                'uses' => 'FavoriteController@createFavorite'
            ]
        );

        // Matches "/api/total_favorites
        $router->get(
            'total_favorites',
            [
                'middleware' => 'auth:users',
                'uses' => 'FavoriteController@getTotalFavorites'
            ]
        );

        // Matches "/api/favorites/1
        $router->delete(
            'favorites/{property_code}',
            [
                'middleware' => 'auth:customers',
                'uses' => 'FavoriteController@deleteFavorite'
            ]
        );

        // Matches "/api/lead
        $router->post('lead', 'LeadController@createLead');

        // Matches "/api/lead
        $router->get(
            'lead',
            [
                'middleware' => 'auth',
                'uses' => 'LeadController@getLeads'
            ]
        );

        // Matches "/api/get_lead
        $router->get(
            'get_lead',
            [
                'middleware' => 'auth',
                'uses' => 'LeadController@getLead'
            ]
        );

        // Matches "/api/lead
        $router->put(
            'lead',
            [
                'middleware' => 'auth',
                'uses' => 'LeadController@updateLead'
            ]
        );

        // Matches "/api/property
        $router->delete(
            'lead',
            [
                'middleware' => 'auth',
                'uses' => 'LeadController@deleteLead'
            ]
        );

        // Matches "/api/message/1
        $router->get(
            'message/{code}',
            [
                'middleware' => 'auth',
                'uses' => 'MessageController@getMessage'
            ]
        );

        // Matches "/api/message
        $router->get(
            'message',
            [
                'middleware' => 'auth',
                'uses' => 'MessageController@getMessages'
            ]
        );

        // Matches "/api/pending_messages
        $router->get(
            'pending_messages',
            [
                'middleware' => 'auth',
                'uses' => 'MessageController@getPendingMessages'
            ]
        );

        // Matches "/api/mark_as_pending
        $router->put(
            'mark_as_pending/{code}',
            [
                'middleware' => 'auth',
                'uses' => 'MessageController@markAsPending'
            ]
        );

        // Matches "/api/mark_as_done
        $router->put(
            'mark_as_done/{code}',
            [
                'middleware' => 'auth',
                'uses' => 'MessageController@markAsDone'
            ]
        );

        // Matches "/api/news
        $router->post(
            'news',
            [
                'middleware' => 'auth',
                'uses' => 'NewsController@createNews'
            ]
        );

        // Matches "/api/news
        $router->get('news', 'NewsController@getNewss');

        // Matches "/api/news/1
        $router->get(
            'news/{id}',
            [
                'middleware' => 'auth',
                'uses' => 'NewsController@getNews'
            ]
        );

        // Matches "/api/total_news
        $router->get('total_news', 'NewsController@getTotalNews');

        // Matches "/api/news_years
        $router->get('news_years', 'NewsController@getNewsYears');

        // Matches "/api/news/1
        $router->put(
            'news/{id}',
            [
                'middleware' => 'auth',
                'uses' => 'NewsController@updateNews'
            ]
        );

        // Matches "/api/news/1
        $router->delete(
            'news/{id}',
            [
                'middleware' => 'auth',
                'uses' => 'NewsController@deleteNews'
            ]
        );
    }
);
