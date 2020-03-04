<?php

namespace App\Http\Controllers\Extension\Sandbox;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\UserSettings;
use App\Permission;
use App\Classes\Sandbox\PHPSandbox;
use Carbon\Carbon;

class MainController extends Controller
{
    private $extension;

    public function __construct()
    {
        $this->middleware(function($request,$next){
            $this->extension = json_decode(file_get_contents(env("EXTENSIONS_PATH") .strtolower(extension()->name) . DIRECTORY_SEPARATOR . "db.json"),true);

            list($result,$redirect) = $this->checkForMissingSettings();

            if(!$result){
                return $redirect;
            }

            $this->checkPermissions();
            $this->sandbox = sandbox();
            return $next($request);
        });
    }

    public function getAPI()
    {
        $page = request('page') ? request('page') : 'index';

        list($output, $timestamp) = $this->executeSandbox($page);

        system_log(7,"EXTENSION_RENDER_PAGE",[
            "extension_id" => extension()->id,
            "server_id" => server()->id,
            "view" => ""
        ]);
        
        return view('extension_pages.server', [
            "viewName" => "",
            "view" => $output,
            "timestamp" => $timestamp
        ]);
    }

    public function postAPI()
    {
        list($output, $timestamp) = $this->executeSandbox(request('function_name'));

        system_log(7,"EXTENSION_RUN",[
            "extension_id" => extension()->id,
            "server_id" => server()->id,
            "target_name" => request('function_name')
        ]);

        $code = 200;
        try{
            $json = json_decode($output,true);
            if(array_key_exists("status",$json)){
                $code = intval($json["status"]);
            }
        }catch (\Exception $exception){};

        if(is_json($output)){
          return response()->json(json_decode($output), $code);
        }

        return response($output, $code);
    }

    private function checkForMissingSettings(){
        foreach ($this->extension["database"] as $setting) {
            if(isset($setting["required"]) && $setting["required"] === false) continue;
            if (!UserSettings::where([
                "user_id" => user()->id,
                "server_id" => server()->id,
                "name" => $setting["variable"]
            ])->exists()) {
                system_log(7,"EXTENSION_MISSING_SETTINGS",[
                    "extension_id" => extension()->id
                ]);
                return [false,redirect(route('extension_server_settings_page', [
                    "server_id" => server()->id,
                    "extension_id" => extension()->id
                ]))];
            }
        }
        return [true,null];
    }

    private function checkPermissions()
    {
        if (!Permission::can(auth()->id(), "function", "name",strtolower(extension()->name) ,request('function_name'))) {
            system_log(7,"EXTENSION_NO_PERMISSION",[
                "extension_id" => extension()->id,
                "target_name" => request('function_name')
            ]);
            $function = request("function_name");
            $extensionJson = json_decode(file_get_contents(env("EXTENSIONS_PATH") .strtolower(extension()->name) . DIRECTORY_SEPARATOR . "db.json"),true);
        
            $functions = collect([]);
    
            if(array_key_exists("functions",$extensionJson)){
                $functions = collect($extensionJson["functions"]);
            }

            $isActive = "false";
            $functionOptions = $functions->where('name', request("function_name"))->first();
            if($functionOptions){
                $isActive = $functionOptions["isActive"];
            }
            if($isActive == "true" && !Permission::can(user()->id,"function","name",strtolower(extension()->name) , $function)){
                abort(403, $function . " için yetkiniz yok.");
            }
        }
        return true;
    }

    private function executeSandbox($function){
        $command = $this->sandbox->command($function);

        $before = Carbon::now();
        $output = shell_exec($command);
        return [$output, $before->diffInMilliseconds(Carbon::now()) / 1000];
    }
}
