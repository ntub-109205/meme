<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Image;
use Storage;
use Auth;
use App\Template;
use App\Category;
use Validator;
use Illuminate\Validation\Rule;

class ImageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function templateStore(Request $request)
    {
        // validate data
        $validator = Validator::make($request->all(), [
            'category_id' => ['required', Rule::In(['1', '2'])],
            'name' => 'required|max:255',
            'share' => 'required|boolean',
            'image' => 'required|image'
        ]);

        if ($validator->fails()) {
            return json_encode(['failed' => 'post template failed']);
        }

        // post data
        $template = new Template;
        $template->user_id = Auth::guard('web')->user()->id;
        $template->category_id = $request->category_id;
        $template->name = $request->name;
        $template->share = $request->share;
            // save image
        $image = $request->file('image');
        $filename = time().'.'.$image->extension();

        $category = Category::find($request->category_id);
        $location = public_path('images/templates/'.$category->name.'/'.$filename);
        Image::make($image)->save($location);
        $template->filelink = $filename;
        $template->save();

        return json_encode(['success' => 'your posts has been successfully saved!']);
    }

    public function memeStore(Request $request)
    {
        
    }
}
