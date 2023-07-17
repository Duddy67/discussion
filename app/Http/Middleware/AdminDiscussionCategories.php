<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminDiscussionCategories
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
	$routeName = $request->route()->getName();

        $create = ['admin.discussions.categories.index', 'admin.discussions.categories.create', 'admin.discussions.categories.store'];
        $update = ['admin.discussions.categories.update', 'admin.discussions.categories.edit'];
        $delete = ['admin.discussions.categories.destroy', 'admin.discussions.categories.massDestroy'];

	if (in_array($routeName, $create) && !auth()->user()->isAllowedTo('create-discussion-categories')) {
	    return redirect()->route('admin')->with('error', __('messages.generic.access_not_auth'));
	}

	if (in_array($routeName, $update) && !auth()->user()->isAllowedTo('update-discussion-categories')) {
	    return redirect()->route('admin.discussions.categories.index')->with('error', __('messages.category.edit_not_auth'));
	}

	if (in_array($routeName, $delete) && !auth()->user()->isAllowedTo('delete-discussion-categories')) {
	    return redirect()->route('admin.discussions.categories.index')->with('error', __('messages.category.delete_not_auth'));
	}

        return $next($request);
    }
}
