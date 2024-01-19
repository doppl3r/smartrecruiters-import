// Global JS code
jQuery(document).ready(function($) {
  function loadMap() {
    // Only load map if the ID exists in the DOM
    if ($('#idx-sr-map').length > 0) {
      // Add Leaflet map
      window.map = L.map('idx-sr-map', { dragging: !L.Browser.mobile, tap: false, zoomControl: false }).setView([33.5641086, -112.1946049], 10);
      window.map.scrollWheelZoom.disable();
      L.tileLayer('https://cartodb-basemaps-{s}.global.ssl.fastly.net/light_all/{z}/{x}/{y}.png', { maxZoom: 18 }).addTo(window.map);
  
      // Icon options
      var icon = L.icon({ iconUrl: path + '/assets/public/img/svg/icon-pin.svg', iconSize: [30, 40], iconAnchor: [15, 40], popupAnchor: [0, -40] });
  
      // Loop through each job and create a pin and popup
      var group = new L.featureGroup([]);
      jobs.forEach(function(job){
        // Populate group with single job
        var marker = L.marker(job['geo'], { icon: icon }).addTo(group);
        marker.bindPopup(
          '<div>' +
            '<a href="' + job['directions'] + '" target="_blank">Get Directions</a>' +
          '</div>'
        );
      });
  
      // zoom settings
      group.addTo(window.map);
      window.map.fitBounds(group.getBounds(), { padding: [4, 4], maxZoom: 16 });
      L.control.zoom({ position:'bottomright' }).addTo(window.map);
  
      // Update map resize when scrolling is triggered
      $(document).on('scroll', function(){ window.map.invalidateSize(); });
    }
  }

  // Load map on runtime
  loadMap();
});