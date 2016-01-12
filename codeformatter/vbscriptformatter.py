# @author 			Avtandil Kikabidze
# @copyright 		Copyright (c) 2008-2015, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link 			http://long.ge
# @license 		The MIT License (MIT)

import os
import sys
import re
import sublime
import subprocess

import vbscriptbeautifier

class VbscriptFormatter:
	def __init__(self, formatter):
		self.formatter = formatter


	def format(self, text):
		text = text.decode("utf-8")
		opts = self.formatter.settings.get('codeformatter_vbscript_options')


		stderr = ""
		stdout = ""
		options = vbscriptbeautifier.default_options()

		if ("indent_size" in opts and opts["indent_size"]):
			options.indent_size = opts["indent_size"]
		else:
			options.indent_size = 1


		if ("indent_char" in opts and opts["indent_char"]):
			options.indent_char = str(opts["indent_char"])
		else:
			options.indent_char = "\t"

		if ("indent_with_tabs" in opts and opts["indent_with_tabs"]):
			options.indent_with_tabs = True
		else:
			options.indent_with_tabs = True

		if ("preserve_newlines" in opts and opts["preserve_newlines"]):
			options.preserve_newlines = True
		else:
			options.preserve_newlines = False

		if ("max_preserve_newlines" in opts and opts["max_preserve_newlines"]):
			options.max_preserve_newlines = opts["max_preserve_newlines"]
		else:
			options.max_preserve_newlines = 10

		if ("opening_tags" in opts and opts["opening_tags"]):
			options.opening_tags = str(opts["opening_tags"])

		if ("middle_tags" in opts and opts["middle_tags"]):
			options.middle_tags = str(opts["middle_tags"])

		if ("closing_tags" in opts and opts["closing_tags"]):
			options.closing_tags = str(opts["closing_tags"])

		try:
 		 	stdout = vbscriptbeautifier.beautify(text, options)
		except Exception as e:
		 	stderr = str(e)

		if (not stderr and not stdout):
			stderr = "Formatting error!"

		return stdout, stderr

