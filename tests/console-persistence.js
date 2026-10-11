const fs=require('fs'),vm=require('vm'),assert=require('assert');
let poll,outputWrites=0,text='';
const output={scrollHeight:200,clientHeight:100,scrollTop:0,get textContent(){return text},set textContent(value){outputWrites++;text=value}};
const context={window:{},document:{hidden:false},setInterval:f=>{poll=f;return 1},clearInterval(){},setTimeout:()=>1,clearTimeout(){}};
vm.createContext(context);vm.runInContext(fs.readFileSync(__dirname+'/../pelican-simplify/resources/views/dashboard.js','utf8'),context);
(async()=>{
 const d=context.window.pelicanSimplify({refreshInfo:async()=>({players:1})},{server:{permissions:{},properties:{}}});
 d.initTheme=()=>{};d.applyFavicon=()=>{};d.connect=async()=>{};d.$nextTick=f=>f();d.$refs={output};
 await d.init();d.logs=['Old server log','A player joined'];d.renderLogs();assert.equal(text,'Old server log\nA player joined');assert.equal(output.scrollTop,0,'manual scroll retained');
 const writes=outputWrites;await poll();assert.equal(outputWrites,writes,'unchanged logs are not rewritten during refresh');
 text='';await poll();assert.equal(text,'Old server log\nA player joined','poll restores cleared DOM without a new log event');
 output.scrollTop=170;d.logs.push('Latest event');d.renderLogs();assert.equal(output.scrollTop,200,'autoscroll near bottom');
 d.logs=[];d.renderLogs();assert.equal(text,'','Clear remains explicit');
 assert.ok(/<pre wire:ignore class="ps-console-output"/.test(fs.readFileSync(__dirname+'/../pelican-simplify/resources/views/dashboard.blade.php','utf8')),'log output protected from Livewire DOM morphing');
 d.destroy();console.log('PASS idle log retention, refresh restoration, unchanged DOM, scroll position and explicit Clear');
})().catch(e=>{console.error(e);process.exit(1)});
