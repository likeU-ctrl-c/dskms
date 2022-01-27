$(function() {
    var controller = $('#search ul.tab').attr('dstype');
    if (controller == "Storelist") {
        $('#search ul.tab li span').eq(0).html('机构');
        $('#search ul.tab li span').eq(1).html('课程');
        $('#search-form').attr("action", HOMESITEURL + "/Storelist/index.html");
    } else {
        $('#search ul.tab li span').eq(0).html('课程');
        $('#search ul.tab li span').eq(1).html('机构');
        $('#search-form').attr("action", HOMESITEURL + "/Search/index.html");
    }
    $('#search').hover(function() {
        $('#search ul.tab li').eq(1).show();
        $('#search ul.tab li i').addClass('over').removeClass('arrow');
    }, function() {
        $('#search ul.tab li').eq(1).hide();
        $('#search ul.tab li i').addClass('arrow').removeClass('over');
    });
    $('#search ul.tab li').eq(1).click(function() {
        $(this).hide();
        if ($(this).find('span').html() == '机构') {
            $('#keyword').attr("placeholder", "请输入您要搜索的机构关键字");
            $('#search ul.tab li span').eq(0).html('机构');
            $('#search ul.tab li span').eq(1).html('课程');
            $('#search-form').attr("action", HOMESITEURL+"/Storelist/index.html");
        } else {
            $('#keyword').attr('placeholder', '请输入您要搜索的课程关键字');
            $('#search ul.tab li span').eq(0).html('课程');
            $('#search ul.tab li span').eq(1).html('机构');
            $('#search-form').attr("action", HOMESITEURL+"/Search/index.html");
        }
        $("#keyword").focus();
    });
});
    // 立即购买
    $('a[dstype="buy_now"]').click(function(){
        eval('var data_str = ' + $(this).attr('data-param'));
        $("#goods_id").val(data_str.goods_id+'|1');
        $("#buynow_form").submit();
    });
/**
 * 
 * @param {type} formid   form表单ID
 * @param {type} type     返回类型  reload 刷新当前界面   default 保持默认状态不做刷新
 * @param {type} url      跳转的连接地址
 * @param {type} time     跳转的时间
 * @returns {undefined}
 */
function ds_ajaxpost(formid,type,url,waittime){
    if (typeof(waittime) == "undefined"){
        waittime = 1000;
    }
    var _form = $("#"+formid);
    $.ajax({
        type: "POST",
        url: _form.attr('action'),
        data: _form.serialize(),
        dataType:"json",
        success: function (res) {
            layer.msg(res.message, {time: waittime}, function () {
                if (res.code == 10000) {
                    if (typeof (type) == 'undefined' && type == null && type == '') {
                        location.reload();
                    } else if(type=='url') {
                        location.href = url;
                    } else if(type=='default') {
                        //不做操作只显示
                    }else{
                        location.reload();
                    }
                }
            });
        }
    });
}

/**
 * 
 * @param {type} url   URL链接地址
 * @param {type} type  返回类型  reload  remove(移除指定行) default默认不做任何操作
 * @param {type} param 参数
 * @returns {undefined}
 */
function ds_ajaxget(url,type,param)
{
    $.ajax({
        url: url,
        type: "get",
        dataType: "json",
        success: function (data) {
            layer.msg(data.message, {time: 1000}, function () {
                if (data.code == 10000) {
                    if (typeof (type) == "undefined" || type == null || type == '' || type=='reload') {
                        location.reload();
                    } else if (type == "remove") {
                        $("#ds_row_" + param).remove();
                    }else {
                        //不做操作
                    }
                }
            });
        }
    });
}
/**
 * 
 * @param {type} url   URL链接地址
 * @param {type} msg   显示提示内容
 * @param {type} type  返回类型  reload  remove(移除指定行) default默认不做任何操作
 * @param {type} param 参数
 * @returns {undefined}
 */
