(function(g){'use strict';
function itemText(v){if(typeof v==='string')return v;if(v&&typeof v==='object')return v.content||v.text||'';return''}
class NookListTool{
  constructor({data,readOnly}){this.data=Object.assign({style:'unordered',items:[]},data||{});this.readOnly=!!readOnly;this.wrapper=null;this.list=null;}
  static get isReadOnlySupported(){return true}
  static get toolbox(){return{title:'Список',icon:'<svg width="18" height="18" viewBox="0 0 24 24"><path d="M8 6h12M8 12h12M8 18h12" stroke="currentColor" stroke-width="2"/><circle cx="4" cy="6" r="1.2"/><circle cx="4" cy="12" r="1.2"/><circle cx="4" cy="18" r="1.2"/></svg>'}}
  static get sanitize(){return{items:{br:true},style:{}}}
  render(){this.wrapper=document.createElement('div');this.wrapper.className='nook-ej-list-wrap';this.makeList(this.data.style||'unordered',this.data.items||[]);return this.wrapper;}
  makeList(style,items){const old=this.list;const el=document.createElement(style==='ordered'?'ol':'ul');el.className='nook-ej-list';el.dataset.style=style==='ordered'?'ordered':'unordered';(items.length?items:['']).forEach(v=>{const li=document.createElement('li');li.contentEditable=String(!this.readOnly);li.innerHTML=itemText(v);el.appendChild(li)});if(old)old.replaceWith(el);else this.wrapper.appendChild(el);this.list=el;}
  save(){return{style:this.list.dataset.style||'unordered',items:[...this.list.querySelectorAll(':scope > li')].map(li=>li.innerHTML)}}
  renderSettings(){return[{icon:'•',label:'Маркированный',isActive:()=>this.list&&this.list.dataset.style==='unordered',onActivate:()=>this.switchStyle('unordered')},{icon:'1.',label:'Нумерованный',isActive:()=>this.list&&this.list.dataset.style==='ordered',onActivate:()=>this.switchStyle('ordered')}];}
  switchStyle(style){if(!this.list||this.list.dataset.style===style)return;const items=[...this.list.querySelectorAll(':scope > li')].map(li=>li.innerHTML);this.makeList(style,items)}
}
g.List=NookListTool;g.NookListTool=NookListTool;
})(window);
