/*
 * E-Phonic MP3 Player Javascript API v1.0 (c)2007 PJ Arts
 * http://www.e-phonic.com/mp3player/
 *
 */

function EP_getElement(id) {
	var e = document.getElementById(id);
	if(e && e.EP_isLoaded) if(e.EP_isLoaded()) return e;
	return undefined;
}

function EP_play(id) {
	var e = EP_getElement(id);
	if(e) e.EP_play();
}

function EP_stop(id) {
	var e = EP_getElement(id);
	if(e) e.EP_stop();
}

function EP_pause(id) {
	var e = EP_getElement(id);
	if(e) e.EP_pause();
}

function EP_prev(id) {
	var e = EP_getElement(id);
	if(e) e.EP_prev();
}

function EP_next(id) {
	var e = EP_getElement(id);
	if(e) e.EP_next();
}

function EP_loadMP3(id, file) {
	var e = EP_getElement(id);
	if(e) e.EP_loadMP3(file);
}