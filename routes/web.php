<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Http\Controllers\OiaaController;
use App\Http\Controllers\AirtableController;

Route::view('/', 'welcome');

Route::post('/', function () {
    $parts = explode('/', request('sheetUrl'));
    if (count($parts) !== 7) {
        return redirect()->back();
    }
    return redirect("/$parts[5]");
});

Route::get('oiaa', [OiaaController::class, 'oiaa']);

Route::get('aasfmarin', [AirtableController::class, 'aasfmarin']);

// "TSML" mode live-fetches and includes warnings
Route::get('tsml/{sheetId}', function ($sheetId) {
    $response = Controller::fetch($sheetId);
    [$rows, $warnings] = $response;
    return ['meetings' => $rows, 'warnings' => $warnings];
});

// Central cache
Route::get('central/{accountId}', function ($accountId) {
    $redirectTo = request('redirectTo');
    $filename = 'central/' . $accountId . '.json';
    $lastModified = Storage::disk('public')->exists($filename) ? Storage::disk('public')->lastModified($filename) : 0;
    $cacheExpires = now()->subMinutes(15)->timestamp;

    if (!$redirectTo && $lastModified > $cacheExpires) {
        // return cache
        $result = json_decode(Storage::disk('public')->get($filename));
    } else {
        // update cache
        $response = Http::get('https://central.code4recovery.org/feeds/' . $accountId);
        Storage::disk('public')->put($filename, $response);
        $result = $response->json();
    }

    // return redirect or json
    return $redirectTo ? redirect($redirectTo) : response()->json($result);
});

Route::get('{sheetId}', function ($sheetId, $redirectTo = false) {
    $redirectTo = request('redirectTo');
    $response = Controller::fetch($sheetId);
    if (!empty($response['error'])) {
        return redirect()->back()->with('error', $response['error'])->with('sheetUrl', 'https://docs.google.com/spreadsheets/d/' . $sheetId . '/edit#gid=0');
    }
    [$rows, $warnings] = $response;
    $response = Controller::generate($rows, $warnings, $sheetId . '.json');
    if (!empty($response['error'])) {
        return redirect()->back()->with('error', $response['error'])->with('sheetUrl', 'https://docs.google.com/spreadsheets/d/' . $sheetId . '/edit#gid=0');
    }
    return ($redirectTo && !count($response['warnings'])) ? redirect($redirectTo) : view('done', $response);
});
