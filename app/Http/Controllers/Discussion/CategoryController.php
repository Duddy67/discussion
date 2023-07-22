<?php

namespace App\Http\Controllers\Discussion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Discussion\Category;
use App\Models\Discussion\Setting as DiscussionSetting;
use App\Models\Menu;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;


class CategoryController extends Controller
{
    public function index(Request $request, $id, $slug)
    {
        $page = 'discussion.category';
        $theme = Setting::getValue('website', 'theme', 'starter');
        $menu = Menu::getMenu('main-menu');

	if (!$category = Category::where('id', $id)->first()) {
            $page = '404';
            return view('themes.'.$theme.'.index', compact('page', 'menu'));
	}

	if (!$category->canAccess()) {
            $page = '403';
            return view('themes.'.$theme.'.index', compact('page', 'menu'));
	}

        $category->global_settings = DiscussionSetting::getDataByGroup('categories');
	$settings = $category->getSettings();
	$discussions = $category->getDiscussions($request);
        $segments = Setting::getSegments('Discussion');
        $metaData = $category->meta_data;
        $timezone = Setting::getValue('app', 'timezone');
	$query = array_merge($request->query(), ['id' => $id, 'slug' => $slug]);

        return view('themes.'.$theme.'.index', compact('page', 'menu', 'category', 'segments', 'settings', 'discussions', 'timezone', 'metaData', 'query'));
    }
}
