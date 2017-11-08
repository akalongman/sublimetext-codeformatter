# @author             Avtandil Kikabidze
# @copyright         Copyright (c) 2008-2015, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link             http://longman.me
# @license         The MIT License (MIT)

import re
import coldfusionbeautifier


class ColdfusionFormatter:

    def __init__(self, formatter):

        self.formatter = formatter
        self.options = coldfusionbeautifier.default_options()

        # parse custom options from settings file
        self.fill_custom_options(formatter.settings.get('codeformatter_coldfusion_options'))

    def fill_custom_options(self, options):

        if not options:
            return

        custom_options = [
            'indent_size',
            'indent_char',
            'minimum_attribute_count',
            'first_attribute_on_new_line',
            'indent_with_tabs',
            'expand_tags',
            'expand_javascript',
            'reduce_empty_tags',
            'reduce_whole_word_tags',
            'exception_on_tag_mismatch',
            'custom_singletons',
            'format_on_save'
        ]

        casters = {'indent_char': str}

        for key in custom_options:

            value = options.get(key)
            if value is None:
                continue

            cast = casters.get(key)
            if cast:
                value = cast(value)

            setattr(self.options, key, value)

    def format(self, text):

        text = text.decode('utf-8')
        stderr = ''
        stdout = ''

        try:
            stdout = coldfusionbeautifier.beautify(text, self.options)
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
