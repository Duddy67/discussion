<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Discussions
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

        $create = ['discussions.index', 'discussions.create', 'discussions.store'];
        $update = ['discussions.update', 'discussions.edit'];
        $delete = ['discussions.destroy', 'discussions.massDestroy'];

        if (in_array($routeName, $create) && !auth()->user()->isAllowedTo('create-discussions')) {
            return redirect()->route('/')->with('error', __('messages.generic.access_not_auth'));
        }

        if (in_array($routeName, $update) &&
            !auth()->user()->isAllowedTo('update-discussions') && !auth()->user()->isAllowedTo('update-own-discussions')) {
            return redirect()->route('/')->with('error', __('messages.discussion.edit_not_auth'));
        }

        if (in_array($routeName, $delete) &&
            !auth()->user()->isAllowedTo('delete-discussions') && !auth()->user()->isAllowedTo('delete-own-discussions')) {
            return redirect()->route('/')->with('error', __('messages.discussion.delete_not_auth'));
        }

        return $next($request);
    }
}
