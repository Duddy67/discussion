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

        $create = ['admin.discussion.categories.index', 'admin.discussion.categories.create', 'admin.discussion.categories.store'];
        $update = ['admin.discussion.categories.update', 'admin.discussion.categories.edit'];
        $delete = ['admin.discussion.categories.destroy', 'admin.discussion.categories.massDestroy'];

	if (in_array($routeName, $create) && !auth()->user()->isAllowedTo('create-discussion-category')) {
	    return redirect()->route('admin')->with('error', __('messages.generic.access_not_auth'));
	}

	if (in_array($routeName, $update) && !auth()->user()->isAllowedTo('update-discussion-category')) {
	    return redirect()->route('admin.discussion.categories.index')->with('error', __('messages.category.edit_not_auth'));
	}

	if (in_array($routeName, $delete) && !auth()->user()->isAllowedTo('delete-discussion-category')) {
	    return redirect()->route('admin.discussion.categories.index')->with('error', __('messages.category.delete_not_auth'));
	}

        return $next($request);
    }
}
