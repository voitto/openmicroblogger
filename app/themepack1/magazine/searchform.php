			<form id="searchform" method="get" action="<?php bloginfo('url'); ?>">
				<input type="text" value="<?php the_search_query(); ?>" name="s" id="s" /> <input id="searchbutton" type="submit" value="Find" />
			</form>