const test = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const source = fs.readFileSync('resources/js/live-status.js', 'utf8');

function browser({ ticket = true, registration = false, returnPage = false, response = {} } = {}) {
    const listeners = {}, documentListeners = {}, calls = [], timers = new Map();
    let id = 0, hidden = false, hides = 0, reloads = 0;
    const replacements = [], message = {textContent:''};
    const ticketNode = {dataset:{statusUrl:'/status',syncUrl:'/sync',ticketStatus:'pending',paymentStatus:'pending'},querySelector:()=>message};
    const document = { get hidden(){return hidden;}, querySelector(selector) {
        if (selector === '[data-ticket-live]') return ticket ? ticketNode : null;
        if (selector === '[data-registration-state-url]') return registration ? {dataset:{registrationStateUrl:'/registration'}} : null;
        if (selector === '[data-refresh-on-return]') return returnPage ? {} : null;
        if (selector.startsWith('meta')) return {content:'csrf'};
    }, addEventListener(name, callback){ documentListeners[name] = callback; }};
    const window = { location:{href:'http://localhost/tickets/1',origin:'http://localhost',replace:url=>replacements.push(url),reload:()=>reloads++},
        snap:{hide:()=>hides++}, addEventListener(name,callback){listeners[name]=callback;} };
    vm.runInNewContext(source,{document,window,URL,AbortController,Date,
        setTimeout:(fn,ms)=>{timers.set(++id,{fn,ms});return id;},clearTimeout:id=>timers.delete(id),
        fetch:async(url,options)=>{calls.push({url,options});return {ok:true,status:200,redirected:false,json:async()=>response};},
    });
    return {listeners,documentListeners,calls,timers,replacements,ticketNode,message,get hides(){return hides;},get reloads(){return reloads;},set hidden(value){hidden=value;}};
}
const flush = () => new Promise(resolve=>setImmediate(resolve));

test('accepted server status closes popup and replaces location exactly once',async()=>{
    const b=browser({response:{ticket_status:'active',payment_status:'confirmed',ticket_url:'http://localhost/tickets/1'}});
    await flush();
    assert.equal(b.hides,1);assert.equal(b.replacements.length,1);
    b.listeners['sportmanado:payment-result']();await flush();
    assert.equal(b.replacements.length,1);assert.equal(b.timers.size,0);
});
test('pending payment stays open and reconciles at bounded intervals with CSRF',async()=>{
    const b=browser({response:{ticket_status:'pending',payment_status:'pending'}});await flush();
    assert.equal(b.hides,0);assert.equal(b.replacements.length,0);
    b.listeners['sportmanado:payment-result']();await flush();
    assert.equal(b.calls[1].options.method,'POST');assert.equal(b.calls[1].options.headers['X-CSRF-TOKEN'],'csrf');
    b.listeners['sportmanado:payment-result']();await flush();
    assert.equal(b.calls[2].options.method,'GET');
});
test('back restored registration checks existing ticket without resubmitting form',async()=>{
    const b=browser({ticket:false,registration:true,response:{ticket_url:'http://localhost/tickets/1'}});await flush();
    assert.deepEqual(b.replacements,['http://localhost/tickets/1']);
    assert.equal(b.calls[0].url,'/registration');
});
test('history restoration refreshes read pages but preserves edited forms',()=>{
    const b=browser({ticket:false,returnPage:true});b.listeners.pageshow({persisted:true});assert.equal(b.reloads,1);
    const edited=browser({ticket:false,returnPage:true});
    edited.documentListeners.input({target:{closest:()=>({})}});edited.listeners.pageshow({persisted:true});assert.equal(edited.reloads,0);
});
test('hidden tabs do not fetch and external redirect targets are refused',async()=>{
    const b=browser({response:{ticket_status:'active',payment_status:'confirmed',ticket_url:'https://outside.example/ticket'}});await flush();
    assert.equal(b.replacements.length,0);b.hidden=true;const count=b.calls.length;
    b.listeners['sportmanado:payment-result']();await flush();assert.equal(b.calls.length,count);
    b.listeners.pagehide();assert.equal(b.timers.size,0);
});
