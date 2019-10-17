import pytest
from unittest.mock import patch, Mock, call

import sublime
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
options_values = []

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
        options_values.append((option, value, expected))

# indent_char pairs of value and expected result
ic_pairs = [(10, '10'), ('hi', 'hi'), (True, 'True'), (False, 'False')]
for value, expected in ic_pairs:
    options_values.append(('indent_char', value, expected))


@pytest.mark.parametrize('option,value,expected', options_values)
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


options_presence = []
for option in options:
    curr_options = []
    if len(options_presence) > 0:
        curr_options = options_presence[-1].copy()
    curr_options.append((option, 'test_value'))
    options_presence.append(curr_options)


@pytest.mark.parametrize('current_options', options_presence)
@patch('codeformatter.htmlformatter.htmlbeautifier')
def test_html_formatter_fill_custom_options_keys_presence(
    htmlbeautifier, current_options
):

    fake_options = type('fake_obj', (object,), {})
    htmlbeautifier.default_options = Mock(return_value=fake_options)
    from codeformatter.htmlformatter import HtmlFormatter

    curr_option = {}
    for option, value in current_options:
        curr_option[option] = value
    mocked_formatter = Mock()
    mocked_formatter.settings = {
        'codeformatter_html_options': curr_option}
    HtmlFormatter(mocked_formatter)

    option_in = []
    for option, value in current_options:
        assert hasattr(fake_options, option)
        option_in.append(option)
    for option in options:
        if option in option_in:
            continue
        assert not hasattr(fake_options, option)


@patch('codeformatter.htmlformatter.htmlbeautifier')
def test_html_formatter_format_option_bs4(htmlbeautifier):

    fake_options = type('fake_obj', (object,), {'formatter_version': 'bs4'})
    htmlbeautifier.default_options = Mock(return_value=fake_options)
    from codeformatter import htmlformatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {'codeformatter_html_options': {}}

    with patch.object(htmlformatter.HtmlFormatter, 'format_with_bs4', return_value=('from_bs4', '')) as mocked_bs4, patch.object(htmlformatter.HtmlFormatter, 'format_with_beautifier', return_value=('from_beautifier', '')) as mocked_beautifier:  # noqa

        input_text = 'test'.encode('utf8')

        htmlformatter.use_bs4 = False
        cff = htmlformatter.HtmlFormatter(mocked_formatter)
        out, err = cff.format(input_text)

        sublime.error_message.assert_called_once_with(
            u'CodeFormatter\n\nUnable to load BeautifulSoup HTML '
            u'formatter. The old RegExp-based formatter was '
            u'automatically used for you instead.'
        )
        mocked_beautifier.assert_called_once_with(input_text.decode('utf-8'))
        assert out == 'from_beautifier'
        assert err == ''

        sublime.reset_mock()
        mocked_bs4.reset_mock()
        mocked_beautifier.reset_mock()

        htmlformatter.use_bs4 = True
        cff = htmlformatter.HtmlFormatter(mocked_formatter)
        out, err = cff.format(input_text)

        assert not sublime.error_message.called
        mocked_bs4.assert_called_once_with(input_text.decode('utf-8'))
        assert out == 'from_bs4'
        assert err == ''


@patch('codeformatter.htmlformatter.htmlbeautifier')
def test_html_formatter_format_not_option_bs4(htmlbeautifier):

    fake_options = type('fake_obj', (object,), {'formatter_version': '-_-'})
    htmlbeautifier.default_options = Mock(return_value=fake_options)
    from codeformatter import htmlformatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {'codeformatter_html_options': {}}

    with patch.object(htmlformatter.HtmlFormatter, 'format_with_bs4', return_value=('from_bs4', '')) as mocked_bs4, patch.object(htmlformatter.HtmlFormatter, 'format_with_beautifier', return_value=('from_beautifier', '')) as mocked_beautifier:  # noqa

        htmlformatter.use_bs4 = False
        input_text = 'test'.encode('utf8')
        cff = htmlformatter.HtmlFormatter(mocked_formatter)
        out, err = cff.format(input_text)

        assert not sublime.error_message.called
        assert not mocked_bs4.called
        mocked_beautifier.assert_called_once_with(input_text.decode('utf-8'))
        assert out == 'from_beautifier'
        assert err == ''

        sublime.reset_mock()
        mocked_bs4.reset_mock()
        mocked_beautifier.reset_mock()

        htmlformatter.use_bs4 = True
        cff = htmlformatter.HtmlFormatter(mocked_formatter)
        out, err = cff.format(input_text)

        assert not sublime.error_message.called
        assert not mocked_bs4.called
        mocked_beautifier.assert_called_once_with(input_text.decode('utf-8'))
        assert out == 'from_beautifier'
        assert err == ''


