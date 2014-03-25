# @author 		Avtandil Kikabidze
# @copyright 		Copyright (c) 2008-2014, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link 			http://long.ge
# @license 		GNU General Public License version 2 or later;

import os
import sys
import re
import sublime
import subprocess

import htmlbeautifier

class HtmlFormatter:
	def __init__(self, formatter):
		self.formatter = formatter


	def format(self, text):
		text = text.decode("utf-8")
		opts = self.formatter.settings.get('codeformatter_html_options')


		stderr = ""
		stdout = ""
		options = htmlbeautifier.default_options()

		if ("indent_size" in opts and opts["indent_size"]):
			options.indent_size = opts["indent_size"]
		else:
			options.indent_size = 4


		if ("indent_char" in opts and opts["indent_char"]):
			options.indent_char = str(opts["indent_char"])
		else:
			options.indent_char = "	"

		if ("indent_with_tabs" in opts and opts["indent_with_tabs"]):
			options.indent_with_tabs = True
		else:
			options.indent_with_tabs = False

		if ("preserve_newlines" in opts and opts["preserve_newlines"]):
			options.preserve_newlines = True
		else:
			options.preserve_newlines = False

		if ("max_preserve_newlines" in opts and opts["max_preserve_newlines"]):
			options.max_preserve_newlines = opts["max_preserve_newlines"]
		else:
			options.max_preserve_newlines = 10

		if ("indent_tags" in opts and opts["indent_tags"]):
			options.indent_tags = str(opts["indent_tags"])

		try:
 		 	stdout = htmlbeautifier.beautify(text, options)
		except Exception as e:
		 	stderr = str(e)

		if (not stderr and not stdout):
			stderr = "Formatting error!"

		return stdout, stderr

