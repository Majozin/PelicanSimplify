(()=>{
 let timer;
 const root=document.documentElement;
 const indicator=()=>{
  let node=document.getElementById('simplify-page-loading');
  if(!node&&document.body){node=document.createElement('div');node.id='simplify-page-loading';node.setAttribute('role','status');node.textContent='Loading Simplify…';document.body.append(node);}
  return node;
 };
 const fail=()=>{clearTimeout(timer);const node=indicator();if(node){node.textContent='Simplify could not finish loading. ';const retry=document.createElement('button');retry.type='button';retry.textContent='Try again';retry.onclick=()=>location.reload();node.append(retry);}};
 const show=()=>{clearTimeout(timer);root.classList.remove('ps-page-loading');document.getElementById('simplify-page-loading')?.remove();};
 window.SimplifyPageReady=async content=>{
  if(!content?.isConnected)return;
  try{
   await Promise.all(['simplify-global-css','simplify-dashboard-css'].map(id=>{
    const link=document.getElementById(id);if(!link)throw new Error('Missing stylesheet');if(link.sheet)return;
    return new Promise((resolve,reject)=>{link.addEventListener('load',resolve,{once:true});link.addEventListener('error',reject,{once:true});});
   }));
   if(document.fonts?.ready)await document.fonts.ready;
   await Promise.all([...content.querySelectorAll('img')].filter(img=>img.loading!=='lazy'&&img.offsetParent!==null&&!img.complete).map(img=>new Promise(resolve=>{img.addEventListener('load',resolve,{once:true});img.addEventListener('error',resolve,{once:true});})));
   await new Promise(resolve=>requestAnimationFrame(()=>requestAnimationFrame(resolve)));
   if(content.isConnected)show();
  }catch{fail();}
 };
 timer=setTimeout(fail,15000);
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',()=>{if(root.classList.contains('ps-page-loading'))indicator();},{once:true});else indicator();
 document.addEventListener('livewire:navigated',()=>{if(!document.querySelector('[data-simplify-root]'))show();});
})();
