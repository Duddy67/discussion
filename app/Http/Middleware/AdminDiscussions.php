<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminDiscussions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $routeName = $request->route()->getName();

        $create = ['admin.discussions.index', 'admin.discussions.create', 'admin.discussions.store'];
        $update = ['admin.discussions.update', 'admin.discussions.edit'];
        $delete = ['admin.discussions.destroy', 'admin.discussions.massDestroy'];

        if (in_array($routeName, $create) && !auth()->user()->isAllowedTo('create-discussion')) {
            return redirect()->route('admin')->with('error', __('messages.generic.access_not_auth'));
        }

        if (in_array($routeName, $update) && !auth()->user()->isAllowedTo('update-discussion') && !auth()->user()->isAllowedTo('update-own-discussion')) {
            return redirect()->route('admin.discussions.index')->with('error', __('messages.discussion.edit_not_auth'));
        }

        if (in_array($routeName, $delete) && !auth()->user()->isAllowedTo('delete-discussion') && !auth()->user()->isAllowedTo('delete-own-discussion')) {
            return redirect()->route('admin.discussions.index')->with('error', __('messages.discussion.delete_not_auth'));
        }

        return $next($request);
    }
}
