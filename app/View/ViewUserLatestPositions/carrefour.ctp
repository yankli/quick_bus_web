<div class="row">
    <div class="page-header" style="margin-top: 10px;">
        <p class="text-center"><strong>班车显示切换</strong></p>
        <div class="progress">
            <div id="progresBar" class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">
            </div>
        </div>
    </div>
</div>
<div class="row" id="maps_display" style="height: 100%;">
</div>
<script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=jENePgN7TufGt711E1uIb7BA"></script>
<script type="text/javascript" src="/js/bootstrap.js"></script>
<script type="text/javascript">
    var NumMapsPerScreen = 2;
    var screenSwitchInterval = 10000;
    var getGpsInterval = 10000;
    var progressUpdateFrequency = 2000;
    
    var progressValue = 0;
    var mapHandlers = {};
    var numberOfScreens = 0;
    var currentScreen = 0;

    function BaiduMapInitialize(bus, map)
    {
        var convertRoutePoints = "";
        
        for (var i = 0; i < bus["UserRoutePoint"].length; i++)
        {
            convertRoutePoints += bus["UserRoutePoint"][i]["longitude"] + "," + bus["UserRoutePoint"][i]["latitude"] + ";";
        }

        convertRoutePoints = convertRoutePoints.substring(0, convertRoutePoints.length - 1);
        var baiduConvertUrl = "http://api.map.baidu.com/geoconv/v1/?coords=" + convertRoutePoints + "&ak=jENePgN7TufGt711E1uIb7BA";
        
        $.get(
            baiduConvertUrl,
            "",
            function(data) {
                var routePoints = [];
                
                for (var i = 0; i < data.result.length; i++)
                {
                    routePoints.push(new BMap.Point(data.result[i].x, data.result[i].y));
                }

                map.setViewport(routePoints);
                map.addOverlay(new BMap.Polyline(routePoints));
            },
            "jsonp"
        );

        var convertStationPoints = "";
        
        for (var i = 0; i < bus["UserStationPoint"].length; i++)
        {
            convertStationPoints += bus["UserStationPoint"][i]["longitude"] + "," + bus["UserStationPoint"][i]["latitude"] + ";";
        }

        convertStationPoints = convertStationPoints.substring(0, convertStationPoints.length - 1);
        var baiduConvertUrl = "http://api.map.baidu.com/geoconv/v1/?coords=" + convertStationPoints + "&ak=jENePgN7TufGt711E1uIb7BA";

        $.get(
            baiduConvertUrl,
            "",
            function(data) {
                var stationPoints = [];
                
                for (var i = 0; i < data.result.length; i++)
                {
                    var imgUrl = "";
                    (i == 0) ? imgUrl = "/img/station_editing.png" : imgUrl = "/img/station.png";
                    
                    var myIcon = new BMap.Icon(imgUrl, new BMap.Size(32, 32));
                    myIcon.setImageSize(new BMap.Size(32, 32));
                    myIcon.setAnchor(new BMap.Size(16, 32));
                    var stationMarker = new BMap.Marker(new BMap.Point(data.result[i].x, data.result[i].y), {icon: myIcon});
                    map.addOverlay(stationMarker);
                }
            },
            "jsonp"
        );
    }

    function initialize()
    {
        $.ajax(
        {
            url: "/ViewUserLatestPositions/get_carrefour_buses",
            type: "GET",
            timeout: 15000,
            success: function(result) {
                var buses = JSON.parse(result);
                var numberOfBuses = buses.length;
                var columnClass = "col-xs-" + parseInt(12 / NumMapsPerScreen);
                numberOfScreens = Math.ceil(numberOfBuses / NumMapsPerScreen);

                for (var i = 0; i < numberOfScreens; i++)
                {
                    var screenID = "screen" + (i + 1);
                    var screen = $("<div>").addClass("row").attr("id", screenID).attr("style", "height: 100%; margin-top: -50px; padding: 50px 0 0 0;");

                    for (var j = 0; j < NumMapsPerScreen; j++)
                    {
                        var mapID = "map" + ((i * NumMapsPerScreen) + j + 1);
                        var busTitle = "";
                        var busStatus = "";
                        
                        if (buses[(i * NumMapsPerScreen) + j] !== undefined)
                        {
                            busTitle = buses[(i * NumMapsPerScreen) + j]["UserRoute"]["name"];
                            busStatus = buses[(i * NumMapsPerScreen) + j]["ViewUserLatestPosition"]["run_status"];
                        }
                        
                        var column = $("<div>").addClass(columnClass).attr("style", "height: 100%;");
                        var title = $("<h4>").addClass("text-center").html($("<strong>").html(busTitle));
                        var status = $("<p>").addClass("text-center").html($("<strong>").html(busStatus));
                        var mapArea = $("<div>").addClass("col-xs-12").attr("id", mapID).attr("style", "height: 100%;");

                        column.append(title).append(status).append(mapArea);
                        screen.append(column);
                    }

                    $("#maps_display").append(screen);
                }

                $("div[id='screen1']").show();

                for (var i = 0; i < numberOfBuses; i++)
                {
                    var mapID = "map" + (i + 1);
                    mapHandlers[mapID] = new BMap.Map(mapID);
                    mapHandlers[mapID].disableDoubleClickZoom();

                    BaiduMapInitialize(buses[i], mapHandlers[mapID]);
                }

                setInterval(UpdateProgressBar, progressUpdateFrequency);
            },
            error: function(xhr, status) {
                alert("初始化错误，请重新打开浏览器");
            }
        });
    }

    function UpdateProgressBar()
    {
        $("#progresBar").attr("aria-valuenow", Math.floor((progressValue / screenSwitchInterval) * 100))
            .attr("style", "width: " + Math.floor(((progressValue / screenSwitchInterval) * 100)) + "%");
        
        if (progressValue == 0)
        {
            $("div[id^='screen']").hide(0, function() {$("div[id='screen" + (currentScreen + 1) + "']").show(0);});

            currentScreen++;
            currentScreen = (currentScreen % numberOfScreens);
        }

        progressValue += progressUpdateFrequency;
        progressValue = progressValue % (screenSwitchInterval);
    }

    $(document).ready(initialize);
    //setInterval(UpdateProgressBar, progressUpdateFrequency);
</script>












