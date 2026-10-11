(()=>{
 const config=window.simplifyThemeConfig||{};
 const key='pelicanSimplify.theme';
 if(window.SimplifyTheme){window.SimplifyTheme.configure(config);return;}
 const media=matchMedia('(prefers-color-scheme: dark)');
 let preference='system',options=config;
 try{const saved=localStorage.getItem(key);if(['light','dark','system'].includes(saved))preference=saved;}catch{}
 const dark=()=>preference==='dark'||(preference==='system'&&media.matches);
 const applyDocument=(doc,kind)=>{
  if(!doc?.documentElement)return;
  const root=doc.documentElement;kind=kind||root.dataset.psFrame;
  const themed=!!options.global_theme&&(!kind||!!options.legacy_theme||['create','settings'].includes(kind));
  root.classList.toggle('ps-theme',themed);
  if(root.style&&/^#[0-9a-f]{6}$/i.test(options.accent_color||''))root.style.setProperty('--ps-accent',options.accent_color);
  if(themed)root.classList.toggle('dark',dark());
  root.classList.toggle('ps-console-dark',!!options.console_dark);
  root.classList.toggle('ps-console-adaptive',!options.console_dark);
  if(kind){root.classList.add('ps-frame');root.dataset.psFrame=kind;}
  if(options.global_theme&&kind&&!doc.getElementById('simplify-global-css')){
   const link=doc.createElement('link');link.id='simplify-global-css';link.rel='stylesheet';link.href=options.css_url;doc.head.append(link);
  }
 };
 const apply=()=>{
  applyDocument(document);
  for(const frame of document.querySelectorAll('iframe[data-ps-frame]'))try{applyDocument(frame.contentDocument,frame.dataset.psFrame);}catch{}
  window.dispatchEvent(new CustomEvent('simplify:theme',{detail:{preference,dark:dark()}}));
 };
 window.SimplifyTheme={
  set(value){if(!['light','dark','system'].includes(value))return;preference=value;try{localStorage.setItem(key,value);localStorage.setItem('theme',value);}catch{}apply();},
  frame(frame,kind){frame.dataset.psFrame=kind;try{applyDocument(frame.contentDocument,kind);frame.contentWindow?.SimplifyTheme?.configure(options);frame.contentWindow?.SimplifyTheme?.set(preference);}catch{}},
  dark,preference:()=>preference,configure(value){options=value;apply();}
 };
 media.addEventListener('change',()=>{if(preference==='system')apply();});
 window.addEventListener('storage',event=>{if(event.key===key&&['light','dark','system'].includes(event.newValue)){preference=event.newValue;apply();}});
 // Native Filament theme controls write the same storage event within this document.
 window.addEventListener('theme-changed',event=>{const value=event.detail?.theme??event.detail;if(['light','dark','system'].includes(value))window.SimplifyTheme.set(value);});
 document.addEventListener('livewire:navigated',apply);
 apply();
})();
