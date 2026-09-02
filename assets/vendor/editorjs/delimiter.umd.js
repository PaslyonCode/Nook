(function(g){'use strict';
class NookDelimiterTool{
 static get isReadOnlySupported(){return true}
 static get toolbox(){return{title:'Разделитель',icon:'<svg width="18" height="18" viewBox="0 0 24 24"><path d="M3 12h18" stroke="currentColor" stroke-width="2"/></svg>'}}
 render(){const w=document.createElement('div');w.className='nook-ej-delimiter';w.innerHTML='<span></span><span></span><span></span>';return w;}
 save(){return{}}
}
g.Delimiter=NookDelimiterTool;g.NookDelimiterTool=NookDelimiterTool;
})(window);
