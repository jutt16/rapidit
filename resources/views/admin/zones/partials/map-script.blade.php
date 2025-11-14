<script>
    function initZoneManager() {
        const config = window.zoneMapConfig || {};
        const mapElement = document.getElementById('zonesMap');

        if (!mapElement || typeof google === 'undefined') {
            return;
        }

        const fallbackCenter = { lat: 20.5937, lng: 78.9629, zoom: 5 };
        const center = config.center || fallbackCenter;

        const map = new google.maps.Map(mapElement, {
            zoom: center.zoom || 11,
            center,
            mapTypeId: 'roadmap',
        });

        const getColor = () => document.getElementById('zone-color')?.value || '#FF7043';

        const basePolygonOptions = {
            editable: true,
            draggable: false,
            strokeWeight: 2,
            strokeOpacity: 0.9,
            fillOpacity: 0.25,
            strokeColor: getColor(),
            fillColor: getColor(),
        };

        const drawingManager = new google.maps.drawing.DrawingManager({
            drawingMode: google.maps.drawing.OverlayType.POLYGON,
            drawingControl: true,
            drawingControlOptions: {
                position: google.maps.ControlPosition.TOP_CENTER,
                drawingModes: ['polygon'],
            },
            polygonOptions: basePolygonOptions,
        });

        drawingManager.setMap(map);

        let activePolygon = null;

        const coordinatesInput = document.getElementById('zone-coordinates');
        const previewElement = document.getElementById('zone-coordinates-preview');
        const pointCounter = document.getElementById('zone-point-count');

        function updateCoordinateFields(pathArray) {
            const coords = pathArray.map((latLng) => ({
                lat: Number(latLng.lat().toFixed(6)),
                lng: Number(latLng.lng().toFixed(6)),
            }));

            if (coordinatesInput) {
                coordinatesInput.value = JSON.stringify(coords);
            }
            if (previewElement) {
                previewElement.textContent = JSON.stringify(coords, null, 2);
            }
            if (pointCounter) {
                pointCounter.textContent = coords.length;
            }
        }

        function attachPathListeners(path) {
            google.maps.event.addListener(path, 'set_at', () => updateCoordinateFields(path.getArray()));
            google.maps.event.addListener(path, 'insert_at', () => updateCoordinateFields(path.getArray()));
            google.maps.event.addListener(path, 'remove_at', () => updateCoordinateFields(path.getArray()));
        }

        function focusPolygon(polygon) {
            const bounds = new google.maps.LatLngBounds();
            polygon.getPath().forEach((latLng) => bounds.extend(latLng));
            if (!bounds.isEmpty()) {
                map.fitBounds(bounds);
            }
        }

        function setPolygonFromCoords(coords = []) {
            if (activePolygon) {
                activePolygon.setMap(null);
                activePolygon = null;
            }

            if (!coords.length) {
                updateCoordinateFields([]);
                return;
            }

            activePolygon = new google.maps.Polygon({
                ...basePolygonOptions,
                paths: coords,
                strokeColor: getColor(),
                fillColor: getColor(),
            });

            activePolygon.setMap(map);
            focusPolygon(activePolygon);
            attachPathListeners(activePolygon.getPath());
            updateCoordinateFields(activePolygon.getPath().getArray());
        }

        google.maps.event.addListener(drawingManager, 'overlaycomplete', (event) => {
            if (event.type !== google.maps.drawing.OverlayType.POLYGON) {
                return;
            }

            const path = event.overlay.getPath().getArray().map((latLng) => ({
                lat: latLng.lat(),
                lng: latLng.lng(),
            }));

            event.overlay.setMap(null);
            setPolygonFromCoords(path);
            drawingManager.setDrawingMode(null);
        });

        const resetButton = document.getElementById('reset-zone');
        resetButton?.addEventListener('click', (e) => {
            e.preventDefault();
            if (activePolygon) {
                activePolygon.setMap(null);
                activePolygon = null;
            }
            updateCoordinateFields([]);
        });

        const colorInput = document.getElementById('zone-color');
        colorInput?.addEventListener('change', () => {
            const color = getColor();
            basePolygonOptions.strokeColor = color;
            basePolygonOptions.fillColor = color;
            if (activePolygon) {
                activePolygon.setOptions({
                    strokeColor: color,
                    fillColor: color,
                });
            }
            drawingManager.setOptions({
                polygonOptions: {
                    ...basePolygonOptions,
                    strokeColor: color,
                    fillColor: color,
                },
            });
        });

        const searchInput = document.getElementById('zone-search');
        if (searchInput) {
            const autocomplete = new google.maps.places.Autocomplete(searchInput);
            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace();
                if (!place.geometry) {
                    return;
                }

                if (place.geometry.viewport) {
                    map.fitBounds(place.geometry.viewport);
                } else if (place.geometry.location) {
                    map.setCenter(place.geometry.location);
                    map.setZoom(14);
                }
            });
        }

        const initialPoints = Array.isArray(config.initialCoordinates)
            ? config.initialCoordinates
            : [];

        if (initialPoints.length >= 3) {
            setPolygonFromCoords(initialPoints);
        } else {
            updateCoordinateFields([]);
        }
    }
</script>

