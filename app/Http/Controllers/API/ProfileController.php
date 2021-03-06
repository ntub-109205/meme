<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Meme;
use App\Template;
use App\User;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
	public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function user()
    {
    	return json_encode(['name' => Auth::guard('api')->user()->name]);
    }

    public function saved()
    {
    	$data = Auth::guard('api')->user()->saved;
    	$data = json_decode($data, 1); //array

    	$saved = [];
    	foreach ($data as $name => $value) {
    		$saved[$name] = [];
    		if (count($value) == 0) {
    			continue;
    		}

    		if ($name == 'meme') {
    			$query = "
	                SELECT m.`filelink`, c.`name`, m.id
					FROM `meme` m
					INNER JOIN `templates` t
					ON m.`template_id` = t.`id`
					INNER JOIN `category` c
					ON t.`category_id` = c.`id`
					WHERE m.`id` = :id
	            	";
    		} else if ($name == 'templates') {
    			$query = "
	                SELECT t.`filelink`, c.`name`, t.id
					FROM `templates` t
					INNER JOIN `category` c
					ON t.`category_id` = c.`id`
					WHERE t.`id` = :id
	            	";
    		}

    		try {
    			foreach ($value as $key => $time) {
		            $info = DB::select($query, ['id' => $key]);
		            $info = $info[0];
		            $saved[$name][$info->name][$info->id] = [
                        'time' => $time,
                        'id' => $info->id,
                        'filelink' => $info->filelink,
                    ];
	    		}
    		} catch(\Throwable $e) {
	            return json_encode(['fail' => $e->getMessage()]);
	        }
    		
    		foreach ($saved[$name] as $fileCategory => $fileInfo) {
                $max = max($fileInfo);
    			$saved[$name][$fileCategory] = [
    				'count' => count($fileInfo),
                    'id' => $max['id'],
    				'filelink' => url("images/{$name}/{$fileCategory}", $max['filelink']),
    			];
    		}
    	}

    	return json_encode($saved);
    }

    public function myWork() 
    {
        $user_id = Auth::guard('api')->user()->id;

        // meme
        $query = "
            SELECT m.`id` AS `meme_id`, m.`filelink`, m.`created_at`, t.`id` AS `template_id`, c.`name` AS `category`
            FROM `meme` m
            INNER JOIN `templates` t
            ON m.`template_id` = t.`id`
            INNER JOIN `category` c
            ON t.`category_id` = c.`id`
            WHERE m.`user_id` = :user_id
            ";
        $meme = DB::select($query, ['user_id' => $user_id]);
        
        // proc meme array
        $meme_array = ['meme' => []];
        if (! $meme == NULL) {
            foreach ($meme as $key => $info) {
                $meme_array['meme'][$info->category][$info->meme_id] = [
                    'created_at' => $info->created_at,
                    'meme_id' => $info->meme_id,
                    'filelink' => $info->filelink,
                    'template_id' => $info->template_id,
                ];
            }
            foreach ($meme_array as $key => $info) {
                foreach ($info as $category => $value) {
                    $max = max($value);
                    $meme_array['meme'][$category] = [
                        'count' => count($value),
                        'meme_id' => $max['meme_id'],
                        'filelink' => url("images/{$key}/{$category}", $max['filelink']),
                        'template_id' => $max['template_id'],
                        'created_at' => $max['created_at'],
                    ];
                }
            }
        }

        // template
        $query = "
            SELECT t.`id` AS `template_id`, t.`filelink`, t.`created_at`, c.`name` AS `category`
            FROM `templates` t
            INNER JOIN `category` c
            ON t.`category_id` = c.`id`
            WHERE `user_id` = :user_id
            ";
        $template = DB::select($query, ['user_id' => $user_id]);

        // proc template array
        $template_array = ['templates' => []];
        if (! $template == NULL) {
            foreach ($template as $key => $info) {
                $template_array['templates'][$info->category][$info->template_id] = [
                    'created_at' => $info->created_at,
                    'template_id' => $info->template_id,
                    'filelink' => $info->filelink,
                ];
            }
            foreach ($template_array as $key => $info) {
                foreach ($info as $category => $value) {
                    $max = max($value);
                    $template_array['templates'][$category] = [
                        'count' => count($value),
                        'template_id' => $max['template_id'],
                        'filelink' => url("images/{$key}/{$category}", $max['filelink']),
                        'created_at' => $max['created_at'],
                    ];
                }
            }
        }

        // return
        return json_encode(array_merge($meme_array, $template_array));
    }

    public function update(Request $request)
    {
    	$validator = Validator::make($request->all(), [
            'table' => ['required', Rule::In(['meme', 'templates'])],
            'id' => 'required|numeric',
            'value' => 'required|string',
        ]);

    	if ($validator->fails()) {
            return json_encode(['failed' => $validator->errors()]);
        }

    	switch ($request->table) {
		    case 'meme':
		        $validator = Validator::make(['field' => $request->field], [
	            	'field' => ['required', Rule::In(['share'])],
	        	]);
		        break;
		    case 'templates':
		        $validator = Validator::make(['field' => $request->field], [
	            	'field' => ['required', Rule::In(['share', 'name'])],
	        	]);
		        break;
		    case 'tags':
		        $validator = Validator::make(['field' => $request->field], [
	            	'field' => ['required', Rule::In(['name'])],
	        	]);
		        break;
		}

		if ($validator->fails()) {
            return json_encode(['failed' => $validator->errors()]);
        }

        try {
            $author = DB::table($request->table)
                ->select('user_id')
                ->where('id', $request->id)
                ->first()
                ->user_id;
            if ($author != Auth::guard('api')->user()->id) {
            	return json_encode(['failed' => "You don't have permission to update this"]);
            }
        	DB::table($request->table)
		        ->where('id', $request->id)
		        ->update([$request->field => $request->value]);
		    return json_encode(['success' => 'This post was successfully change!']);
        } catch(\Throwable $e) {
            return json_encode(['failed' => $e->getMessage()]);
        }
    }
}
