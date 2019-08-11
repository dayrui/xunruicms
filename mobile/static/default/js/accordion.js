/*
hui 折叠面板组件
作者 : 深海
官网 : http://hui.hcoder.net/
*/
hui.accordion = function(closeAll, firstShow){
	if(typeof(closeAll) == 'undefined'){closeAll = false;}
	if(typeof(firstShow) == 'undefined'){firstShow = false;}
	hui('.hui-accordion').each(function(cDom){
		var accordionTitle = hui(cDom).find('.hui-accordion-title');
		accordionTitle.click(function(){
			var accordionContent = hui(this).parent().find('.hui-accordion-content');
			var accordionTitleHtml   = accordionTitle.html();
			if(accordionContent.isShow()){
				accordionContent.hide();
				accordionTitle.removeClass('hui-accordion-title-up');
			}else{
				if(closeAll){hui('.hui-accordion-content').hide(); hui('.hui-accordion-title').removeClass('hui-accordion-title-up');}
				accordionContent.show();
				accordionTitle.addClass('hui-accordion-title-up');
			}
			hui.scrollTop(0);
		});
	});
	if(firstShow){hui('.hui-accordion').eq(0).find('.hui-accordion-title').click();}
};