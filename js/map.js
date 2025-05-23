document.addEventListener('DOMContentLoaded', () => {
    const apiKey = 'DBUe1dg9BjoCDDiQnet5';  // 🔥 Replace with your MapTiler key


    // 👉 Get lat/lng fields
    const latInput = document.querySelector('#latitud');
    const lngInput = document.querySelector('#longitud');

    // ✅ Use saved values or fallback to defaults
    const savedLat = parseFloat(latInput.value) || 20.6768;  // Default: Guadalajara
    const savedLng = parseFloat(lngInput.value) || -103.3478;

    // 🌍 Initialize Map
    const map = new maplibregl.Map({
        container: 'map',
        style: `https://api.maptiler.com/maps/streets-v2/style.json?key=${apiKey}`,
        center: [savedLng, savedLat],
        zoom: 12
    });

    // 📌 Add Marker at saved or default position
    const marker = new maplibregl.Marker({ draggable: true })
        .setLngLat([savedLng, savedLat])
        .addTo(map);

    // 📌 Marker drag event → Update fields
    marker.on('dragend', () => {
        const lngLat = marker.getLngLat();
        latInput.value = lngLat.lat.toFixed(6);
        lngInput.value = lngLat.lng.toFixed(6);

        // ✅ Trigger MetaBox save
        latInput.dispatchEvent(new Event('change', { bubbles: true }));
        lngInput.dispatchEvent(new Event('change', { bubbles: true }));
    });

    // 📌 Address search with Nominatim
    document.getElementById('geocode-btn').addEventListener('click', () => {
        const query = document.getElementById('address-search').value;
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    const { lat, lon } = data[0];
                    map.flyTo({ center: [lon, lat], zoom: 15 });
                    marker.setLngLat([lon, lat]);

                    // ✅ Update fields and trigger MetaBox save
                    latInput.value = lat;
                    lngInput.value = lon;
                    latInput.dispatchEvent(new Event('change', { bubbles: true }));
                    lngInput.dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    alert('Dirección no encontrada.');
                }
            });
    });
});