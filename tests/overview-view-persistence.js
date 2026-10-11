const fs=require('fs'),vm=require('vm'),assert=require('assert');
const saved=new Map();const context={window:{},innerWidth:1440,localStorage:{getItem:key=>saved.get(key)||null,setItem:(key,value)=>saved.set(key,value)},URL,location:{origin:'https://panel.example'}};
vm.createContext(context);vm.runInContext(fs.readFileSync(__dirname+'/../pelican-simplify/resources/views/dashboard.js','utf8'),context);
const page=user=>context.window.pelicanSimplify({}, {server:null,servers:[],user});
const first=page('ExampleUser');first.restoreOverviewView();assert.equal(first.overviewView,'grid');first.setOverviewView('list');
const reload=page('ExampleUser');reload.restoreOverviewView();assert.equal(reload.overviewView,'list');assert.equal(reload.gameFilter,'all');
const other=page('Other');other.restoreOverviewView();assert.equal(other.overviewView,'grid');reload.setOverviewView('grid');const next=page('ExampleUser');next.restoreOverviewView();assert.equal(next.overviewView,'grid');
next.setOverviewView('invalid');assert.equal(saved.get(next.overviewViewKey()),'grid');saved.set(next.overviewViewKey(),'invalid');next.restoreOverviewView();assert.equal(next.overviewView,'grid');
context.localStorage.getItem=()=>{throw Error('blocked')};context.localStorage.setItem=()=>{throw Error('blocked')};const blocked=page('Blocked');blocked.restoreOverviewView();blocked.setOverviewView('list');assert.equal(blocked.overviewView,'list');
console.log('PASS grid/list preference across reloads, account isolation, All default, invalid values and blocked browser storage');

context.localStorage.getItem=key=>saved.get(key)||null;context.localStorage.setItem=(key,value)=>saved.set(key,value);saved.set('pelicanSimplify.overviewView.ExampleUser','grid');
context.innerWidth=390;const mobile=page('ExampleUser');assert.equal(mobile.overviewView,'list','phone begins in list view before initialization');mobile.restoreOverviewView();assert.equal(mobile.overviewView,'list');mobile.setOverviewView('grid');assert.equal(mobile.overviewView,'list','phone cannot switch to grid');assert.equal(saved.get(mobile.overviewViewKey()),'grid','phone must retain the desktop preference');
context.innerWidth=1440;mobile.restoreOverviewView();assert.equal(mobile.overviewView,'grid','desktop preference returns after resizing');context.innerWidth=600;mobile.restoreOverviewView();assert.equal(mobile.overviewView,'list');context.innerWidth=601;mobile.restoreOverviewView();assert.equal(mobile.overviewView,'grid');
console.log('PASS phone defaults to list, hides switching behavior and preserves desktop preference across resizing');
