<?php
/**
 * The template for displaying Archive Fosa pages
 */

get_header(); 

?>
<link rel="stylesheet" href="https://unpkg.com/maplibre-gl@3.3.0/dist/maplibre-gl.css">
<script src="https://unpkg.com/maplibre-gl@3.3.0/dist/maplibre-gl.js"></script>

	<div class="content-wide" role="main">
        <div id="map" style="height: 600px; width: 100%;"></div>
		<?php 
		if ( have_posts() ) : 

			$fosa_posts = [];
			// Start the Loop.
			while ( have_posts() ) : the_post(); 
				$lat = get_post_meta(get_the_ID(), 'latitud', true);
				$lng = get_post_meta(get_the_ID(), 'longitud', true);
				$fosa_posts[] = [
					'title' => get_the_title(),
					'excerpt' => get_the_excerpt(),
					'lat' => $lat,
					'lng' => $lng
				];
				?>
				<hr />
				<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
				<?php the_excerpt(); ?>
				<p class="quiet">Posted by <?php print get_the_author_link() ?> in <?php print get_the_category_list( ', ' ) ?>.</p>
				<?php
			endwhile;

		else :

			print "<p>There are currently no posts to list here.</p>";

		endif;
		?>

	</div><!-- #primary -->

	<?php paginate(); ?>

<?php

get_footer();

?>
<script>
	document.addEventListener('DOMContentLoaded', () => {
		const apiKey = 'DBUe1dg9BjoCDDiQnet5';  // 🔥 Replace with your MapTiler key

		// 🌍 Initialize Map
		const map = new maplibregl.Map({
			container: 'map',
			style: `https://api.maptiler.com/maps/streets-v2/style.json?key=${apiKey}`,
			center: [-103.3478, 20.6768],  // Default: Guadalajara
			zoom: 12
		});

		map.on('load', () => {
			// 📌 Add markers for each fosa post
			const fosaPosts = <?php echo json_encode($fosa_posts); ?>;
			fosaPosts.forEach(post => {
				if (post.lat && post.lng) {
					const lat = parseFloat(post.lat);
					const lng = parseFloat(post.lng);
					if (!isNaN(lat) && !isNaN(lng)) {
						const marker = new maplibregl.Marker()
							.setLngLat([lng, lat])
							.setPopup(new maplibregl.Popup().setHTML(`<h3>${post.title}</h3><p>${post.excerpt}</p>`))
							.addTo(map);
					}
				}
			});
		});
	});
</script>