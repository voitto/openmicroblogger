<?php
  
  $packname = 'themepack1';
  
  $themename = environment('theme');
  
  $dir = $GLOBALS['PATH']['app'] . $packname . DIRECTORY_SEPARATOR;
  
  if (is_dir( $dir . $themename ))
    $GLOBALS['PATH']['themes'] = $dir;

