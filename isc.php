<?php

/*
  Plugin Name: Image Source Control
  Version: 0.1
  Plugin URI: none
  Description: The Image Source Control saves the source of an image, lists them and warns if it is missing.
  Author: Thomas Maier
  Author URI: http://www.webzunft.de
  License: GPL v3

  Image Source Control Plugin for WordPress
  Copyright (C) 2012, Thomas Maier - post@webzunft.de

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 */

define('ISCVERSION', '0.1');
define('ISCNAME', 'Image Source Control');
define('ISCTEXTDOMAIN', 'isc');
define('ISCDIR', basename(dirname(__FILE__)));
define('ISCPATH', plugin_dir_path(__FILE__));

load_plugin_textdomain(WZADSTEXTDOMAIN, false, WZADSPATH . '/lang');