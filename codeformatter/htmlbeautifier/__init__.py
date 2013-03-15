# @author 		Avtandil Kikabidze
# @copyright 		Copyright (c) 2008-2013, Avtandil Kikabidze (akalongman@gmail.com)
# @link 			http://long.ge
# @license 		GNU General Public License version 2 or later;

import subprocess

class Beautifier:
	def __init__(self, formatter):
		self.formatter = formatter

	def beautify(self, text, options):
		exec_path = self.formatter.packages_path + "/CodeFormatter/codeformatter/htmlbeautifier/exec.js"

		cmd = ["node", exec_path, options]
		stderr = ""
		stdout = ""
		try:
			if (self.formatter.platform == "windows"):
				p = subprocess.Popen(cmd, shell=True, stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE, creationflags=subprocess.SW_HIDE)
			else:
				p = subprocess.Popen(cmd, stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
			stdout, stderr  = p.communicate(text)
		except Exception as e:
			stderr = str(e)

		if (not stderr and not stdout):
			stderr = "Formatting error!"

		return stdout, stderr

