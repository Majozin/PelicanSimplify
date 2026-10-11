<?php
declare(strict_types=1);
// Safe plugin updates: checksum guard, install status kept, failed installs restore the previous version.
namespace Illuminate\Support\Facades {
 class File {
  public static array $fs = [];
  public static function json($path) { return json_decode(self::$fs[$path], true); }
  public static function put($path, $content) { self::$fs[$path] = $content; return true; }
  public static function deleteDirectory($dir) { foreach (array_keys(self::$fs) as $k) if (str_starts_with($k, $dir.'/')) unset(self::$fs[$k]); return true; }
  public static function ensureDirectoryExists($dir) {}
  public static function isDirectory($dir) { foreach (self::$fs as $k => $v) if (str_starts_with($k, $dir.'/')) return true; return false; }
  public static function copyDirectory($from, $to) { foreach (self::$fs as $k => $v) if (str_starts_with($k, $from.'/')) self::$fs[$to.substr($k, strlen($from))] = $v; return true; }
 }
 class Cache {
  public static function lock(...$a) { return new class { public function get() { return true; } public function release() {} }; }
  public static function remember($k, $t, $c) { return $c(); }
  public static function forget($k) {}
 }
 class Http {
  public static string $body = 'zip';
  public static array $json = [];
  public static function timeout($s) { return new self; }
  public function connectTimeout($s) { return $this; }
  public function withHeaders($h) { return $this; }
  public function throw() { return $this; }
  public function get($url) { return $this; }
  public function json() { return self::$json; }
  public function body() { return self::$body; }
 }
 class Process {
  public static bool $installOk = true;
  public static array $runs = [];
  public static function path($p) { return new self; }
  public function timeout($s) { return $this; }
  public function run(array $command) {
   self::$runs[] = implode(' ', $command);
   $ok = !in_array('simplify:plugin-install', $command, true) || self::$installOk;
   return new class($ok) implements \Illuminate\Contracts\Process\ProcessResult { public function __construct(private bool $ok) {} public function successful(): bool { return $this->ok; } public function errorOutput(): string { return $this->ok ? '' : 'boom'; } public function output(): string { return ''; } };
  }
 }
}
namespace Illuminate\Contracts\Process { interface ProcessResult {} }
namespace Illuminate\Http { class UploadedFile { public function __construct(...$a) {} } }
namespace App\Services\Helpers {
 class PluginService {
  public static int $extracts = 0;
  public function downloadPluginFromUrl($url, $id) { $this->extract($id); }
  public function downloadPluginFromFile($file, $id) { $this->extract($id); }
  private function extract($id) { self::$extracts++; \Illuminate\Support\Facades\File::deleteDirectory('/plugins/'.$id); \Illuminate\Support\Facades\File::$fs['/plugins/'.$id.'/plugin.json'] = json_encode(['id' => $id, 'version' => '2.0']); \Illuminate\Support\Facades\File::$fs['/plugins/'.$id.'/code.php'] = 'new'; }
 }
}
namespace App\Models {
 class Plugin {
  public string $id = 'demo'; public string $name = 'Demo'; public string $version = '1.0'; public ?string $update_url = 'https://example.test/update.json';
  public function getDownloadUrlForUpdate() { return 'https://example.test/demo.zip'; }
  public static function refreshRows() {}
 }
}
namespace {
 require __DIR__.'/../src/Services/PluginUpdater.php';
 use Illuminate\Support\Facades\{File, Http, Process};
 use Simplify\Panel\Services\PluginUpdater as U;
 function plugin_path($id) { return '/plugins/'.$id; }
 function storage_path($p) { return '/storage/'.$p; }
 function base_path() { return '/panel'; }
 function app($c) { return new $c; }
 function report($e) {}
 function cache() { return new class { public function forget($k) {} }; }
 function config($k) { return '1.0.0-beta38'; }
 function check($ok, $m) { if (!$ok) throw new \RuntimeException($m); echo "PASS $m\n"; }
 $reset = function () { File::$fs = ['/plugins/demo/plugin.json' => json_encode(['id' => 'demo', 'version' => '1.0', 'meta' => ['status' => 'enabled']]), '/plugins/demo/code.php' => 'old']; \App\Services\Helpers\PluginService::$extracts = 0; Process::$runs = []; };

 $reset(); Http::$body = 'zip'; Http::$json = ['1.0.0-beta38' => ['version' => '2.0', 'download_url' => 'https://example.test/demo.zip', 'sha256' => hash('sha256', 'zip')]]; Process::$installOk = true;
 $r = U::update(new \App\Models\Plugin);
 $manifest = File::json('/plugins/demo/plugin.json');
 check($r['ok'] && $manifest['version'] === '2.0' && $manifest['meta']['status'] === 'enabled', 'update keeps the plugin enabled');
 check((bool) preg_grep('/simplify:plugin-install demo --enable/', Process::$runs), 'install runs in a fresh process with the previous status');

 $reset(); Http::$json = ['1.0.0-beta38' => ['version' => '2.0', 'download_url' => 'https://example.test/demo.zip', 'sha256' => str_repeat('0', 64)]];
 $r = U::update(new \App\Models\Plugin);
 check(!$r['ok'] && \App\Services\Helpers\PluginService::$extracts === 0 && File::$fs['/plugins/demo/code.php'] === 'old', 'checksum mismatch never touches the installed plugin');

 $reset(); Http::$json = ['1.0.0-beta38' => ['version' => '2.0', 'download_url' => 'https://example.test/demo.zip', 'sha256' => hash('sha256', 'zip')]]; Process::$installOk = false;
 $r = U::update(new \App\Models\Plugin);
 check(!$r['ok'] && File::$fs['/plugins/demo/code.php'] === 'old' && File::json('/plugins/demo/plugin.json')['version'] === '1.0', 'failed install restores the previous version');
}
