<?php
declare(strict_types=1);
namespace Filament\Pages { class Page {} }
namespace Filament\Forms\Concerns { trait InteractsWithForms {} }
namespace Filament\Forms\Contracts { interface HasForms {} }
namespace Filament\Notifications { class Notification { public static array $titles=[]; public static function make(){return new self;} public function title($title){self::$titles[]=$title;return $this;} public function body($value){return $this;} public function danger(){return $this;} public function send(){} } }
namespace {
 require __DIR__.'/../src/Services/DependencyCheck.php';
 require __DIR__.'/../src/Filament/Admin/Pages/SimplifySettings.php';
 function user(){return new class{function isRootAdmin(){return true;}};}
 function abort_unless($ok,$code){if(!$ok)throw new \RuntimeException((string)$code);}
 function base_path(){return sys_get_temp_dir().'/simplify-nonexistent-panel';}
 function plugin_path($id){return __DIR__.'/..';}
 function config($name){return $name==='app.version'?'1.0.0-beta38':'sync';}
 $page=new \Simplify\Panel\Filament\Admin\Pages\SimplifySettings;
 // No form or save services exist here. A direct save must return before accessing them.
 $page->save();
 if($page->requirementsReady()||!in_array('Setup blocked',\Filament\Notifications\Notification::$titles,true))throw new \RuntimeException('direct save bypassed dependency gate');
 echo "PASS direct setup save cannot bypass failed dependencies or write settings\n";
}
