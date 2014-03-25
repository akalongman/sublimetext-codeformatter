# @author 		Avtandil Kikabidze
# @copyright 		Copyright (c) 2008-2014, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link 			http://long.ge
# @license 		GNU General Public License version 2 or later;

import sublime, subprocess

class Beautifier:
	def __init__(self, formatter):
		self.formatter = formatter

	def beautify(self, text, indent, filters):
		stderr = ""
		stdout = ""
		php_path = self.formatter.settings.get('codeformatter_php_path', '');
		if (php_path == ""):
			php_path = "php"

		try:
			if (self.formatter.platform == "windows"):
				startupinfo = subprocess.STARTUPINFO()
				startupinfo.dwFlags |= subprocess.STARTF_USESHOWWINDOW
				startupinfo.wShowWindow = subprocess.SW_HIDE

				beautifier_exe = sublime.packages_path()+"\CodeFormatter\codeformatter\lib\phpbeautifier\php_beautifier"
				cmd = sublime.packages_path()+"\CodeFormatter\codeformatter\lib\phpbeautifier\php_beautifier.bat"
				fullcmd = [str(cmd), str(php_path), str(beautifier_exe), indent, "-l", filters, "-f", "-", "-o", "-"]
				#print(" ".join(map(str, fullcmd)))
				p = subprocess.Popen(fullcmd, stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE, startupinfo=startupinfo, shell=False, creationflags=subprocess.SW_HIDE)
			else:
				cmd = sublime.packages_path()+"/CodeFormatter/codeformatter/lib/phpbeautifier/php_beautifier"
				p = subprocess.Popen([str(cmd), indent, "-l", filters, "-f", "-", "-o", "-"], stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
			stdout, stderr = p.communicate(text)
		except Exception as e:
			stderr = str(e)

		if (not stderr and not stdout):
			stderr = "Formatting error!"

		return stdout, stderr
