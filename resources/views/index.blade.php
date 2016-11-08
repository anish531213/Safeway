<!DOCTYPE html>
<html data-ng-app="">
	<head>
		<title>MapIntegration</title>
		<link rel="stylesheet" type="text/css" href="project.css">
		<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

	<!-- Optional theme -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

	<script   src="https://code.jquery.com/jquery-3.1.1.js"   integrity="sha256-16cdPddA6VdVInumRGo6IbivbERE8p7CQR3HzTBuELA="   crossorigin="anonymous"></script>


	<!-- Latest compiled and minified JavaScript -->
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
	</head>

	<body >
	<style>
	#custom-search-input{
    padding: 3px;
    border: solid 1px #E4E4E4;
    border-radius: 6px;
    background-color: #fff;
}

#custom-search-input input{
    border: 0;
    box-shadow: none;
}

#custom-search-input button{
    margin: 2px 0 0 0;
    background: none;
    box-shadow: none;
    border: 0;
    color: #666666;
    padding: 0 8px 0 10px;
    border-left: solid 1px #ccc;
}

#custom-search-input button:hover{
    border: 0;
    box-shadow: none;
    border-left: solid 1px #ccc;
}

#custom-search-input .glyphicon-search{
    font-size: 18px;
}

body{

    background-image: url("background.jpg");
    min-height: 100%;
    min-width: 100%;
    position: fixed;
    width : 100%;
    height: auto;
    top:0;
    left:0;
}

.container_button{
  border: 2px solid #28ECC3;
  width: 200px;
  text-align: center;
  height: 48px;
  padding: 2px 4px;
  border-radius: 100px;
  color: black;
  margin: 0 auto;
  -webkit-transition: all .3s;
  transition: all .3s;
  position: relative;
  z-index: 3;
  line-height: 42px;
  text-transform: uppercase;
  font-weight: 700;
  font-size: 14px;
  background: #41EFB6;
}
</style>
	<br />
	<br />
	<br />
		<div class="container">
			<div class="row">
        		<div class="col-md-12">
    				<h3 style="font-size: 35px; color: white; -webkit-text-stroke: 1.5px black;">Starting Point</h3>
            		<div id="custom-search-input">
                		<div class="input-group col-md-6">
                			<input id="pac-input" class="form-control input-md" type="text" placeholder="Enter a location">
                    		<!--<input type="text" class="form-control input-md" placeholder="Street Name" />-->
                    		<span class="input-group-btn">
                        		<button class="btn btn-info btn-lg" type="button"></button>
                    		</span>
                		</div>
            		</div>
        		</div>
			</div>
			<br />

			<div class="row">
        		<div class="col-md-12">
    				<h3 style="font-size: 35px; color: white; -webkit-text-stroke: 1.5px black;">Destination</h3>
            		<div id="custom-search-input">
                		<div class="input-group col-md-6">
                		<input id="pac-input_d" class="form-control input-md" type="text" placeholder="Enter a location">
                    		<!--<input type="text" class="form-control input-md" placeholder="Street Name" />-->
                    		<span class="input-group-btn">
                        		<button class="btn btn-info btn-lg" type="button"></button>
                    		</span>
                		</div>
            		</div>
        		</div>
			</div>

			<div class = "map">
			<!--This is where the actual map will be located-->


			</div>
			<br />
			<br />
			<a href="{{ url('walk') }}" onclick="callsavedata()" id="walking" class="btn btn-lg container_button"></span> Walking</a>
      <a href="{{ url('walk')  }}" onclick="callsavedata()" id="metro" class="btn btn-lg container_button"></span> Car</a>
      <a href="{{ url('walk') }}" onclick="callsavedata()" id="car" class="btn btn-lg container_button"></span> Metro</a>

		</div>

    <div id="map" style="display:none;"></div>

    <script>
      // This example requires the Places library. Include the libraries=places
      // parameter when you first load the API. For example:
      // <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places">

      function initMap() {
        var map = new google.maps.Map(document.getElementById('map'), {
          center: {lat: -33.8688, lng: 151.2195},
          zoom: 13
        });
        var input = /** @type {!HTMLInputElement} */(
            document.getElementById('pac-input'));
        var input1 = /** @type {!HTMLInputElement} */(
            document.getElementById('pac-input_d'));


        var autocomplete = new google.maps.places.Autocomplete(input);
        var autocomplete1 = new google.maps.places.Autocomplete(input1);
        //autocomplete.bindTo('bounds', map);

        var infowindow = new google.maps.InfoWindow();
        var marker = new google.maps.Marker({
          map: map,
          anchorPoint: new google.maps.Point(0, -29)
        });

        autocomplete.addListener('place_changed', function() {
          infowindow.close();
          marker.setVisible(false);
          var place = autocomplete.getPlace();
          if (!place.geometry) {
            window.alert("Autocomplete's returned place contains no geometry");
            return;
          }

          // If the place has a geometry, then present it on a map.
          if (place.geometry.viewport) {
            map.fitBounds(place.geometry.viewport);
          } else {
            map.setCenter(place.geometry.location);
            map.setZoom(17);  // Why 17? Because it looks good.
          }
          marker.setIcon(/** @type {google.maps.Icon} */({
            url: place.icon,
            size: new google.maps.Size(71, 71),
            origin: new google.maps.Point(0, 0),
            anchor: new google.maps.Point(17, 34),
            scaledSize: new google.maps.Size(35, 35)
          }));
          marker.setPosition(place.geometry.location);
          marker.setVisible(true);

          var address = '';
          if (place.address_components) {
            address = [
              (place.address_components[0] && place.address_components[0].short_name || ''),
              (place.address_components[1] && place.address_components[1].short_name || ''),
              (place.address_components[2] && place.address_components[2].short_name || '')
            ].join(' ');
          }

          infowindow.setContent('<div><strong>' + place.name + '</strong><br>' + address);
          infowindow.open(map, marker);
        });

        // Sets a listener on a radio button to change the filter type on Places
        // Autocomplete.
        function setupClickListener(id, types) {
          var radioButton = document.getElementById(id);
          radioButton.addEventListener('click', function() {
            autocomplete.setTypes(types);
          });
        }

        setupClickListener('changetype-all', []);
        setupClickListener('changetype-address', ['address']);
        setupClickListener('changetype-establishment', ['establishment']);
        setupClickListener('changetype-geocode', ['geocode']);
      }

      
  function callsavedata() {
    var start = $("#pac-input").val();
    
    var end = $("#pac-input_d").val();

    // console.log(start, end);

    saveData(start, end);
  }

  function saveData(user, pass) {
     var account = {
       User: user,
       Pass: pass
     };
     //converts to JSON string the Object
     account = JSON.stringify(account);
     //creates a base-64 encoded ASCII string
     account = btoa(account);
     //save the encoded accout to web storage
     localStorage.setItem('_account', account);
  }

  </script>
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCpiElXvn4djU-rmsGQ6aXuK4qqEzChTs0&libraries=places&callback=initMap"
        async defer></script>
  </body>
</html>

	</body>

</html>