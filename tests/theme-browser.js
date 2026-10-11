const fs=require('fs'),http=require('http'),assert=require('assert');
const {chromium}=require('playwright');
const root=__dirname+'/../resources/views/';
const css=fs.readFileSync(root+'creator.css','utf8')+'\n'+fs.readFileSync(root+'global.css','utf8');
const theme=fs.readFileSync(root+'theme.js','utf8');
const html=`<!doctype html><html><head><link rel="stylesheet" href="/global.css"></head><body><section class="fi-section"><button class="fi-btn">Save</button><input class="fi-input"></section><section class="ps-console">Console</section><iframe id="creator" src="/child"></iframe><iframe id="legacy" src="/child"></iframe><iframe id="settings" src="/child" style="width:100%;height:500px"></iframe><script>window.simplifyThemeConfig={global_theme:true,legacy_theme:true,console_dark:true,css_url:'/global.css'};</script><script src="/theme.js"></script></body></html>`;
(async()=>{
 const server=http.createServer((req,res)=>{res.setHeader('Content-Type',req.url==='/global.css'?'text/css':req.url==='/theme.js'?'application/javascript':'text/html');res.end(req.url==='/global.css'?css:req.url==='/theme.js'?theme:req.url==='/child'?'<html><head></head><body style="min-height:100vh;background:yellow"><div class="fi-layout" style="min-height:100vh"><main class="fi-main" style="padding:48px"><div class="fi-page" style="padding:32px"><header class="fi-header">Page title</header><div class="ssc-wizard"><button class="fi-btn">Next</button><input class="fi-input"></div><div class="ps-settings-page"><section class="ps-setup-checks"><div class="ps-check-grid"><article class="ps-check-card">PHP runtime</article><article class="ps-check-card">Panel components</article></div></section></div></div></main></div></body></html>':html);});
 await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
 const browser=await chromium.launch({headless:true});
 try{
  const page=await browser.newPage();await page.goto('http://127.0.0.1:'+server.address().port);await page.waitForLoadState('networkidle');
  await page.evaluate(()=>{SimplifyTheme.frame(document.querySelector('#settings'),'settings');SimplifyTheme.frame(document.querySelector('#creator'),'create');SimplifyTheme.frame(document.querySelector('#legacy'),'native');SimplifyTheme.set('dark');});
  await page.waitForTimeout(120);
  const colors=await page.evaluate(()=>{const f=document.querySelector('#creator').contentDocument;return {surface:getComputedStyle(document.querySelector('.fi-section')).backgroundColor,button:getComputedStyle(document.querySelector('.fi-btn')).backgroundColor,console:getComputedStyle(document.querySelector('.ps-console')).backgroundColor,creator:getComputedStyle(f.querySelector('.fi-btn')).backgroundColor,legacy:document.querySelector('#legacy').contentDocument.documentElement.classList.contains('dark')};});
  assert.equal(colors.surface,'rgb(26, 26, 31)');assert.equal(colors.button,colors.creator);assert.equal(colors.console,'rgb(9, 9, 11)');assert.equal(colors.legacy,true);
  await page.evaluate(()=>SimplifyTheme.set('light'));
  const light=await page.evaluate(()=>({body:getComputedStyle(document.body).backgroundColor,console:getComputedStyle(document.querySelector('.ps-console')).backgroundColor,creator:document.querySelector('#creator').contentDocument.documentElement.classList.contains('dark')}));
  assert.equal(light.body,'rgb(244, 244, 245)');assert.equal(light.console,'rgb(9, 9, 11)');assert.equal(light.creator,false);
  await page.reload();assert.equal(await page.evaluate(()=>SimplifyTheme.preference()),'light');
  await page.evaluate(()=>{SimplifyTheme.configure({...simplifyThemeConfig,legacy_theme:false,console_dark:false});SimplifyTheme.frame(document.querySelector('#settings'),'settings');SimplifyTheme.frame(document.querySelector('#legacy'),'native');SimplifyTheme.set('dark');});
  assert.equal(await page.evaluate(()=>document.querySelector('#legacy').contentDocument.documentElement.classList.contains('ps-theme')),false);
  await page.evaluate(()=>SimplifyTheme.set('light'));assert.equal(await page.evaluate(()=>getComputedStyle(document.querySelector('.ps-console')).backgroundColor),'rgb(255, 255, 255)');
  await page.waitForFunction(()=>{const doc=document.querySelector('#settings').contentDocument;return doc&&getComputedStyle(doc.querySelector('.fi-header')).display==='none';});
  const settings=await page.evaluate(()=>{const doc=document.querySelector('#settings').contentDocument;return {themed:doc.documentElement.classList.contains('ps-theme'),header:getComputedStyle(doc.querySelector('.fi-header')).display,background:getComputedStyle(doc.body).backgroundColor,padding:getComputedStyle(doc.querySelector('.fi-page')).padding,columns:getComputedStyle(doc.querySelector('.ps-check-grid')).gridTemplateColumns.split(' ').length};});assert.equal(settings.background,'rgb(255, 255, 255)');assert.equal(settings.padding,'0px');assert.equal(settings.themed,true);assert.equal(settings.header,'none');assert.equal(settings.columns,2);
  await page.setViewportSize({width:390,height:844});assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),true);
  assert.equal(await page.evaluate(()=>{const frame=document.querySelector('#settings');return frame.contentDocument.documentElement.scrollWidth<=frame.contentWindow.innerWidth&&getComputedStyle(frame.contentDocument.querySelector('.ps-check-grid')).gridTemplateColumns.split(' ').length===1;}),true);
  const hoverPage=await browser.newPage({viewport:{width:1100,height:800}});
  await hoverPage.setContent('<style>'+fs.readFileSync(root+'dashboard.css','utf8')+'</style><main class="ps"><div class="ps-server-grid"><div class="ps-create-tiles"><button class="ps-server-card ps-create-card is-minecraft"><span class="ps-create-plus">M</span><h3>Create Minecraft server</h3></button><button disabled class="ps-server-card ps-create-card is-other"><span class="ps-create-plus">G</span><h3>Create Other server</h3></button></div><article class="ps-server-card" style="height:260px">Existing server</article></div></main>');
  const choices=hoverPage.locator('.ps-create-card');
  const snapshot=()=>hoverPage.evaluate(()=>[...document.querySelectorAll('.ps-create-card')].map(el=>({width:el.getBoundingClientRect().width,height:el.getBoundingClientRect().height,top:el.getBoundingClientRect().top,transform:getComputedStyle(el).transform,icon:getComputedStyle(el.querySelector('.ps-create-plus')).width,title:getComputedStyle(el.querySelector('h3')).opacity})));
  const before=await snapshot();await choices.nth(0).hover();await hoverPage.waitForTimeout(350);const hovered=await snapshot();
  assert.equal(hovered[0].transform,'none');assert.equal(hovered[0].width,before[0].width);assert.equal(hovered[0].height,before[0].height);assert.deepEqual(hovered[1],before[1]);
  await hoverPage.locator('article').hover();await hoverPage.waitForTimeout(250);assert.notEqual(await hoverPage.locator('article').evaluate(el=>getComputedStyle(el).transform),'none');await hoverPage.close();
  console.log('PASS shared dark/light controls, creator and native frames, black console, persistence, legacy opt-out, adaptive console and mobile theme');
 }finally{await browser.close();await new Promise(resolve=>server.close(resolve));}
})().catch(e=>{console.error(e);process.exit(1)});
