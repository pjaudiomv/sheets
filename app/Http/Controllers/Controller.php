<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Carbon\Carbon;

use Code4Recovery\Spec;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    public static $tsml_conference_providers = [
        'bluejeans.com' => 'Bluejeans',
        'discord.gg' => 'Discord',
        'freeconference.com' => 'Free Conference',
        'freeconferencecall.com' => 'FreeConferenceCall',
        'goto.com' => 'GoTo',
        'gotomeet.me' => 'GoTo',
        'gotomeeting.com' => 'GoTo',
        'horizon.meta.com' => 'Virtual Reality',
        'maps.secondlife.com' => 'Virtual Reality',
        'meet.google.com' => 'Google Meet',
        'meet.jit.si' => 'Jitsi',
        'meetings.dialpad.com' => 'Dialpad',
        'signal.group' => 'Signal',
        'skype.com' => 'Skype',
        'slurl.com' => 'Virtual Reality',
        'teams.live.com' => 'Teams',
        'teams.microsoft.com' => 'Teams',
        'vrchat.com' => 'Virtual Reality',
        'webex.com' => 'WebEx',
        'zoho.com' => 'Zoho',
        'zoom.us' => 'Zoom',
    ];

    public static function fetch($sheetId)
    {
        $fields = [
            'address',
            'approximate',
            'city',
            'conference_phone',
            'conference_phone_notes',
            'conference_url',
            'conference_url_notes',
            'contact_1_email',
            'contact_1_name',
            'contact_1_phone',
            'contact_2_email',
            'contact_2_name',
            'contact_2_phone',
            'contact_3_email',
            'contact_3_name',
            'contact_3_phone',
            'coordinates',
            'country',
            'day',
            'district',
            'districts',
            'edit_url',
            'email',
            'end_time',
            'feedback_url',
            'formatted_address',
            'group',
            'group_notes',
            'latitude',
            'location',
            'location_notes',
            'longitude',
            'name',
            'notes',
            'paypal',
            'phone',
            'postal_code',
            'region',
            'regions',
            'slug',
            'square',
            'state',
            'sub_district',
            'sub_region',
            'time',
            'timezone',
            'types',
            'updated',
            'url',
            'venmo',
            'website',
        ];

        $spec = new Spec();
        $types = $spec->getTypesByLanguage('en');
        $types = array_flip($types);

        $types = array_change_key_case($types, CASE_LOWER);

        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        // fetch data
        $response = Http::get(
            'https://sheets.googleapis.com/v4/spreadsheets/' .
            $sheetId . '/values/A1:ZZ?key=' .
            env('GOOGLE_SHEET_API_KEY')
        );

        if (!$response->successful()) {
            return $response;
        }

        if (empty($response['values'])) {
            return ['error' => 'The sheet is empty'];
        }

        $rows = $response['values'];

        $slugs = $warnings = [];

        // get columns
        $columns = array_map(function ($column) {
            return Str::slug($column, '_');
        }, array_shift($rows));

        // validate columns
        if (empty($columns)) {
            return ['error' => 'The sheet is empty'];
        } elseif (!in_array('slug', $columns) && !in_array('id', $columns)) {
            return ['error' => 'The sheet is missing a "slug" or "id" column'];
        } elseif (!in_array('name', $columns)) {
            return ['error' => 'The sheet is missing a "slug" or "id" column'];
        }

        $column_count = count($columns);
        $types_column = self::getColumnFromNumber(array_search('types', $columns));
        $slug_column = self::getColumnFromNumber(array_search('slug', $columns) ?: array_search('id', $columns));
        $name_column = self::getColumnFromNumber(array_search('name', $columns));

        // loop through and format rows
        $rows = array_map(function ($row, $index) use ($columns, $column_count, $fields, $days, $types, &$warnings, $sheetId, $types_column, &$slugs, $slug_column, $name_column) {

            // skip empty row
            if (!count($row) || !strlen(implode('', $row))) {
                return null;
            }

            // basic row fixup
            $row = array_map('trim', $row);
            $row = array_combine($columns, array_pad($row, $column_count, null));

            // check that id/slug exists
            if (empty($row['slug'])) {
                // accept "id" as an alias for "slug"
                if (!empty($row['id'])) {
                    $row['slug'] = $row['id'];
                } else {
                    $warnings[] = [
                        'link' => 'https://docs.google.com/spreadsheets/d/' . $sheetId . '/edit#gid=0&range=' . $slug_column . $index + 2,
                        'error' => 'empty id or slug',
                        'value' => ['Row ' . $index + 2]
                    ];
                    return null;
                }
            }

            // check that name exists
            if (empty($row['name'])) {
                $warnings[] = [
                    'link' => 'https://docs.google.com/spreadsheets/d/' . $sheetId . '/edit#gid=0&range=' . $name_column . $index + 2,
                    'error' => 'empty name',
                    'value' => ['Row ' . $index + 2]
                ];
                return null;
            }

            // check for duplicate slugs
            if (in_array($row['slug'], $slugs)) {
                $warnings[] = [
                    'link' => 'https://docs.google.com/spreadsheets/d/' . $sheetId . '/edit#gid=0&range=' . $slug_column . $index + 2,
                    'error' => 'duplicate slug',
                    'value' => [$row['slug']]
                ];
            }
            $slugs[] = $row['slug'];

            // format "time" and "end_time" columns
            foreach (['time', 'end_time'] as $col) {
                if (!empty($row[$col])) {
                    $row[$col] = date('H:i', strtotime($row[$col]));
                }
            }

            // handle timezone
            if (isset($row['timezone']) && !str_contains($row['timezone'], '/')) {
                unset($row['timezone']);
            }

            // format "day" column
            if (!empty($row['day']) && in_array($row['day'], $days)) {
                $row['day'] = array_search($row['day'], $days);
            }

            // formatted_address


            // regions
            if (!empty($row['regions'])) {
                $row['regions'] = array_map('trim', explode('>', $row['regions']));
                if (!empty($row['regions'][0])) {
                    $row['region'] = $row['regions'][0];
                } else {
                    unset($row['region']);
                }
                if (!empty($row['regions'][1])) {
                    $row['sub_region'] = $row['regions'][1];
                } else {
                    unset($row['sub_region']);
                }
            }

            // districts
            if (!empty($row['districts'])) {
                $row['districts'] = array_map('trim', explode('>', $row['districts']));
                unset($row['district']);
                unset($row['sub_district']);
            }

            // handle types
            if (!empty($row['types'])) {
                $row['types'] = explode(',', trim($row['types']));
                $row['types'] = array_map('trim', $row['types']);

                $unknown_types = array_values(array_filter($row['types'], function ($type) use ($types) {
                    return !array_key_exists(strtolower($type), $types) && !in_array($type, $types);
                }));

                if ($count = count($unknown_types)) {
                    $warnings[] = [
                        'link' => 'https://docs.google.com/spreadsheets/d/' . $sheetId . '/edit#gid=0&range=' . $types_column . $index + 2,
                        'error' => $count > 1 ? 'unknown types' : 'unknown type',
                        'value' => $unknown_types
                    ];
                }

                $row['types'] = array_map(function ($type) use ($types) {
                    return array_key_exists(strtolower($type), $types) ? $types[strtolower($type)] : strtoupper($type);
                }, $row['types']);

                // automatically apply "digital basket" type
                $row['types'] = array_filter($row['types'], function ($type) {
                    return $type !== 'DB';
                });
                if (!empty($row['venmo']) || !empty($row['paypal']) || !empty($row['square'])) {
                    array_push($row['types'], 'DB');
                }

                $row['types'] = array_values($row['types']);
            }

            // format "latitude" and "longitude" columns
            foreach (['latitude', 'longitude'] as $col) {
                if (!empty($row[$col])) {
                    $row[$col] = floatval($row[$col]);
                }
            }

            // updated
            if (!empty($row['updated'])) {
                try {
                    $row['updated'] = Carbon::parse($row['updated'])->toDateString();
                } catch (\Exception $e) {
                    $row['updated'] = null;
                    $warnings[] = [
                        'link' => 'https://docs.google.com/spreadsheets/d/' . $sheetId . '/edit#gid=0&range=' . $slug_column . $index + 2,
                        'error' => 'could not parse updated date',
                        'value' => [$row['updated']]
                    ];
                }
            }

            // link to row
            if (empty($row['edit_url'])) {
                $row['edit_url'] = 'https://docs.google.com/spreadsheets/d/' . $sheetId . '/edit#gid=0&range=' . $index + 2 . ':' . $index + 2;
            }

            // remove unknown or empty columns
            $keys = array_filter(array_keys($row), function ($key) use ($row, $fields) {
                return in_array($key, $fields) && (!empty($row[$key]) || $row[$key] === 0);
            });

            return array_intersect_key($row, array_flip($keys));
        }, $rows, array_keys($rows));

        // remove empty rows
        $rows = array_values(array_filter($rows));

        return [$rows, $warnings];
    }

    public static function generate($rows, $warnings, $filename)
    {

        $created = !Storage::disk('public')->exists($filename);

        Storage::disk('public')->put($filename, json_encode($rows));

        $feedUrl = env('APP_URL') . '/storage/' . $filename;

        return compact('feedUrl', 'warnings', 'created');
    }

    private static function getColumnFromNumber($num)
    {
        $numeric = $num % 26;
        $letter = chr(65 + $numeric);
        $num2 = intval($num / 26);
        if ($num2 > 0) {
            return self::getColumnFromNumber($num2 - 1) . $letter;
        } else {
            return $letter;
        }
    }
}
