<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Discussion\Category;
use App\Models\Discussion\Setting as DiscussionSetting;
//use App\Models\Post\Category as PostCategory;
//use App\Models\Post\Setting as PostSetting;
use App\Models\Cms\Setting;

class SiteController extends Controller
{
    public function index(Request $request)
    {
        $name = ($request->segment(1)) ? $request->segment(1) : 'home';
        $page = Setting::getPage($name);
        $discussions = null;
        $settings = $metaData = [];
        $query = $request->query();

        if ($category = Category::where('slug', $page)->first()) {
            $category->settings = $category->getSettings();
            $metaData = $category->meta_data;
            $discussions = ($page['name'] == 'home') ? $category->getAll() : $category->getAllDiscussions($request);

            if (count($discussions)) {
                // Use the first discussion as model to get the global discussion settings.
                $globalPostSettings = Setting::getDataByGroup('discussions', $discussions[0]);

                // Set the setting values manually to improve performance a bit.
                /*foreach ($discussions as $discussion) {
                    // N.B: Don't set the values directly through the object. Use an array to
                    // prevent the "Indirect modification of overloaded property has no effect" error.
                    $settings = [];

                    foreach ($discussion->settings as $key => $value) {
                        // Set the item setting values against the item global setting.
                        $settings[$key] = ($value == 'global_setting') ? $globalPostSettings[$key] : $discussion->settings[$key];
                    }

                    $discussion->settings = $settings;
                }*/
            }
        }
        elseif ($page['name'] == 'home' || file_exists(resource_path().'/views/themes/'.$page['theme'].'/pages/'.$page['name'].'.blade.php')) {
            return view('themes.'.$page['theme'].'.index', compact('page', 'query'));
        }
        else {
            $page['name'] = '404';
            return view('themes.'.$page['theme'].'.index', compact('page'));
        }

        $segments = Setting::getSegments('Discussion');

        return view('themes.'.$page['theme'].'.index', compact('page', 'category', 'discussions', 'segments', 'metaData', 'query'));
    }


    public function show(Request $request)
    {
        $page = Setting::getPage($request->segment(1));

        // First make sure the category exists.
	if (!$category = Category::where('slug', $page['name'])->first()) {
            $page['name'] = '404';
            return view('themes.'.$page['theme'].'.index', compact('page'));
        }

        // Then make sure the discussion exists and is part of the category.
	if (!$discussion = $category->discussions->where('id', $request->segment(2))->first()) {
            $page['name'] = '404';
            return view('themes.'.$page['theme'].'.index', compact('page'));
        }

        $post->global_settings = PostSetting::getDataByGroup('posts');
        $page['name'] = $page['name'].'-details';
        $segments = Setting::getSegments('Discussion');
        $metaData = $post->meta_data;
	$query = $request->query();

        return view('themes.'.$page['theme'].'.index', compact('page', 'category', 'discussion', 'segments', 'metaData', 'query'));
    }
}
