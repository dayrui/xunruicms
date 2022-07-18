function dr_sku_add_group() {
	var id = parseInt($("#dr_sku_result .fc-sku-group:last").attr("did"));
	if (id == NaN || id == 'NaN' || isNaN(id)) {
		id = 0;
	} else {
		id++;
	}
	id = dr_sku_get_id(id);
	var html = tpl_group;
	html = html.replace(/\{id\}/g, id);
	html = html.replace(/\{name\}/g, "属性名称_"+(id+1));
	html = html.replace(/\{value\}/g, "");
	$("#dr_sku_result").append(html);
}
function dr_sku_get_id(id) {
	if ($('#dr_sku_group_'+id).length) {
		id = parseInt(id) + 1;
		return dr_sku_get_id(id);
	}
	return id;
}
function dr_sku_del_group(id) {
	$("#dr_sku_group_"+id).remove();
	step.Creat_Table();
}
function dr_sku_del_value(id, iid) {
	$("#dr_sku_value_"+id+"_"+iid).remove();
	step.Creat_Table();
}
function dr_sku_init() {
	step.Creat_Table();
}
function dr_sku_edit_group(id) {
	var name = $("#dr_sku_group_"+id+" .fc-sku-group-name .fc-sku-group-name-input").html();
	$("#dr_sku_group_"+id+" .fc-sku-group-name .fc-sku-group-name-input").html('<input type="text" value="'+name+'" onblur="dr_sku_save_group('+id+')" class="name form-control">');
	$("#dr_sku_group_"+id+" .fc-sku-group-name .edit").hide();
	$("#dr_sku_group_"+id+" .fc-sku-group-name .save").show();
}
function dr_sku_save_group(id) {
	var name = $("#dr_sku_group_"+id+" .fc-sku-group-name .fc-sku-group-name-input .name").val();
	$("#dr_sku_group_"+id+" .fc-sku-group-name .fc-sku-group-name-input").html(name);
	$("#dr_sku_group_"+id+" .fc-sku-group-name .edit").show();
	$("#dr_sku_group_"+id+" .fc-sku-group-name .save").hide();
	$("#dr_sku_group_text_"+id).val(name);

	step.Creat_Table();
}
function dr_sku_add_value(id) {
	var html = tpl_value;

	var iid = parseInt($("#dr_sku_value_"+id+" .fc-sku-group-value:last").attr("did"));
	if (iid == NaN || iid == 'NaN' || isNaN(iid)) {
		iid = 0;
	} else {
		iid++;
	}

	html = html.replace(/\{id\}/g, id);
	html = html.replace(/\{iid\}/g, iid);
	html = html.replace(/\{name\}/g, "值_"+(iid+1));
	$("#dr_sku_value_"+id).append(html);
	step.Creat_Table();
}

function dr_select_sku_price() {
	$('.fc-sku-select-price .fc-sku-value').click(function () {
		$(this).parent('.fc-sku-select-price').find('.fc-sku-value').removeClass('red');
		$(this).addClass('red');
		dr_get_sku_price();
	});
}

function dr_get_sku_price() {
	var oname = new Array();
	$('.fc-sku-select-price').each(function () {
		oname.push($(this).find('.red').attr('fvalue'));
	});
	var k = oname.join("_");
	$('#dr_sku_value').val(k);
	$('#dr_sku_price').html($('#dr_sku_price_'+k).val());
	$('#dr_sku_quantity').html($('#dr_sku_quantity_'+k).val());
	$('#dr_sku_sn').html($('#dr_sku_sn_'+k).val());

}


var myArraymin=function(array) {
	return Float.min.apply(Float,array);
}

