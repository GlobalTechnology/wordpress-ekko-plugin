<?php namespace Ekko {
	/*
	 * Plugin Name: Ekko
	 * Plugin URI:  ekkomobile.com
	 * Description: Ekko Course Creator and Content Management
	 * Author:      Brian Zoetewey
	 * Author URI:  ekkomobile.com
	 * Version:     0.4.0
	 * Text Domain: ekko
	 * Domain Path: /languages/
	 * License:     Modified BSD
	 */
	/*
	 * Copyright (c) 2013, Campus Crusade for Christ, Intl.
	 * All rights reserved.
	 *
	 * Redistribution and use in source and binary forms, with or without modification,
	 * are permitted provided that the following conditions are met:
	 *
	 *     Redistributions of source code must retain the above copyright notice, this
	 *         list of conditions and the following disclaimer.
	 *     Redistributions in binary form must reproduce the above copyright notice,
	 *         this list of conditions and the following disclaimer in the documentation
	 *         and/or other materials provided with the distribution.
	 *     Neither the name of CAMPUS CRUSADE FOR CHRIST nor the names of its
	 *         contributors may be used to endorse or promote products derived from this
	 *         software without specific prior written permission.
	 *
	 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
	 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
	 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
	 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
	 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
	 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
	 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
	 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
	 * OF THE POSSIBILITY OF SUCH DAMAGE.
	 */

	require_once( dirname( __FILE__ ) . '/constants.php' );
	require_once( dirname( __FILE__ ) . '/conf.php' );
	require_once( dirname( __FILE__ ) . '/autoload.php' );
	require_once( dirname( __FILE__ ) . '/lib/gto-framework/autoload.php' );

	//Instantiate the Ekko Plugin
	\Ekko\Core\Plugin::singleton();
}
