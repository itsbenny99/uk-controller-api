<?php

use App\Rules\VatsimCallsign;
use Illuminate\Support\Facades\Route;

// Routes that the plugin user will use
Route::middleware('plugin.user')->group(function () {

    // Default routes, just used to check if the API is available and the user is authenticated
    Route::get(
        '/',
        [
            'middleware' => [
                'user.lastlogin',
            ],
            'uses' => 'TeapotController@teapot',
        ]
    );

    Route::get(
        '/authorise',
        [
            'middleware' => [
                'user.lastlogin',
            ],
            'uses' => 'TeapotController@normalTeapots',
        ]
    );

    // Dependencies
    Route::get('dependency', 'DependencyController@getAllDependencies');

    // Holds
    Route::put('hold/assigned', 'HoldController@assignHold');
    Route::delete('hold/assigned/{callsign}', 'HoldController@deleteAssignedHold')
        ->where('callsign', VatsimCallsign::CALLSIGN_REGEX);
    ;
    Route::get('hold/profile', 'HoldController@getUserHoldProfiles');

    // Whenever a hold profile is updated, we need to reset the dependency
    Route::middleware('dependency.update:DEPENDENCY_HOLD_PROFILE')->group(function () {
        Route::put('hold/profile', 'HoldController@createUserHoldProfile');
        Route::put('hold/profile/{profile_id}', 'HoldController@updateUserHoldProfile')
            ->where('profile_id', '\d+');
        Route::delete('hold/profile/{profile_id}', 'HoldController@deleteUserHoldProfile')
            ->where('profile_id', '\d+');
    });

    // Squawks
    Route::get('squawk-assignment/{callsign}', 'SquawkController@getSquawkAssignment')
        ->where('callsign', VatsimCallsign::CALLSIGN_REGEX);
    Route::put('squawk-assignment/{callsign}', 'SquawkController@assignSquawk')
        ->where('callsign', VatsimCallsign::CALLSIGN_REGEX);
    Route::delete('squawk-assignment/{callsign}', 'SquawkController@deleteSquawkAssignment')
        ->where('callsign', VatsimCallsign::CALLSIGN_REGEX);
});

// Routes for user administration
Route::middleware('admin.user')->group(function () {

    // A test route for useradmin access
    Route::get('useradmin', 'TeapotController@teapot');

    // Get user
    Route::get(
        'user/{cid}',
        [
            'middleware' => 'vatsim.cid',
            'uses' => 'UserController@getUser',
        ]
    )->where('cid', '[0-9]+');

    // Create user
    Route::post(
        'user/{cid}',
        [
            'middleware' => 'vatsim.cid',
            'uses' => 'UserController@createUser',
        ]
    )->where('cid', '[0-9]+');

    // Reactivate user account
    Route::put(
        'user/{cid}/reactivate',
        [
            'middleware' => 'vatsim.cid',
            'uses' => 'UserController@reactivateUser',
        ]
    )->where('cid', '[0-9]+');

    // Ban user account
    Route::put(
        'user/{cid}/ban',
        [
            'middleware' => 'vatsim.cid',
            'uses' => 'UserController@banUser',
        ]
    )->where('cid', '[0-9]+');

    // Disable user account
    Route::put(
        'user/{cid}/disable',
        [
            'middleware' => 'vatsim.cid',
            'uses' => 'UserController@disableUser',
        ]
    )->where('cid', '[0-9]+');

    // Create user token
    Route::post(
        'user/{cid}/token',
        [
            'middleware' => 'vatsim.cid',
            'uses' => 'UserController@createUserToken',
        ]
    )->where('cid', '[0-9]+');

    // Delete user token
    Route::delete(
        'token/{tokenId}',
        [
            'uses' => 'UserController@deleteUserToken',
        ]
    );
});

