# @author 			Avtandil Kikabidze
# @copyright 		Copyright (c) 2008-2015, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link 			http://long.ge
# @license 		The MIT License (MIT)

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

		if "indent_size" in opts:
			options.indent_size = opts["indent_size"]

		if "indent_char" in opts:
			options.indent_char = str(opts["indent_char"])

		if "minimum_attribute_count" in opts:
			options.minimum_attribute_count = opts["minimum_attribute_count"]

		if "indent_with_tabs" in opts:
			options.indent_with_tabs = opts["indent_with_tabs"]

		if "expand_tags" in opts:
			options.expand_tags = opts["expand_tags"]

		if "expand_javascript" in opts:
			options.expand_javascript = opts["expand_javascript"]

		if "reduce_empty_tags" in opts:
			options.reduce_empty_tags = opts["reduce_empty_tags"]

		if "exception_on_tag_mismatch" in opts:
			options.exception_on_tag_mismatch = opts["exception_on_tag_mismatch"]

		try:
 		 	stdout = htmlbeautifier.beautify(text, options)
		except Exception as e:
		 	stderr = str(e)

		if (not stderr and not stdout):
			stderr = "Formatting error!"

		return stdout, stderr
