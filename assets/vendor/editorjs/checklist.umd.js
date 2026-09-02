(function(g){'use strict';
class NookChecklistTool{
 constructor({data,readOnly}){this.data=Object.assign({items:[]},data||{});this.readOnly=!!readOnly;}
 static get isReadOnlySupported(){return true}
 static get toolbox(){return{title:'Чек-лист',icon:'<svg width="18" height="18" viewBox="0 0 24 24"><rect x="3" y="4" width="5" height="5" rx="1" fill="none" stroke="currentColor"/><path d="m4 6 1.5 1.5L8 4.8M11 6.5h10M3 14h5v5H3zM11 16.5h10" fill="none" stroke="currentColor" stroke-width="1.7"/></svg>'}}
 static get sanitize(){return{items:{text:{br:true},checked:{}}}}
 render(){const box=document.createElement('div');box.className='nook-ej-checklist';const items=(this.data.items&&this.data.items.length)?this.data.items:[{text:'',checked:false}];items.forEach(v=>{const row=document.createElement('label');row.className='nook-ej-check-row';const cb=document.createElement('input');cb.type='checkbox';cb.checked=!!v.checked;cb.disabled=this.readOnly;const text=document.createElement('span');text.contentEditable=String(!this.readOnly);text.innerHTML=v.text||'';row.append(cb,text);box.appendChild(row)});return box;}
 save(block){return{items:[...block.querySelectorAll('.nook-ej-check-row')].map(row=>({text:row.querySelector('span').innerHTML,checked:row.querySelector('input').checked}))}}
}
g.Checklist=NookChecklistTool;g.NookChecklistTool=NookChecklistTool;
})(window);
