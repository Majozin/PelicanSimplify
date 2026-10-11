window.simplifySetupProgress=function(){return {
 step:1,furthestStep:1,timer:null,
 init(){this.$nextTick(()=>this.sync());this.timer=setInterval(()=>this.sync(),250);},
 wizard(){return Alpine.$data(this.$root.querySelector('.fi-sc-wizard'));},
 sync(){try{const wizard=this.wizard();const index=wizard.getStepIndex(wizard.step);if(Number.isInteger(index)&&index>=0){this.step=index+1;this.furthestStep=Math.max(this.furthestStep,this.step);}}catch{}},
 goToStep(target){this.sync();if(!Number.isInteger(target)||target<1||target>this.furthestStep)return;try{const wizard=this.wizard();const selected=wizard.getSteps()[target-1];const value=typeof selected==='string'?selected:selected?.value;if(!value)return;wizard.step=value;this.sync();}catch{}},
 destroy(){clearInterval(this.timer);}
};};
