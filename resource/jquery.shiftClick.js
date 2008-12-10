/*
 * Copyright (c) 2008 John Sutherland <john@sneeu.com>
 *
 * Permission to use, copy, modify, and distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

(function($) {
	$.fn.shiftClick = function() {
		var lastSelected;
		var checkBoxes = $(this);

		this.each(function() {
			$(this).click(function(ev) {
				if (ev.shiftKey) {
					var last = checkBoxes.index(lastSelected);
					var first = checkBoxes.index(this);

					var start = Math.min(first, last);
					var end = Math.max(first, last);

					var chk = lastSelected.checked;
					for (var i = start; i < end; i++) {
						checkBoxes[i].checked = chk;
					}
				} else {
					lastSelected = this;
				}
			})
		});
	};
})(jQuery);