// Routes for user administration
Route::middleware('admin.version')->group(function () {
    // A test route for useradmin access
    Route::get('versionadmin', 'TeapotController@teapot');

    // Routes for returning information about versions
    Route::get('version', 'VersionController@getAllVersions');
    Route::get('version/{version}', 'VersionController@getVersion')
        ->where('version', '[A-Za-z0-9\.\-]+');

    // Route for updating and creating versions
    Route::put('version/{version}', 'VersionController@createOrUpdateVersion')
        ->where('version', '[A-Za-z0-9\.\-]+');
});

// Routes for dependency administration
Route::middleware('admin.dependency')->group(function () {

    // Initial altitudes and sids
    Route::middleware('dependency.update:DEPENDENCY_PRENOTE,DEPENDENCY_INITIAL_ALTITUDES,DEPENDENCY_SID_HANDOFF')
        ->group(
            function () {
                Route::delete('sid/{id}', 'SidController@deleteSid')
                    ->where('sid', 'd+');
                Route::put('sid', 'SidController@createSid');
                Route::put('sid/{id}', 'SidController@updateSid')
                    ->where('sid', 'd+');
            }
        );
});

Route::middleware('admin.github')->group(function () {
    Route::post('github', 'GithubController@processGithubWebhook');
});

// Routes that can be hit by anybody at all, mostly login and informational routes
Route::middleware('public')->group(function () {

    // Aircraft
    Route::get('aircraft', 'AircraftController@getAllAircraft');
    Route::get('wake-category', 'AircraftController@getAllWakeCategories');
    Route::get('wake-category/dependency', 'AircraftController@getWakeCategoriesDependency');

    // Initial altitudes and sids
    Route::get('sid', 'SidController@getAllSids');
    Route::get('sid/{id}', 'SidController@getSid')
        ->where('sid', 'd+');
    Route::get('initial-altitude', 'SidController@getInitialAltitudeDependency');
    Route::get('handoffs', 'SidController@getSidHandoffsDependency');

    // Version checking
    Route::get(
        'version/{version}/status',
        [
            'middleware' => [
                'user.version',
            ],
            'uses' => 'VersionController@getVersionStatus',
        ]
    )->where('version', '[A-Za-z0-9\.\-]+');

    // Controller positions
    Route::get('controller', 'ControllerPositionController@getAllControllers');
    Route::get('controller-positions', 'ControllerPositionController@getControllerPositionsDependency');

    // Airfields
    Route::get('airfield', 'AirfieldController@getAllAirfields');
    Route::get('airfield-ownership', 'AirfieldController@getAirfieldOwnershipDependency');

    // Holds
    Route::get('hold', 'HoldController@getAllHolds');
    Route::get('hold/assigned', 'HoldController@getAssignedHolds');

    // Handoffs
    Route::get('handoff', 'HandoffController@getAllHandoffs');

    // Prenotes
    Route::get('prenote', 'PrenoteController@getAllPrenotes');

    // Regional Pressure
    Route::get('regional-pressure', 'RegionalPressureController@getRegionalPressures');
    Route::get('altimeter-setting-region', 'RegionalPressureController@getAltimeterSettingRegions');

    // Minimum stack levels
    Route::get('msl', 'MinStackController@getAllMinStackLevels');
    Route::get('msl/airfield', 'MinStackController@getAirfieldMinStackLevels');
    Route::get('msl/tma', 'MinStackController@getTmaMinStackLevels');
    Route::get('msl/airfield/{icao}', 'MinStackController@getMslForAirfield')
        ->where('icao', '[A-Z]{4}');
    Route::get('msl/tma/{tma}', 'MinStackController@getMslForTma')
        ->where('tma', '[A-Z]{4}');

    // Standard Route Document
    Route::get('srd/route/search', 'SrdController@searchRoutes');

    // Navaids
    Route::get('navaid/dependency', 'NavaidController');

    // Admin login
    Route::prefix('admin')->group(function () {
        Route::post('login', 'UserController@adminLogin');
    });
});
