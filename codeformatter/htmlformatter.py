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
	from .htmlbeautifier import Beautifier
except (ValueError):
	# Python 2
	from htmlbeautifier import Beautifier


class HtmlFormatter:
	def __init__(self, formatter):
		self.formatter = formatter


	def format(self, text):
		opts = self.formatter.settings.get('codeformatter_html_options')


		options = []
		if (opts["indent_size"]):
			options.append("indent_size:"+str(opts["indent_size"]))
		else:
			options.append("indent_size:1")

		if (opts["indent_with_tabs"]):
			options.append("indent_char:	")
		else:
			options.append("indent_char: ")

		if (opts["max_char"]):
			options.append("max_char:"+str(opts["max_char"]))
		else:
			options.append("max_char:70")

		if (opts["brace_style"]):
			options.append("brace_style:"+str(opts["brace_style"]))
		else:
			options.append("brace_style:collapse")

		if (opts["unformatted"]):
			unformatted = "|".join(opts["unformatted"])
			options.append("unformatted:"+unformatted)



		options = ";".join(options)

		beautifier = Beautifier(self.formatter)
		stdout, stderr = beautifier.beautify(text, options);

		return stdout, stderr



