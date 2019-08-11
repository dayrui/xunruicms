
function dr_baidumap(mapObj, name, city, level) {

    //向地图中添加缩放控件
    var ctrl_nav = new BMap.NavigationControl(
        {
            anchor:BMAP_ANCHOR_TOP_LEFT,
            type:BMAP_NAVIGATION_CONTROL_LARGE
        }
    );
    mapObj.addControl(ctrl_nav);
    mapObj.enableDragging();//启用地图拖拽事件，默认启用(可不写)
    mapObj.enableScrollWheelZoom();//启用地图滚轮放大缩小
    mapObj.enableDoubleClickZoom();//启用鼠标双击放大，默认启用(可不写)
    mapObj.enableKeyboard();//启用键盘上下左右键移动地图

    if($('#dr_'+name).val()) {
        drawPoints(mapObj, name, level);
    } else {
        mapObj.centerAndZoom(city);
    }

}

//设置切换城市
function keywordSearch(city) {
    if(city==null || city=='') {
        var city=$("#citywd").val();
    }
    mapObj.setCenter(city);
    $("#curCity").html(city);
}

function drawPoints(mapObj, name, level){
    var data = $('#dr_'+name).val();
    var data = data.split(',');
    var lngX = data[0];
    var latY = data[1];
    var zoom = level;
    mapObj.centerAndZoom(new BMap.Point(lngX,latY),zoom);
    // 创建图标对象
    var myIcon = new BMap.Icon(assets_path+'images/mak.png', new BMap.Size(27, 45));

    // 创建标注对象并添加到地图
    var center = mapObj.getCenter();
    var point = new BMap.Point(lngX,latY);
    var marker = new BMap.Marker(point, {icon: myIcon});
    marker.enableDragging();
    mapObj.addOverlay(marker);
    var ZoomLevel = mapObj.getZoom();
    marker.addEventListener("dragend", function(e){
        $('#dr_'+name).val(e.point.lng+','+e.point.lat);
    })
}

// 搜索地址
function baiduSearchAddress(mapObj, name){
    var address = $('#baidu_address_'+name).val();
    var myGeo = new BMap.Geocoder();
    // 将地址解析结果显示在地图上,并调整地图视野
    myGeo.getPoint(address, function(point){
        if (point) {
            mapObj.centerAndZoom(point, 13);
            mapObj.addOverlay(new BMap.Marker(point));
        }else{
            dr_tips(0, "没有找到这个地址");
        }
    });
    //mapObj.setCenter(address);
}

// 添加标注
function addMarker(mapObj, name){
    mapObj.clearOverlays();
    // 创建图标对象
    var myIcon = new BMap.Icon(assets_path+'images/mak.png', new BMap.Size(27, 45));
    // 创建标注对象并添加到地图
    var center = mapObj.getCenter();
    var point = new BMap.Point(center.lng,center.lat);
    var marker = new BMap.Marker(point, {icon: myIcon});
    marker.enableDragging();
    mapObj.addOverlay(marker);
    var ZoomLevel = mapObj.getZoom();
    $('#dr_'+name).val(center.lng+','+center.lat);
    marker.addEventListener("dragend", function(e){
        $('#dr_'+name).val(e.point.lng+','+e.point.lat);
    })
}