function ds_ajaxget_confirm(url,msg,type,param) {
    if (typeof (msg) != 'undefined' && msg != null && msg != '') {
        layer.confirm(msg, {
            btn: ['确定', '取消'],
            title: false,
        }, function () {
            ds_ajaxget(url,type,param);
        });
    }else{
        ds_ajaxget(url,type,param);
    }
}

/**
 * 
 * @param {type} msg  显示提示
 * @param {type} url  跳转URL 
 * @returns {undefined}
 */
function ds_get_confirm(msg, url){
    if(msg != ''){
        layer.confirm(msg, {
            btn: ['确定', '取消'],
            title: false,
        }, function () {
            window.location = url;
        });
    }else{
    	window.location = url;
    }
}

/**
 * Layer 通用ifram弹出窗口
 */
function ds_layer_open(url, title,width,height) {
    if (!width)	width = '900px';
    if (!height) height = '500px';
    layer.open({
        type: 2,
        title: title,
        area: [width,height],
        fixed: false, //不固定
        maxmin: true,
        content: url
    });
}




function go(url){
    window.location = url;
}


/* 格式化金额 */
function price_format(price){
    if(typeof(PRICE_FORMAT) == 'undefined'){
        PRICE_FORMAT = '&yen;%s';
    }
    price = number_format(price, 2);
    return price;
}
function number_format(num, ext){
    if(ext < 0){
        return num;
    }
    num = Number(num);
    if(isNaN(num)){
        num = 0;
    }
    var _str = num.toString();
    var _arr = _str.split('.');
    var _int = _arr[0];
    var _flt = _arr[1];
    if(_str.indexOf('.') == -1){
        /* 找不到小数点，则添加 */
        if(ext == 0){
            return _str;
        }
        var _tmp = '';
        for(var i = 0; i < ext; i++){
            _tmp += '0';
        }
        _str = _str + '.' + _tmp;
    }else{
        if(_flt.length == ext){
            return _str;
        }
        /* 找得到小数点，则截取 */
        if(_flt.length > ext){
            _str = _str.substr(0, _str.length - (_flt.length - ext));
            if(ext == 0){
                _str = _int;
            }
        }else{
            for(var i = 0; i < ext - _flt.length; i++){
                _str += '0';
            }
        }
    }

    return _str;
}

/* 火狐下取本地全路径 */
function getFullPath(obj)
{
    if(obj)
    {
        //ie
        if (window.navigator.userAgent.indexOf("MSIE")>=1)
        {
            obj.select();
            if(window.navigator.userAgent.indexOf("MSIE") == 25){
                obj.blur();
            }
            return document.selection.createRange().text;
        }
        //firefox
        else if(window.navigator.userAgent.indexOf("Firefox")>=1)
        {
            if(obj.files)
            {
                //return obj.files.item(0).getAsDataURL();
                return window.URL.createObjectURL(obj.files.item(0));
            }
            return obj.value;
        }
        return obj.value;
    }
}
/* 转化JS跳转中的 ＆ */
function transform_char(str)
{
    if(str.indexOf('&'))
    {
        str = str.replace(/&/g, "%26");
    }
    return str;
}

