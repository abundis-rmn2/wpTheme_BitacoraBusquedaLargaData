<?php
/**
 * The template for displaying the footer
 *
 * Contains footer content and the closing of the #main and #page div elements.
 *
 * @package WordPress
 * @subpackage Twenty_Twelve
 * @since Twenty Twelve 1.0
 */
$admin_email = get_option( 'admin_email' );

$active_footers = 0;
if (is_active_sidebar('footer-widget-1')) $active_footers++;
if (is_active_sidebar('footer-widget-2')) $active_footers++;
if (is_active_sidebar('footer-widget-3')) $active_footers;

if ($active_footers > 0): ?>
    <footer class="footer-widgets" style="display: flex; gap: 20px; justify-content: space-between;">
        <?php if (is_active_sidebar('footer-widget-1')): ?>
            <div class="footer-widget-1" style="flex: 1;">
                <?php dynamic_sidebar('footer-widget-1'); ?>
            </div>
        <?php endif; ?>
        <?php if (is_active_sidebar('footer-widget-2')): ?>
            <div class="footer-widget-2" style="flex: 1;">
                <?php dynamic_sidebar('footer-widget-2'); ?>
            </div>
        <?php endif; ?>
        <?php if (is_active_sidebar('footer-widget-3')): ?>
            <div class="footer-widget-3" style="flex: 1;">
                <?php dynamic_sidebar('footer-widget-3'); ?>
            </div>
        <?php endif; ?>
    </footer>
<?php endif; ?>
	
	</section>
	
	<footer class="footer">
		<p><?php bloginfo('name'); ?> - <?php bloginfo('description'); ?><br>
		Esta pagina funciona con <b>Bitácora de Búsqueda</b> desarrollado por <a href="https://tejer.red">Tejer.Red</a></p>
	</footer>

</div><!-- #container -->

<?php wp_footer(); ?>
</body>
</html>