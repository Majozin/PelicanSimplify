const fs=require('fs'),vm=require('vm'),assert=require('assert');
const timers=new Map(),cleared=[],context={window:{},document:{hidden:false},setInterval(fn,ms){timers.set(ms,fn);return ms},clearInterval:id=>cleared.push(id),clearTimeout(){}};
vm.runInNewContext(fs.readFileSync(__dirname+'/../resources/views/dashboard.js','utf8'),context);
(async()=>{
 let calls=0,resolve;const d=context.window.pelicanSimplify({refreshInfo(){calls++;return new Promise(r=>resolve=r)}},{server:{pending:{restart_at:1},permissions:{},properties:{}}});
 d.initTheme=()=>{};d.applyFavicon=()=>{};d.connect=async()=>{};d.renderLogs=()=>{};await d.init();
 timers.get(2000)();assert.equal(calls,1,'pending operations progress every two seconds');
 const overlap=d.refreshServerInfo();await overlap;assert.equal(calls,1,'overlapping polling requests suppressed');
 resolve({pending:null});await new Promise(r=>setImmediate(r));assert.equal(d.server.pending,null,'successful apply removes banner');
 timers.get(2000)();assert.equal(calls,1,'fast polling stops after apply');
 d.destroy();assert.ok(cleared.includes(2000)&&cleared.includes(15000),'both polling timers cleaned up');
 console.log('PASS pending progression, no overlapping requests, banner clearing and timer cleanup');
})().catch(e=>{console.error(e);process.exit(1)});
