<?php

namespace App\Http\Middleware;

use App\Script;
use Closure;

class ScriptParameters
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $extension_id = null;
        if($request->route('extension_id') != null){
            $extension_id = $request->route('extension_id');
        }
        if($request->has('extension_id')){
            $extension_id = $request->get('extension_id');
        }

        $extension = \App\Extension::where('_id',$extension_id)->first();

        $url = null;
        if($request->route('unique_code') != null){
            $url = $request->route('unique_code');
            $url = explode("&",$url)[0];
        }else if($request->has('url')){
            $url = $request->get('url');
        }
        $scripts = [];
        if(isset($extension->views[$url])){
            foreach ($extension->views[$url] as $script_name){
                array_push($scripts,Script::where('extensions','like',$extension->name)->where('unique_code',$script_name)->first());
            }
        }else{
            array_push($scripts,Script::where('unique_code',$url)->first());
        }

        $request->request->add(['scripts' => $scripts]);
        $request->request->add(['extension' => $extension]);
        $request->request->add(['url' => $url]);
        return $next($request);
    }
}