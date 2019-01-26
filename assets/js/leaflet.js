/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.leaflet = {
    attach: function (context, settings) {

      if (drupalSettings.leaflet === null) {
        // Reset map.
        document.getElementById('ajax-map').innerHTML = '<div id="leaflet-map"">No data for the given filter and data type. Try changing values or create more events.</div>';
        return;
      }
      // Reset map.
      document.getElementById('ajax-map').innerHTML = '<div id="leaflet-map" style="height: 40em;"></div>';
      let bounds = [];
      let map = new L.Map(
        'leaflet-map',
        {
          zoom: 9
        }
      );
      L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
        attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
        maxZoom: 18,
        id: 'mapbox.streets',
        accessToken: drupalSettings.leaflet.key,
      }).addTo(map);
      if (drupalSettings.leaflet.type === 'heatmap') {
        let coords = [];
        drupalSettings.leaflet.places.forEach(function (element) {
          coords.push([
            element.gps.latitude,
            element.gps.longitude,
            element.intensity,
          ]);
          bounds.push([element.gps.latitude, element.gps.longitude]);
        });
        L.heatLayer(coords, {
          minOpacity: 0.4,
          radius: 25,
          blur: 10
        }).addTo(map);
      }
      else {
        let markers = L.markerClusterGroup();
        drupalSettings.leaflet.places.forEach(function(element) {
          let popup = L.popup();
          if (element.description === null) {
            popup.setContent('<a href="' + element.url + '"><h1>' + element.title + '</h1></a>');
          }
          else {
            popup.setContent('<a href="' + element.url + '"><h1>' + element.title + '</h1><p>' + element.description + '</p></a>');
          }
          // Add marker for element.
          markers.addLayer(L.marker([element.gps.latitude, element.gps.longitude], {
            title: element.title,
            alt: element.title,
          }).bindPopup(popup).openPopup());
          // Add coordinates to bounding box.
          bounds.push([element.gps.latitude, element.gps.longitude]);
        });
        map.addLayer(markers);
      }
      map.fitBounds(bounds);
    },
  };

})(jQuery, Drupal, drupalSettings);