bs4_options_scenarios = [
    ({}, 4),
    ({'another_option': 1}, 4),
    ({'indent_size': 2}, 2),
    ({'indent_size': 3}, 3),
]


@pytest.mark.parametrize('current_options,expected', bs4_options_scenarios)
@patch('codeformatter.htmlformatter.BeautifulSoup')
@patch('codeformatter.htmlformatter.htmlbeautifier')
def test_html_formatter_format_with_bs4_valid(
    htmlbeautifier, mocked_bs4, current_options, expected
):

    fake_options = type('fake_obj', (object,), {})
    htmlbeautifier.default_options = Mock(return_value=fake_options)
    from codeformatter import htmlformatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {'codeformatter_html_options': current_options}

    mocked_bs4_instance = mocked_bs4.return_value
    mocked_bs4_instance.prettify = Mock(return_value='returned value')

    formatter = htmlformatter.HtmlFormatter(mocked_formatter)
    res = formatter.format_with_bs4('testing_call')

    mocked_bs4.assert_called_once_with('testing_call', 'html.parser')
    mocked_bs4_instance.prettify.assert_called_once_with(
        formatter=None, indent_size=expected)
    assert res == ('returned value', '')


@patch('codeformatter.htmlformatter.BeautifulSoup')
@patch('codeformatter.htmlformatter.htmlbeautifier')
def test_html_formatter_format_with_bs4_exception(htmlbeautifier, mocked_bs4):

    fake_options = type('fake_obj', (object,), {})
    htmlbeautifier.default_options = Mock(return_value=fake_options)
    mocked_bs4.side_effect = Exception('some error')
    from codeformatter import htmlformatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {'codeformatter_html_options': {}}

    formatter = htmlformatter.HtmlFormatter(mocked_formatter)
    res = formatter.format_with_bs4('testing')

    assert res == ('', 'some error')


beautifier_options_scenarios = [
    ('good result', ('good result', '')),
    ('another result', ('another result', '')),
    ('', ('', 'Formatting error!')),
]


@pytest.mark.parametrize('result,expected', beautifier_options_scenarios)
@patch('codeformatter.htmlformatter.htmlbeautifier')
def test_html_formatter_format_with_beautifier_valid(
    htmlbeautifier, result, expected
):

    fake_options = type('fake_obj', (object,), {})
    htmlbeautifier.default_options = Mock(return_value=fake_options)
    htmlbeautifier.beautify = Mock(return_value=result)
    from codeformatter import htmlformatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {'codeformatter_html_options': {}}

    formatter = htmlformatter.HtmlFormatter(mocked_formatter)
    res = formatter.format_with_beautifier('another one')

    htmlbeautifier.beautify.assert_called_once_with(
        'another one', fake_options)
    assert res == expected


beautifier_except_options_scenarios = [
    ('some strange error', ('', 'some strange error')),
    ('a very strange error', ('', 'a very strange error')),
    ('', ('', 'Formatting error!')),
]


@pytest.mark.parametrize('text,expected', beautifier_except_options_scenarios)
@patch('codeformatter.htmlformatter.htmlbeautifier')
def test_html_formatter_format_with_beautifier_exception(
    htmlbeautifier, text, expected
):

    fake_options = type('fake_obj', (object,), {})
    htmlbeautifier.default_options = Mock(return_value=fake_options)
    htmlbeautifier.beautify.side_effect = Exception(text)
    from codeformatter import htmlformatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {'codeformatter_html_options': {}}

    formatter = htmlformatter.HtmlFormatter(mocked_formatter)
    res = formatter.format_with_beautifier('out of words')

    assert res == expected


@pytest.mark.parametrize('options,filename,expected', format_on_save_scenarios)
@patch('codeformatter.htmlformatter.htmlbeautifier')
def test_html_formatter_format_on_save_enabled(
    htmlbeautifier, options, filename, expected
):

    fake_options = type('fake_obj', (object,), {})
    htmlbeautifier.default_options = Mock(return_value=fake_options)
    from codeformatter import htmlformatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {'codeformatter_html_options': options}

    formatter = htmlformatter.HtmlFormatter(mocked_formatter)
    res = formatter.format_on_save_enabled(filename)
    assert res is expected
