import pytest
from unittest.mock import patch, Mock, call

from .scenarios import format_on_save_scenarios


@patch('codeformatter.cssformatter.cssbeautifier')
def test_css_formatter_instance_creation(cssbeautifier):

    cssbeautifier.default_options = Mock(
        return_value={'band': 'beatles'})
    from codeformatter.cssformatter import CssFormatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {
        'codeformatter_css_options': 'a day in the life'}

    with patch.object(CssFormatter, 'fill_custom_options'):
        cf = CssFormatter(mocked_formatter)
        assert cf.formatter == mocked_formatter
        assert cf.options == {'band': 'beatles'}
        cf.fill_custom_options.assert_called_once_with('a day in the life')


# options map to generate scenarios
options_to_test = [
    ('indent_size', (10, 10), (40, 40), (1, 1), 4),
    ('indent_char', ('a', 'a'), (1, '1'), (True, 'True'), ' '),
    ('indent_with_tabs', (True, True), ('a', True), (False, False), False),
    ('selector_separator_newline', (True, True), ('a', True), (False, False), False),
    ('end_with_newline', (True, True), ('a', True), (False, False), False),
    ('eol', (1, 1), ('a', 'a'), (False, False), '\n'),
    ('space_around_combinator', (True, True), ('a', True), (False, False), False),
    ('newline_between_rules', (True, True), ('a', True), (False, False), False),
]


# scenarios of not used properties
def get_not_used_scenarios(key):
    values = []
    for option in options_to_test:
        if key == option[0]:
            continue
        values.append((option[0], option[-1]))
    return values

# scenarios with partialy the values
options_scenarios_partialy = []
for key, t1, t2, t3, _ in options_to_test:
    not_used_scenarios = get_not_used_scenarios(key)
    options_scenarios_partialy.append((key, t1[0], t1[1], not_used_scenarios))
    options_scenarios_partialy.append((key, t2[0], t2[1], not_used_scenarios))
    options_scenarios_partialy.append((key, t3[0], t3[1], not_used_scenarios))


@pytest.mark.parametrize(
    'key,value,expected,not_used_scenarios', options_scenarios_partialy)
@patch('codeformatter.cssformatter.cssbeautifier')
def test_css_formatter_fill_custom_options_partialy(
    cssbeautifier, key, value, expected, not_used_scenarios
):

    fake_options = type('fake_obj', (object,), {})
    cssbeautifier.default_options = Mock(return_value=fake_options)
    from codeformatter.cssformatter import CssFormatter

    mocked_formatter = Mock()
    curr_options = {}
    curr_options[key] = value

    mocked_formatter.settings = {'codeformatter_css_options': curr_options}

    CssFormatter(mocked_formatter)
    assert getattr(fake_options, key) == expected
    for key, expected in not_used_scenarios:
        assert getattr(fake_options, key, None) == expected


# scenarios with full the values
full_options_1, full_options_2, full_options_3 = [], [], []
for key, t1, t2, t3, _ in options_to_test:
    full_options_1.append((key, t1[0], t1[1]))
    full_options_2.append((key, t2[0], t2[1]))
    full_options_3.append((key, t3[0], t3[1]))
options_scenarios_full = [full_options_1, full_options_2, full_options_3]


@pytest.mark.parametrize('full_options', options_scenarios_full)
@patch('codeformatter.cssformatter.cssbeautifier')
def test_css_formatter_fill_custom_options_full(
    cssbeautifier, full_options
):
    fake_options = type('fake_obj', (object,), {})
    cssbeautifier.default_options = Mock(return_value=fake_options)
    from codeformatter.cssformatter import CssFormatter

    mocked_formatter = Mock()
    curr_values = {}
    curr_expected = []
    for key, value, expected in full_options:
        curr_values[key] = value
        curr_expected.append((key, expected))

    mocked_formatter.settings = {'codeformatter_css_options': curr_values}

    CssFormatter(mocked_formatter)
    for key, expected in curr_expected:
        assert getattr(fake_options, key, None) == expected


@patch('codeformatter.cssformatter.cssbeautifier')
def test_css_formatter_format(cssbeautifier):

    cssbeautifier.default_options = Mock(return_value={})
    cssbeautifier.beautify = Mock(return_value='graham nash')
    from codeformatter.cssformatter import CssFormatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {'codeformatter_css_options': {}}

    input_text = 'test'.encode('utf8')
    cff = CssFormatter(mocked_formatter)
    out, err = cff.format(input_text)

    assert cssbeautifier.beautify.called_with(
        call(input_text.decode('utf-8'), {}))
    assert out == 'graham nash'
    assert err == ''


@patch('codeformatter.cssformatter.cssbeautifier')
def test_css_formatter_format_exception(cssbeautifier):

    cssbeautifier.beautify = Mock(
        side_effect=Exception('looks like it failed'))
    from codeformatter.cssformatter import CssFormatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {'codeformatter_css_options': {}}

    input_text = 'test'.encode('utf8')
    cff = CssFormatter(mocked_formatter)

    out, err = cff.format(input_text)
    assert out == ''
    assert err == 'looks like it failed'


@patch('codeformatter.cssformatter.cssbeautifier')
def test_css_formatter_format_empty_exception(cssbeautifier):

    cssbeautifier.beautify = Mock(side_effect=Exception(''))
    from codeformatter.cssformatter import CssFormatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {'codeformatter_css_options': {}}

    input_text = 'test'.encode('utf8')
    cff = CssFormatter(mocked_formatter)

    out, err = cff.format(input_text)
    assert out == ''
    assert err == 'Formatting error!'


@pytest.mark.parametrize('options,filename,expected', format_on_save_scenarios)
@patch('codeformatter.cssformatter.cssbeautifier')
def test_css_formatter_format_on_save_enabled(
    cssbeautifier, options, filename, expected
):

    fake_options = type('fake_obj', (object,), {})
    cssbeautifier.default_options = Mock(return_value=fake_options)
    from codeformatter.cssformatter import CssFormatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {'codeformatter_css_options': options}

    cff = CssFormatter(mocked_formatter)
    res = cff.format_on_save_enabled(filename)
    assert res is expected
