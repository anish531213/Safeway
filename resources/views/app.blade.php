<!DOCTYPE html>
<html>
<head>
    <title>Verbosity, LLC</title>

    <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">
    <!--<link href="/css/all.css" rel="stylesheet">-->
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">


    <!-- jQuery library -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>

    <!-- Latest compiled JavaScript -->
    <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDdMKNKRCFo5UVgFV30JivZ5SUf8U-pqvk&libraries=geometry,drawing,places"></script>


    <script type="text/javascript">

        var line;

        var controller = {
            map:null,
            slantMode:false,
            autocomplete:null,
            places:null,
            mode:'H',
            draw_mode: 'line',
            shift:{
                point1: null,
                point2: null,
                gradient1: null,
                gradient2: null,
                distance: null,
                on: false,
                makeShift: false,
                shift_constant: null,
            },
            constant:{
                x: 3.80952/364560,
                y: 10/364560,
                spacing: 8/364560,
                offset: 13/364560
            },
            plotter:{
                parent:this,
                lastLinePath:[],

                //Stores references to shapes
                shapes:[],

                //Stores references to Intervals
                intervals:[],

                //Adds a shape object to Map and tracks for removal
                addShape:function(shape){
                    shape.setMap(this.getMap());
                    this.shapes.push(shape);
                    return this;
                },

                addDeletePolygon:function(polygon){
                    google.maps.event.addListener(polygon, 'dblclick', function (e) {
                        event.preventDefault();
                        event.stopImmediatePropagation();
                        controller.plotter.removeShape(polygon);
                    });
                },

                removeShape:function(shape){
                    for (var i=0;i<this.shapes.length;i++){
                        if (this.shapes[i]===shape){
                            this.shapes[i].setMap(null);
                            this.shapes[i]=undefined;
                            this.shapes.splice(i, 1);
                            return this;
                        }

                    }
                },

                getMap:function(){
                    //Get the overall map
                    return controller.map;
                },

                clearAll:function(){
                    var i;
                    for (i=0;i<this.shapes.length;i++){
                        this.shapes[i].setMap(null);
                        this.shapes[i]=undefined;
                    }
                    this.shapes=[];
                    for (i=0;i<this.intervals.length;i++){
                        clearInterval(this.intervals[i]);
                        delete this.intervals[i];

                    }
                    this.intervals=[];
                },

                getMap:function(){
                    //Get the overall map
                    return controller.map;
                },

                slope:function(prj_map, ptA, ptB){
                    var map = prj_map;
                    var prj = map.getProjection();
                    var x1 = prj.fromLatLngToPoint(ptB).x;
                    var x2 = prj.fromLatLngToPoint(ptA).x;
                    var y1 = prj.fromLatLngToPoint(ptB).y;
                    var y2 = prj.fromLatLngToPoint(ptA).y;

                    if (x2-x1 != 0)
                        return (y2-y1)/(x2-x1);
                    else
                        return Math.E+10;
                },
                // Calculate the angle using slope
                calculateAngle:function(gradient) {
                    return Math.atan(gradient);
                },

                getOffsetPoint:function(point, gradient, offset_value) {
                    var map = this.getMap();
                    var prj = map.getProjection();
                    var p_point = prj.fromLatLngToPoint(point);
                    var r = Math.sqrt(1+gradient*gradient);
                    p_point.x += (gradient*offset_value)/r;
                    p_point.y -= offset_value/r;
                    return prj.fromPointToLatLng(p_point);
                },

                getIntersectPoint:function(pt1, pt2, m1, m2) {
                    var map = this.getMap();
                    var prj = map.getProjection();
                    var intersect = prj.fromLatLngToPoint(pt1);
                    var p_pt1 = prj.fromLatLngToPoint(pt1);
                    var p_pt2 = prj.fromLatLngToPoint(pt2);
                    var c1 = p_pt1.y-m1*p_pt1.x;
                    var c2 = p_pt2.y-m2*p_pt2.x;
                    intersect.x = (c2-c1)/(m1-m2);
                    intersect.y = m1*intersect.x+c1;
                    return prj.fromPointToLatLng(intersect);
                },
                // currently, the lines are created at non-parallel angles when the angle
                // between is close to 0 or 180 degrees without actually reaching those angles
                parallelLines:function(line) {
                    if (controller.mode == 'H') {
                        controller.constant.x = 3.80952/364560;
                        controller.constant.y = 10/364560;
                        controller.constant.spacing = 8/364560;
                        controller.constant.offset = 13/364560;
                    } else {
                        controller.constant.y = 3.80592/364560;
                        controller.constant.x = 10/364560;
                        controller.constant.spacing = 21/364560;
                        controller.constant.offset = 8/364560;
                    }
                    var poly1 = this.createParallelLine(line, 1);
                    var poly2 = this.createParallelLine(line, -1);
                    this.polylineDrawn(poly1);
                    this.polylineDrawn(poly2);
                    poly1.setMap(null);
                    poly2.setMap(null);
                },

                createParallelLine:function(polyline, val) {
                    var map = this.getMap();
                    var coordinates = []; //empty array for path of parallel line
                    polyline = polyline.getPath();
                    var pt1 = polyline.getAt(0);
                    var pt2 = polyline.getAt(1);
                    var pt_last = polyline.getAt(polyline.length-1);
                    var gradient = this.slope(map, pt1, pt2);
                    var off1 = this.getOffsetPoint(pt1, gradient, controller.constant.offset*val);
                    var off2 = this.getOffsetPoint(pt2, gradient, controller.constant.offset*val);
                    coordinates.push({lat: off1.lat(), lng: off1.lng()});

                    var m1 = gradient;
                    var m2 = gradient;
                    var m, ptA, ptB, offA, offB, intersect;
                    for (var i = 1; i < polyline.length-1; i++) {
                        ptA = polyline.getAt(i);
                        ptB = polyline.getAt(i+1);
                        m2 = this.slope(map, ptA, ptB);
                        offA = this.getOffsetPoint(ptA, m1, controller.constant.offset*val);
                        offB = this.getOffsetPoint(ptB, m2, controller.constant.offset*val);
                        intersect = this.getIntersectPoint(offA, offB, m1, m2);
                        coordinates.push({lat: intersect.lat(), lng:intersect.lng()});
                        m1 = m2;
                    }
                    off_last = this.getOffsetPoint(pt_last, m2, controller.constant.offset*val);
                    coordinates.push({lat: off_last.lat(), lng: off_last.lng()});
                    var newline = new google.maps.Polyline({
                        map: map,
                        path: coordinates
                    });
                    return newline;
                },

                createSinglePolygon:function(start, end) {
                    console.log("working");
                },

                createMultiplePolygon:function(prj_map, start, end, gradient) {

                    var map = prj_map;
                    var prj = map.getProjection();
                    var p_center = prj.fromLatLngToPoint(start);
                    //Updating the center
                    var spacing_constant = 0.5;
                    if (controller.shift.makeShift == true) {
                        console.log(controller.shift.gradient1);
                        console.log(controller.shift.gradient2);
                        spacing_constant = controller.shift.shift_constant;
                        controller.shift.makeShift = false;
                    }
                    if (prj.fromLatLngToPoint(start).x < prj.fromLatLngToPoint(end).x) {
                        p_center.y = p_center.y + (Math.sqrt((Math.pow(controller.constant.spacing*spacing_constant,2))/(1+Math.pow(controller.constant.spacing*spacing_constant,2))))*controller.constant.spacing*spacing_constant;
                        p_center.x = p_center.x + Math.sqrt((Math.pow(controller.constant.spacing*spacing_constant,2))/(1+Math.pow(controller.constant.spacing*spacing_constant,2)));
                    } else {
                        p_center.y = p_center.y - (Math.sqrt((Math.pow(controller.constant.spacing*spacing_constant,2))/(1+Math.pow(controller.constant.spacing*spacing_constant,2))))*controller.constant.spacing*spacing_constant;
                        p_center.x = p_center.x - Math.sqrt((Math.pow(controller.constant.spacing*spacing_constant,2))/(1+Math.pow(controller.constant.spacing*spacing_constant,2)));
                    }

                    var distance = Math.sqrt(Math.pow((prj.fromLatLngToPoint(start).x - prj.fromLatLngToPoint(end).x),2) + Math.pow((prj.fromLatLngToPoint(start).y - prj.fromLatLngToPoint(end).y), 2));

                    var total = (distance)/controller.constant.spacing;

                    if (((total-Math.floor(total))>0.5) && (controller.mode != 'H')){
                        controller.shift.gradient1 = gradient;
                        controller.shift.on = true;
                        controller.makeShift = true;
                        controller.shift.shift_constant = total-Math.floor(total);
                        controller.shift.distance = (total-Math.floor(total))*controller.constant.spacing;
                        total += 1;
                    }
                    total = Math.floor(total);

                    //loop to create multiple polygon
                    for (var i = 0; i < total; i++) {
                        
                        var coords;

                        var p_coords = {
                            ne: {x: p_center.x+controller.constant.x, y: p_center.y+controller.constant.y},
                            se: {x: p_center.x+controller.constant.x, y: p_center.y-controller.constant.y},
                            sw: {x: p_center.x-controller.constant.x, y: p_center.y-controller.constant.y},
                            nw: {x: p_center.x-controller.constant.x, y: p_center.y+controller.constant.y}
                        };

                        var p_coords2 = {
                            ne: {
                                x: p_center.x+((p_coords.ne.x-p_center.x)*Math.cos(Math.PI/6))-((p_coords.ne.y-p_center.y)*Math.sin(Math.PI/6)), 
                                y: p_center.y+((p_coords.ne.x-p_center.x)*Math.sin(Math.PI/6))+((p_coords.ne.y-p_center.y)*Math.cos(Math.PI/6))
                            },
                            se: {
                                x: p_center.x+((p_coords.se.x-p_center.x)*Math.cos(Math.PI/6))-((p_coords.se.y-p_center.y)*Math.sin(Math.PI/6)), 
                                y: p_center.y+((p_coords.se.x-p_center.x)*Math.sin(Math.PI/6))+((p_coords.se.y-p_center.y)*Math.cos(Math.PI/6))
                            },
                            sw: {
                                x: p_center.x+((p_coords.sw.x-p_center.x)*Math.cos(Math.PI/6))-((p_coords.sw.y-p_center.y)*Math.sin(Math.PI/6)), 
                                y: p_center.y+((p_coords.sw.x-p_center.x)*Math.sin(Math.PI/6))+((p_coords.sw.y-p_center.y)*Math.cos(Math.PI/6))
                            },
                            nw: {
                                x: p_center.x+((p_coords.nw.x-p_center.x)*Math.cos(Math.PI/6))-((p_coords.nw.y-p_center.y)*Math.sin(Math.PI/6)), 
                                y: p_center.y+((p_coords.nw.x-p_center.x)*Math.sin(Math.PI/6))+((p_coords.nw.y-p_center.y)*Math.cos(Math.PI/6))
                            }
                        };

                        var coords1 = [
                            {lat: prj.fromPointToLatLng(p_coords.ne).lat(), lng: prj.fromPointToLatLng(p_coords.ne).lng()},
                            {lat: prj.fromPointToLatLng(p_coords.se).lat(), lng: prj.fromPointToLatLng(p_coords.se).lng()},
                            {lat: prj.fromPointToLatLng(p_coords.sw).lat(), lng: prj.fromPointToLatLng(p_coords.sw).lng()},
                            {lat: prj.fromPointToLatLng(p_coords.nw).lat(), lng: prj.fromPointToLatLng(p_coords.nw).lng()}
                        ];

                        var coords2 = [
                            {lat: prj.fromPointToLatLng(p_coords2.ne).lat(), lng: prj.fromPointToLatLng(p_coords2.ne).lng()},
                            {lat: prj.fromPointToLatLng(p_coords2.se).lat(), lng: prj.fromPointToLatLng(p_coords2.se).lng()},
                            {lat: prj.fromPointToLatLng(p_coords2.sw).lat(), lng: prj.fromPointToLatLng(p_coords2.sw).lng()},
                            {lat: prj.fromPointToLatLng(p_coords2.nw).lat(), lng: prj.fromPointToLatLng(p_coords2.nw).lng()}
                        ];

                        if (prj.fromLatLngToPoint(start).x < prj.fromLatLngToPoint(end).x) {
                            p_center.y = p_center.y + (Math.sqrt((Math.pow(controller.constant.spacing,2))/(1+Math.pow(controller.constant.spacing,2))))*controller.constant.spacing;
                            p_center.x = p_center.x + Math.sqrt((Math.pow(controller.constant.spacing,2))/(1+Math.pow(controller.constant.spacing,2)));
                        } else {
                            p_center.y = p_center.y - (Math.sqrt((Math.pow(controller.constant.spacing,2))/(1+Math.pow(controller.constant.spacing,2))))*controller.constant.spacing;
                            p_center.x = p_center.x - Math.sqrt((Math.pow(controller.constant.spacing,2))/(1+Math.pow(controller.constant.spacing,2)));
                        }

                        if (controller.slantMode == true)
                            coords = coords2;
                        else
                            coords = coords1;

                        var poly = this.createRectangularPolygon(map, coords);

                        this.addShape(poly);
                        this.addDeletePolygon(poly);

                        controller.plotter.rotatePolygon(poly, controller.plotter.calculateAngle(gradient), start);
                    }


                },

                createRectangularPolygon:function(map, coords) {
                    var polygon = new google.maps.Polygon({
                        map: map,
                        paths: coords,
                        strokeColor: '#FF0000',
                        strokeOpacity: 0.8,
                        strokeWeight: 2,
                        fillColor: '#FF0000',
                        fillOpacity: 0.35
                    });
                    return polygon;
                },

                // Function to rotate polygon at a certain angle from an arbitary point 'center'
                rotatePolygon:function(polygon,angle,center) {
                    var map = polygon.getMap();
                    var prj = map.getProjection();
                    var origin = prj.fromLatLngToPoint(center); //rotate around first point
                    // console.log(polygon.getPath().getAt(0));
                    var coords = polygon.getPath().getArray().map(function(latLng){
                        var point = prj.fromLatLngToPoint(latLng);
                        var rotatedLatLng =  prj.fromPointToLatLng(controller.plotter.rotatePoint(point,origin,angle));
                        // console.log(point);
                        return {lat: rotatedLatLng.lat(), lng: rotatedLatLng.lng()};
                    });
                    polygon.setPath(coords);
                },

                // Function to rotate point a certain angle from origin
                rotatePoint:function(point, origin, angle) {
                    var angleRad = angle;
                    return {
                        x: Math.cos(angleRad) * (point.x - origin.x) - Math.sin(angleRad) * (point.y - origin.y) + origin.x,
                        y: Math.sin(angleRad) * (point.x - origin.x) + Math.cos(angleRad) * (point.y - origin.y) + origin.y
                    };
                },

                polylineDrawn:function(polyline) {

                    polyline.setEditable(true);
                    polyline.setDraggable(true);
                    // polyline.setMap(null);
                    var line = polyline.getPath();
                    // Calculated at Start
                    var j = 0;

                    for (var i=1; i<line.length; i++) {

                        begin = line.getAt(j);
                        end = line.getAt(j+1);
                        
                        // var heading = this.getHeading(begin, end);
                        // console.log(heading);

                        var gradient = controller.plotter.slope(this.getMap(), begin, end);

                        if (controller.shift.on == true) {
                            controller.shift.gradient2 = gradient;
                            controller.shift.makeShift = true;
                            controller.shift.on = false;
                        }

                        controller.plotter.createMultiplePolygon(this.getMap(), begin, end, gradient);

                        j+=1;
                    }
                },
                redrawLastLine:function() {
                    var val = document.getElementById("inputbox").value;
                    controller.constant.spacing = val/364560;
                    controller.plotter.clearAll();
                    var newLine = new google.maps.Polyline({
                        // map: controller.map,
                        path: controller.plotter.lastLinePath,
                        geodesic: true,
                        strokeColor: '#FF0000',
                        strokeOpacity: 1.0,
                        strokeWeight: 2
                    });
                    controller.plotter.polylineDrawn(newLine);
                },
                horizontal:function() {
                    controller.mode = 'H';
                    controller.slantMode = false;
                },
                vertical:function() {
                    controller.mode = 'V';
                    controller.slantMode = false;
                },
                slant:function() {
                    controller.mode = 'H';
                    controller.slantMode = true;
                },
                parallel:function() {
                    if (controller.draw_mode == 'line') {
                        controller.draw_mode = 'parallel_line';
                        $("#setParallel").addClass("btn-info");
                    }
                    else {
                        $("#setParallel").removeClass("btn-info");
                        $("#setParallel").addClass("btn-default");
                        controller.draw_mode = 'line';
                    }
                },

                onPlaceChanged:function() {
                    var place = controller.autocomplete.getPlace();

                    if (place.geometry) {
                        controller.map.panTo(place.geometry.location);
                        controller.map.setZoom(15);

                    } else {
                        document.getElementById('viewport').placeholder = 'Enter a Spot';
                    }
                }
            }
        };



        var mapper = {
            map: null,
            directionDisplay: null,
            directionsService: null,
            stepDisplay: null,
            markerArray: [],
            position: null,
            trackArray: [],
            marker: null,
            polyline: null,
            poly2: null,
            speed: 0.000005,
            wait: 1,
            infowindow: null,
            myPano: null,
            panoClient: null,
            nextPanoId: null,
            timerHandle: null,
            endLocation: null,
            startLocation: null,
            currentHeading: null,

            clickManager:{
                clickMode: true,
                clicks:[],
                ready:function(){
                    return this.clicks.length==2;
                },
                hasClicks:function(){
                    return this.clicks.length>0;
                },
                clear:function(){ this.clicks = [] },
                init:function(map){
                    google.maps.event.addListener(map, 'click', function(event) {
                        var clicks = mapper.clickManager.clicks;
                        if (clicks.length<2){
                            clicks.push(event.latLng);
                        } else if (clicks.length==2){
                            clicks.pop();
                            clicks.push(event.latLng);
                            //clicks.reverse();
                        }

                        if (clicks.length<2) return alert("Click on an endpoint");

                        //Do routing...
                        mapper.calcRoute();
                    });
                }
            },
            marker_image: {
                url: "/images/spotjunky/car_N.png",
                size: new google.maps.Size(100, 100),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(20, 37.5),
                scaledSize: new google.maps.Size(40, 75)
            },

            createMarker: function(latlng, label, html) {
                var contentString = '<b>'+label+'</b><br>'+html;
                var marker = new google.maps.Marker({
                    position: latlng,
                    map: mapper.map,
                    title: label,
                    icon: mapper.marker_image,
                    zIndex: Math.round(latlng.lat()*-100000)<<5
                });
                marker.myname = label;
                // gmarkers.push(marker);

                google.maps.event.addListener(marker, 'click', function() {
                    infowindow.setContent(contentString);
                    infowindow.open(map,marker);
                });
                return marker;
            },
            initMap: function() {
                mapper.infowindow = new google.maps.InfoWindow(
                        {
                            size: new google.maps.Size(150,50)
                        });
                // Instantiate a directions service.
                mapper.directionsService = new google.maps.DirectionsService();
                var center = {lat: 39.293, lng: -76.615};
                // Create a map and center it on Manhattan.
                var myOptions = {
                    zoom: 13,
                    center: center,
                    mapTypeId: google.maps.MapTypeId.ROADMAP
                };
                mapper.map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

                map = mapper.map;

                // New Stuffs

                //Phonegap Listeneer W
                window.addEventListener("message", receiveMessage, false);

                function receiveMessage(event)
                {
                  var origin = event.origin || event.originalEvent.origin; // For Chrome, the origin property is in the event.originalEvent object.
                  if (origin !== "http://example.org:8080")
                    console.log("Working phonegap inside if");
                    return;

                  console.log("Working phonegap");
                }

                controller.autocomplete = new google.maps.places.Autocomplete(
                    /** @type {!HTMLInputElement} */ (
                            document.getElementById('viewport')), {
                        types: ['(cities)']//,
                        //componentRestrictions: countryRestrict
                    });
                controller.places = new google.maps.places.PlacesService(map);

                controller.autocomplete.addListener('place_changed', controller.plotter.onPlaceChanged);

                dm = new google.maps.drawing.DrawingManager({
                drawingMode: google.maps.drawing.OverlayType.POLYLINE,
                drawingControl: true,
                drawingControlOptions: {
                    position: google.maps.ControlPosition.TOP_CENTER,
                    drawingModes: [
                        //google.maps.drawing.OverlayType.MARKER,
                        //google.maps.drawing.OverlayType.CIRCLE,
                        // google.maps.drawing.OverlayType.POLYGON,
                        google.maps.drawing.OverlayType.POLYLINE,
                        // google.maps.drawing.OverlayType.RECTANGLE,
                    ]
                },
                markerOptions: {icon: 'images/beachflag.png'},
                circleOptions: {
                    fillColor: '#ffff00',
                    fillOpacity: 0.2,
                    strokeWeight: 3,
                    clickable: true,
                    editable: true,
                    zIndex: 1,
                    radius: 20,
                }
                });

                dm.setMap(map);

                google.maps.event.addListener(dm, 'polygoncomplete', function(polygon) {
                    polygon.setEditable(true);
                    polygon.setDraggable(true);
                });

                google.maps.event.addListener(dm, 'circlecomplete', function(circle) {
                    circle.setEditable(true);
                    circle.setDraggable(true);
                });

                google.maps.event.addListener(dm, 'rectanglecomplete', function(rectangle) {
                    rectangle.setEditable(true);
                    rectangle.setDraggable(true);
                });

                google.maps.event.addListener(dm, 'polylinecomplete', function(polyline) {
                    if (controller.mode == 'H') {
                        controller.constant.x = 3.80592/364560;
                        controller.constant.y = 10/364560;
                        controller.constant.spacing = 8/364560;
                        controller.constant.offset = 13/364560;
                    } else {
                        controller.constant.y = 3.80592/364560;
                        controller.constant.x = 10/364560;
                        controller.constant.spacing = 21/364560;
                        controller.constant.offset = 8/364560;
                    }
                    controller.plotter.lastLinePath = polyline.getPath().getArray();
                    controller.plotter.addShape(polyline);
                    if (controller.shift.on == true) {
                        console.log("working");
                        controller.shift.on = false;
                    }
                    
                    if (controller.draw_mode == 'parallel_line')
                        controller.plotter.parallelLines(polyline);
                    else if (controller.draw_mode == 'line')
                        controller.plotter.polylineDrawn(polyline);
                    polyline.setMap(null);

                });

                controller.map = map;
                /*
                address = 'baltimore, MD';
                geocoder = new google.maps.Geocoder();
                geocoder.geocode( { 'address': address}, function(results, status) {
                    mapper.map.setCenter(results[0].geometry.location);
                });
                */

                // Create a renderer for directions and bind it to the map.
                var rendererOptions = {
                    map: mapper.map
                };
                mapper.directionsDisplay = new google.maps.DirectionsRenderer(rendererOptions);

                // Instantiate an info window to hold step text.
                mapper.stepDisplay = new google.maps.InfoWindow();

                mapper.polyline = new google.maps.Polyline({
                    path: [],
                    strokeColor: '#FF0000',
                    strokeWeight: 3
                });
                mapper.poly2 = new google.maps.Polyline({
                    path: [],
                    strokeColor: '#FF0000',
                    strokeWeight: 3
                });
                //Initialize click directions
                mapper.clickManager.init(mapper.map);
            },


            steps: [],

            calcRoute: function(){
                var useClicks = mapper.clickManager.ready();
                if (mapper.timerHandle) { clearTimeout(mapper.timerHandle); }
                if (mapper.marker) { mapper.marker.setMap(null);}
                mapper.polyline.setMap(null);
                mapper.poly2.setMap(null);
                mapper.directionsDisplay.setMap(null);
                mapper.polyline = new google.maps.Polyline({
                    path: [],
                    strokeColor: '#FF0000',
                    strokeWeight: 3
                });
                mapper.poly2 = new google.maps.Polyline({
                    path: [],
                    strokeColor: '#FF0000',
                    strokeWeight: 3
                });
                // Create a renderer for directions and bind it to the map.
                var rendererOptions = {
                    map: mapper.map
                };
                mapper.directionsDisplay = new google.maps.DirectionsRenderer(rendererOptions);

                var start = useClicks ? mapper.clickManager.clicks[0] : document.getElementById("start").value;
                var end = useClicks ? mapper.clickManager.clicks[1] : document.getElementById("end").value;

                var travelMode = google.maps.DirectionsTravelMode.DRIVING;

                var request = {
                    origin: start,
                    destination: end,
                    travelMode: travelMode
                };
                // function to create markers for each step.
                mapper.directionsService.route(request, function(response, status) {
                    if (status == google.maps.DirectionsStatus.OK){
                        mapper.directionsDisplay.setDirections(response);

                        var bounds = new google.maps.LatLngBounds();
                        var route = response.routes[0];
                        mapper.startLocation = new Object();
                        mapper.endLocation = new Object();

                        // For each route, display summary information.
                        var path = response.routes[0].overview_path;
                        var legs = response.routes[0].legs;
                        for (i=0;i<legs.length;i++) {
                            if (i == 0) {
                                mapper.startLocation.latlng = legs[i].start_location;
                                mapper.startLocation.address = legs[i].start_address;
                                // marker = google.maps.Marker({map:map,position: startLocation.latlng});
                                mapper.marker = mapper.createMarker(legs[i].start_location,"start",legs[i].start_address,"green");
                            }
                            mapper.endLocation.latlng = legs[i].end_location;
                            mapper.endLocation.address = legs[i].end_address;
                            var steps = legs[i].steps;
                            for (j=0;j<steps.length;j++) {
                                var nextSegment = steps[j].path;
                                for (k=0;k<nextSegment.length;k++) {
                                    mapper.polyline.getPath().push(nextSegment[k]);
                                    bounds.extend(nextSegment[k]);
                                }
                            }
                        }

                        mapper.polyline.setMap(mapper.map);
                        mapper.map.fitBounds(bounds);
                        // createMarker(endLocation.latlng,"end",endLocation.address,"red");
                        mapper.map.setZoom(18);
                        mapper.startAnimation();
                    }
                });
            },

            step: 50,
            tick: 150,
            eol: null,
            k: 0,
            stepnum: 0,
            speed: "",
            lastVertex: 1,
            //N
            images_N: {
                url: "/images/spotjunky/car_N.png",
                size: new google.maps.Size(100, 100),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(17.5, 32),
                scaledSize: new google.maps.Size(35, 64)
            },
            //NNE
            images_NNE: {
                url: "/images/spotjunky/car_NNE.png",
                size: new google.maps.Size(100, 100),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(35, 35.5),
                scaledSize: new google.maps.Size(70, 71)
            },
            //NE
            images_NE: {
                url: "/images/spotjunky/car_NE.png",
                size: new google.maps.Size(100, 100),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(30, 30.5),
                scaledSize: new google.maps.Size(70, 71)
            },
            //E
            images_E: {
                url: "/images/spotjunky/car_E.png",
                size: new google.maps.Size(100, 100),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(32, 17.5),
                scaledSize: new google.maps.Size(64, 35)
            },
            //SEE
            images_SEE: {
                url: "/images/spotjunky/car_SEE.png",
                size: new google.maps.Size(100, 100),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(36.5, 28.5),
                scaledSize: new google.maps.Size(73, 57)
            },
            //SE
            images_SE: {
                url: "/images/spotjunky/car_SE.png",
                size: new google.maps.Size(100, 100),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(35, 35.5),
                scaledSize: new google.maps.Size(70, 71)
            },
            //SSE
            images_SSE: {
                url: "/images/spotjunky/car_SSE.png",
                size: new google.maps.Size(100, 100),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(24.5, 35.5),
                scaledSize: new google.maps.Size(49, 71)
            },
            //NEE
            images_NEE: {
                url: "/images/spotjunky/car_NEE.png",
                size: new google.maps.Size(100, 100),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(35, 35.5),
                scaledSize: new google.maps.Size(70, 71)
            },
            //S
            images_S: {
                url: "/images/spotjunky/car_S.png",
                size: new google.maps.Size(100, 100),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(17.5, 32),
                scaledSize: new google.maps.Size(35, 64)
            },
            //SSW
            images_SSW: {
                url: "/images/spotjunky/car_SSW.png",
                size: new google.maps.Size(100, 100),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(24.5, 35.5),
                scaledSize: new google.maps.Size(49, 71)
            },
            //SW
            images_SW: {
                url: "/images/spotjunky/car_SW.png",
                size: new google.maps.Size(100, 100),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(35, 35.5),
                scaledSize: new google.maps.Size(70, 71)
            },
            //SWW
            images_SWW: {
                url: "/images/spotjunky/car_SWW.png",
                size: new google.maps.Size(100, 100),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(36.5, 28.5),
                scaledSize: new google.maps.Size(73, 57)
            },
            //W
            images_W: {
                url: "/images/spotjunky/car_W.png",
                size: new google.maps.Size(100, 100),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(32, 17.5),
                scaledSize: new google.maps.Size(64, 35)
            },
            //NWW
            images_NWW: {
                url: "/images/spotjunky/car_NWW.png",
                size: new google.maps.Size(100, 100),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(36.5, 28.5),
                scaledSize: new google.maps.Size(73, 57)
            },
            //NW
            images_NW: {
                url: "/images/spotjunky/car_NW.png",
                size: new google.maps.Size(100, 100),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(35, 35.5),
                scaledSize: new google.maps.Size(70, 71)
            },
            //NNW
            images_NNW: {
                url: "/images/spotjunky/car_NNW.png",
                size: new google.maps.Size(100, 100),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(26.5, 36),
                scaledSize: new google.maps.Size(53, 72)
            },


            updatePoly: function(d) {
                // Spawn a new polyline every 20 vertices, because updating a 100-vertex poly is too slow
                if (mapper.poly2.getPath().getLength() > 20) {
                    mapper.poly2=new google.maps.Polyline([mapper.polyline.getPath().getAt(mapper.lastVertex-1)]);
                    // map.addOverlay(poly2)
                }

                if (mapper.polyline.GetIndexAtDistance(d) < mapper.lastVertex+2) {
                    if (mapper.poly2.getPath().getLength()>1) {
                        mapper.poly2.getPath().removeAt(mapper.poly2.getPath().getLength()-1)
                    }
                    mapper.poly2.getPath().insertAt(mapper.poly2.getPath().getLength(),mapper.polyline.GetPointAtDistance(d));
                } else {
                    mapper.poly2.getPath().insertAt(mapper.poly2.getPath().getLength(), mapper.endLocation.latlng);
                }
            },
            compareHeading: function(heading){
                if (mapper.currentHeading != heading){
                    mapper.currentHeading = heading;
                    return false;
                }
                return true;
            },
            animate: function(d) {

                if (d>mapper.eol) {
                    mapper.map.panTo(mapper.endLocation.latlng);
                    mapper.marker.setPosition(mapper.endLocation.latlng);
                    return;
                }
                var p = mapper.polyline.GetPointAtDistance(d);
                var heading = google.maps.geometry.spherical.computeHeading(mapper.trackArray[mapper.trackArray.length-1], p);

                var msg = "";


                if (heading>-11 && heading <=11){
                    //N
                    if (!mapper.compareHeading("N")){
                        mapper.marker.setIcon(mapper.images_N);
                    }
                } else if (heading>11 && heading <=33){
                    //NNE
                    if (!mapper.compareHeading("NNE")) {
                        mapper.marker.setIcon(mapper.images_NNE);
                    }
                } else if (heading>33 && heading <=56){
                    //NE
                    if (!mapper.compareHeading("NE")) {
                        mapper.marker.setIcon(mapper.images_NE);
                    }
                } else if (heading>56 && heading <=79){
                    //NEE
                    if (!mapper.compareHeading("NEE")) {
                        mapper.marker.setIcon(mapper.images_NEE);
                    }
                } else if (heading>79 && heading <=101){
                    //E
                    if (!mapper.compareHeading("E")) {
                        mapper.marker.setIcon(mapper.images_E);
                    }
                } else if (heading>101 && heading <=123){
                    //SEE
                    if (!mapper.compareHeading("SEE")) {
                        mapper.marker.setIcon(mapper.images_SEE);
                    }
                } else if (heading>123 && heading <=146){
                    //SE
                    if (!mapper.compareHeading("SE")) {
                        mapper.marker.setIcon(mapper.images_SE);
                    }
                } else if (heading>146 && heading <=169){
                    //SSE
                    if (!mapper.compareHeading("SSE")) {
                        mapper.marker.setIcon(mapper.images_SSE);
                    }
                } else if ((heading>169 && heading <=180)||(heading <= -169 && heading >= -180)){
                    //S
                    if (!mapper.compareHeading("S")) {
                        mapper.marker.setIcon(mapper.images_S);
                    }
                } else if (heading > -169 && heading <= -146){
                    //SSW
                    if (!mapper.compareHeading("SSW")) {
                        mapper.marker.setIcon(mapper.images_SSW);
                    }
                } else if (heading> -146 && heading <= -123){
                    //SW
                    if (!mapper.compareHeading("SW")) {
                        mapper.marker.setIcon(mapper.images_SW);
                    }
                } else if (heading> -123 && heading <= -101){
                    //SWW
                    if (!mapper.compareHeading("SWW")) {
                        mapper.marker.setIcon(mapper.images_SWW);
                    }
                } else if (heading> -101 && heading <= -79){
                    //W
                    if (!mapper.compareHeading("W")) {
                        mapper.marker.setIcon(mapper.images_W);
                    }
                } else if (heading> -79 && heading <= -56){
                    //NWW
                    if (!mapper.compareHeading("NWW")) {
                        mapper.marker.setIcon(mapper.images_NWW);
                    }
                } else if (heading> -56 && heading <= -33){
                    //NW
                    if (!mapper.compareHeading("NW")) {
                        mapper.marker.setIcon(mapper.images_NW);
                    }
                } else if (heading> -33 && heading <= -11){
                    //NNW
                    if (!mapper.compareHeading("NNW")) {
                        mapper.marker.setIcon(mapper.images_NNW);
                    }
                }


                mapper.trackArray.push(p);
                // console.log(p);
                mapper.map.panTo(p);
                mapper.marker.setPosition(p);
                mapper.updatePoly(d);
                mapper.timerHandle = setTimeout("mapper.animate("+(d+mapper.step)+")", mapper.tick);

            },

            startAnimation: function() {
                mapper.eol=google.maps.geometry.spherical.computeLength(mapper.polyline.getPath());
                mapper.map.setCenter(mapper.polyline.getPath().getAt(0));
                mapper.trackArray.push(mapper.polyline.getPath().getAt(0));
                // map.addOverlay(new google.maps.Marker(polyline.getAt(0),G_START_ICON));
                // map.addOverlay(new GMarker(polyline.getVertex(polyline.getVertexCount()-1),G_END_ICON));
                // marker = new google.maps.Marker({location:polyline.getPath().getAt(0)} /* ,{icon:car} */);
                // map.addOverlay(marker);
                mapper.poly2 = new google.maps.Polyline({path: [mapper.polyline.getPath().getAt(0)], strokeColor:"#0000FF", strokeWeight:10});
                // map.addOverlay(poly2);
                setTimeout("mapper.animate(50)",2000);  // Allow time for the initial map display
            },

        };



        $(document).ready(function(){

            
            //Put jQuery related code in here
            google.maps.event.addDomListener(window, 'load', mapper.initialize);
            google.maps.LatLng.prototype.latRadians = function() {
                return this.lat() * Math.PI/180;
            };

            google.maps.LatLng.prototype.lngRadians = function() {
                return this.lng() * Math.PI/180;
            };


            // === A method which returns a GLatLng of a point a given distance along the path ===
            // === Returns null if the path is shorter than the specified distance ===
            google.maps.Polyline.prototype.GetPointAtDistance = function(metres) {
                // some awkward special cases
                if (metres == 0) return this.getPath().getAt(0);
                if (metres < 0) return null;

                var _path = this.getPath().getLength();
                if (this.getPath().getLength() < 2) return null;
                var dist=0;
                var olddist=0;
                for (var i=1; (i < this.getPath().getLength() && dist < metres); i++) {
                    olddist = dist;
                    dist += google.maps.geometry.spherical.computeDistanceBetween(this.getPath().getAt(i),this.getPath().getAt(i-1));
                }
                if (dist < metres) {
                    return null;
                }
                var p1= this.getPath().getAt(i-2);
                var p2= this.getPath().getAt(i-1);
                var m = (metres-olddist)/(dist-olddist);
                return new google.maps.LatLng( p1.lat() + (p2.lat()-p1.lat())*m, p1.lng() + (p2.lng()-p1.lng())*m);
            };

            // === A method which returns the Vertex number at a given distance along the path ===
            // === Returns null if the path is shorter than the specified distance ===
            google.maps.Polyline.prototype.GetIndexAtDistance = function(metres) {
                // some awkward special cases
                if (metres == 0) return this.getPath().getAt(0);
                if (metres < 0) return null;
                var dist=0;
                var olddist=0;
                for (var i=1; (i < this.getPath().getLength() && dist < metres); i++) {
                    olddist = dist;
                    dist += google.maps.geometry.spherical.computeDistanceBetween(this.getPath().getAt(i),this.getPath().getAt(i-1));
                }
                if (dist < metres) {return null;}
                return i;
            };

            //Put jQuery related code in here
            $('.slider-change').popover({
                html: true,
                content: function() {
                    return $('#popover-content').html();
                }
            });

            $("#horizontal").click(function() {
              // Instead of directly editing CSS, toggle a class
              // console.log("working");
              $(this).addClass("btn-primary");
              $("#vertical").removeClass("btn-primary");
              $("#vertical").addClass("btn-default");
              $("#slant").removeClass("btn-primary");
              $("#slant").addClass("btn-default");
            });

            $("#vertical").click(function() {
              // Instead of directly editing CSS, toggle a class
              // console.log("working");
              $("#horizontal").removeClass("btn-primary");
              $("#horizontal").addClass("btn-default");
              $("#slant").removeClass("btn-primary");
              $("#slant").addClass("btn-default");
              $(this).addClass("btn-primary");
            });

            $("#slant").click(function() {
              // Instead of directly editing CSS, toggle a class
              // console.log("working");
              $("#horizontal").removeClass("btn-primary");
              $("#horizontal").addClass("btn-default");
              $("#vertical").removeClass("btn-primary");
              $("#vertical").addClass("btn-default");
              $(this).addClass("btn-primary");
            });

        });




    </script>

    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        #map {
            height: 90%;
            width: 100%;
            margin-left:auto;
            margin-right:auto;
            display:block;
            font-size: 6px;
        }
        .clicked {
            background-color: white;
        }

    </style>
