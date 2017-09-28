# @author             Avtandil Kikabidze
# @copyright         Copyright (c) 2008-2015, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link             http://longman.me
# @license         The MIT License (MIT)

import re
import vbscriptbeautifier


class VbscriptFormatter:

    def __init__(self, formatter):
        self.formatter = formatter
        self.opts = formatter.settings.get('codeformatter_vbscript_options')

    def format(self, text):

        text = text.decode('utf-8')
        stderr = ''
        stdout = ''
        options = vbscriptbeautifier.default_options()

        if ('indent_size' in self.opts and self.opts['indent_size']):
            options.indent_size = self.opts['indent_size']
        else:
            options.indent_size = 1

        if ('indent_char' in self.opts and self.opts['indent_char']):
            options.indent_char = str(self.opts['indent_char'])
        else:
            options.indent_char = '\t'

        if ('indent_with_tabs' in self.opts and self.opts['indent_with_tabs']):
            options.indent_with_tabs = True
        else:
            options.indent_with_tabs = True

        if (
            'preserve_newlines' in self.opts and
            self.opts['preserve_newlines']
        ):
            options.preserve_newlines = True
        else:
            options.preserve_newlines = False

        if (
            'max_preserve_newlines' in self.opts and
            self.opts['max_preserve_newlines']
        ):
            options.max_preserve_newlines = self.opts['max_preserve_newlines']
        else:
            options.max_preserve_newlines = 10

        if ('opening_tags' in self.opts and self.opts['opening_tags']):
            options.opening_tags = str(self.opts['opening_tags'])

        if ('middle_tags' in self.opts and self.opts['middle_tags']):
            options.middle_tags = str(self.opts['middle_tags'])

        if ('closing_tags' in self.opts and self.opts['closing_tags']):
            options.closing_tags = str(self.opts['closing_tags'])

        try:
            stdout = vbscriptbeautifier.beautify(text, options)
        except Exception as e:
            stderr = str(e)

        if (not stderr and not stdout):
            stderr = 'Formatting error!'

        return stdout, stderr

    def format_on_save_enabled(self, file_name):
        format_on_save = False
        if ('format_on_save' in self.opts and self.opts['format_on_save']):
            format_on_save = self.opts['format_on_save']
        if (isinstance(format_on_save, str)):
            format_on_save = re.search(format_on_save, file_name) is not None
        return format_on_save
