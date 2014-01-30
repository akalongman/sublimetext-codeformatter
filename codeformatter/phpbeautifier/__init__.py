# @author 		Avtandil Kikabidze
# @copyright 		Copyright (c) 2008-2013, Avtandil Kikabidze (akalongman@gmail.com)
# @link 			http://long.ge
# @license 		GNU General Public License version 2 or later;

import subprocess

class Beautifier:
	def __init__(self, formatter):
		self.formatter = formatter

	def beautify(self, text, indent, filters):
		stderr = ""
		stdout = ""

		php_path = self.formatter.settings.get('codeformatter_php_path', '');
		try:
			if (self.formatter.platform == "windows"):
				if (php_path == ""):
					cmd = 'php_beautifier.bat'
				else:
					cmd = php_path+'/php_beautifier.bat'
				p = subprocess.Popen([cmd, indent, "-l", filters, "-f", "-", "-o", "-"], stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE, shell=True, creationflags=subprocess.SW_HIDE)
			else:
				if (php_path == ""):
					cmd = 'php_beautifier'
				else:
					cmd = php_path+'/php_beautifier'
				p = subprocess.Popen([cmd, indent, "-l", filters, "-f", "-", "-o", "-"], stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
			stdout, stderr = p.communicate(text)
		except Exception as e:
			stderr = str(e)

		if (not stderr and not stdout):
			stderr = "Formatting error!"

		return stdout, stderr
