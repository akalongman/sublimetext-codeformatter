/**
 * Nodejs runnable file
 *
 * @author 		Avtandil Kikabidze
 * @copyright 	Copyright (c) 2008-2013, Avtandil Kikabidze (akalongman@gmail.com)
 * @link 			http://long.ge
 * @license 		GNU General Public License version 2 or later;
 */
(function() {
	"use strict";
	process.stdin.resume();
	process.stdin.setEncoding('utf8');
	var i, len, hash, key, value, params, arg,
	argv = process.argv,
	path = require('path');
	global.cssbeautify = require(path.join(__dirname, "cssbeautify.js")).cssbeautify;
	global.js_beautify = require(path.join(__dirname, "beautify.js")).js_beautify;
	process.stdin.on('data', function (text) {
		if (text !== "") {
			var options = {};
			arg = argv[2];
			if (arg !== undefined) {
				params = arg.split(";");
				for (i = 0, len = params.length; i < len; i++) {
					hash = params[i].split(":");
					key = hash[0];
					value = hash[1];
					if (value == 'true') {
						value = true;
					} else if (value == 'false') {
						value = false;
					}
					options[key] = value;
				}
			}
			process.stdout.write(global.cssbeautify(text, options));
		}
	});

}());