//图片垂直水平缩放裁切显示
(function($){
    $.fn.VMiddleImg = function(options) {
        var defaults={
            "width":null,
            "height":null
        };
        var opts = $.extend({},defaults,options);
        return $(this).each(function() {
            var $this = $(this);
            var objHeight = $this.height(); //图片高度
            var objWidth = $this.width(); //图片宽度
            var parentHeight = opts.height||$this.parent().height(); //图片父容器高度
            var parentWidth = opts.width||$this.parent().width(); //图片父容器宽度
            var ratio = objHeight / objWidth;
            if (objHeight > parentHeight && objWidth > parentWidth) {
                if (objHeight > objWidth) { //赋值宽高
                    $this.width(parentWidth);
                    $this.height(parentWidth * ratio);
                } else {
                    $this.height(parentHeight);
                    $this.width(parentHeight / ratio);
                }
                objHeight = $this.height(); //重新获取宽高
                objWidth = $this.width();
                if (objHeight > objWidth) {
                    $this.css("top", (parentHeight - objHeight) / 2);
                    //定义top属性
                } else {
                    //定义left属性
                    $this.css("left", (parentWidth - objWidth) / 2);
                }
            }
            else {
                if (objWidth > parentWidth) {
                    $this.css("left", (parentWidth - objWidth) / 2);
                }
                $this.css("top", (parentHeight - objHeight) / 2);
            }
        });
    };
})(jQuery);
function ResizeImage(ImgD,FitWidth,FitHeight){
    var image=new Image();
    image.src=ImgD.src;
    if(image.width>0 && image.height>0)
    {
        if(image.width/image.height>= FitWidth/FitHeight)
        {
            if(image.width>FitWidth)
            {
                ImgD.width=FitWidth;
                ImgD.height=(image.height*FitWidth)/image.width;
            }
            else
            {
                ImgD.width=image.width;
                ImgD.height=image.height;
            }
        }
        else
        {
            if(image.height>FitHeight)
            {
                ImgD.height=FitHeight;
                ImgD.width=(image.width*FitHeight)/image.height;
            }
            else
            {
                ImgD.width=image.width;
                ImgD.height=image.height;
            }
        }
    }
}

function trim(str) {
    return (str + '').replace(/(\s+)$/g, '').replace(/^\s+/g, '');
}
//弹出框登录
function login_dialog(){
    CUR_DIALOG = ajax_form('login','登录',HOMESITEURL+'/Login/login.html?inajax=1',360,1);
}

/* 显示Ajax表单 */
function ajax_form(id, title, url, width, model)
{
    if (!width)	width = 480;
    if (!model) model = 1;
    var d = DialogManager.create(id);
    d.setTitle(title);
    d.setContents('ajax', url);
    d.setWidth(width);
    d.show('center',model);
    return d;
}
//显示一个内容为自定义HTML内容的消息
function html_form(id, title, _html, width, model) {
    if (!width)	width = 480;
    if (!model) model = 0;
    var d = DialogManager.create(id);
    d.setTitle(title);
    d.setContents(_html);
    d.setWidth(width);
    d.show('center',model);
    return d;
}
//收藏机构js
function collect_store(fav_id, jstype, jsobj) {
    $.get(HOMESITEURL+'/Index/login', function(result) {
        if (result == '0') {
            login_dialog();
        } else {
            var url = HOMESITEURL+'/Memberfavorites/favoritesstore';
            $.getJSON(url, {'fid': fav_id}, function(data) {
                if (data.done) {
                    layer.msg(data.msg);
                    if (jstype == 'count') {
                        $('[dstype="' + jsobj + '"]').each(function() {
                            $(this).html(parseInt($(this).text()) + 1);
                        });
                    }
                    if (jstype == 'succ') {
                        $('[dstype="' + jsobj + '"]').each(function() {
                            $(this).html("收藏成功");
                        });
                    }
                    if (jstype == 'store') {
                        $('[ds_store="' + fav_id + '"]').each(function() {
                            $(this).before('<span class="goods-favorite" title="该机构已收藏"><i class="have">&nbsp;</i></span>');
                            $(this).remove();
                        });
                    }
                }
                else
                {
                    layer.msg(data.msg);
                }
            });
        }
    });
}
//收藏商品js
function collect_goods(fav_id, jstype, jsobj) {
    $.get(HOMESITEURL+'/Index/login.html', function(result) {
        if (result == '0') {
            login_dialog();
        } else {
            var url = HOMESITEURL+'/Memberfavorites/favoritesgoods';
            $.getJSON(url, {'fid': fav_id}, function(data) {
                if (data.done)
                {
                    layer.msg(data.msg);
                    if (jstype == 'count') {
                        $('[dstype="' + jsobj + '"]').each(function() {
                            $(this).html(parseInt($(this).text()) + 1);
                        });
                    }
                    if (jstype == 'succ') {
                        $('[dstype="' + jsobj + '"]').each(function() {
                            $(this).html("收藏成功");
                        });
                    }
                }
                else
                {
                    layer.msg(data.msg);
                }
            });
        }
    });
}

