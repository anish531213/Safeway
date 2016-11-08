<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Bachat</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">

        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

        <script   src="https://code.jquery.com/jquery-3.1.1.js"   integrity="sha256-16cdPddA6VdVInumRGo6IbivbERE8p7CQR3HzTBuELA="   crossorigin="anonymous"></script>

        <script async defer
        src="https://maps.googleapis.com/maps/api/js?libraries=geometry&key=AIzaSyDdMKNKRCFo5UVgFV30JivZ5SUf8U-pqvk&callback=initMap">
        </script>

    <script type="text/javascript">
        var xxresult;
        var controller = {
            changenum: 0,

            previous_line: null,

            map: null,

            result: null,

            nextOn: function() {
                this.previous_line.setMap(null);

                if (this.changenum == 2) {
                    this.changenum = 0;
                } else {
                    this.changenum += 1;
                }
                controller.findDirection(start, end, "driving");

                changeParameter();

            },

            findDirection: function(origin, destination, way) { 
                var direction_url = 'http://localhost:8000/directions/'+origin+'/'+destination+'/'+way;

                $.get(direction_url, function(data) {
                    var map = controller.map;
                    // All your work here
                    var routes = data.routes;

                    var first = routes[controller.changenum]['overview_polyline']['points'];

                    var decode_url = 'http://localhost:8000/decode/'+first;

                    var decodedPath = controller.decodePolyline(first);

                    constructPolyline(decodedPath);

                    function constructPolyline(path)  {
                    
                        var flightPath = new google.maps.Polyline({
                            path: decodedPath,
                            geodesic: true,
                            strokeColor: '#0000ff',
                            strokeOpacity: 1.0,
                            strokeWeight: 2
                          });

                        var marker = new google.maps.Marker({
                            position: decodedPath[0],
                            title:"start"
                        });

                        // To add the marker to the map, call setMap();
                        marker.setMap(map);

                        var marker2 = new google.maps.Marker({
                            position: decodedPath[decodedPath.length-1],
                            title:"end"
                        });

                        // To add the marker to the map, call setMap();
                        marker2.setMap(map);
                        

                        flightPath.setMap(map);

                        controller.previous_line = flightPath;

                    }

                });
                
            },

            decodePolyline: function(encoded) {
                var decodedPath = google.maps.geometry.encoding.decodePath(encoded);
                return decodedPath;
            }

        }


        function initMap() {
            var current_location = {lat: 38.8984356, lng: -77.0125935};
            var map = new google.maps.Map(document.getElementById('map'), {
              zoom: 12,
              center: current_location
            });

            controller.map = map;
          }

        function changeParameter() {

            var the_val = meter1Function();
            $("#a_meter").val(the_val);
            $("#damage").val(damage_val());

        }
       
        var k = loadData();

        var start = k[0].replace(/[, ]+/g, "").trim();
        // var start = start.replace("  ", "");
        // var start =start.replace(" ", "");
        // var start = start.replace(", ", "+");
 
        var end = k[1].replace(/[, ]+/g, "").trim();
        // var end = end.replace("  ", "");
        // var end = end.replace(" ", "");
        // var end = end.replace(", ", "+");

        console.log(start, end);


        var result = controller.findDirection(start, end, "driving");


        function loadData() {
           var account = localStorage.getItem('_account');
           if (!account) return false;
           localStorage.removeItem('_account');
           //decodes a string data encoded using base-64
           account = atob(account);
           //parses to Object the JSON string
           account = JSON.parse(account);
           
           //do what you need with the Object
           return [account.User, account.Pass];
        }
        </script>

        <!-- Styles -->
        <style>

            #map {
                height: 70%;
                width: 100%;
               }
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: 'Raleway', sans-serif;
                font-weight: 100;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .top-right {
                position: absolute;
                right: 10px;
                top: 18px;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 84px;
            }

            .links > a {
                color: #636b6f;
                padding: 0 25px;
                font-size: 12px;
                font-weight: 600;
                letter-spacing: .1rem;
                text-decoration: none;
                text-transform: uppercase;
            }

            .m-b-md {
                margin-bottom: 30px;
            }
        </style>
    </head>
    <body>
        <div id="map"></div>

        </center><button class="btn btn-primary" id="next_button" type="submit" onclick="controller.nextOn()">Button</button>


        <script type="text/javascript">
            function CrimeScoretoMeterValue(crime_score) {
            if (crime_score >= 0 && crime_score < 25){
                return 15;
            } else if (crime_score >= 25 && crime_score <30){
                return 14;
            } else if (crime_score >= 30 && crime_score <35){
                return 13;
            } else if (crime_score >= 35 && crime_score < 40){
                return 12;
            } else if (crime_score >= 40 && crime_score < 50){
                return 11;
            } else if (crime_score >= 50 && crime_score < 55){
                return 10;
            } else if (crime_score >= 55 && crime_score < 60){
                return 9;
            } else if (crime_score >= 60 && crime_score < 65){
                return 8;
            } else if (crime_score >= 65 && crime_score < 70){
                return 7;
            } else if (crime_score >= 70 && crime_score < 75){
                return 6;
            } else if (crime_score >= 75 && crime_score < 80){
                return 5;
            } else if (crime_score >= 80 && crime_score < 85){
                return 4;
            } else if (crime_score >= 85 && crime_score < 90){
                return 3;
            } else if (crime_score >= 90 && crime_score < 95){
                return 2;
            } else if (crime_score >= 95 && crime_score < 100){
                return 1;
            }else if (crime_score == 100){
                return 0;
            }else{
                alert("Error!");
            }
        }
        

        function meter1Function(){
            var score_1 = Math.floor(Math.random() * 100);
            var score_2 = Math.floor(Math.random() * 100);
            var score_3 = Math.floor(Math.random() * 100);
            var score_4 = Math.floor(Math.random() * 100);
            var score_5 = Math.floor(Math.random() * 100);

            // for the walking path
            // function average_score_walk(score_1,score_2,score_3,score_4,score_5) {
                return CrimeScoretoMeterValue((score_5 + score_4 + score_3 + score_2 + score_1)/5);
            }
        

        // for the car path
        function average_score_car(score_1, score_2, score_3){
            return CrimeScoretoMeterValue((score_1+score_2+score_3)/3);
        }
        //test values for walk
        var the_val = meter1Function();
        //test values for car path, return in same variable the_val
        //var the_val = average_score_car(80, 60, 50);

        //average damage

        var damage_val = function(){
            var damage_1 = Math.floor(Math.random() * 100);
            var damage_2 = Math.floor(Math.random() * 100);
            var damage_3 = Math.floor(Math.random() * 100);
            var damage_4 = Math.floor(Math.random() * 100);
            var damage_5 = Math.floor(Math.random() * 100);
            return ((damage_1+damage_2+damage_3+damage_4+damage_5)/5);
        };


        document.getElementById("a_meter").value = the_val;
  </script>
    <style>
            .styled progress::-webkit-progress-bar {
              background: #EEE;
              box-shadow: 0 2px 3px rgba(0,0,0,0.2) inset;
              border-radius: 3px;
            }

            .styled progress::-webkit-progress-value {
              background-color: #CC0000;
              border-radius: 3px;
            }

            .styled progress::-moz-progress-bar {
              background-color: #CC0000;
              border-radius: 3px;
            }


            .styled meter {
              /* Reset the default appearance */
              -webkit-appearance: none;
              -moz-appearance: none;
              appearance: none;

              width: 40%;
              height: 40px;
              margin-left: 25%;
              margin-top: 3%;
              
              /* For Firefox */
              background: #EEE;
              box-shadow: 0 2px 3px rgba(0,0,0,0.2) inset;
              border-radius: 3px;
            }

            /* WebKit */
            .styled meter::-webkit-meter-bar {
              background: #EEE;
              box-shadow: 0 2px 3px rgba(0,0,0,0.2) inset;
              border-radius: 3px;
            }

            .styled meter::-webkit-meter-optimum-value,
            .styled meter::-webkit-meter-suboptimum-value,
            .styled meter::-webkit-meter-even-less-good-value {
              border-radius: 3px;
            }

            .styled meter::-webkit-meter-optimum-value {
              background: #CC4600;
            }

            .styled meter::-webkit-meter-suboptimum-value {
              background: #FFDB1A;
            }

            .styled meter::-webkit-meter-even-less-good-value  {
              background: #86CC00;
            }


            /* Firefox */
            .styled meter::-moz-meter-bar {
              border-radius: 3px;
            }

            .styled meter:-moz-meter-optimum::-moz-meter-bar {
              background: #CC4600;
            }

            .styled meter:-moz-meter-sub-optimum::-moz-meter-bar {
              background: #FFDB1A;
            }

            .styled meter:-moz-meter-sub-sub-optimum::-moz-meter-bar {
              background: #86CC00;
            }
    </style>
    <p class="styled">
    <meter id="a_meter" min="0" max="15" low="4" high="7" optimum="15" value=""></meter>
    <script>document.getElementById("a_meter").value = the_val; </script>
    Crimes
  </p>
    
    <p class="styled">
    <meter id="damage" min="0" max="100" low="20" high="50" optimum="100" value=""></meter>
    <script>document.getElementById("damage").value = damage_val(); </script>
    Damages
  </p>



    </body>
</html>
