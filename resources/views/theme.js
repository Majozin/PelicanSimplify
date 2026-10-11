(()=>{
 const config=window.simplifyThemeConfig||{};
 const key='pelicanSimplify.theme';
 if(window.SimplifyTheme){window.SimplifyTheme.configure(config);return;}
 const media=matchMedia('(prefers-color-scheme: dark)');
 let preference='system',options=config;
 try{const saved=localStorage.getItem(key);if(['light','dark','system'].includes(saved))preference=saved;}catch{}
 const dark=()=>preference==='dark'||(preference==='system'&&media.matches);
 const iconPaths={
  save:'M5 3h12l4 4v14H3V3h2Z M7 3v6h10V3M7 21v-8h10v8',upload:'M12 16V3m-5 5 5-5 5 5M3 15v6h18v-6',download:'M12 3v13m-5-5 5 5 5-5M3 15v6h18v-6',
  folder:'M3 7V5h7l2 2h9v13H3Z M3 10h18',file:'M5 3h9l5 5v13H5Z M14 3v6h5M8 13h8m-8 4h6',edit:'m16 3 5 5-13 13H3v-5L16 3Z M13 6l5 5',
  trash:'M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7',copy:'M8 8h13v13H8Z M16 8V3H3v13h5',archive:'M3 3h18v5H3Z M5 8v13h14V8M9 12h6',
  plus:'M12 4v16M4 12h16',search:'M21 21l-6-6M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0',refresh:'M3 10a9 9 0 1 1 1 8M3 3v7h7',more:'M5 12h.01M12 12h.01M19 12h.01',close:'m6 6 12 12M18 6 6 18',settings:'M4 7h16M4 17h16M8 4v6m8 4v6'
 };
 const frameIcons=doc=>{
  for(const svg of doc.querySelectorAll?.('svg.fi-icon,svg.fi-btn-icon,svg.fi-icon-btn-icon,svg.fi-ta-text-item-icon,svg.fi-dropdown-list-item-icon')||[]){
   const control=svg.closest('button,a,[role="menuitem"]'),hint=((control?.getAttribute('wire:click')||'')+' '+(control?.getAttribute('aria-label')||'')+' '+(control?.getAttribute('title')||'')+' '+(control?.textContent||'')).toLowerCase();
   let icon=null;
   for(const [pattern,key] of [[/save|submit/,'save'],[/upload/,'upload'],[/download/,'download'],[/delete|trash/,'trash'],[/rename|edit/,'edit'],[/duplicate|copy/,'copy'],[/archive|compress|extract/,'archive'],[/new.?folder|directory/,'folder'],[/new.?file|create/,'plus'],[/search/,'search'],[/refresh|reload/,'refresh'],[/more|actions/,'more'],[/close|cancel/,'close']])if(pattern.test(hint)){icon=key;break;}
   if(!icon&&svg.matches('.fi-ta-text-item-icon')){const row=svg.closest('.fi-ta-row');const link=row?.querySelector('a[href]');icon=link&&/\/edit\//.test(link.getAttribute('href')||'')?'file':'folder';}
   if(!icon)continue;const markup='<path d="'+iconPaths[icon]+'"/>';
   if(svg.innerHTML!==markup)svg.innerHTML=markup;
   svg.setAttribute('viewBox','0 0 24 24');svg.classList.add('ps-frame-icon');svg.dataset.psIcon=icon;
   if(icon==='save'&&control)control.dataset.psPrimary='1';
  }
 };
 const accentInk=color=>{const channels=color.slice(1).match(/../g).map(v=>{const x=parseInt(v,16)/255;return x<=.04045?x/12.92:((x+.055)/1.055)**2.4;});return channels[0]*.2126+channels[1]*.7152+channels[2]*.0722>.179?'#111118':'#ffffff';};
 const applyDocument=(doc,kind,accent)=>{
  if(!doc?.documentElement)return;
  const root=doc.documentElement;kind=kind||root.dataset.psFrame;
  const legacyPage=!kind&&options.legacy_page;
  const themed=!legacyPage&&!!options.global_theme&&(!kind||!!options.legacy_theme||['create','settings'].includes(kind));
  root.classList.toggle('ps-theme',themed);
  if(root.style&&/^#[0-9a-f]{6}$/i.test(accent||options.accent_color||'')){const color=accent||options.accent_color;root.style.setProperty('--ps-accent',color);root.style.setProperty('--ps-on-accent',accentInk(color));}
  if(themed)root.classList.toggle('dark',dark());
  root.classList.toggle('ps-console-dark',!!options.console_dark);
  root.classList.toggle('ps-console-adaptive',!options.console_dark);
  if(options.global_theme&&kind&&!doc.getElementById('simplify-global-css')){
   const link=doc.createElement('link');link.id='simplify-global-css';link.rel='stylesheet';link.href=options.css_url;doc.head.append(link);
  }
  if(kind){frameIcons(doc);if(!doc._psIconObserver&&typeof MutationObserver!=='undefined'){let queued=false;const observer=new MutationObserver(()=>{if(queued)return;queued=true;requestAnimationFrame(()=>{queued=false;frameIcons(doc);});});observer.observe(doc.body,{childList:true,subtree:true});doc._psIconObserver=observer;doc.defaultView?.addEventListener('pagehide',()=>observer.disconnect(),{once:true});}root.classList.add('ps-frame');root.dataset.psFrame=kind;root.dataset.psDark=dark()?'1':'0';if(options.frame_css_url&&!doc.getElementById('simplify-frame-css')){const link=doc.createElement('link');link.id='simplify-frame-css';link.rel='stylesheet';link.href=options.frame_css_url;doc.head.append(link);}}

 };
 const apply=()=>{
  applyDocument(document);
  for(const frame of document.querySelectorAll('iframe[data-ps-frame]'))try{applyDocument(frame.contentDocument,frame.dataset.psFrame,frame.dataset.psAccent);}catch{}
  window.dispatchEvent(new CustomEvent('simplify:theme',{detail:{preference,dark:dark()}}));
 };
 window.SimplifyTheme={
  set(value){if(!['light','dark','system'].includes(value))return;preference=value;try{localStorage.setItem(key,value);localStorage.setItem('theme',value);}catch{}apply();},
  frame(frame,kind,accent){frame.dataset.psFrame=kind;if(accent)frame.dataset.psAccent=accent;try{frame.contentWindow?.SimplifyTheme?.configure(options);frame.contentWindow?.SimplifyTheme?.set(preference);applyDocument(frame.contentDocument,kind,frame.dataset.psAccent);}catch{}},
  dark,preference:()=>preference,configure(value){options=value;apply();}
 };
 media.addEventListener('change',()=>{if(preference==='system')apply();});
 window.addEventListener('storage',event=>{if(event.key===key&&['light','dark','system'].includes(event.newValue)){preference=event.newValue;apply();}});
 // Native Filament theme controls write the same storage event within this document.
 window.addEventListener('theme-changed',event=>{const value=event.detail?.theme??event.detail;if(['light','dark','system'].includes(value))window.SimplifyTheme.set(value);});
 document.addEventListener('livewire:navigated',()=>window.SimplifyTheme.configure(window.simplifyThemeConfig||options));
 apply();
})();