//加载最近浏览的商品
function load_history_information(){
    $.getJSON(HOMESITEURL+'/Index/viewed_info.html', function(result){
        var obj = $('.header .user_menu .my-mall');
        if(result['m_id'] >0){
            if (typeof result['consult'] !== 'undefined') obj.find('#member_consult').html(result['consult']);
            if (typeof result['consult'] !== 'undefined') obj.find('#member_voucher').html(result['voucher']);
        }
        var goods_id = 0;
        var text_append = '';
        var n = 0;
        if (typeof result['viewed_goods'] !== 'undefined') {
            for (goods_id in result['viewed_goods']) {
                var goods = result['viewed_goods'][goods_id];
                text_append += '<li class="goods-thumb"><a href="'+goods['url']+'" title="'+goods['goods_name']+
                '" target="_blank"><img src="'+goods['goods_image']+'" alt="'+goods['goods_name']+'"></a>';
                text_append += '</li>';
                n++;
                if (n > 4) break;
            }
        }
        if (text_append == '') text_append = '<li class="no-goods">暂无课程</li>';;
        obj.find('.browse-history ul').html(text_append);
    });
}

/*
 * 弹出窗口
 */
(function($) {
    $.fn.ds_show_dialog = function(options) {

        var that = $(this);
        var settings = $.extend({}, {width: 480, title: '', close_callback: function() {}}, options);

        var init_dialog = function(title) {
            var _div = that;
            that.addClass("dialog_wrapper");
            that.wrapInner(function(){
                return '<div class="dialog_content">';
            });
            that.wrapInner(function(){
                return '<div class="dialog_body" style="position: relative;">';
            });
            that.find('.dialog_body').prepend('<h3 class="dialog_head" style="cursor: move;"><span class="dialog_title"><span class="dialog_title_icon">'+settings.title+'</span></span><span class="dialog_close_button">X</span></h3>');
            that.append('<div style="clear:both;"></div>');

            $(".dialog_close_button").click(function(){
                settings.close_callback();
                _div.hide();
            });

            that.draggable({handle: ".dialog_head"});
        };

        if(!$(this).hasClass("dialog_wrapper")) {
            init_dialog(settings.title);
        }
        settings.left = $(window).scrollLeft() + ($(window).width() - settings.width) / 2;
        settings.top  = ($(window).height() - $(this).height()) / 2;
        $(this).attr("style","display:none; z-index: 1100; position: fixed; width: "+settings.width+"px; left: "+settings.left+"px; top: "+settings.top+"px;");
        $(this).show();

    };
})(jQuery);
/**
 * Membership card
 *
 *
 * Example:
 *
 * HTML part
 * <a href="javascript" dstype="mcard" data-param="{'id':5}"></a>
 *
 * JAVASCRIPT part
 * <script type="text/javascript" src="<?php echo HOME_SITE_ROOT;?>/js/qtip/jquery.qtip.min.js"></script>
 * <link href="<?php echo HOME_SITE_ROOT;?>/js/qtip/jquery.qtip.min.css" rel="stylesheet" type="text/css">
 * $('a[dstype="mcard"]').membershipCard();
 */
