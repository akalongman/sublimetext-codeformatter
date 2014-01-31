# @author 		Avtandil Kikabidze
# @copyright 		Copyright (c) 2008-2013, Avtandil Kikabidze (akalongman@gmail.com)
# @link 			http://long.ge
# @license 		GNU General Public License version 2 or later;

import os
import sys
import re
import sublime
import subprocess


#from bs4 import BeautifulSoup

#from PyQt4.QtGui import QAction, QIcon
#from PyQt4.QtCore import SIGNAL
from .lib.htmlformat.bs4 import BeautifulSoup

class HtmlFormatter:
	def __init__(self, formatter):
		self.formatter = formatter


	def format(self, text):
		text = text.decode("utf-8")
		opts = self.formatter.settings.get('codeformatter_html_options')


		stderr = ""
		stdout = ""

		pretty_code = BeautifulSoup(text)


		print(pretty_code)



		return stdout, stderr



