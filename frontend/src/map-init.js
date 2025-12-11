const map = new ol.Map({
  target: "parkingMap", // get the target id of a div
  layers: [
    new ol.layer.Tile({
      source: new ol.source.OSM(),
    }),
  ],
  view: new ol.View({
    center: ol.proj.fromLonLat([121.156101 ,14.044943]),
    zoom: 19,
  }),
});


map.on("click", function (event) {
  const coord = ol.proj.toLonLat(event.coordinate);
  const lon = coord[0].toFixed(6);
  const lat = coord[1].toFixed(6);

  console.log("Clicked at:", lon, lat);
});