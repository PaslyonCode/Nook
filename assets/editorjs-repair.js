(function(g){'use strict';
  const richTools=()=>({
    header:g.Header,
    list:g.List,
    checklist:g.Checklist,
    quote:g.Quote,
    delimiter:g.Delimiter,
    table:g.Table
  });
  function normaliseTool(existing,klass,inline){
    if(existing){
      if(typeof existing==='function') return inline?{class:existing,inlineToolbar:true}:existing;
      if(existing&&typeof existing==='object'&&inline&&existing.inlineToolbar==null)return Object.assign({},existing,{inlineToolbar:true});
      return existing;
    }
    if(!klass)return existing;
    return inline?{class:klass,inlineToolbar:true}:klass;
  }
  function enrich(config){
    const cfg=Object.assign({},config||{}),tools=Object.assign({},cfg.tools||{}),r=richTools();
    tools.header=normaliseTool(tools.header,r.header,true);
    tools.list=normaliseTool(tools.list,r.list,true);
    tools.checklist=normaliseTool(tools.checklist,r.checklist,true);
    tools.quote=normaliseTool(tools.quote,r.quote,true);
    tools.delimiter=normaliseTool(tools.delimiter,r.delimiter,false);
    tools.table=normaliseTool(tools.table,r.table,true);
    // Preserve Nook's existing local image / resizable-image tool exactly as configured.
    cfg.tools=tools;
    if(cfg.defaultBlock==null)cfg.defaultBlock='paragraph';
    return cfg;
  }
  function wrap(Ctor){
    if(typeof Ctor!=='function'||Ctor.__nookRichFormatting)return Ctor;
    function WrappedEditorJS(config){return new Ctor(enrich(config));}
    WrappedEditorJS.prototype=Ctor.prototype;
    try{Object.setPrototypeOf(WrappedEditorJS,Ctor)}catch(_e){}
    Object.getOwnPropertyNames(Ctor).forEach(k=>{if(['length','name','prototype','caller','arguments'].includes(k))return;try{Object.defineProperty(WrappedEditorJS,k,Object.getOwnPropertyDescriptor(Ctor,k))}catch(_e){}});
    Object.defineProperty(WrappedEditorJS,'__nookRichFormatting',{value:true});
    return WrappedEditorJS;
  }
  function install(){if(typeof g.EditorJS==='function'&&!g.EditorJS.__nookRichFormatting){g.EditorJS=wrap(g.EditorJS);return true}return false}
  if(!install()){
    let current;
    try{
      const d=Object.getOwnPropertyDescriptor(g,'EditorJS');
      if(!d||d.configurable){
        Object.defineProperty(g,'EditorJS',{configurable:true,enumerable:true,get(){return current},set(v){current=wrap(v)}});
      }
    }catch(_e){}
    let attempts=0;const timer=setInterval(()=>{attempts++;if(install()||attempts>200)clearInterval(timer)},25);
  }
  g.NookEditorJSFormatting={enrichConfig:enrich,tools:richTools};
})(window);
