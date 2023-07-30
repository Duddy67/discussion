<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Discussion\Category;
use App\Models\Discussion\Setting as DiscussionSetting;
//use App\Models\Post\Category as PostCategory;
//use App\Models\Post\Setting as PostSetting;
//use App\Models\Menu;
use App\Models\Setting;

class SiteController extends Controller
{
    public function index(Request $request)
    {
        $name = ($request->segment(1)) ? $request->segment(1) : 'home';
        $page = Setting::getPage($name);
        $discussions = null;
        $settings = $metaData = [];
        //$menu = Menu::getMenu('main-menu');
        //$menu->allow_registering = Setting::getValue('website', 'allow_registering', 0);
        //$theme = Setting::getValue('website', 'theme', 'starter');
        $query = $request->query();
        //$timezone = Setting::getValue('app', 'timezone');

        if ($category = Category::where('slug', $page)->first()) {

            $discussions = ($page['name'] == 'home') ? $category->getAll() : $category->getAllDiscussions($request);

            $globalSettings = DiscussionSetting::getDataByGroup('categories');
            $settings = DiscussionSetting::getItemSettings($category, 'categories');

            /*foreach ($category->settings as $key => $value) {
                if ($value == 'global_setting') {
                    $settings[$key] = $globalSettings[$key];
                }
                else {
                    $settings[$key] = $category->settings[$key];
                }
            }*/

            $category->global_settings = $globalSettings;
            $metaData = $category->meta_data;

            /*$globalSettings = PostSetting::getDataByGroup('posts');

            foreach ($discussions as $discussion) {
                $post->global_settings = $globalSettings;
            }*/
        }
        elseif ($page['name'] == 'home' || file_exists(resource_path().'/views/themes/'.$page['theme'].'/pages/'.$page['name'].'.blade.php')) {
            return view('themes.'.$page['theme'].'.index', compact('page', 'query'));
        }
        else {
            $page['name'] = '404';
            return view('themes.'.$page['theme'].'.index', compact('page'));
        }

        $segments = Setting::getSegments('Discussion');

        return view('themes.'.$page['theme'].'.index', compact('page', 'category', 'settings', 'discussions', 'segments', 'metaData', 'query'));
    }


    public function show(Request $request)
    {
        $page = Setting::getPage($request->segment(1));
        //$menu = Menu::getMenu('main-menu');
        //$menu->allow_registering = Setting::getValue('website', 'allow_registering', 0);
        //$theme = Setting::getValue('website', 'theme', 'starter');
        //$timezone = Setting::getValue('app', 'timezone');

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
