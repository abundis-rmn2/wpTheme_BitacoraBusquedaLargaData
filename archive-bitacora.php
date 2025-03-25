<?php
/**
 * The template for displaying Archive Bitacora pages
 */

get_header(); 

?>
<link rel="stylesheet" href="https://unpkg.com/maplibre-gl@3.3.0/dist/maplibre-gl.css">
<script src="https://unpkg.com/maplibre-gl@3.3.0/dist/maplibre-gl.js"></script>

	<div class="content-wide" role="main">
		<!-- Zona Taxonomy Filter -->
		<div class="taxonomy-filter">
			<h3>Filter by Zona:</h3>
			<?php 
			$zonas = get_terms(array(
				'taxonomy' => 'zona',
				'hide_empty' => true,
			));
			
			if (!empty($zonas) && !is_wp_error($zonas)) : ?>
				<select id="zona-filter" class="taxonomy-filter" data-taxonomy="zona">
					<option value="">All Zonas</option>
					<?php foreach ($zonas as $zona) : ?>
						<option value="<?php echo $zona->slug; ?>"><?php echo $zona->name; ?></option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>
		</div>
		
		<!-- Map Display -->
		<div id="map" style="height: 600px; width: 100%; margin-bottom: 30px;"></div>
		
		<?php 
		$bitacora_posts = [];
		
		if ( have_posts() ) : 
			// Collect post data for map
			while ( have_posts() ) : the_post();
				$lat = get_post_meta(get_the_ID(), 'latitud', true);
				$lng = get_post_meta(get_the_ID(), 'longitud', true);
				$zona_terms = wp_get_post_terms(get_the_ID(), 'zona', ['fields' => 'slugs']);
				$zona_slugs = !empty($zona_terms) ? implode(' ', $zona_terms) : '';
				
				// Only add to map data if coordinates exist
				if ($lat && $lng) {
					$bitacora_posts[] = [
						'title' => get_the_title(),
						'excerpt' => get_the_excerpt(),
						'permalink' => get_permalink(),
						'lat' => $lat,
						'lng' => $lng,
						'zona' => $zona_slugs
					];
				}
			endwhile;
			
			// Reset post data to start loop again for display
			rewind_posts();
			
			// Display posts
			while ( have_posts() ) : the_post(); 
				$zona_terms = wp_get_post_terms(get_the_ID(), 'zona', ['fields' => 'slugs']);
				$zona_slugs = !empty($zona_terms) ? implode(' ', $zona_terms) : '';
				?>
				<hr />
				<article class="bitacora-entry" data-zona="<?php echo $zona_slugs; ?>">
					<div class="bitacora-content">
						<?php if (has_post_thumbnail()) : ?>
						<div class="bitacora-image">
							<a href="<?php the_permalink(); ?>">
								<?php the_post_thumbnail('medium'); ?>
							</a>
						</div>
						<?php endif; ?>
						
						<div class="bitacora-text">
							<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
							<div class="bitacora-incipit">
								<?php the_excerpt(); ?>
							</div>
							<p class="quiet">Posted by <?php print get_the_author_link() ?> in <?php print get_the_category_list( ', ' ) ?>.</p>
							<?php if (!empty($zona_terms)) : ?>
							<p class="taxonomies">
								<strong>Zona:</strong> 
								<?php foreach (wp_get_post_terms(get_the_ID(), 'zona') as $term) : ?>
									<span class="taxonomy"><?php echo $term->name; ?></span>
								<?php endforeach; ?>
							</p>
							<?php endif; ?>
						</div>
					</div>
				</article>
				<?php
			endwhile;

		else :

			print "<p>There are currently no posts to list here.</p>";

		endif;
		?>

	</div><!-- #primary -->

	<?php paginate(); ?>

<style>
.taxonomies .taxonomy {
    display: inline-block;
    background: #f0f0f0;
    padding: 2px 5px;
    margin: 2px;
    border-radius: 3px;
}
</style>

<script>
	document.addEventListener('DOMContentLoaded', () => {
		const apiKey = 'DBUe1dg9BjoCDDiQnet5';  // Replace with your MapTiler key
		let markers = [];

		// Initialize Map
		const map = new maplibregl.Map({
			container: 'map',
			style: `https://api.maptiler.com/maps/streets-v2/style.json?key=${apiKey}`,
			center: [-103.3478, 20.6768],  // Default: Guadalajara
			zoom: 12
		});

		// JavaScript filtering for zona
		document.querySelector('#zona-filter').addEventListener('change', function() {
			const selectedZona = this.value;
			
			// Filter articles
			document.querySelectorAll('.bitacora-entry').forEach(entry => {
				const zonaAttribute = entry.getAttribute('data-zona') || '';
				
				if (!selectedZona || (zonaAttribute && zonaAttribute.includes(selectedZona))) {
					entry.style.display = 'block';
				} else {
					entry.style.display = 'none';
				}
			});
			
			// Update map markers
			updateMapMarkers(selectedZona);
		});

		// Add markers to map
		map.on('load', () => {
			updateMapMarkers('');
		});

		function updateMapMarkers(selectedZona) {
			// Clear existing markers
			markers.forEach(marker => marker.remove());
			markers = [];
			
			// Add markers for each bitacora post
			const bitacoraPosts = <?php echo json_encode($bitacora_posts); ?>;
			
			// If we have posts with coordinates, set map bounds to include them all
			if (bitacoraPosts.length > 0) {
				const bounds = new maplibregl.LngLatBounds();
				let hasVisibleMarkers = false;
				
				bitacoraPosts.forEach(post => {
					// Check if post matches the zona filter
					const postMatches = !selectedZona || (post.zona && post.zona.includes(selectedZona));
					
					if (postMatches && post.lat && post.lng) {
						const lat = parseFloat(post.lat);
						const lng = parseFloat(post.lng);
						if (!isNaN(lat) && !isNaN(lng)) {
							// Add marker
							const marker = new maplibregl.Marker()
								.setLngLat([lng, lat])
								.setPopup(new maplibregl.Popup().setHTML(`
									<h3><a href="${post.permalink}">${post.title}</a></h3>
									<p>${post.excerpt}</p>
								`))
								.addTo(map);
								
							markers.push(marker);
							
							// Extend map bounds
							bounds.extend([lng, lat]);
							hasVisibleMarkers = true;
						}
					}
				});
				
				// Fit map to bounds if we have any valid coordinates
				if (hasVisibleMarkers && !bounds.isEmpty()) {
					map.fitBounds(bounds, {
						padding: 50,
						maxZoom: 15
					});
				}
			}
		}
	});
</script>

<?php

get_footer();

?>