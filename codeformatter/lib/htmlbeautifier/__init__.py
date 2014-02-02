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
		self.indent_size = 4
		self.indent_char = ' '
		self.indent_with_tabs = False
		self.preserve_newlines = False
		self.max_preserve_newlines = 10
		self.indent_tags = 'html|head|body|div|nav|ul|ol|dl|li|table|thead|tbody|tr|th|td|blockquote|select|form|option|optgroup|fieldset|legend|label|header|section|aside|footer|figure|video'

	def __repr__(self):
		return \
"""indent_size = %d
indent_char = [%s]
indent_with_tabs = [%s]
preserve_newlines = [%s]
max_preserve_newlines = [%d]
indent_tags = [%d]
""" % (self.indent_size, self.indent_char, self.indent_with_tabs, self.preserve_newlines, self.max_preserve_newlines, self.indent_tags)


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

	print("htmlbeautifier.py@" + __version__ + """

HTML beautifier (http://jsbeautifier.org/)

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
		self.indentTags = opts.indent_tags


	def beautify(self):

			ignored_tag_opening = "<script|<style|<!--|{\\*|<\\?php"
			ignored_tag_closing = "</script|</style|-->|\\*}|\\?>"

			tag_indent = "<"+self.indentTags.replace('|', '|<')
			tag_unindent = "</"+self.indentTags.replace('|', '|</')

			tag_pos_inline = "<link.*/>|<link.*\">|<meta.*/>|<script.*</script>|<div.*</div>|<li.*</li>|<dt.*</dt>|<dd.*</dd>|<th.*</th>|<td.*</td>|<legend.*</legend>|<label.*</label>|<option.*</option>|<input.*/>|<input.*\">|<!--.*-->"

			tag_raw_flat_opening = "<pre"
			tag_raw_flat_closing = "</pre"

			rawcode = self.source_text


			rawcode = rawcode.strip()

			rawcode_list = re.split('\n', rawcode)

			rawcode_flat = ""
			is_block_ignored = False
			is_block_raw = False

			indent_char = self.indentSize * self.indentChar
			preserved_new_lines = 0;
			for item in rawcode_list:

				if item == "":
					if self.preserveNewlines == False:
						continue
					else:
						preserved_new_lines += 1
						if preserved_new_lines >= self.maxPreserveNewlines:
							preserved_new_lines = 0
							continue




				# ignore raw code
				if re.search(tag_raw_flat_closing, item, re.IGNORECASE):
					tmp = item.strip()
					is_block_raw = False
				elif re.search(tag_raw_flat_opening, item, re.IGNORECASE):
					tmp = item.strip()
					is_block_raw = True

				if re.search(ignored_tag_closing, item, re.IGNORECASE):
					tmp = item.strip()
					is_block_ignored = False
				elif re.search(ignored_tag_opening, item, re.IGNORECASE):

					ignored_block_tab_count = item.count('\t')
					tmp = item.strip()
					is_block_ignored = True

				else:
					if is_block_raw == True:
						# remove tabs from raw_flat content
						tmp = re.sub('\t', '', item)
					elif is_block_ignored == True:
						tab_count = item.count(indent_char) - ignored_block_tab_count
						tmp = indent_char * tab_count + item.strip()
					else:
						tmp = item.strip()

				rawcode_flat = rawcode_flat + tmp + '\n'

			rawcode_flat_list = re.split('\n', rawcode_flat)

			beautified_code = ""

			indent_level = 0
			is_block_ignored = False
			is_block_raw = False

			for item in rawcode_flat_list:

				if re.search(tag_pos_inline, item, re.IGNORECASE):
					tmp = (indent_char * indent_level) + item

				elif re.search(tag_unindent, item, re.IGNORECASE):
					indent_level = indent_level - 1
					tmp = (indent_char * indent_level) + item

				elif re.search(tag_indent, item, re.IGNORECASE):
					tmp = (indent_char * indent_level) + item
					indent_level = indent_level + 1

				elif re.search(tag_raw_flat_opening, item, re.IGNORECASE):
					tmp = item
					is_block_raw = True
				elif re.search(tag_raw_flat_closing, item, re.IGNORECASE):
					tmp = item
					is_block_raw = False
				else:
					if is_block_raw == True or item == "":
						tmp = item

					else:
						tmp = (indent_char * indent_level) + item

				beautified_code = beautified_code + tmp + '\n'

			beautified_code = beautified_code.strip()

			return beautified_code