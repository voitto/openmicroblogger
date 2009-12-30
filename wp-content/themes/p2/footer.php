<div id="footerclear"></div>
</div> <!-- // wrapper -->

	<div id="footer">
		<p><a href="http://rp.ly/">rp.ly <?php global $ombversion; echo $ombversion; ?></a> | P2 theme by <a href="http://automattic.com/">Automattic</a><?php global $request; if ($request->resource == 'posts') render_partial('pagespan'); ?></p>
	</div>
	
<?php wp_footer(); ?>

</body>
</html>