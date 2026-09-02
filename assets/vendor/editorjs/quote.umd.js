(function(g){'use strict';
class NookQuoteTool{
 constructor({data,readOnly}){this.data=Object.assign({text:'',caption:'',alignment:'left'},data||{});this.readOnly=!!readOnly;this.root=null;}
 static get isReadOnlySupported(){return true}
 static get toolbox(){return{title:'Цитата',icon:'<svg width="18" height="18" viewBox="0 0 24 24"><path d="M5 6h6v6H7c0 3-1 5-4 6 2-2 2-4 2-6V6Zm10 0h6v6h-4c0 3-1 5-4 6 2-2 2-4 2-6V6Z" fill="currentColor"/></svg>'}}
 static get sanitize(){return{text:{br:true},caption:{br:true},alignment:{}}}
 render(){const root=document.createElement('blockquote');root.className='nook-ej-quote';root.dataset.align=this.data.alignment||'left';const text=document.createElement('div');text.className='nook-ej-quote-text';text.contentEditable=String(!this.readOnly);text.innerHTML=this.data.text||'';const cap=document.createElement('div');cap.className='nook-ej-quote-caption';cap.contentEditable=String(!this.readOnly);cap.innerHTML=this.data.caption||'';cap.dataset.placeholder='Автор / источник';root.append(text,cap);this.root=root;return root;}
 save(block){return{text:block.querySelector('.nook-ej-quote-text').innerHTML,caption:block.querySelector('.nook-ej-quote-caption').innerHTML,alignment:block.dataset.align||'left'}}
 renderSettings(){return[{icon:'←',label:'Слева',isActive:()=>this.root&&this.root.dataset.align==='left',onActivate:()=>{if(this.root)this.root.dataset.align='left'}},{icon:'↔',label:'По центру',isActive:()=>this.root&&this.root.dataset.align==='center',onActivate:()=>{if(this.root)this.root.dataset.align='center'}}]}
}
g.Quote=NookQuoteTool;g.NookQuoteTool=NookQuoteTool;
})(window);
