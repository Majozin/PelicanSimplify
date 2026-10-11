const fs=require('fs'),http=require('http'),assert=require('assert');
const {chromium}=require('playwright');
const root=__dirname+'/../resources/views/';
const css=fs.readFileSync(root+'creator.css','utf8')+'\n'+fs.readFileSync(root+'global.css','utf8');
const theme=fs.readFileSync(root+'theme.js','utf8');
const html=`<!doctype html><html><head><link rel="stylesheet" href="/global.css"></head><body><section class="fi-section"><button class="fi-btn">Save</button><input class="fi-input"></section><section class="ps-console">Console</section><iframe id="creator" src="/child"></iframe><iframe id="legacy" src="/child"></iframe><iframe id="settings" src="/child" style="width:100%;height:500px"></iframe><script>window.simplifyThemeConfig={global_theme:true,legacy_theme:true,console_dark:true,css_url:'/global.css'};</script><script src="/theme.js"></script></body></html>`;
(async()=>{
 const server=http.createServer((req,res)=>{res.setHeader('Content-Type',req.url==='/global.css'?'text/css':req.url==='/theme.js'?'application/javascript':'text/html');res.end(req.url==='/global.css'?css:req.url==='/theme.js'?theme:req.url==='/child'?'<html><head></head><body><header class="fi-header">Page title</header><div class="ssc-wizard"><button class="fi-btn">Next</button><input class="fi-input"></div><div class="ps-settings-page"><section class="ps-setup-checks"><div class="ps-check-grid"><article class="ps-check-card">PHP runtime</article><article class="ps-check-card">Panel components</article></div></section></div></body></html>':html);});
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
  const settings=await page.evaluate(()=>{const doc=document.querySelector('#settings').contentDocument;return {themed:doc.documentElement.classList.contains('ps-theme'),header:getComputedStyle(doc.querySelector('.fi-header')).display,columns:getComputedStyle(doc.querySelector('.ps-check-grid')).gridTemplateColumns.split(' ').length};});assert.equal(settings.themed,true);assert.equal(settings.header,'none');assert.equal(settings.columns,2);
  await page.setViewportSize({width:390,height:844});assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),true);
  assert.equal(await page.evaluate(()=>{const frame=document.querySelector('#settings');return frame.contentDocument.documentElement.scrollWidth<=frame.contentWindow.innerWidth&&getComputedStyle(frame.contentDocument.querySelector('.ps-check-grid')).gridTemplateColumns.split(' ').length===1;}),true);
  console.log('PASS shared dark/light controls, creator and native frames, black console, persistence, legacy opt-out, adaptive console and mobile theme');
 }finally{await browser.close();await new Promise(resolve=>server.close(resolve));}
})().catch(e=>{console.error(e);process.exit(1)});
