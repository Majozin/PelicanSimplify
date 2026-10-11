const fs=require('fs'),vm=require('vm'),assert=require('assert');
const timers=new Map(),cleared=[],context={window:{},document:{hidden:false},Date,setInterval(fn,ms){timers.set(ms,fn);return ms},clearInterval:id=>cleared.push(id),setTimeout:()=>1,clearTimeout(){}};
vm.runInNewContext(fs.readFileSync(__dirname+'/../resources/views/js/dashboard.js','utf8'),context);
(async()=>{
 let calls=0,resolve;const due=Math.floor(Date.now()/1000)-5;
 const d=context.window.pelicanSimplify({refreshInfo(){calls++;return new Promise(r=>resolve=r)}},{performance:{server_seconds:25,restart_seconds:4},server:{pending:{restart_at:due},permissions:{},properties:{}}});
 d.initTheme=()=>{};d.applyFavicon=()=>{};d.connect=async()=>{};d.renderLogs=()=>{};await d.init();
 assert.ok(timers.has(4000)&&timers.has(25000),'polling intervals come from the performance settings');
 timers.get(4000)();assert.equal(calls,1,'a due restart is followed closely');
 const overlap=d.refreshServerInfo();await overlap;assert.equal(calls,1,'overlapping polling requests suppressed');
 resolve({pending:null});await new Promise(r=>setImmediate(r));assert.equal(d.server.pending,null,'successful apply removes banner');
 timers.get(4000)();assert.equal(calls,1,'fast polling stops after apply');
 d.server.pending={restart_at:Math.floor(Date.now()/1000)+900};timers.get(4000)();assert.equal(calls,1,'a restart scheduled later does not poll every few seconds');
 d.modal='settings';timers.get(25000)();assert.equal(calls,1,'regular refresh pauses while a popup is open');
 d.modal=null;timers.get(25000)();assert.equal(calls,2,'regular refresh resumes');resolve({});await new Promise(r=>setImmediate(r));
 d.destroy();assert.ok(cleared.includes(4000)&&cleared.includes(25000),'both polling timers cleaned up');
 console.log('PASS configurable intervals, due-restart progression, no overlapping requests, banner clearing, popup pause and timer cleanup');
})().catch(e=>{console.error(e);process.exit(1)});
