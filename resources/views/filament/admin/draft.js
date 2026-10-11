window.sscDraft = function (wire) {
 return {
  dirty:false, saving:false, editingText:false, switching:false, uploading:false, completed:false, status:'Draft ready', savedCount:0, hasSaved:true, refreshCount:0, revision:0, savedRevision:0, step:1, furthestStep:1, timer:null, pending:null,
  init(){
   this.step=Number(wire.draftStep)||1;this.furthestStep=Math.max(this.step,Number(wire.furthestStep)||1);this.status=wire.draftRestored?'Draft restored':'Draft ready';this.hasSaved=true;
   this.unload=(event)=>{if(!this.completed&&(this.dirty||this.saving||this.uploading)){event.preventDefault();event.returnValue='';}};
   window.addEventListener('beforeunload',this.unload);
   window.sscSaveDraft=()=>this.flush();
   this.timer=setInterval(()=>{const step=this.currentStep();this.furthestStep=Math.max(this.furthestStep,step);if(step!==this.step){this.step=step;this.changed();this.flush().catch(()=>{});}},1000);
  },
  currentStep(){try{const wizard=Alpine.$data(this.$root.querySelector('.fi-sc-wizard'));const index=wizard.getStepIndex(wizard.step);return Number.isInteger(index)&&index>=0?index+1:this.step;}catch{return this.step;}},
  async goToStep(target){
   this.furthestStep=Math.max(this.furthestStep,this.currentStep());
   if(!Number.isInteger(target)||target<1||target>this.furthestStep||this.switching||this.uploading)return;
   this.refreshCount++;this.switching=true;try{await this.flush();const wizard=Alpine.$data(this.$root.querySelector('.fi-sc-wizard'));const steps=wizard.getSteps();const selected=steps[target-1];const value=typeof selected==='string'?selected:selected?.value;if(!value)return;wizard.step=value;this.step=target;this.changed();await this.flush();}catch{this.status='Could not switch steps — try again';}finally{this.switching=false;}
  },
  savedVisible(){return this.hasSaved&&!this.dirty&&!this.saving&&!this.switching;},
  isTextField(target){return target?.tagName==='TEXTAREA'||target?.tagName==='INPUT'&&!['checkbox','radio','file','color','range','button','submit','hidden'].includes(target.type);},
  fieldFocused(event){this.editingText=this.isTextField(event.target);},
  fieldChanged(event){this.changed();if(!this.isTextField(event.target)){this.refreshCount++;this.flush(false).catch(()=>{});}},
  buttonClicked(event){if(event.target?.closest?.('.ssc-next-button,.ssc-back-button,.fi-sc-wizard-footer .fi-btn,.fi-sc-wizard-actions .fi-btn'))this.refreshCount++;},
  fieldExited(event){if(!this.isTextField(event.target))return;this.editingText=false;if(this.dirty)this.flush(false).catch(()=>{});},
  changed(){if(this.completed)return;this.revision++;this.dirty=true;this.status='Unsaved changes';},
  capture(){
   for(const input of this.$root.querySelectorAll('input,textarea,select')){
    const model=Array.from(input.attributes).find(a=>a.name==='wire:model'||a.name.startsWith('wire:model.'));
    if(!model||!model.value.startsWith('data.')||input.disabled||input.type==='hidden'||input.type==='file'||model.value==='data.game'||input.type==='radio'&&!input.checked)continue;
    const value=input.type==='checkbox'?input.checked:input.value;
    const stored=typeof wire.$get==='function'?wire.$get(model.value):undefined;
    if(stored!==undefined&&(input.type==='checkbox'?Boolean(stored)===value:String(stored??'')===String(value)))continue;
    wire.$set(model.value,value,false);
   }
  },
  async flush(force=true){
   if(this.completed)return;
   if(this.uploading){this.status='Wait for the icon upload to finish';throw Error('Upload still in progress');}
   this.capture();
   if(this.pending){await this.pending;if(this.dirty&&(force||!this.editingText))return this.flush(force);return;}
   const revision=this.revision;this.saving=true;this.status='Saving draft…';
   this.pending=(async()=>{try{await wire.saveDraft(this.currentStep(),Math.max(this.furthestStep,this.currentStep()));this.savedRevision=revision;this.dirty=this.revision!==revision;this.status=this.dirty?'Unsaved changes':'Draft saved';this.hasSaved=true;if(!this.dirty)this.savedCount++;}catch(error){this.dirty=true;this.status='Draft not saved — try again';throw error;}finally{this.saving=false;this.pending=null;}})();
   await this.pending;if(this.dirty&&(force||!this.editingText))return this.flush(force);
  },
  complete(){this.completed=true;this.dirty=false;clearInterval(this.timer);},
  async discard(){if(!confirm('Discard this draft and start again?'))return;await this.flush();this.complete();await wire.discardDraft();},
  destroy(){clearInterval(this.timer);window.removeEventListener('beforeunload',this.unload);delete window.sscSaveDraft;}
 };
};
