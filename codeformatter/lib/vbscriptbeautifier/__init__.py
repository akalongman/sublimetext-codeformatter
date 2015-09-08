from __future__ import print_function
import sys
import re
try:
 	# Python 3
	from .__version__ import __version__
except (ValueError):
 	# Python 2
	from __version__ import __version__

class BeautifierOptions:
	def __init__(self):
		self.indent_size = 1
		self.indent_char = "\t"
		self.indent_with_tabs = True
		self.preserve_newlines = True
		self.max_preserve_newlines = 10
		self.opening_tags = '^(Function .*|Sub .*|If .* Then|For .*|Do While .*|Select Case.*)'
		self.middle_tags = '^(Else|ElseIf .* Then|Case .*)$'
		self.closing_tags = '(End Function|End Sub|End If|Next|Loop|End Select)$'

	def __repr__(self):
		return \
"""indent_size = %d
indent_with_tabs = [%s]
preserve_newlines = [%s]
max_preserve_newlines = [%d]
opening_tags = [%d]
middle_tags = [%d]
closing_tags = [%d]
""" % (self.indent_size, self.indent_with_tabs, self.preserve_newlines, self.max_preserve_newlines, self.opening_tags, self.middle_tags, self.closing_tags)


def default_options():
	return BeautifierOptions()


def beautify(string, opts=default_options()):
	b = Beautifier(string, opts)
	return b.beautify()


def beautify_file(file_name, opts=default_options()):
	if file_name == '-':  # stdin
		stream = sys.stdin
	else:
		stream = open(file_name)
	content = ''.join(stream.readlines())
	b = Beautifier(content, opts)
	return b.beautify()


def usage(stream=sys.stdout):

	print("vbscriptbeautifier.py@" + __version__ + """

VBScript beautifier

""", file=stream)
	if stream == sys.stderr:
		return 1
	else:
		return 0



class Beautifier:

	def __init__(self, source_text, opts=default_options()):
		self.source_text = source_text
		self.opts = opts
		self.indentSize = opts.indent_size
		self.indentChar = opts.indent_char
		if opts.indent_with_tabs:
			self.indentChar = "\t"
			self.indentSize = 1
		self.preserveNewlines = opts.preserve_newlines
		self.maxPreserveNewlines = opts.max_preserve_newlines
		self.opening_tags = opts.opening_tags
		self.middle_tags = opts.middle_tags
		self.closing_tags = opts.closing_tags


	def beautify(self):
			rawcode = self.source_text
			rawcode = rawcode.strip()
			rawcode_list = re.split('\n', rawcode)
			indent_char = self.indentSize * self.indentChar
			preserved_new_lines = 0
			rawcode_flat = ""

			for item in rawcode_list:
				if item == "":
					if self.preserveNewlines == False:
						continue
					else:
						preserved_new_lines += 1
						if preserved_new_lines >= self.maxPreserveNewlines:
							preserved_new_lines = 0
							continue
				tmp = item.strip()
				rawcode_flat = rawcode_flat + tmp + '\n'

			rawcode_flat_list = re.split('\n', rawcode_flat)

			beautified_code = ""
			indent_level = 0
			is_block_ignored = False
			is_block_raw = False

			for item in rawcode_flat_list:
				is_middle = False
				if re.search(self.closing_tags, item, re.IGNORECASE):
					indent_level -= 1
					if indent_level < 0:
						indent_level = 0
				if re.search(self.middle_tags, item, re.IGNORECASE):
					is_middle = True
				if re.search(self.opening_tags, item, re.IGNORECASE):
					indent_level += 1
				
				tmp = (indent_char * (indent_level-is_middle)) + item
				beautified_code = beautified_code + tmp + '\n'
				
			beautified_code = beautified_code.strip()
			return beautified_code
