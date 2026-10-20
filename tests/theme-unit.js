const fs=require('fs'),vm=require('vm'),assert=require('assert');
const source=fs.readFileSync(__dirname+'/../resources/views/js/core.js','utf8');
const storage=new Map();
// Each page (dashboard or embedded workspace) runs its own copy of core.js.
function page(config,frame=null){
 const classes=new Set(),events={},style={};
 const root={style:{setProperty(k,v){style[k]=v},removeProperty(k){delete style[k]}},dataset:{},classList:{toggle(k,v){v?classes.add(k):classes.delete(k)},add:k=>classes.add(k),remove:k=>classes.delete(k),contains:k=>classes.has(k)}};
 const window={simplifyThemeConfig:config,frameElement:frame,dispatchEvent(e){for(const fn of events[e.type]||[])fn(e)},addEventListener(t,fn){(events[t]??=[]).push(fn)}};
 window.parent=window;
 const context={window,document:{documentElement:root,addEventListener(){},querySelectorAll:()=>[],body:null},localStorage:{getItem:k=>storage.get(k),setItem:(k,v)=>storage.set(k,v),removeItem:k=>storage.delete(k)},matchMedia:()=>({matches:false,addEventListener(){}}),CustomEvent:class{constructor(type,{detail}){this.type=type;this.detail=detail}},getComputedStyle:()=>({getPropertyValue:()=>''}),setTimeout,clearTimeout};
 vm.createContext(context);vm.runInContext(source,context);
 return {window,root,style,has:k=>classes.has(k)};
}
const config={global_theme:true,legacy_theme:true,console_dark:true,accent_color:'#446688',legacy_page:false};
const dashboard=page(config);
assert.equal(dashboard.window.SimplifyTheme.preference(),'system');assert(dashboard.has('ps-theme'));assert(!dashboard.has('ps-frame'));
assert.equal(dashboard.style['--ps-accent'],'#446688');assert.equal(dashboard.style['--ps-on-accent'],'#ffffff');assert(dashboard.has('ps-console-dark'));
dashboard.window.SimplifyTheme.set('dark');assert(dashboard.has('dark'));assert.equal(storage.get('pelicanSimplify.theme'),'dark');assert.equal(storage.get('theme'),'dark');
// Embedded workspaces read their kind and accent from the iframe element before the page paints.
const creator=page(config,{dataset:{psFrame:'create',psAccent:'#bb6677'}});
assert(creator.has('ps-frame'));assert.equal(creator.root.dataset.psFrame,'create');assert(creator.has('dark'),'saved preference applies inside frames');assert.equal(creator.style['--ps-accent'],'#bb6677');
const legacy=page({...config,legacy_theme:false},{dataset:{psFrame:'native'}});
assert(legacy.has('ps-frame'));assert(!legacy.has('ps-theme'),'native workspaces follow the legacy theme switch');
const settings=page({...config,legacy_theme:false},{dataset:{psFrame:'settings'}});assert(settings.has('ps-theme'),'Simplify settings are always themed');
// Live sync from the dashboard.
creator.window.SimplifyTheme.sync('light','#123456');assert(!creator.has('dark'));assert.equal(creator.style['--ps-accent'],'#123456');
const frameEl={dataset:{psFrame:'native'},contentWindow:legacy.window};dashboard.window.SimplifyTheme.frame(frameEl,'files','#abcdef');assert.equal(frameEl.dataset.psFrame,'files');assert.equal(frameEl.dataset.psAccent,'#abcdef');
// Native pages and theme opt-out.
const native=page({...config,legacy_page:true});assert(!native.has('ps-theme'));
dashboard.window.SimplifyTheme.configure({...config,global_theme:false,console_dark:false});assert(!dashboard.has('ps-theme'));assert(dashboard.has('ps-console-adaptive'));
dashboard.window.SimplifyTheme.set('unsupported');assert.equal(dashboard.window.SimplifyTheme.preference(),'dark');
assert.equal(typeof dashboard.window.Simplify.confirm,'function');assert.equal(typeof dashboard.window.Simplify.toast,'function');
// Text size from Customize: per user, applied when core.js loads; 'panel' and unknown values leave the panel setting.
storage.set('pelicanSimplify.textScale.jera','110');const sized=page({...config,user:'jera'});assert.equal(sized.style['--ps-scale'],'1.1');assert.equal(sized.root.style.fontSize,'110%');
assert.equal(page({...config,user:'other'}).style['--ps-scale'],undefined);
sized.window.Simplify.textScale.set('panel');assert.equal(sized.style['--ps-scale'],undefined);assert.equal(storage.has('pelicanSimplify.textScale.jera'),false);
sized.window.Simplify.textScale.set('999');assert.equal(sized.window.Simplify.textScale.get(),'panel');
console.log('PASS theme preference, accent, console modes, embedded frame detection, legacy opt-out, live sync and shared UI API');