var step = {
	//SKU信息组合
	Creat_Table: function () {
		step.hebingFunction();
		var SKUObj = $(".fc-sku-group");
		//var skuCount = SKUObj.length;//
		var arrayName = new Array();　//名称组数
		var arrayTile = new Array();　//标题组数
		var arrayInfor = new Array();　//盛放每组选中的CheckBox值的对象 
		var arrayColumn = new Array(); //指定列，用来合并哪些列
		var bCheck = true;//是否全选
		$.each(SKUObj, function () {
            var columnIndex = $(this).attr('did');
            var itemName = "dr_sku_value_" + columnIndex;
            console.log(itemName);
            if ($("#"+itemName).length>0) {
                arrayColumn.push(columnIndex);
                arrayTile.push($(this).find(".fc-sku-group-name-input").html());
                //选中的CHeckBox取值
                var order = new Array();
                var order_name = new Array();
                var rowIndex = 0;
                $("#" + itemName + " .fc-sku-value-name-input").each(function () {
                    order.push($(this).val());
                    order_name.push($(this).attr("fname"));
                    rowIndex++;

                });

                arrayInfor.push(order);
                arrayName.push(order_name);

                if (order.join() == "") {
                    bCheck = false;
                }
                //var skuValue = SKUObj.find("li").eq(index).html();
            }

		});

        //console.log(arrayTile);
		//开始创建Table表            
		if (bCheck == true) {
			var RowsCount = 0;
			$("#dr_sku_table").html("");
			var table = $("<table class=\"fc-sku-table table table-striped table-bordered \"></table>");
			table.appendTo($("#dr_sku_table"));
			var thead = $("<thead></thead>");
			thead.appendTo(table);
			var trHead = $("<tr></tr>");
			trHead.appendTo(thead);
			//创建表头

			$.each(arrayTile, function (index, item) {
				var td = $("<th>" + item + "</th>");
				td.appendTo(trHead);
			});
			var itemColumHead = $(sku_field_name);
			itemColumHead.appendTo(trHead);

			var tbody = $("<tbody></tbody>");
			tbody.appendTo(table);

			////生成组合
			var zuheDate = step.doExchange(arrayInfor);
			var zuheDate2 = step.doExchange(arrayName);
			if (zuheDate != 'undefined' && zuheDate != undefined && zuheDate.length > 0) {
				//创建行
				$.each(zuheDate, function (index, item) {
					var td_array = item.split(",");
					var tr = $("<tr></tr>");
					var oname = zuheDate2[index].replace(/,/g, "_");

					tr.appendTo(tbody);
					$.each(td_array, function (i, values) {
						var td = $("<td>" + values + "</td>");
						td.appendTo(tr);
					});
					for(var key in sku_field_id){
						var ovalue = arrayValue[oname+"_"+sku_field_id[key]];
						if (ovalue == undefined) {
							ovalue = '';
						}
						if (sku_field_id[key] == 'image') {
							// 图片模式
							var oimg = '';
							var is_show_img = 'display:none';
							if (ovalue && ovalue != 0) {
								oimg = arrayValue[oname+"_"+sku_field_id[key]+"_url"]
								is_show_img = 'display:block';
							}
							var td = $("<td ><label><input class=\"form-control2 form-control-file\" type=\"hidden\" name=\"data["+field_name+"][value]["+oname+"]["+sku_field_id[key]+"]\" value=\""+ovalue+"\" ><input class=\"form-control3 form-control-link form-control-preview\" type=\"hidden\" value=\""+oimg+"\" ><a href=\"javascript:;\" onclick=\"dr_ftable_myfileinput(this, '"+sku_image_url+"')\" class=\"ftable-fileinput pull-left btn green btn-sm\">上传</a><a href=\"javascript:;\" onclick=\"dr_ftable_myshow(this)\" style=\""+is_show_img+"\" class=\"ftable-show pull-left btn blue btn-sm\">预览</a><a href=\"javascript:;\" onclick=\"dr_ftable_mydelete(this)\" style=\""+is_show_img+"\" class=\"ftable-delete pull-left btn red btn-sm\">删除</a> </label></td>");
						} else {
							var td = $("<td ><input type=\"text\" name=\"data["+field_name+"][value]["+oname+"]["+sku_field_id[key]+"]\"  value=\""+ovalue+"\" class=\"input-sm form-control\"></td>");
						}
						td.appendTo(tr);
					}
				});
			}
			//结束创建Table表
			arrayColumn.pop();//删除数组中最后一项
			//合并单元格
			$(table).mergeCell({
				// 目前只有cols这么一个配置项, 用数组表示列的索引,从0开始
				cols: arrayColumn
			});
		}
	},//合并行
	hebingFunction: function () {
		$.fn.mergeCell = function (options) {
			return this.each(function () {
				var cols = options.cols;
				for (var i = cols.length - 1; cols[i] != undefined; i--) {
					// fixbug console调试 
					// console.debug(cols[i]); 
					mergeCell($(this), cols[i]);
				}
				dispose($(this));
			});
		};
		// 如果对javascript的closure和scope概念比较清楚, 这是个插件内部使用的private方法            
		function mergeCell($table, colIndex) {
			$table.data('col-content', ''); // 存放单元格内容 
			$table.data('col-rowspan', 1); // 存放计算的rowspan值 默认为1 
			$table.data('col-td', $()); // 存放发现的第一个与前一行比较结果不同td(jQuery封装过的), 默认一个"空"的jquery对象 
			$table.data('trNum', $('tbody tr', $table).length); // 要处理表格的总行数, 用于最后一行做特殊处理时进行判断之用 
			// 我们对每一行数据进行"扫面"处理 关键是定位col-td, 和其对应的rowspan 
			$('tbody tr', $table).each(function (index) {
				// td:eq中的colIndex即列索引 
				var $td = $('td:eq(' + colIndex + ')', this);
				// 取出单元格的当前内容 
				var currentContent = $td.html();
				// 第一次时走此分支 
				if ($table.data('col-content') == '') {
					$table.data('col-content', currentContent);
					$table.data('col-td', $td);
				} else {
					// 上一行与当前行内容相同 
					if ($table.data('col-content') == currentContent) {
						// 上一行与当前行内容相同则col-rowspan累加, 保存新值 
						var rowspan = $table.data('col-rowspan') + 1;
						$table.data('col-rowspan', rowspan);
						// 值得注意的是 如果用了$td.remove()就会对其他列的处理造成影响 
						$td.hide();
						// 最后一行的情况比较特殊一点 
						// 比如最后2行 td中的内容是一样的, 那么到最后一行就应该把此时的col-td里保存的td设置rowspan 
						if (++index == $table.data('trNum'))
							$table.data('col-td').attr('rowspan', $table.data('col-rowspan'));
					} else { // 上一行与当前行内容不同 
						// col-rowspan默认为1, 如果统计出的col-rowspan没有变化, 不处理 
						if ($table.data('col-rowspan') != 1) {
							$table.data('col-td').attr('rowspan', $table.data('col-rowspan'));
						}
						// 保存第一次出现不同内容的td, 和其内容, 重置col-rowspan 
						$table.data('col-td', $td);
						$table.data('col-content', $td.html());
						$table.data('col-rowspan', 1);
					}
				}
			});
		}
		// 同样是个private函数 清理内存之用 
		function dispose($table) {
			$table.removeData();
		}
	},
	//组合数组
	doExchange: function (doubleArrays) {
		var len = doubleArrays.length;
		if (len >= 2) {
			var arr1 = doubleArrays[0];
			var arr2 = doubleArrays[1];
			var len1 = doubleArrays[0].length;
			var len2 = doubleArrays[1].length;
			var newlen = len1 * len2;
			var temp = new Array(newlen);
			var index = 0;
			for (var i = 0; i < len1; i++) {
				for (var j = 0; j < len2; j++) {
					temp[index] = arr1[i] + "," + arr2[j];
					index++;
				}
			}
			var newArray = new Array(len - 1);
			newArray[0] = temp;
			if (len > 2) {
				var _count = 1;
				for (var i = 2; i < len; i++) {
					newArray[_count] = doubleArrays[i];
					_count++;
				}
			}
			//console.log(newArray);
			return step.doExchange(newArray);
		}
		else {
			return doubleArrays[0];
		}
	}
}
