<?php

namespace App\Http\Controllers;

use App\Extension;
use App\Key;
use App\Script;
use App\Server;
use App\ServerFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServerController extends Controller
{
    public function index(){
        return view('server.index',[
            "servers" => Server::all()
        ]);
    }


    public function add(Request $request){
        $data = $request->all();
        $server = new Server($data);
        $server->user_id = Auth::id();
        $server->extensions = [];
        $server->save();
        $output = Key::init(request('username'), request('password'), request('ip_address'),
            request('port'),Auth::id());
        $key = new Key($data);
        $key->server_id = $server->id;
        $key->user_id = Auth::id();
        $key->save();
        return [
            "result" => 200,
            "id" => $server->id,
            "output" => $output
        ];
    }

    public function remove(){
        Server::where('_id',\request('server_id'))->delete();
        Key::where('server_id', \request('server_id'))->delete();
        return [
            "result" => 200
        ];
    }

    public function one(){
        $scripts = Script::where('features','server')->get();
        $server = \request('server');
        $services = $server->extensions;
        for ($i = 0 ; $i < count($services); $i++){
            if($services[$i] == "kullanıcılar" || $services[$i] == "gruplar"){
                unset($services[$i]);
                array_push($services,'ldap');
            }
        }
        return view('server.one',[
            "stats" => \request('server')->run("df -h"),
            "server" => \request('server'),
            "services" => $services,
            "scripts" => $scripts
        ]);
    }

    public function run(){
        $output = Server::where('_id',\request('server_id'))->first()->run(\request('command'));
        return [
            "result" => 200,
            "data" => $output
        ];
    }

    public function runScript(){
        $script = Script::where('_id',\request('script_id'))->first();
        $inputs = explode(',',$script->inputs);
        $params = "";
        foreach ($inputs as $input){
            $params = $params. " " . \request(explode(':',$input)[0]);
        }
        $output = Server::where('_id',\request('server_id'))->first()->runScript($script, $params);
        return [
            "result" => 200,
            "data" => $output
        ];
    }

    public function check(){
        $feature = Extension::where('name','like',request('feature'))->first();
        $output = Server::where('_id',\request('server_id'))->first()->isRunning($feature->service);
        if($output == "active\n"){
            $result = 200;
        }else if($output === "inactive\n"){
            $result = 202;
        }else{
            $result = 201;
        }
        return [
            "result" => $result,
            "data" => $output
        ];
    }

    public function network(){
        $server = \request('server');
        $parameters = \request('ip') . ' ' . \request('cidr') . ' ' . \request('gateway') . ' ' . \request('interface');
        $server->systemScript('network',$parameters);
        sleep(3);
        $output = shell_exec("echo exit | telnet " . \request('ip') ." " . $server->port);
        if (strpos($output,"Connected to " . \request('ip')) == false){
            return [
                "result" => 201,
                "data" => $output
            ];
        }
        $server->update([
            'ip_address' => \request('ip')
        ]);
        Key::init($server->key["username"], request('password'), \request('ip'),
            $server->port,Auth::id());
        return [
            "result" => 200,
            "data" => $output
        ];
    }

    public function hostname(){
        $server = \request('server');
        $output = $server->systemScript('hostname',\request('hostname'));
        return [
            "result" => 200,
            "data" => $output
        ];
    }

    public function isAlive(){
        $output = shell_exec("echo exit | telnet " . \request('ip') ." " . \request('port'));
        if (strpos($output,"Connected to " . \request('ip')) == false){
            return [
                "result" => 201,
                "data" => $output
            ];
        }else{
            return [
                "result" => 200,
                "data" => $output
            ];
        }
    }
}
