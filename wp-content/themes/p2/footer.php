<div id="footerclear"></div>
</div> <!-- // wrapper -->

	<div id="footer">
		<p><a href="http://openmicroblogger.org/">OpenMicroblogger <?php global $ombversion; echo $ombversion; ?></a> | P2 theme by <a href="http://automattic.com/">Automattic</a><?php render_partial( array(
    'resource'=>'posts',
    'action'=>'pagespan'
  )); ?></p>
	</div>
	
<?php wp_footer(); ?>

</body>
</html>