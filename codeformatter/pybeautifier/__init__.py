# @author 			Avtandil Kikabidze
# @copyright 		Copyright (c) 2008-2015, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link 			http://longman.me
# @license 		The MIT License (MIT)

try:
	from io import StringIO
except ImportError:
	import StringIO
from PythonTidy.config import version, summary
from PythonTidy import PythonTidy
from PythonTidy import PythonTidyWrapper


class Beautifier:
	def __init__(self, formatter):
		self.formatter = formatter

	def beautify(self, text, options):
		stderr = ""
		stdout = ""

		config = PythonTidyWrapper.Config()

		for key, val in options.iteritems():
			config.set_global(key, val)

		config.to_pythontidy_namespace()

		try:
			source = StringIO(text)
			output = StringIO()
			PythonTidy.tidy_up(source, output)
			stdout = output.getvalue()
		except Exception as e:
			stderr = str(e)

		if (not stderr and not stdout):
			stderr = "Formatting error!"

		return stdout, stderr
