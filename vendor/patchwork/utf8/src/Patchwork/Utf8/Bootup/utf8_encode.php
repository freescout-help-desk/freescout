<?php

/*
 * Copyright (C) 2016 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

use Patchwork\PHP\Shim as s;

@trigger_error('You are using a fallback implementation of the xml extension. Installing the native one is highly recommended instead.', E_USER_DEPRECATED);

function utf8_encode($s) {return s\Xml::utf8_encode($s);}
function utf8_decode($s) {return s\Xml::utf8_decode($s);}
