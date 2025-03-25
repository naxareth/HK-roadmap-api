<?php
namespace Middleware;

abstract class Middleware {
    abstract public function handle($request, $next);
    
    protected function next($request) {
        return function($request) {
            return $request;
        };
    }
}
