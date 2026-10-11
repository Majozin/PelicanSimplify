const fs=require('fs'),vm=require('vm'),assert=require('assert');
let timerTick;
const context={window:{addEventListener(){},removeEventListener(){}},setInterval:fn=>{timerTick=fn;return 1},clearInterval(){},confirm:()=>true,Alpine:{$data:()=>({step:'Resources',getStepIndex:()=>3})}};
vm.createContext(context);vm.runInContext(fs.readFileSync(__dirname+'/../resources/views/filament/admin/draft.js','utf8'),context);
(async()=>{
 let saves=0,discard=0,fail=false,release;
 const values={};const wire={$set:(key,value)=>values[key]=value,draftStep:4,draftRestored:true,saveDraft:async step=>{assert.equal(step,4);saves++;if(fail)throw Error('offline');if(release)await release;},discardDraft:async()=>discard++};
 const d=context.window.sscDraft(wire);d.$root={querySelector:()=>({}),querySelectorAll:()=>[{attributes:[{name:'wire:model.blur',value:'data.name'}],value:'Unblurred server name',type:'text'}]};d.init();assert.equal(d.status,'Draft restored');
 d.changed();await context.window.sscSaveDraft();assert.equal(d.dirty,false);assert.equal(d.status,'Draft saved');assert.equal(d.savedCount,1,'successful autosave signals icon animation');assert.equal(values['data.name'],'Unblurred server name');d.uploading=true;await assert.rejects(d.flush());d.uploading=false;
 const successfulSaves=d.savedCount;fail=true;d.changed();await assert.rejects(d.flush());assert.equal(d.dirty,true);assert.equal(d.status,'Draft not saved — try again');assert.equal(d.savedCount,successfulSaves,'failed saves must not signal success');
 let blocked=false;d.unload({preventDefault(){blocked=true;}});assert.equal(blocked,true);
 fail=false;await d.flush();blocked=false;d.unload({preventDefault(){blocked=true;}});assert.equal(blocked,false);
 let resolve;release=new Promise(r=>resolve=r);d.changed();const saving=d.flush();d.changed();release=null;resolve();await saving;assert.equal(d.dirty,false);assert.ok(saves>=5);
 await d.discard();assert.equal(discard,1);assert.equal(d.completed,true);const count=saves;await d.flush();assert.equal(saves,count);d.destroy();assert.equal(context.window.sscSaveDraft,undefined);
 const inputs=[
 {attributes:[{name:'wire:model',value:'data.game'}],value:'',type:'hidden'},
 {attributes:[{name:'wire:model',value:'data.modpack_search'}],value:'',type:'hidden'},
 {attributes:[{name:'wire:model.blur',value:'data.name'}],value:'Same name',type:'text'},
 {attributes:[{name:'wire:model',value:'data.accept_eula'}],checked:true,type:'checkbox'}];
 const changed=[];const guarded=context.window.sscDraft({$get:key=>({'data.name':'Same name','data.accept_eula':false})[key],$set:(key,value)=>changed.push([key,value])});guarded.$root={querySelectorAll:()=>inputs};guarded.capture();assert.equal(changed.length,1);assert.equal(changed[0][0],'data.accept_eula');assert.equal(changed[0][1],true,'EULA selection is captured without resetting game or hidden state');
 const wizard={step:'review',getStepIndex:step=>['type','content','resources','backups','review'].indexOf(step),getSteps:()=>['type','content','resources','backups','review']};context.Alpine.$data=()=>wizard;
 const nav=context.window.sscDraft({saveDraft:async()=>{},$set(){}});nav.$root={querySelector:()=>({}),querySelectorAll:()=>[]};nav.furthestStep=5;await nav.goToStep(2);assert.equal(wizard.step,'content');assert.equal(nav.savedVisible(),true,'step switch saves its new position and restores check');assert.equal(nav.dirty,false);await nav.goToStep(5);assert.equal(wizard.step,'review','return to completed review without clearing inputs');nav.furthestStep=2;wizard.step='type';await nav.goToStep(4);assert.equal(wizard.step,'type','unvisited steps cannot be clicked');
 let autoWrites=0,finishAuto;const pauseAuto=new Promise(resolve=>finishAuto=resolve);
 const auto=context.window.sscDraft({draftStep:1,saveDraft:async()=>{autoWrites++;await pauseAuto;},$set(){}});auto.$root={querySelector:()=>({}),querySelectorAll:()=>[]};wizard.step='type';auto.init();assert.equal(auto.savedVisible(),true,'new wizard starts checked');const textEvent={target:{tagName:'INPUT',type:'text'}};
 auto.fieldFocused(textEvent);auto.changed();auto.changed();timerTick();await Promise.resolve();assert.equal(autoWrites,0,'typing and periodic step checks do not save text');
 auto.fieldExited(textEvent);assert.equal(autoWrites,1,'leaving the field starts one autosave');auto.fieldFocused(textEvent);auto.changed();finishAuto();await auto.pending;await Promise.resolve();assert.equal(autoWrites,1,'typing in the next field does not chain extra saves');assert.equal(auto.dirty,true);
 auto.fieldExited(textEvent);await auto.pending;assert.equal(autoWrites,2);assert.equal(auto.dirty,false);assert.equal(auto.savedCount,1,'check mark signals the completed field-exit save');assert.equal(auto.savedVisible(),true,'check stays visible while draft is saved');auto.fieldFocused(textEvent);auto.changed();assert.equal(auto.savedVisible(),false,'typing immediately hides the check');auto.fieldExited(textEvent);await auto.pending;assert.equal(auto.savedVisible(),true,'check returns after field-exit save');auto.destroy();
 const refreshBefore=auto.refreshCount;auto.buttonClicked({target:{closest:()=>({})}});assert.equal(auto.refreshCount,refreshBefore+1,'native navigation triggers refresh animation');
 console.log('PASS autosave waits for text-field exit and does not save new typing during an in-flight save');
 console.log('PASS navigation among visited steps and future-step blocking');
 console.log('PASS hidden category/input protection and unchanged-field filtering');
 console.log('PASS draft restoration status, step, flush, network failure, unload guard, edits during save, discard and completed-session protection');
})().catch(e=>{console.error(e);process.exit(1)});
