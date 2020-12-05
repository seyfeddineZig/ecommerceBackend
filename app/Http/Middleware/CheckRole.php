<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use Illuminate\Support\Facades\Route;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $role)
    {
        $exceptions = [
            'IMPORT_PRODUCT_FILES',
            'VALIDATE_INVENTORY',
            'VALIDATE_INPUT',
            'VALIDATE_OUTPUT',
            'POST_PRODUCT'
        ];

        $route = Route::getRoutes()->match($request);
        $currentroute = $route->getActionName();

        if(in_array($role, $exceptions)){
            $user_role = $role;
        }
        else{
            $method = $route->methods[0];
            $user_role = $method . '_' . $role;
        }
        

        if (! Auth::user()->group->hasRole($user_role) ) {

            return response()->json(['Unauthorized access'], 401);
            
        }

        return $next($request);
    }
}
