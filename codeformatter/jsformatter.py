# @author 		Avtandil Kikabidze
# @copyright 		Copyright (c) 2008-2014, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link 			http://long.ge
# @license 		GNU General Public License version 2 or later;

import os
import sys
import re
import sublime
import subprocess

import jsbeautifier

class JsFormatter:
	def __init__(self, formatter):
		self.formatter = formatter


	def format(self, text):
		text = text.decode("utf-8")
		opts = self.formatter.settings.get('codeformatter_js_options')

		stderr = ""
		stdout = ""
		options = jsbeautifier.default_options()


		if ("indent_size" in opts and opts["indent_size"]):
			options.indent_size = opts["indent_size"]
		else:
			options.indent_size = 4


		if ("indent_char" in opts and opts["indent_char"]):
			options.indent_char = str(opts["indent_char"])
		else:
			options.indent_char = " "

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

		if ("space_in_paren" in opts and opts["space_in_paren"]):
			options.space_in_paren = True
		else:
			options.space_in_paren = False

		if ("e4x" in opts and opts["e4x"]):
			options.e4x = True
		else:
			options.e4x = False

		if ("jslint_happy" in opts and opts["jslint_happy"]):
			options.jslint_happy = True
		else:
			options.jslint_happy = False


		if ("brace_style" in opts and opts["brace_style"]):
			options.brace_style = opts["brace_style"]
		else:
			options.brace_style = 'collapse'


		if ("keep_array_indentation" in opts and opts["keep_array_indentation"]):
			options.keep_array_indentation = True
		else:
			options.keep_array_indentation = False


		if ("keep_function_indentation" in opts and opts["keep_function_indentation"]):
			options.keep_function_indentation = True
		else:
			options.keep_function_indentation = False


		if ("eval_code" in opts and opts["eval_code"]):
			options.eval_code = True
		else:
			options.eval_code = False


		if ("unescape_strings" in opts and opts["unescape_strings"]):
			options.unescape_strings = True
		else:
			options.unescape_strings = False


		if ("wrap_line_length" in opts and opts["wrap_line_length"]):
			options.wrap_line_length = opts["wrap_line_length"]
		else:
			options.wrap_line_length = 0


		if ("break_chained_methods" in opts and opts["break_chained_methods"]):
			options.break_chained_methods = True
		else:
			options.break_chained_methods = False


		try:
 		 	stdout = jsbeautifier.beautify(text, options)
		except Exception as e:
		 	stderr = str(e)

		if (not stderr and not stdout):
			stderr = "Formatting error!"

		return stdout, stderr



