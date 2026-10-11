<?php
declare(strict_types=1);
namespace Test {class Component {public array $options=[];public function __construct(public ?string $label=null){}public static function make($label=null){return new static($label);}public function __call($method,$arguments){$this->options[$method]=$arguments;return $this;}}}
namespace Livewire {trait WithFileUploads {}}
namespace Filament\Pages {class Page {}}
namespace Filament\Forms\Concerns {trait InteractsWithForms {}}
namespace Filament\Forms\Contracts {interface HasForms {}}
namespace Filament\Forms\Components {class Repeater extends \Test\Component {}class Toggle extends \Test\Component {}class TextInput extends \Test\Component {}class Select extends \Test\Component {}class ColorPicker extends \Test\Component {}}
namespace Filament\Schemas {class Schema extends \Test\Component {}}
namespace Filament\Schemas\Components {class View extends \Test\Component {}class Section extends \Test\Component {}class Wizard extends \Test\Component {public static function make($steps=null){$instance=new self;$instance->options['steps']=$steps;return $instance;}}class Tabs extends \Test\Component {}}
namespace Filament\Schemas\Components\Wizard {class Step extends \Test\Component {}}
namespace Filament\Schemas\Components\Tabs {class Tab extends \Test\Component {}}
namespace Illuminate\Support {class HtmlString {public function __construct($value){}}}
namespace Simplify\Panel\Services {
 class FeatureSettings {public static bool $complete=false;public static function enabled($key){return self::$complete;}public static function definitions(){return ['dashboard'=>['Dashboard','Description',true,'Interface'],'theme'=>['Theme','Description',true,'Appearance']];}}
 class Extensions {public static function webInstalled(){return false;}}
}
namespace {
 require __DIR__.'/../src/Filament/Admin/Pages/SimplifySettings.php';
 function view($name){return new class{function render(){return '<button>Finish setup</button>';}};}
 $page=new \Simplify\Panel\Filament\Admin\Pages\SimplifySettings;
 $schema=$page->form(new \Filament\Schemas\Schema);$wizard=$schema->options['components'][0][0];$steps=$wizard->options['steps'];
 if(array_map(fn($step)=>$step->label,$steps)!==$page->setupSteps())throw new \RuntimeException('progress navigation and wizard steps disagree');
 if($steps[1]->label!=='Appearance'||count($steps[1]->options['schema'][0])!==1||$steps[6]->label!=='Server defaults')throw new \RuntimeException('categories not separate pages');
 if(!isset($wizard->options['submitAction'])||$wizard->options['skippable']!==[false])throw new \RuntimeException('wizard final save or validation guard missing');
 \Simplify\Panel\Services\FeatureSettings::$complete=true;
 $schema=$page->form(new \Filament\Schemas\Schema);$tabs=$schema->options['components'][0][0]->options['tabs'][0];
 if(array_map(fn($tab)=>$tab->label,$tabs)!==[...$page->setupSteps(),'Server banners'])throw new \RuntimeException('settings tabs lost a setup category');
 $profiles=$tabs[6]->options['schema'][0][0]->options['schema'][0];
 if(count($profiles)!==3||$profiles[2]->label!=='Modded / modpacks'||count($profiles[2]->options['schema'][0])!==3)throw new \RuntimeException('CPU/RAM/disk profiles missing from settings');
 echo "PASS separate setup pages, matching numbered navigation, final-step submit, category tabs and editable modpack resource columns\n";
}
