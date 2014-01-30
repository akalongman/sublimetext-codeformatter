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
	from .lib import htmlbeautifier
except (ValueError):
 	# Python 2
	from lib import htmlbeautifier

class HtmlFormatter:
	def __init__(self, formatter):
		self.formatter = formatter


	def format(self, text):
		text = text.decode("utf-8")
		opts = self.formatter.settings.get('codeformatter_html_options')


		stderr = ""
		stdout = ""


		options = htmlbeautifier.default_options()

		if (opts["indent_inner_html"]):
			options.indent_inner_html = True
		else:
			options.indent_inner_html = False


		if (opts["indent_size"]):
			options.indent_size = opts["indent_size"]
		else:
			options.indent_size = 4

		if (opts["indent_char"]):
			options.indent_char = opts["indent_char"]
		else:
			options.indent_char = ' '

		if (opts["brace_style"]):
			options.brace_style = opts["brace_style"]
		else:
			options.brace_style = "collapse"

		if (opts["indent_scripts"]):
			options.indent_scripts = opts["indent_scripts"]
		else:
			options.indent_scripts = "normal"

		if (opts["wrap_line_length"]):
			options.wrap_line_length = opts["wrap_line_length"]
		else:
			options.wrap_line_length = 250

		if (opts["preserve_newlines"]):
			options.preserve_newlines = True
		else:
			options.preserve_newlines = False

		if (opts["max_preserve_newlines"]):
			options.max_preserve_newlines = opts["max_preserve_newlines"]
		else:
			options.max_preserve_newlines = 10

		if (opts["unformatted"]):
			options.unformatted = "|".join(opts["unformatted"])
		else:
			options.unformatted = "a"


		try:
 		 	stdout = htmlbeautifier.beautify(text, options)
		except Exception as e:
		 	stderr = str(e)

		if (not stderr and not stdout):
			stderr = "Formatting error!"

		return stdout, stderr



