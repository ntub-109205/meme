<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Meme;
use App\Template;
use App\User;
use Illuminate\Support\Facades\DB;

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

    public function showSaved()
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
	                SELECT m.`filelink`, c.`name`
					FROM `meme` m
					INNER JOIN `templates` t
					ON m.`template_id` = t.`id`
					INNER JOIN `category` c
					ON t.`category_id` = c.`id`
					WHERE m.`id` = :id
	            	";
    		} else if ($name == 'templates') {
    			$query = "
	                SELECT t.`filelink`, c.`name`
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
		            $saved[$name][$info->name][$info->filelink] = $time;
	    		}
    		} catch(\Throwable $e) {
	            return json_encode(['fail' => $e->getMessage()]);
	        }
    		
    		foreach ($saved[$name] as $fileCategory => $fileInfo) {
    			$max = array_keys($fileInfo, max($fileInfo))[0];
    			$saved[$name][$fileCategory] = [
    				"count" => count($fileInfo),
    				"filelink" => url("images/{$name}/{$fileCategory}", $max),
    			];
    		}
    	}

    	return json_encode($saved);
    }

    public function showMyWork() 
    {
        $query = "
            SELECT m.`filelink`, c.`name`
            FROM `meme` m
            INNER JOIN `templates` t
            ON m.`template_id` = t.`id`
            INNER JOIN `category` c
            ON t.`category_id` = c.`id`
            WHERE m.`id` = :id
            ";
    }
}
