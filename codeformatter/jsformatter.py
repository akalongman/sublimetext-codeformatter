# @author 		Avtandil Kikabidze
# @copyright 		Copyright (c) 2008-2013, Avtandil Kikabidze (akalongman@gmail.com)
# @link 			http://long.ge
# @license 		GNU General Public License version 2 or later;

import os
import sys
import re
import sublime
import subprocess

try:
	# Python 3
	from .jsbeautifier import Beautifier
except (ValueError):
	# Python 2
	from jsbeautifier import Beautifier


class JsFormatter:
	def __init__(self, formatter):
		self.formatter = formatter


	def format(self, text):
		opts = self.formatter.settings.get('codeformatter_js_options')


		options = []
		if (opts["indent_size"]):
			options.append("indent_size:"+str(opts["indent_size"]))
		else:
			options.append("indent_size:1")

		if (opts["indent_with_tabs"]):
			options.append("indent_char:	")
		else:
			options.append("indent_char: ")

		if (opts["preserve_newlines"]):
			options.append("preserve_newlines:true")
		else:
			options.append("preserve_newlines:false")

		if (opts["max_preserve_newlines"]):
			options.append("max_preserve_newlines:"+str(opts["max_preserve_newlines"]))
		else:
			options.append("max_preserve_newlines:10")

		if (opts["jslint_happy"]):
			options.append("jslint_happy:true")
		else:
			options.append("jslint_happy:false")

		if (opts["brace_style"]):
			options.append("brace_style:"+str(opts["brace_style"]))
		else:
			options.append("brace_style:collapse")

		if (opts["keep_array_indentation"]):
			options.append("keep_array_indentation:true")
		else:
			options.append("keep_array_indentation:false")





		options = ";".join(options)

		beautifier = Beautifier(self.formatter)
		stdout, stderr = beautifier.beautify(text, options);

		return stdout, stderr



