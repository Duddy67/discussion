<?php

namespace App\Http\Controllers\Discussion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Discussion\Category;
use App\Models\Cms\Setting;
use Illuminate\Support\Facades\Auth;


class CategoryController extends Controller
{
    public function index(Request $request, $id, $slug)
    {
        $page = Setting::getPage('discussion.category');

	if (!$category = Category::where('id', $id)->first()) {
            $page['name'] = '404';
            return view('themes.'.$page['theme'].'.index', compact('page'));
	}

	if (!$category->canAccess()) {
            $page['name'] = '403';
            return view('themes.'.$page['theme'].'.index', compact('page'));
	}

        $category->settings = $category->getSettings();
	$discussions = $category->getDiscussions($request);

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

        $segments = Setting::getSegments('Discussion');
        $metaData = $category->meta_data;
	$query = array_merge($request->query(), ['id' => $id, 'slug' => $slug]);

        return view('themes.'.$page['theme'].'.index', compact('page', 'category', 'segments', 'discussions', 'metaData', 'query'));
    }
}
