<?php
function lv($package, $subname = '') 
{
	return new lv\load\Node($package, $subname);
}