(function($){
	$.fn.membershipCard = function(options){
		var defaults = {
				type:''	
			};
		options = $.extend(defaults,options);
		return this.each(function(){
			var $this = $(this);
			var data_str = $(this).attr('data-param');eval('data_str = '+data_str);
			var _uri = HOMESITEURL+'/Membercard/index.html?callback=?&uid='+data_str.id+'&from='+options.type;
			$this.qtip({
	            content: {
	                text: 'Loading...',
	                ajax: {
	                    url: _uri,
		                type: 'GET',
		                dataType: 'jsonp',
		                success: function(data) {
		                	if(data){
		                		var _dl = $('<dl></dl>');
		                		// sex
		                		$('<dt class="member-id"></dt>').append('<i class="sex'+data.sex+'"></i>')
		                			.append('<a href="javascript:void(0)" target="_blank">'+data.name+'</a>'+(data.truename != ''?'('+data.truename+')':''))
		                			.appendTo(_dl);
		                		// avatar
		                		$('<dd class="avatar"><a href="javascript:void(0)" target="_blank"><img src="'+data.avatar+'" /></a><dd>')
		                			.appendTo(_dl);
		                		// info
		                		var _info = '';
		                		if(data.qq != ''){
		                			_info += '<a target="_blank" href="http://wpa.qq.com/msgrd?v=3&uin='+data.qq+'&site=qq&menu=yes" title="QQ: '+data.qq+'"><img border="0" src="http://wpa.qq.com/pa?p=2:'+data.qq+':52" style=" vertical-align: middle;"/></a>';
		                		}
		                		if(data.ww != ''){
		                			_info += '<a target="_blank" href="http://amos.im.alisoft.com/msg.aw?v=2&amp;uid='+data.ww+'&site=cntaobao&s=1" ><img border="0" src="http://amos.im.alisoft.com/online.aw?v=2&uid='+data.ww+'&site=cntaobao&s=2" alt="点击这里给我发消息" style=" vertical-align: middle;"/></a>';
		                		}
		                		if(_info == ''){
		                			_info = '--';
		                		}
		                		var _ul = $('<ul></ul>').append('<li>城市：'+((data.areainfo != null)?data.areainfo:'--')+'</li>')
		                			.append('<li>生日：'+((data.birthday != null)?data.birthday:'--')+'</li>')
		                			.append('<li>联系：'+_info+'</li>').appendTo('<dd class="info"></dd>').parent().appendTo(_dl);
		                		// ajax info
		                		if(data.url != ''){
			                		$.getJSON(data.url+'/Membercard/mcard_info.html?uid='+data.id, function(d){
			                			if(d){
			                				eval('var msg = '+options.type+'_function(d);');
			                				msg.appendTo(_dl);
			                			}
			                		});
			                		data.url = '';
			                	}

		                		// bottom
		                		var _bottom;
		                		if(data.follow != 2){
			                		_bottom = $('<div class="bottom"></div>');
		                			var _a;
		                			if(data.follow == 1){
		                				$('<div class="follow-handle" dstype="follow-handle'+data.id+'" data-param="{\'mid\':'+data.id+'}"></div>')
		                					.append('<a href="javascript:void(0);" >已关注</a>')
		                					.append('<a href="javascript:void(0);" dstype="nofollow">取消关注</a>').find('a[dstype="nofollow"]').click(function(){
		                						onfollow($(this));
		                					}).end().appendTo(_bottom);
		                			}else{
		                				$('<div class="follow-handle" dstype="follow-handle'+data.id+'" data-param="{\'mid\':'+data.id+'}"></div>')
		                					.append('<a href="javascript:void(0);" dstype="follow">加关注</a>').find('a[dstype="follow"]').click(function(){
		                						follow($(this));
		                					}).end().appendTo(_bottom);
		                			}
		                			$('<div class="send-msg"> <a href="'+HOMESITEURL+'/Membermessage/sendmsg.html?member_id='+data.id+'" target="_blank"><i></i>站内信</a> </div>').appendTo(_bottom);
		                		}

		                		var _content = $('<div class="member-card"></div>').append(_dl).append(_bottom);
			                    this.set('content.text', ' ');this.set('content.text', _content);
		                	}
		                }
	                }
	            },
	            position: {
	                viewport: $(window)
	            },
	            hide: {
					fixed: true,
					delay: 300
				},
	            style: 'qtip-wiki'
	         });
		});
		function follow(o){
			var data_str = o.parent().attr('data-param');
			eval( "data_str = "+data_str);
			$.getJSON(HOMESITEURL+'/Membersnsfriend/addfollow.html?callback=?&mid='+data_str.mid, function(data){
				if(data.code==10000){
					$('[dstype="follow-handle'+data_str.mid+'"]').html('<a href="javascript:void(0);" >已关注</a> <a href="javascript:void(0);" dstype="nofollow">取消关注</a>').find('a[dstype="nofollow"]').click(function(){
						onfollow($(this));
					});
				}
			});
		}
		function onfollow(o){
			var data_str = o.parent().attr('data-param');
			eval( "data_str = "+data_str);
			$.getJSON(HOMESITEURL+'/Membersnsfriend/delfollow.html?callback=?&mid='+data_str.mid, function(data){
				if(data.code==10000){
					$('[dstype="follow-handle'+data_str.mid+'"]').html('<a href="javascript:void(0);" dstype="follow">加关注</a>').find('a[dstype="follow"]').click(function(){
						follow($(this));
					});
				}
			});
		}
		function shop_function(d){
			return $('<dd class="ajax-info">买家信用：'+((d.member_credit == 0)?'暂无信用':d.member_credit)+'</dd>');
		}
	};
})(jQuery);




