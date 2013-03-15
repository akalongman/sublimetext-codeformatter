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
	from .cssbeautifier import Beautifier
except (ValueError):
	# Python 2
	from cssbeautifier import Beautifier


class CssFormatter:
	def __init__(self, formatter):
		self.formatter = formatter


	def format(self, text):
		opts = self.formatter.settings.get('codeformatter_css_options')


		options = []

		if (opts["indent_with_tab"]):
			options.append("indent:	")
		else:
			options.append("indent: ")


		if (opts["openbrace"]):
			options.append("openbrace:"+str(opts["openbrace"]))
		else:
			options.append("openbrace:end-of-line")


		options = ";".join(options)

		beautifier = Beautifier(self.formatter)
		stdout, stderr = beautifier.beautify(text, options);

		return stdout, stderr



