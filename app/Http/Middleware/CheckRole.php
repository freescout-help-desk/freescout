<?php

namespace App\Http\Middleware;

use App\User;
use Closure;

class CheckRole{

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		// Get the required roles from the route
		$roles = $this->getRequiredRoleForRoute($request->route());

		// Check if a role is required for the route, and
		// if so, ensure that the user has that role.
		if (!$roles || in_array($request->user()->getRoleName(), $roles)) {
			return $next($request);
		}
		abort(403, __('You are not authorized to access this resource.'));
		// return response([
		// 	'error' => [
		// 		'code' => 'INSUFFICIENT_ROLE',
		// 		'description' => 
		// 	]
		// ], 401);
	}

	private function getRequiredRoleForRoute($route)
	{
		$actions = $route->getAction();
		return isset($actions['roles']) ? $actions['roles'] : null;
	}

}
