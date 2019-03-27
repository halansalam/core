<?php

namespace App\Classes\Connector;

use App\Key;
use App\ServerLog;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SFTP;
use phpseclib\Net\SSH2;

/**
 * Class SSHConnector
 * @package App\Classes
 */
class SSHConnector implements Connector
{
    /**
     * @var mixed
     */
    protected $connection;
    protected $server;
    protected $ssh;
    protected $key;
    protected $user_id;
    protected $username;
    /**
     * SSHConnector constructor.
     * @param \App\Server $server
     * @param null $user_id
     */
    public function __construct(\App\Server $server, $user_id)
    {
        ($key = Key::where([
            "user_id" => $user_id,
            "server_id" => $server->_id
        ])->first()) || abort(504,"SSH Anahtarınız yok.");
       try{
           $ssh = new SSH2($server->ip_address, $server->port);
       }catch (\Exception $exception){
           abort(504,"Sunucuya Bağlanılamadı");
       }
        $this->user_id = $user_id;
        $this->username = $key->username;
        $rsa = new RSA();
        $rsa->password = env("APP_KEY") . $user_id;
        $rsa->loadKey(file_get_contents(storage_path('keys') . DIRECTORY_SEPARATOR . $user_id));
        try{
            if(!$ssh->login($key->username,$rsa)){
                abort(504,"Anahtarınız ile giriş yapılamadı.");
            }
        }catch (\Exception $exception){
            abort(504,$exception->getMessage());
        }

        $this->ssh = $ssh;
        $this->key = $key;
        $this->server = $server;
    }

    /**
     * SSHConnector destructor
     */
    public function __destruct()
    {
        if($this->ssh){
            $this->ssh->disconnect();
        }
    }


    public function execute($command,$flag = true)
    {
        $output = $this->ssh->exec($command);
        if($flag){
            ServerLog::new($command,$output, $this->server->_id,$this->user_id);
        }
        return $output;
    }

    /**
     * @param $script
     * @param $parameters
     * @param null $extra
     * @return string
     */
    public function runScript($script, $parameters, $extra = null)
    {
        $this->sendFile(storage_path('app/scripts/' . $script->_id), '/tmp/' . $script->_id,0555);

        // First Let's Run Before Part Of the Script
        $query = ($script->root == 1) ? 'sudo ' : '';
        $query = $query . $script->language . ' /tmp/' . $script->_id . " before " . $parameters . $extra;
        $before = $this->execute($query);
        if($before != "ok\n"){
            abort(504, $before);
        }

        // Run Part Of The Script
        $query = ($script->root == 1) ? 'sudo ' : '';
        $query = $query . $script->language . ' /tmp/' . $script->_id . " run " . $parameters . $extra;
        $output = $this->execute($query);

        // Run After Part Of the Script
        $query = ($script->root == 1) ? 'sudo ' : '';
        $query = $query . $script->language . ' /tmp/' . $script->_id . " after " . $parameters . $extra;
        $after = $this->execute($query);
        if($after != "ok\n"){
            abort(504, $after);
        }

        return $output;
    }

    /**
     * @param $localPath
     * @param $remotePath
     * @param int $permissions
     * @return bool
     */
    public function sendFile($localPath, $remotePath, $permissions = 0644)
    {
        $sftp = new SFTP($this->server->ip_address, $this->server->port);
        $key = new RSA();
        $key->password = env("APP_KEY") . $this->user_id;
        $key->loadKey(file_get_contents(storage_path('keys') . DIRECTORY_SEPARATOR . $this->user_id));
        if(!$sftp->login($this->username,$key)){
            abort(504,"Anahtar Hatası");
        }
        return $sftp->put($remotePath, $localPath, SFTP::SOURCE_LOCAL_FILE);
    }

    /**
     * @param $localPath
     * @param $remotePath
     * @return bool
     */
    public function receiveFile($localPath, $remotePath)
    {
        $sftp = new SFTP($this->server->ip_address, $this->server->port);
        $key = new RSA();
        $key->password = env("APP_KEY") . $this->user_id;
        $key->loadKey(file_get_contents(storage_path('keys') . DIRECTORY_SEPARATOR . $this->user_id));
        if(!$sftp->login($this->username,$key)){
            abort(504,"Anahtar Hatası");
        }
        return $sftp->get($remotePath, $localPath);
    }

    /**
     * @param \App\Server $server
     * @param $username
     * @param $password
     * @param $user_id
     * @param $key
     * @return bool
     */
    public static function create(\App\Server $server, $username, $password, $user_id,$key)
    {
        if(!is_file(storage_path('keys') . DIRECTORY_SEPARATOR . $user_id)){
            $rsa = new RSA();
            $rsa->password = env("APP_KEY") . $user_id;
            $rsa->comment = "liman";
            $rsa->setPublicKeyFormat(RSA::PUBLIC_FORMAT_OPENSSH);
            $keys = $rsa->createKey(4096);
            file_put_contents(storage_path('keys') . DIRECTORY_SEPARATOR . $user_id, $keys["privatekey"]);
            file_put_contents(storage_path('keys') . DIRECTORY_SEPARATOR . $user_id . ".pub", $keys["publickey"]);
        }else{
            $keys["publickey"] = file_get_contents(storage_path('keys') . DIRECTORY_SEPARATOR . $user_id . ".pub");
        }
        try{
            $ssh = new SSH2($server->ip_address, $server->port);
        }catch (\Exception $exception){
            return __("Sunucuya bağlanılamadı");
        }

        $flag = $ssh->login($username,$password);

        if(!$flag){
            return __("Bu Kullanıcı Adı ve Şifre ile Giriş Yapılamadı.");
        }

        $query = 'sudo -S <<< "' . $password. '"';

        $ssh->exec($query . ' useradd -m liman');
        $flag = $ssh->exec('[ -d "/home/liman" ] && echo "OK"');
        if($flag != "OK\n"){
            $ssh->exec($query . ' mkdir -p /home/liman');
            $flag = $ssh->exec('[ -d "/home/liman" ] && echo "OK"');
            if($flag != "OK\n"){
                return __("Liman Kullanıcısı Eklenemedi.");
            }
        }

        $ssh->exec($query . ' mkdir -p /home/liman/.ssh');
        $flag = $ssh->exec('[ -d "/home/liman/.ssh" ] && echo "OK"');
        if($flag != "OK\n"){
            return __("Gerekli klasör oluşturulamadı.");
        }

        $ssh->exec($query . ' touch /home/liman/.ssh/authorized_keys');
        $flag = $ssh->exec('[ -e "/home/liman/.ssh" ] && echo "OK"');
        if($flag != "OK\n"){
            return __("Gerekli dosya oluşturulamadı.");
        }

        $ssh->exec('sudo -S sh -c "echo \'' . $keys["publickey"] .'\' >> /home/liman/.ssh/authorized_keys " <<< "' . $password .'"');
        $ssh->exec('sudo -S <<< "' . $password . '" passwd -l liman');
        $ssh->exec('sudo -S sh -c "echo \'liman  ALL=(ALL:ALL) NOPASSWD:ALL\' >> /etc/sudoers " <<< "' . $password .'"');
        $ssh->disconnect();

        $key->username = "liman";
        $key->save();
        try{
            new SSHConnector($server, $user_id);
        }catch (\Exception $exception){
            return __("Anahtar eklendi fakat giriş yapılamadı, lütfen yönetime bildiriniz.");
        }
        return "OK";
    }
}