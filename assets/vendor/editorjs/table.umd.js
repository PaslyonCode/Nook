(function(g){'use strict';
class NookTableTool{
 constructor({data,readOnly}){this.data=Object.assign({withHeadings:false,content:[['',''],['','']]},data||{});this.readOnly=!!readOnly;this.root=null;}
 static get isReadOnlySupported(){return true}
 static get toolbox(){return{title:'Таблица',icon:'<svg width="18" height="18" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="1" fill="none" stroke="currentColor"/><path d="M3 10h18M9 4v16M15 4v16" stroke="currentColor"/></svg>'}}
 static get sanitize(){return{content:{br:true},withHeadings:{}}}
 render(){const root=document.createElement('div');root.className='nook-ej-table-wrap';root.dataset.headings=this.data.withHeadings?'1':'0';const table=document.createElement('table');table.className='nook-ej-table';let rows=Array.isArray(this.data.content)?this.data.content:[];if(!rows.length)rows=[['',''],['','']];rows.forEach((r,ri)=>{const tr=document.createElement('tr');(Array.isArray(r)?r:['']).forEach(v=>{const td=document.createElement(ri===0&&root.dataset.headings==='1'?'th':'td');td.contentEditable=String(!this.readOnly);td.innerHTML=typeof v==='string'?v:String(v??'');tr.appendChild(td)});table.appendChild(tr)});root.appendChild(table);this.root=root;return root;}
 save(block){return{withHeadings:block.dataset.headings==='1',content:[...block.querySelectorAll('tr')].map(tr=>[...tr.children].map(td=>td.innerHTML))}}
 renderSettings(){return[{icon:'+R',label:'Добавить строку',onActivate:()=>this.addRow()},{icon:'+C',label:'Добавить столбец',onActivate:()=>this.addCol()},{icon:'H',label:'Заголовок таблицы',isActive:()=>this.root&&this.root.dataset.headings==='1',onActivate:()=>this.toggleHeadings()}]}
 addRow(){if(!this.root)return;const table=this.root.querySelector('table'),cols=Math.max(1,table.rows[0]?table.rows[0].cells.length:2),tr=document.createElement('tr');for(let i=0;i<cols;i++){const td=document.createElement('td');td.contentEditable=String(!this.readOnly);tr.appendChild(td)}table.appendChild(tr)}
 addCol(){if(!this.root)return;[...this.root.querySelectorAll('tr')].forEach((tr,ri)=>{const cell=document.createElement(ri===0&&this.root.dataset.headings==='1'?'th':'td');cell.contentEditable=String(!this.readOnly);tr.appendChild(cell)})}
 toggleHeadings(){if(!this.root)return;this.root.dataset.headings=this.root.dataset.headings==='1'?'0':'1';const first=this.root.querySelector('tr');if(!first)return;[...first.children].forEach(old=>{const cell=document.createElement(this.root.dataset.headings==='1'?'th':'td');cell.contentEditable=String(!this.readOnly);cell.innerHTML=old.innerHTML;old.replaceWith(cell)})}
}
g.Table=NookTableTool;g.NookTableTool=NookTableTool;
})(window);
