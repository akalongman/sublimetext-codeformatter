# @author 		Avtandil Kikabidze
# @copyright 		Copyright (c) 2008-2014, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link 			http://long.ge
# @license 		GNU General Public License version 2 or later;

import os
import sys
import re
import sublime

try:
	# Python 3
	from .phpbeautifier import Beautifier
except (ValueError):
	# Python 2
	from phpbeautifier import Beautifier

class PhpFormatter:
	def __init__(self, formatter):
		self.formatter = formatter



	def format(self, text):
		opts = self.formatter.settings.get('codeformatter_php_options')


		# Filters
		filters = []

		# Default
		default = []
		if ("newline_before_comment" in opts and opts["newline_before_comment"]):
			default.append("newline_before_comment=true")
		default = ",".join(map(str, default))
		filters.append("Default("+default+")")


		# Pear
		if ("pear" in opts and opts["pear"]):
			pear = []
			if ("pear_add_header" in opts and opts["pear_add_header"]):
				pear.append("add_header="+opts["pear_add_header"])

			if ("pear_newline_class" in opts and opts["pear_newline_class"]):
				pear.append("newline_class=true")

			if ("pear_newline_trait" in opts and opts["pear_newline_trait"]):
				pear.append("newline_trait=true")

			if ("pear_newline_function" in opts and opts["pear_newline_function"]):
				pear.append("newline_function=true")

			if ("pear_switch_without_indent" in opts and opts["pear_switch_without_indent"]):
				pear.append("switch_without_indent=true")


			pear = ",".join(map(str, pear))
			filters.append("Pear("+pear+")")


		# Line filters
		new_line_before = ""
		if ("new_line_before" in opts and opts["new_line_before"]):
			new_line_before = opts["new_line_before"].replace(",", ":")

		new_line_after = ""
		if ("new_line_after" in opts and opts["new_line_after"]):
			new_line_after = opts["new_line_after"].replace(",", ":")

		new_lines = ""
		if (new_line_before and new_line_after):
			new_lines += "NewLines(before="+new_line_before+",after="+new_line_after+")"
		elif (new_line_before != ""):
			new_lines += "NewLines(before="+new_line_before+")"
		elif (new_line_after != ""):
			new_lines += "NewLines(after="+new_line_after+")"

		if (new_lines):
			filters.append(new_lines)

		# Array Nested
		if ("format_array_nested" in opts and opts["format_array_nested"]):
			filters.append("ArrayNested()")

		# Lowercase
		if ("lowercase" in opts and opts["lowercase"]):
			filters.append("Lowercase()")


		# Fluent
		if ("fluent" in opts and opts["fluent"]):
			filters.append("Fluent()")


		# phpBB
		if ("phpbb" in opts and opts["phpbb"]):
			filters.append("phpBB()")

		# ListClassFunction
		if ("list_class_function" in opts and opts["list_class_function"]):
			filters.append("ListClassFunction()")

		# EqualsAlign
		if ("equals_align" in opts and opts["equals_align"]):
			filters.append("EqualsAlign()")

		# SpaceInParen
		if ("space_in_paren" in opts and opts["space_in_paren"]):
			filters.append("SpaceInParen()")

		# SpaceInSquare
		if ("space_in_square" in opts and opts["space_in_square"]):
			filters.append("SpaceInSquare()")
			
		# Identation
		if ("indent_with_tabs" in opts and opts["indent_with_tabs"]):
			ident_type = "t"
		else:
			ident_type = "s"

		if ("indent_size" in opts and opts["indent_size"]):
			indent_size = str(opts["indent_size"])
		else:
			indent_size = "4"

		indent = "-"+ident_type+indent_size

		# Indent style
		if ("indent_style" in opts and opts["indent_style"]):
			indent_style = opts["indent_style"]
		else:
			indent_style = "k&r"

		filters.append("IndentStyles(style="+indent_style+")")


		filters = " ".join(map(str, filters))

		beautifier = Beautifier(self.formatter)
		stdout, stderr = beautifier.beautify(text, indent, filters)


		return stdout, stderr