(function($) {
    $.fn.ds_region = function(options) {
        var $region = $(this);
        var settings = $.extend({}, {
            area_id: 0,
            region_span_class: "_region_value",
            src: "cache",
            show_deep: 0,
            btn_style_html: "",
            tip_type: ""
        }, options);
        settings.islast = false;
        settings.selected_deep = 0;
        settings.last_text = "";
        this.each(function() {
            var $inputArea = $(this);
            if ($inputArea.val() === "") {
                initArea($inputArea)
            } else {
                var $region_span = $('<span id="_area_span" class="' + settings.region_span_class + '">' + $inputArea.val() + "</span>");
                var $region_btn = $('<input type="button" class="input-btn" ' + settings.btn_style_html + ' value="编辑" />');
                $inputArea.after($region_span);
                $region_span.after($region_btn);
                $region_btn.on("click", function() {
                    $region_span.remove();
                    $region_btn.remove();
                    initArea($inputArea)
                });
                settings.islast = true
            }
            this.settings = settings;
            if ($inputArea.val() && /^\d+$/.test($inputArea.val())) {
                $.getJSON(HOMESITEURL  + "/Index/json_area_show?area_id=" + $inputArea.val() + "&callback=?", function(data) {
                    $("#_area_span").html(data.text == null ? "无" : data.text)
                })
            }
        });

        function initArea($inputArea) {
            settings.$area = $("<select></select>");
            $inputArea.before(settings.$area);
            loadAreaArray(function() {
                loadArea(settings.$area, settings.area_id)
            })
        }
        function loadArea($area, area_id) {
            if ($area && ds_a[area_id].length > 0) {
                var areas = [];
                areas = ds_a[area_id];
                if (settings.tip_type && settings.last_text != "") {
                    $area.append("<option value=''>" + settings.last_text + "(*)</option>")
                } else {
                    $area.append("<option value=''>-请选择-</option>")
                }
                for (i = 0; i < areas.length; i++) {
                    $area.append("<option value='" + areas[i][0] + "'>" + areas[i][1] + "</option>")
                }
                settings.islast = false
            }
            $area.on("change", function() {
                var region_value = "",
                    area_ids = [],
                    selected_deep = 1;
                $(this).nextAll("select").remove();
                $region.parent().find("select").each(function() {
                    if ($(this).find("option:selected").val() != "") {
                        region_value += $(this).find("option:selected").text() + " ";
                        area_ids.push($(this).find("option:selected").val())
                    }
                });
                settings.selected_deep = area_ids.length;
                settings.area_ids = area_ids.join(" ");
                $region.val(region_value);
                settings.area_id_1 = area_ids[0] ? area_ids[0] : "";
                settings.area_id_2 = area_ids[1] ? area_ids[1] : "";
                settings.area_id_3 = area_ids[2] ? area_ids[2] : "";
                settings.area_id_4 = area_ids[3] ? area_ids[3] : "";
                settings.last_text = $region.prevAll("select").find("option:selected").last().text();
                var area_id = settings.area_id = $(this).val();
                if ($('#_area_1').length > 0) $("#_area_1").val(settings.area_id_1);
                if ($('#_area_2').length > 0) $("#_area_2").val(settings.area_id_2);
                if ($('#_area_3').length > 0) $("#_area_3").val(settings.area_id_3);
                if ($('#_area_4').length > 0) $("#_area_4").val(settings.area_id_4);
                if ($('#_area').length > 0) $("#_area").val(settings.area_id);
                if ($('#_areas').length > 0) $("#_areas").val(settings.area_ids);
                if (settings.show_deep > 0 && $region.prevAll("select").size() == settings.show_deep) {
                    settings.islast = true;
                    if (typeof settings.last_click == 'function') {
                        settings.last_click(area_id);
                    }
                    return
                }
                if (area_id > 0) {
                    if (ds_a[area_id] && ds_a[area_id].length > 0) {
                        var $newArea = $("<select></select>");
                        $(this).after($newArea);
                        loadArea($newArea, area_id);
                        settings.islast = false
                    } else {
                        settings.islast = true;
                        if (typeof settings.last_click == 'function') {
                            settings.last_click(area_id);
                        }
                    }
                } else {
                    settings.islast = false
                }
                if ($('#islast').length > 0) $("#islast").val("");
            })
        }
        function loadAreaArray(callback) {
            if (typeof ds_a === "undefined") {
                $.getJSON(HOMESITEURL  + "/Index/json_area.html?src=" + settings.src + "&callback=?", function(data) {
                    ds_a = data;
                    callback()
                })
            } else {
                callback()
            }
        }
        if (typeof jQuery.validator != 'undefined') {
            jQuery.validator.addMethod("checklast", function(value, element) {
                return $(element).fetch('islast');
            }, "请将地区选择完整");
        }
    };
    $.fn.fetch = function(k) {
        var p;
        this.each(function() {
            if (this.settings) {
                p = eval("this.settings." + k);
                return false
            }
        });
        return p
    }


})(jQuery);


