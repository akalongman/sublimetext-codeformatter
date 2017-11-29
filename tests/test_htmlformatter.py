import pytest
from unittest.mock import patch, Mock, call

from .scenarios import format_on_save_scenarios


@patch('codeformatter.htmlformatter.htmlbeautifier')
def test_html_formatter_instance_creation(htmlbeautifier):

    htmlbeautifier.default_options = Mock(
        return_value={'movie': 'interstellar'})
    from codeformatter.htmlformatter import HtmlFormatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {'codeformatter_html_options': {'test': 123}}

    with patch.object(HtmlFormatter, 'fill_custom_options'):
        cf = HtmlFormatter(mocked_formatter)
        assert cf.formatter == mocked_formatter
        assert cf.options == {'movie': 'interstellar'}
        cf.fill_custom_options.assert_called_once_with({'test': 123})


# keys verified in the formatter
options_scenarios = []

# generic pair of initial value and expected results
generic_pairs = [(10, 10), ('hi', 'hi'), (False, False), (True, True)]
options = [
    'formatter_version',
    'indent_size',
    'minimum_attribute_count',
    'first_attribute_on_new_line',
    'indent_with_tabs',
    'expand_tags',
    'reduce_empty_tags',
    'reduce_whole_word_tags',
    'exception_on_tag_mismatch',
    'custom_singletons',
    'format_on_save'
]
for option in options:
    for value, expected in generic_pairs:
        options_scenarios.append((option, value, expected))

# indent_char pairs of value and expected result
ic_pairs = [(10, '10'), ('hi', 'hi'), (True, 'True'), (False, 'False')]
for value, expected in ic_pairs:
    options_scenarios.append(('indent_char', value, expected))


@pytest.mark.parametrize('option,value,expected', options_scenarios)
@patch('codeformatter.htmlformatter.htmlbeautifier')
def test_html_formatter_fill_custom_options_values(
    htmlbeautifier, option, value, expected
):

    fake_options = type('fake_obj', (object,), {})
    htmlbeautifier.default_options = Mock(return_value=fake_options)
    from codeformatter.htmlformatter import HtmlFormatter

    curr_option = {}
    curr_option[option] = value
    mocked_formatter = Mock()
    mocked_formatter.settings = {
        'codeformatter_html_options': curr_option}
    HtmlFormatter(mocked_formatter)

    assert getattr(fake_options, option) == expected


@pytest.mark.parametrize('option,value,expected', options_scenarios)
@patch('codeformatter.htmlformatter.htmlbeautifier')
def test_html_formatter_fill_custom_options_keys_presence(
    htmlbeautifier, option, value, expected
):

    fake_options = type('fake_obj', (object,), {})
    htmlbeautifier.default_options = Mock(return_value=fake_options)
    from codeformatter.htmlformatter import HtmlFormatter

    curr_option = {}
    curr_option[option] = value
    mocked_formatter = Mock()
    mocked_formatter.settings = {
        'codeformatter_html_options': curr_option}
    HtmlFormatter(mocked_formatter)

    assert getattr(fake_options, option) == expected


# # scenarios with full the values
# full_options_1, full_options_2, full_options_3 = [], [], []
# for key, t1, t2, t3, _ in options_to_test:
#     full_options_1.append((key, t1[0], t1[1]))
#     full_options_2.append((key, t2[0], t2[1]))
#     full_options_3.append((key, t3[0], t3[1]))
# options_scenarios_full = [full_options_1, full_options_2, full_options_3]


# @pytest.mark.parametrize('full_options', options_scenarios_full)
# @patch('codeformatter.htmlformatter.htmlbeautifier')
# def test_html_formatter_fill_custom_options_full(
#     htmlbeautifier, full_options
# ):
#     fake_options = type('fake_obj', (object,), {})
#     htmlbeautifier.default_options = Mock(return_value=fake_options)
#     from codeformatter.htmlformatter import htmlformatter

#     mocked_formatter = Mock()
#     curr_values = {}
#     curr_expected = []
#     for key, value, expected in full_options:
#         curr_values[key] = value
#         curr_expected.append((key, expected))

#     mocked_formatter.settings = {'codeformatter_css_options': curr_values}

#     htmlformatter(mocked_formatter)
#     for key, expected in curr_expected:
#         assert getattr(fake_options, key, None) == expected


# @patch('codeformatter.htmlformatter.htmlbeautifier')
# def test_html_formatter_format(htmlbeautifier):

#     htmlbeautifier.default_options = Mock(return_value={})
#     htmlbeautifier.beautify = Mock(return_value='graham nash')
#     from codeformatter.htmlformatter import htmlformatter

#     mocked_formatter = Mock()
#     mocked_formatter.settings = {'codeformatter_css_options': {}}

#     input_text = 'test'.encode('utf8')
#     cff = htmlformatter(mocked_formatter)
#     out, err = cff.format(input_text)

#     assert htmlbeautifier.beautify.called_with(
#         call(input_text.decode('utf-8'), {}))
#     assert out == 'graham nash'
#     assert err == ''


# @patch('codeformatter.htmlformatter.htmlbeautifier')
# def test_html_formatter_format_exception(htmlbeautifier):

#     htmlbeautifier.beautify = Mock(
#         side_effect=Exception('looks like it failed'))
#     from codeformatter.htmlformatter import htmlformatter

#     mocked_formatter = Mock()
#     mocked_formatter.settings = {'codeformatter_css_options': {}}

#     input_text = 'test'.encode('utf8')
#     cff = htmlformatter(mocked_formatter)

#     out, err = cff.format(input_text)
#     assert out == ''
#     assert err == 'looks like it failed'


# @patch('codeformatter.htmlformatter.htmlbeautifier')
# def test_html_formatter_format_empty_exception(htmlbeautifier):

#     htmlbeautifier.beautify = Mock(side_effect=Exception(''))
#     from codeformatter.htmlformatter import htmlformatter

#     mocked_formatter = Mock()
#     mocked_formatter.settings = {'codeformatter_css_options': {}}

#     input_text = 'test'.encode('utf8')
#     cff = htmlformatter(mocked_formatter)

#     out, err = cff.format(input_text)
#     assert out == ''
#     assert err == 'Formatting error!'


# @pytest.mark.parametrize('options,filename,expected', format_on_save_scenarios)
# @patch('codeformatter.htmlformatter.htmlbeautifier')
# def test_html_formatter_format_on_save_enabled(
#     htmlbeautifier, options, filename, expected
# ):

#     fake_options = type('fake_obj', (object,), {})
#     htmlbeautifier.default_options = Mock(return_value=fake_options)
#     from codeformatter.htmlformatter import htmlformatter

#     mocked_formatter = Mock()
#     mocked_formatter.settings = {'codeformatter_css_options': options}

#     cff = htmlformatter(mocked_formatter)
#     res = cff.format_on_save_enabled(filename)
#     assert res is expected
