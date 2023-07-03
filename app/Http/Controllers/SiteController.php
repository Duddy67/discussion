<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Discussion\Category;
use App\Models\Discussion\Setting as DiscussionSetting;
//use App\Models\Post\Category as PostCategory;
//use App\Models\Post\Setting as PostSetting;
use App\Models\Menu;
use App\Models\Setting;

class SiteController extends Controller
{
    public function index(Request $request)
    {
        $page = ($request->segment(1)) ? $request->segment(1) : 'home';
        $discussions = null;
        $settings = $metaData = [];
        $menu = Menu::getMenu('main-menu');
        $menu->allow_registering = Setting::getValue('website', 'allow_registering', 0);
        $theme = Setting::getValue('website', 'theme', 'starter');
        $query = $request->query();
        $timezone = Setting::getValue('app', 'timezone');

        if ($category = Category::where('slug', $page)->first()) {
            $discussions = $category->getAllDiscussions($request);

            $globalSettings = DiscussionSetting::getDataByGroup('categories');

            foreach ($category->settings as $key => $value) {
                if ($value == 'global_setting') {
                    $settings[$key] = $globalSettings[$key];
                }
                else {
                    $settings[$key] = $category->settings[$key];
                }
            }

            $category->global_settings = $globalSettings;
            $metaData = $category->meta_data;

            /*$globalSettings = PostSetting::getDataByGroup('posts');

            foreach ($discussions as $discussion) {
                $post->global_settings = $globalSettings;
            }*/
        }
        elseif ($page == 'home' || file_exists(resource_path().'/views/themes/'.$theme.'/pages/'.$page.'.blade.php')) {
            return view('themes.'.$theme.'.index', compact('page', 'menu', 'query'));
        }
        else {
            $page = '404';
            return view('themes.'.$theme.'.index', compact('page', 'menu'));
        }

        $segments = Setting::getSegments('Discussion');

        return view('themes.'.$theme.'.index', compact('page', 'menu', 'category', 'settings', 'discussions', 'segments', 'metaData', 'timezone', 'query'));
    }


    public function show(Request $request)
    {
        $page = $request->segment(1);
        $menu = Menu::getMenu('main-menu');
        $menu->allow_registering = Setting::getValue('website', 'allow_registering', 0);
        $theme = Setting::getValue('website', 'theme', 'starter');
        $timezone = Setting::getValue('app', 'timezone');

        // First make sure the category exists.
	if (!$category = Category::where('slug', $page)->first()) {
            $page = '404';
            return view('themes.'.$theme.'.index', compact('page', 'menu'));
        }

        // Then make sure the discussion exists and is part of the category.
	if (!$discussion = $category->discussions->where('id', $request->segment(2))->first()) {
            $page = '404';
            return view('themes.'.$theme.'.index', compact('page', 'menu'));
        }

        $post->global_settings = PostSetting::getDataByGroup('posts');
        $page = $page.'-details';
        $segments = Setting::getSegments('Discussion');
        $metaData = $post->meta_data;
	$query = $request->query();

        return view('themes.'.$theme.'.index', compact('page', 'menu', 'category', 'discussion', 'segments', 'metaData', 'timezone', 'query'));
    }
}
