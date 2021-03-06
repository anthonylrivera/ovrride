<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the id #maincontentcontainer div and all content after.
 * There are also four footer widgets displayed. These will be displayed from
 * one to four columns, depending on how many widgets are active.
 *
 * @package Quark
 * @since Quark 1.0
 */
?>

  </div> <!-- /#maincontentcontainer -->

    <div id="footercontainer-top">
      <footer class="site-footer row clearfix" role="contentinfo">

			<?php
			// Count how many footer sidebars are active so we can work out how many containers we need
			$footerSidebars = 0;
			for ( $x=1; $x<=4; $x++ ) {
				if ( is_active_sidebar( 'sidebar-footer' . $x ) ) {
					$footerSidebars++;
				}
			}

			// If there's one or more one active sidebars, create a row and add them
			if ( $footerSidebars > 0 ) { ?>
				<?php
				// Work out the container class name based on the number of active footer sidebars
				$containerClass = "grid_" . 12 / $footerSidebars . "_of_12";

				// Display the active footer sidebars
				for ( $x=1; $x<=4; $x++ ) {
					if ( is_active_sidebar( 'sidebar-footer'. $x ) ) { ?>
						<div class="col <?php echo $containerClass?>">
							<div class="widget-area" role="complementary">
								<?php dynamic_sidebar( 'sidebar-footer'. $x ); ?>
							</div>
						</div> <!-- /.col.<?php echo $containerClass?> -->
					<?php }
				} ?>

			<?php } ?>

      </footer> <!-- /.site-footer.row -->
    </div>

  <div id="footercontainer">
    <footer class="site-footer row clearfix" role="contentinfo">
      <div class="col grid_3_of_12">
        <a href="http://www.blades.com" title="Blades" target="_blank"><div class="shop blades"></div></a>
        <a href="http://skatebrooklynny.com" title="Skate Brooklyn" target="_blank"><div class="shop skate-brooklyn"></div></a>
        <a href="http://aegirboardworks.com" title="Aegir Boardworks" target="_blank"><div class="shop aegir"></div></a>
        <a href="http://www.burton.com/default/stores-nyc.html" title="Burton Store NYC" target="_blank"><div class="shop burton"></div></a>
      </div>

      <div class="col grid_6_of_12" style="text-align:center;">
        <a href="/">Home</a>
        <span> | </span>
        <a href="/blog/">Blog</a>
        <span> | </span>
        <a href="/faq/">FAQ</a>
        <span> | </span>
        <a href="/contact-us/">Contact Us</a>
        <span> | </span>
        <span><a href="/terms-and-conditions/">Terms &amp; Conditions</a></span>
        <span> | </span>
        <span><a href="/waiver">Waiver</a></span>
        <br>
        <span>&copy; COPYRIGHT <?php echo date('Y'); ?> - <a href="/">OvR ride LLC.</a> - ALL RIGHTS RESERVED</span>
        <br>
        <span><a href="http://ajalabs.com" target="_new">Created and maintained by AJAlabs</a></span>
      </div>

      <div style="text-align:right;" class="col grid_3_of_12">
        <a href="http://www.homagebrooklyn.com/site/" title="Homage Brooklyn" target="_blank"><div class="shop homage"></div></a>
        <a href="http://www.rei.com/stores/soho.html" title="REI SoHo NYC" target="_blank"><div class="shop rei"></div></a>
        <a href="http://www.shutnyc.com" title="SHUT Skateboards NYC" target="_blank"><div class="shop shut"></div></a>
      </div>
    </footer>
  </div> <!-- /.footercontainer -->

</div> <!-- /.#wrapper.hfeed.site -->


<?php wp_footer(); ?>
</body>

</html>