function setCookie(name, value, days) {
    var exp = new Date();
    exp.setTime(exp.getTime() + days * 24 * 60 * 60 * 1000);
    var arr = document.cookie.match(new RegExp("(^| )" + name + "=([^;]*)(;|$)"));
    document.cookie = name + "=" + escape(value) + ";expires=" + exp.toGMTString();
}
function getCookie(name) {
    var arr = document.cookie.match(new RegExp("(^| )" + name + "=([^;]*)(;|$)"));
    if (arr != null) {
        return unescape(arr[2]);
        return null;
    }
}
function delCookie(name) {
    var exp = new Date();
    exp.setTime(exp.getTime() - 1);
    var cval = getCookie(name);
    if (cval != null) {
        document.cookie = name + "=" + cval + ";expires=" + exp.toGMTString();
    }
}


(function($) {
    $.fn.F_slider = function(options){
        var defaults = {
            page : 1,
            len : 0,		// 滚动篇幅
            axis : 'y'		// y为上下滚动，x为左右滚动
        }
        var options = $.extend(defaults,options);
        return this.each(function(){
            var $this = $(this);
            var len = options.len;
            var page = options.page;
            if(options.axis == 'y'){
                var Val = $(this).find('.F-center').height();
                var Param = 'top';
            }else if(options.axis == 'x'){
                var Val = $(this).find('.F-center').parent().width();
                var Param = 'left';
            }
            $this.find('.F-prev').click(function(){
                if( page == 1){
                    eval("$this.find('.F-center').animate({"+Param+":'-=' + Val*(len-1)},'slow');");
                    page=len;
                }else{
                    eval("$this.find('.F-center').animate({"+Param+":'+=' + Val},'slow');");
                    page--;
                }
            });
            $this.find('.F-next').click(function(){
                if(page == len){
                    eval("$this.find('.F-center').animate({"+Param+":0},'slow');");
                    page=1;
                }else{
                    eval("$this.find('.F-center').animate({"+Param+":'-=' + Val},'show');");
                    page++;
                }
            });
        });
    }
})(jQuery);