</head>

<body onload="mapper.initMap()">

    <div class="topbuttons" align="left">
        <button type="button" id="horizontal" class="btn btn-md btn-primary" onclick="controller.plotter.horizontal()">Horizontal</button>
        <button type="button" id="vertical" class="btn btn-md btn-default" onclick="controller.plotter.vertical()">Vertical</button>
        <button type="button" id="slant" class="btn btn-md btn-default" onclick="controller.plotter.slant()">Slant</button>    
        <button type="button" id="setParallel" class="btn btn-md btn-default" onclick="controller.plotter.parallel()">Parallel</button>
        <button type="button" class="btn btn-md btn-danger" onclick="controller.plotter.clearAll()">Clear All</button>

    </div>


    <div align="center">
        <button type="button" class="btn btn-md slider-change" id="popover" data-toggle="popover" data-title="Modify Parking Space" data-place="bottom">Modify Parking Spots
        </button>
    </div>

    <div id="popover-content" class="hidden">
        <b>Modify/Enter the spacing between parking spots</b>
        <input type="number" id="inputbox" min="0" onchange="controller.plotter.redrawLastLine()" max="50" step="0.25" value="8" style="width:100px"/>
    </div>

    <div id="tools">
        start:
        <input type="text" name="start" id="start" value="union square, NY" onchange="mapper.clickManager.clear()"/>
        end:
        <input type="text" name="end" id="end" value="times square, NY" onchange="mapper.clickManager.clear()"/>
        <button class="btn btn-default btn-sm" onclick="mapper.clickManager.clear();mapper.calcRoute();">Route</button>
        <button class="btn btn-info btn-sm" onclick="mapper.calcRoute();">Reroute</button>
        <button class="btn btn-info btn-sm" onclick="mapper.clickManager.clear()">Clear</button>
    </div>

    <div id="map_canvas" style="width:100%;height:100%;"></div>




</body>