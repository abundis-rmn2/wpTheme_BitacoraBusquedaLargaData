<?php
/**
 * The template for displaying Archive Indicio pages
 */

get_header(); 

?>

<div class="content-wide" role="main">

    <div id="filter-controls">
        <?php
        $taxonomies = ['tipo_prenda', 'color', 'marca', 'talla', 'material'];
        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms($taxonomy);
            if ($terms) {
                echo '<label for="' . $taxonomy . '-filter">' . ucfirst(str_replace('_', ' ', $taxonomy)) . ':</label>';
                echo '<select id="' . $taxonomy . '-filter" class="taxonomy-filter" data-taxonomy="' . $taxonomy . '">';
                echo '<option value="">All</option>';
                foreach ($terms as $term) {
                    echo '<option value="' . $term->slug . '">' . $term->name . '</option>';
                }
                echo '</select>';
            }
        }
        ?>
    </div>

    <div id="indicio-gallery" class="grid">
        <?php 
        if ( have_posts() ) : 

            // Start the Loop.
            while ( have_posts() ) : the_post(); 
                // Create a structured data object for taxonomies
                $item_taxonomies = array();
                foreach ($taxonomies as $taxonomy) {
                    $terms = wp_get_post_terms(get_the_ID(), $taxonomy, ['fields' => 'slugs']);
                    if (!empty($terms)) {
                        $item_taxonomies[$taxonomy] = $terms;
                    }
                }
                ?>
                <div class="indicio-item" 
                    <?php foreach($taxonomies as $tax): ?>
                        data-<?php echo $tax; ?>="<?php 
                            $terms = wp_get_post_terms(get_the_ID(), $tax, ['fields' => 'slugs']);
                            echo implode(' ', $terms); 
                        ?>"
                    <?php endforeach; ?>
                >
                    <a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('medium'); ?></a>
                    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                    <?php the_excerpt(); ?>
                    <p class="quiet">Posted by <?php print get_the_author_link() ?> in <?php print get_the_category_list( ', ' ) ?>.</p>
                    <p class="taxonomies">
                        <?php
                        foreach ($taxonomies as $taxonomy) {
                            $terms = wp_get_post_terms(get_the_ID(), $taxonomy);
                            if ($terms) {
                                echo '<strong>' . ucfirst(str_replace('_', ' ', $taxonomy)) . ':</strong> ';
                                foreach ($terms as $term) {
                                    echo '<span class="taxonomy">' . $term->name . '</span> ';
                                }
                                echo '<br>';
                            }
                        }
                        ?>
                    </p>
                </div>
                <?php
            endwhile;

        else :

            print "<p>There are currently no posts to list here.</p>";

        endif;
        ?>
    </div><!-- #indicio-gallery -->

</div><!-- #primary -->

<script>
document.querySelectorAll('.taxonomy-filter').forEach(function(select) {
    select.addEventListener('change', function() {
        var filters = {};
        document.querySelectorAll('.taxonomy-filter').forEach(function(select) {
            var taxonomy = select.dataset.taxonomy;
            filters[taxonomy] = select.value;
        });

        console.log('Current filters:', filters);

        var items = document.querySelectorAll('.indicio-item');
        items.forEach(function(item) {
            var show = true;
            
            for (var taxonomy in filters) {
                if (filters[taxonomy]) {
                    var termAttribute = 'data-' + taxonomy;
                    var itemTerms = item.getAttribute(termAttribute) || '';
                    console.log(`Item ${item.querySelector('h3').innerText} has ${taxonomy} terms: "${itemTerms}"`);
                    console.log(`Checking if it contains: "${filters[taxonomy]}"`);
                    
                    // Check if the item has the term as a whole word
                    var termRegex = new RegExp('\\b' + filters[taxonomy] + '\\b');
                    if (!termRegex.test(itemTerms)) {
                        show = false;
                        console.log(`Hiding item due to ${taxonomy} with filter value: ${filters[taxonomy]}`);
                        break;
                    }
                }
            }
            
            item.style.display = show ? 'block' : 'none';
        });
    });
});
</script>

<style>
#indicio-gallery.grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}
.indicio-item {
    border: 1px solid #ddd;
    padding: 10px;
    background: #fff;
}
.taxonomies .taxonomy {
    display: inline-block;
    background: #f0f0f0;
    padding: 2px 5px;
    margin: 2px;
    border-radius: 3px;
}
</style>

<?php

get_footer();

?>