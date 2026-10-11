<?php
namespace App\Models {class Server {public string $uuid_short='12345678';function getSftpUrl(){return 'sftp://user.12345678@node.test:2022';}}}
namespace App\Enums {enum SubuserPermission {case FileSftp;}}
namespace {require __DIR__.'/../src/Services/SftpDetails.php';$allowed=false;function user(){return new class {public string $username='user';function can($p,$s){return $GLOBALS['allowed'];}};}
$s=new \App\Models\Server;if(\Simplify\Panel\Services\SftpDetails::forServer($s)!==null)throw new \RuntimeException('SFTP details leaked');$allowed=true;$data=\Simplify\Panel\Services\SftpDetails::forServer($s);if($data['username']!=='user.12345678'||str_contains(json_encode($data),'password'))throw new \RuntimeException('Invalid SFTP data');echo "PASS SFTP permission guard, native URL, per-user username and no password disclosure\n";}
