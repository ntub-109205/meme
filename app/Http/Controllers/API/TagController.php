<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;

class TagController extends Controller
{
	public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function popular(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'sometimes|numeric',
            'name' => 'sometimes|string',
        ]);
        
        if ($validator->fails()) {
            return json_encode(['failed' => $validator->errors()]);
        }

        $param = [];

    	$query = "
            SELECT mt.`tag_id`, t.`name`
			FROM `meme_tag` mt
			INNER JOIN `tags` t
			ON mt.`tag_id` = t.`id`
            INNER JOIN  `meme` m
            ON mt.`meme_id` = m.`id`
            WHERE m.`share` = 1
        ";

        if (isset($request->name)) {
            $query .= "AND t.`name` LIKE :name";
            $param['name'] = "%{$request->name}%";
        }

        $query .= "
            GROUP BY mt.`tag_id`, t.`name`
            ORDER BY COUNT(`tag_id`) DESC 
        ";

        if ($request->limit) {
        	$query .= "LIMIT :limit";
        	$param['limit'] = $request->limit;
        }

        try {
            $popular = DB::select($query, $param);
            return json_encode(['popular' => $popular]);
        } catch(\Throwable $e) {
        	return json_encode(['failed' => $e->getMessage()]);
        }
    }
}
