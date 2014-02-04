# @author 		Avtandil Kikabidze
# @copyright 		Copyright (c) 2008-2013, Avtandil Kikabidze (akalongman@gmail.com)
# @link 			http://long.ge
# @license 		GNU General Public License version 2 or later;

import sublime,subprocess

class Beautifier:
	def __init__(self, formatter):
		self.formatter = formatter

	def beautify(self, text, indent, filters):
		stderr = ""
		stdout = ""
		php_path = self.formatter.settings.get('codeformatter_php_path', '');
		if (php_path == ""):
			php_path = "php"
		beautifier_exe = sublime.packages_path()+"\CodeFormatter\codeformatter\lib\phpbeautifier\php_beautifier"

		try:
			if (self.formatter.platform == "windows"):
				cmd = sublime.packages_path()+"\CodeFormatter\codeformatter\lib\phpbeautifier\php_beautifier.bat"
				p = subprocess.Popen([str(cmd), str(php_path), str(beautifier_exe), indent, "-l", filters, "-f", "-", "-o", "-"], stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE, shell=True, creationflags=subprocess.SW_HIDE)
			else:
				cmd = sublime.packages_path()+"/CodeFormatter/codeformatter/lib/phpbeautifier/php_beautifier"
				p = subprocess.Popen([str(cmd), indent, "-l", filters, "-f", "-", "-o", "-"], stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
			stdout, stderr = p.communicate(text)
		except Exception as e:
			stderr = str(e)

		if (not stderr and not stdout):
			stderr = "Formatting error!"

		return stdout, stderr
