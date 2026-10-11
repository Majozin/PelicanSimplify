<?php

declare(strict_types=1);

namespace Simplify\Panel\Filament\Admin\Pages;

use App\Models\Egg;
use App\Models\Node;
use App\Models\User;
use App\Models\Server;
use Simplify\Panel\Services\AppearanceSettings;
use Simplify\Panel\Services\ServerAppearance;
use Filament\Forms\Components\ColorPicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Simplify\Panel\Services\CreatorAccess;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class SimplifySettings extends Page implements HasForms
{
    use InteractsWithForms;
    protected static ?string $navigationLabel = 'PelicanSimplify settings';
    protected static ?string $title = 'PelicanSimplify settings';
    protected static ?string $slug = 'pelican-simplify-settings';
    protected string $view = 'pelican-simplify::admin-settings';
    public static function getNavigationIcon(): \Illuminate\Contracts\Support\Htmlable
    {
        return new \Illuminate\Support\HtmlString(view('pelican-simplify::simplify-icon')->render());
    }
    public ?array $data = [];
    public array $requirements = [];
    public bool $setupStarted = false;
    public bool $showDiagnostics = false;
    public function refreshRequirements(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->requirements = \Simplify\Panel\Services\DependencyCheck::inspect(base_path(), plugin_path('pelican-simplify'));
    }
    public function requirementsReady(): bool
    {
        return \Simplify\Panel\Services\DependencyCheck::ready($this->requirements);
    }
    public function beginSetup(): void
    {
        $this->refreshRequirements();
        $this->setupStarted = $this->requirementsReady();
    }
    public function toggleDiagnostics(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->showDiagnostics = !$this->showDiagnostics;
        if ($this->showDiagnostics) $this->refreshRequirements();
    }
    public function requirementGroups(): array
    {
        $groups = [];
        foreach ($this->requirements as $check) {
            $name = $check['name'];
            $group = match (true) {
                str_starts_with($name, 'PHP') => 'PHP runtime',
                str_starts_with($name, 'Composer:') || str_contains($name, 'Composer') || str_contains($name, 'Tabler') => 'Panel components',
                str_starts_with($name, 'Plugin asset:') || str_starts_with($name, 'Built ') || str_starts_with($name, 'Node.') || $name === 'Yarn' => 'Styles and scripts',
                str_starts_with($name, 'Writable ') || str_contains($name, 'storage') => 'Storage permissions',
                default => 'Panel services',
            };
            $groups[$group][] = $check;
        }
        return $groups;
    }

    public static function canAccess(): bool { return (bool) user()?->isRootAdmin(); }
    public function needsSetup(): bool { return !\Simplify\Panel\Services\FeatureSettings::enabled('setup_completed'); }
    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->form->fill([...CreatorAccess::settings(),'creation_defaults'=>\Simplify\Panel\Services\CreationDefaults::all(),'appearance'=>AppearanceSettings::all(),'features'=>\Simplify\Panel\Services\FeatureSettings::all(),'web'=>\Simplify\Panel\Services\Extensions::webInstalled()?app('simplify.web')->options():[]]);
    }
    public function form(Schema $schema): Schema
    {
        $groups = ['Interface','Appearance','Access and visibility','User access','Server features','Automation'];
        $featureSections = array_map(function(string $group){
                    $definitions=array_filter(\Simplify\Panel\Services\FeatureSettings::definitions(),fn($item)=>($item[3]??'Server features')===$group);
                    $fields=array_map(fn(array $definition,string $key)=>Toggle::make('features.'.$key)->label($definition[0])->helperText($definition[1]),$definitions,array_keys($definitions));
                    if($group==='Appearance')$fields=[...$fields,
                        ColorPicker::make('appearance.accent_color')->label('Panel accent color')->hex()->required()->rules(['regex:/^#[0-9a-fA-F]{6}$/'])->helperText('Used throughout the shared interface and by every server when separate colors are off.'),
                        Toggle::make('appearance.individual_colors')->label('Use separate server colors')->live()->helperText('On assigns a color to every server without one. Off makes all servers use the panel accent; saved colors are retained.'),
                        Select::make('appearance.server_uuid')->label('Server color to edit')->searchable()->options(fn()=>Server::query()->orderBy('name')->pluck('name','uuid')->all())->live()->visible(fn(Get $get)=>$get('appearance.individual_colors'))->afterStateUpdated(function(?string $state,Set $set):void{$server=$state?Server::query()->where('uuid',$state)->first():null;$set('appearance.server_color',$server?ServerAppearance::color($server):null);}),
                        ColorPicker::make('appearance.server_color')->label('Selected server accent')->hex()->nullable()->rules(['regex:/^#[0-9a-fA-F]{6}$/'])->visible(fn(Get $get)=>$get('appearance.individual_colors')&&filled($get('appearance.server_uuid')))->helperText('Choose a color and save settings to apply it to this server.'),
                    ];
                    return Section::make($group)->columns(2)->schema($fields);
                },$groups);
        $sections=[
            Section::make('User resource allowances')->columns(2)->description('Allow selected owners to adjust resources within these limits. The easy creator uses these same grants. This does not grant admin-panel access.')->schema([
                Toggle::make('enabled')->label('Enable user resource allowances / optional server creation'),
                Select::make('user_ids')->label('Allowed users')->multiple()->searchable()->options(fn () => User::query()->orderBy('username')->pluck('username', 'id')->all()),
                Select::make('node_ids')->label('Hosting machine')->multiple()->searchable()->options(fn () => Node::query()->pluck('name', 'id')->all())->helperText('Select the hosting nodes available for self-service creation. Users do not choose ports or nodes.'),
                Toggle::make('minecraft_enabled')->label('Allow Minecraft'),
                Select::make('other_egg_ids')->label('Other games available to users')->multiple()->searchable()->options(fn () => Egg::query()->orderBy('name')->pluck('name', 'id')->all()),
            ]),
            Section::make('Allowance per user')->description('Server count, RAM and disk include all servers owned by that user. Admin creators retain their existing privileges.')->columns(2)->schema([
                TextInput::make('max_servers')->label('Maximum servers')->integer()->minValue(1)->maxValue(100)->required(),
                TextInput::make('max_memory_mb')->label('Total RAM (MB)')->integer()->minValue(1024)->maxValue(131072)->required(),
                TextInput::make('max_disk_mb')->label('Total disk (MB)')->integer()->minValue(1024)->maxValue(10485760)->required(),
                TextInput::make('max_cpu')->label('CPU per server (%)')->integer()->minValue(1)->maxValue(1600)->required(),
                TextInput::make('max_backups')->label('Backup slots per server')->integer()->minValue(0)->maxValue(100)->required(),
            ]),
        ];
        $categories = array_combine($groups, array_map(fn($section) => [$section], $featureSections));
        $categories['Server defaults'] = [Section::make('Default resources for new servers')
            ->description('Choose the starting CPU, RAM and disk for each Minecraft type. These apply to new servers and remain subject to user allowances. Existing servers keep their resources.')
            ->schema(array_map(fn(string $type, string $label) => Section::make($label)->columns(3)->schema([
                TextInput::make('creation_defaults.'.$type.'.cpu')->label('CPU (%)')->integer()->minValue(1)->maxValue(1600)->required()->helperText('100% is one core; 200% is two cores.'),
                TextInput::make('creation_defaults.'.$type.'.ram_gb')->label('RAM (GB)')->integer()->minValue(1)->maxValue(128)->required(),
                TextInput::make('creation_defaults.'.$type.'.disk_mb')->label('Disk (MB)')->integer()->minValue(1024)->maxValue(10485760)->required(),
            ]), ['vanilla','plus','modded'], ['Vanilla','Vanilla+','Modded / modpacks']))];
        $categories['Creator access'] = [$sections[0]];
        $categories['Resource limits'] = [$sections[1]];
        if (\Simplify\Panel\Services\Extensions::webInstalled()) $categories['Website'] = app('simplify.web')->settingsSections();
        if ($this->needsSetup()) {
            return $schema->statePath('data')->components([
                \Filament\Schemas\Components\Wizard::make(
                    array_map(fn(string $label, array $content) => \Filament\Schemas\Components\Wizard\Step::make($label)->schema($content), array_keys($categories), array_values($categories))
                )->skippable(false)->columnSpanFull()
                    ->nextAction(fn (\Filament\Actions\Action $action) => $action->label('Next')->color('gray')->extraAttributes(['class' => 'ssc-next-button']))
                    ->previousAction(fn (\Filament\Actions\Action $action) => $action->label('Back')->color('gray')->extraAttributes(['class' => 'ssc-back-button']))
                    ->submitAction(new \Illuminate\Support\HtmlString(view('pelican-simplify::setup-submit')->render())),
            ]);
        }
        return $schema->statePath('data')->components([
            \Filament\Schemas\Components\Tabs::make('Simplify settings')->columnSpanFull()->tabs(
                array_map(fn(string $label, array $content) => \Filament\Schemas\Components\Tabs\Tab::make($label)->schema($content), array_keys($categories), array_values($categories))
            ),
        ]);
    }
    public function setupSteps(): array
    {
        $steps = ['Interface','Appearance','Access and visibility','User access','Server features','Automation','Server defaults','Creator access','Resource limits'];
        if (\Simplify\Panel\Services\Extensions::webInstalled()) $steps[] = 'Website';
        return $steps;
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->refreshRequirements();
        if (!$this->requirementsReady()) {
            $this->setupStarted = false;
            $this->showDiagnostics = true;
            Notification::make()->title('Setup blocked')->body('Fix the failed dependency checks and check again.')->danger()->send();
            return;
        }
        $firstSetup = $this->needsSetup();
        if ($firstSetup && !$this->setupStarted) return;
        $state=$this->form->getState();
        if(!empty($state['appearance']['server_uuid'])&&!empty($state['appearance']['server_color'])){
            $server=Server::query()->where('uuid',$state['appearance']['server_uuid'])->firstOrFail();
            ServerAppearance::save($server,$state['appearance']['server_color']);
        }
        AppearanceSettings::save($state['appearance']??[]);
        $data = array_intersect_key($state, CreatorAccess::defaults());
        foreach (['user_ids' => User::class, 'node_ids' => Node::class, 'other_egg_ids' => Egg::class] as $key => $model) {
            $data[$key] = $model::query()->whereIn('id', $data[$key] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->all();
        }
        if ($data['enabled'] && (!$data['user_ids'] || !$data['node_ids'] || (!$data['minecraft_enabled'] && !$data['other_egg_ids']))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['data.enabled' => 'Choose users, a hosting machine and at least one game first.']);
        }
        \Simplify\Panel\Services\CreationDefaults::save($state['creation_defaults']??[]);
        if (!Storage::disk('local')->put(CreatorAccess::FILE, json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT))) {
            throw new \RuntimeException('Could not save creator settings.');
        }
        if(\Simplify\Panel\Services\Extensions::webInstalled())app('simplify.web')->saveOptions($state['web']??[]);
        app(CreatorAccess::class)->reset();
        \Simplify\Panel\Services\FeatureSettings::save($state['features']??[],true);
        Notification::make()->title('PelicanSimplify settings saved')->success()->send();
        if ($firstSetup) {
            $this->redirect(\Simplify\Panel\Filament\App\Pages\Dashboard::getUrl(panel: 'app'));
        } else {
            $this->dispatch('simplify-settings-saved');
        }
    }
}
