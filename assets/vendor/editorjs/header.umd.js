(function(g){'use strict';
class NookHeaderTool{
  constructor({data,config,readOnly}){this.data=Object.assign({text:'',level:(config&&config.defaultLevel)||2},data||{});this.readOnly=!!readOnly;this.wrapper=null;}
  static get isReadOnlySupported(){return true}
  static get toolbox(){return{title:'Заголовок',icon:'<svg width="18" height="18" viewBox="0 0 24 24"><path d="M5 4v16M19 4v16M5 12h14" fill="none" stroke="currentColor" stroke-width="2"/></svg>'}}
  static get sanitize(){return{text:{br:true}}}
  render(){const w=document.createElement('div');w.className='nook-ej-header';w.dataset.level=String(this.data.level||2);w.contentEditable=String(!this.readOnly);w.innerHTML=this.data.text||'';this.wrapper=w;return w;}
  save(block){return{text:block.innerHTML||'',level:parseInt(block.dataset.level||'2',10)||2}}
  renderSettings(){return [1,2,3,4,5,6].map(level=>({icon:'H'+level,label:'H'+level,isActive:()=>Number(this.wrapper&&this.wrapper.dataset.level)===level,onActivate:()=>{if(this.wrapper)this.wrapper.dataset.level=String(level)}}));}
}
g.Header=NookHeaderTool;g.NookHeaderTool=NookHeaderTool;
})(window);
