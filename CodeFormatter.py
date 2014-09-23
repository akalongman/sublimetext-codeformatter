# @author 		Avtandil Kikabidze
# @copyright 		Copyright (c) 2008-2014, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link 			http://long.ge
# @license 		GNU General Public License version 2 or later;

import os, sys, sublime, sublime_plugin



st_version = 2
if sublime.version() == '' or int(sublime.version()) > 3000:
	st_version = 3

reloader_name = 'codeformatter.reloader'
# ST3 loads each package as a module, so it needs an extra prefix
if st_version == 3:
	reloader_name = 'CodeFormatter.' + reloader_name
	from imp import reload

if reloader_name in sys.modules:
	reload(sys.modules[reloader_name])

try:
	# Python 3
	from .codeformatter import reloader
	from .codeformatter.formatter import Formatter

except (ValueError):
	# Python 2
	from codeformatter import reloader
	from codeformatter.formatter import Formatter

# fix for ST2
cprint = globals()["__builtins__"]["print"]

def plugin_loaded():
	cprint('CodeFormatter: Plugin Initialized')
	if (sublime.platform() != "windows"):
		import stat
		path = sublime.packages_path()+"/CodeFormatter/codeformatter/lib/phpbeautifier/php_beautifier"
		st = os.stat(path)
		os.chmod(path, st.st_mode | stat.S_IEXEC)


if st_version == 2:
	plugin_loaded()


class CodeFormatterCommand(sublime_plugin.TextCommand):

	def run(self, edit, syntax=False):

		if self.view.is_scratch():
			return show_error("File is scratch.")

		file_name = self.view.file_name()

		# if not file_name:
		# 	return show_error("File does not exist.")

		# if not os.path.exists(file_name):
		# 	return show_error("File "+file_name+" does not exist.")

		formatter = Formatter(self.view, file_name, syntax)
		if not formatter.exists():
			return show_error("Formatter for this file type ("+formatter.syntax+") not found.")

		file_text = sublime.Region(0, self.view.size())
		file_text_utf = self.view.substr(file_text).encode('utf-8')
		if (len(file_text_utf) == 0):
			return show_error("No code found.")

		stdout, stderr = formatter.format(file_text_utf)

		if len(stderr) == 0 and len(stdout) > 0:
			self.view.replace(edit, file_text, stdout)
		else:
			show_error("Format error:\n"+stderr)

def console_write(text, prefix=False):
	if prefix:
		sys.stdout.write('CodeFormatter: ')
	sys.stdout.write(text+"\n")


def show_error(text):
    sublime.error_message(u'CodeFormatter\n\n%s' % text)
