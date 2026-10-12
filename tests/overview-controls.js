const fs=require('fs'),vm=require('vm'),assert=require('assert');
const context={window:{},innerWidth:1440,URL,location:{origin:'https://panel.example'}};
vm.createContext(context);vm.runInContext(fs.readFileSync(__dirname+'/../resources/views/js/dashboard.js','utf8'),context);
(async()=>{
 const minecraft={uuid:'mc',game:'minecraft',game_label:'Minecraft',linked:true},paper={uuid:'paper',game:'minecraft',game_label:'Minecraft',linked:false},valheim={uuid:'valheim',game:'other',game_label:'Valheim',linked:false};
 let servers=[minecraft,paper,valheim];const d=context.window.pelicanSimplify({listing:async()=>servers,hostHealth:async()=>({})},{server:null,servers,can_create:true});
 assert.equal(d.gameFilter,'all');assert.equal(d.overviewView,'grid');assert.equal(d.visibleServers().length,3);assert.equal(d.gameOptions().length,2);assert.equal(d.overviewColumns(),3);
 d.setGameFilter('minecraft');assert.equal(d.visibleServers().length,2);assert.equal(d.visibleServers()[1].linked,false,'filtering never adds management access');assert.equal(d.overviewColumns(),2);
 d.setOverviewView('list');assert.equal(d.overviewView,'list');d.setOverviewView('invalid');assert.equal(d.overviewView,'list');d.setGameFilter('valheim');assert.equal(d.visibleServers()[0].uuid,'valheim');d.setGameFilter('not-a-game');assert.equal(d.gameFilter,'valheim');
 servers=[minecraft,paper];await d.refreshOverview();assert.equal(d.gameFilter,'all','removed game falls back to All');assert.equal(d.gameOptions().length,1);assert.equal(d.visibleServers().length,2);assert.equal(d.overviewView,'list','refresh preserves current view');
 d.boot.servers=[];assert.equal(d.gameOptions().length,0);assert.equal(d.overviewColumns(),1);assert.equal(d.visibleServers().length,0);
 console.log('PASS overview defaults, unique present games, filtering, view selection, refresh fallback, permission preservation and empty overview');
})().catch(e=>{console.error(e);process.exit(1)});
