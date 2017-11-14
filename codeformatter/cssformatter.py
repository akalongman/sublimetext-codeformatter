# @author             Avtandil Kikabidze
# @copyright         Copyright (c) 2008-2015, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link             http://longman.me
# @license         The MIT License (MIT)

import re
import cssbeautifier


class CssFormatter:

    def __init__(self, formatter):
        self.formatter = formatter
        self.options = cssbeautifier.default_options()

        # parse custom options from settings file
        self.fill_custom_options(formatter.settings.get('codeformatter_css_options'))

    def fill_custom_options(self, options):

        if not options:
            return

        # map of key, value if key exists and value if key not exists
        custom_options = [
            ('indent_size', None, 4),
            ('indent_char', None, ' '),
            ('indent_with_tabs', True, False),
            ('selector_separator_newline', True, False),
            ('end_with_newline', True, False),
            ('eol', None, '\n'),
            ('space_around_combinator', True, False),
            ('newline_between_rules', True, False),
            ('format_on_save', None, False)
        ]

        casters = {'indent_char': str}

        for key, on_value, off_value in custom_options:

            if key not in options:
                value = off_value
            else:
                value = options[key]
                if value and on_value:
                    value = on_value
                else:
                    cast = casters.get(key)
                    if cast:
                        value = cast(value)

            setattr(self.options, key, value)

    def format(self, text):

        text = text.decode('utf-8')
        stderr = ''
        stdout = ''

        try:
            stdout = cssbeautifier.beautify(text, self.options)
        except Exception as e:
            stderr = str(e)

        if (not stderr and not stdout):
            stderr = 'Formatting error!'

        return stdout, stderr

    def format_on_save_enabled(self, file_name):
        format_on_save = getattr(self.options, 'format_on_save', False)
        if isinstance(format_on_save, str):
            format_on_save = re.search(format_on_save, file_name) is not None
        return format_on_save
