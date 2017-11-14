import pytest
from unittest.mock import patch, Mock, call

from .scenarios import format_on_save_scenarios


@patch('codeformatter.coldfusionformatter.coldfusionbeautifier')
def test_coldfusion_formatter_instance_creation(coldfusionbeautifier):

    coldfusionbeautifier.default_options = Mock(
        return_value={'singer': 'nina'})
    from codeformatter.coldfusionformatter import ColdfusionFormatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {
        'codeformatter_coldfusion_options': 'feeling good'}

    with patch.object(ColdfusionFormatter, 'fill_custom_options'):
        cff = ColdfusionFormatter(mocked_formatter)
        assert cff.formatter == mocked_formatter
        assert cff.options == {'singer': 'nina'}
        cff.fill_custom_options.assert_called_once_with('feeling good')


# options map to generate scenarios
options_to_test = [
    ('indent_size', 10),
    ('indent_char', 'a'),
    ('minimum_attribute_count', 5),
    ('first_attribute_on_new_line', 'v'),
    ('indent_with_tabs', True),
    ('expand_tags', False),
    ('expand_javascript', 'on'),
    ('reduce_empty_tags', 1),
    ('reduce_whole_word_tags', True),
    ('exception_on_tag_mismatch', 'yeap'),
    ('custom_singletons', 1)
]

options_scenarios = []
for idx, (k, v) in enumerate(options_to_test):
    new_item = ()
    if idx > 0:
        new_item = options_scenarios[idx-1]
    new_item += ((k, v),)
    options_scenarios.append(new_item)


@pytest.mark.parametrize('options', options_scenarios)
@patch('codeformatter.coldfusionformatter.coldfusionbeautifier')
def test_coldfusion_formatter_fill_custom_options(
    coldfusionbeautifier, options
):

    fake_options = type('fake_obj', (object,), {})
    coldfusionbeautifier.default_options = Mock(return_value=fake_options)
    from codeformatter.coldfusionformatter import ColdfusionFormatter

    mocked_formatter = Mock()
    curr_options = {}
    for k, v in options:
        curr_options[k] = v

    mocked_formatter.settings = {
        'codeformatter_coldfusion_options': curr_options}

    ColdfusionFormatter(mocked_formatter)
    keys_tested = []
    for k, v in options:
        assert getattr(fake_options, k) == v
        keys_tested.append(k)
    for key, _ in options_to_test:
        if key in keys_tested:
            continue
        assert not getattr(fake_options, key, None)


@patch('codeformatter.coldfusionformatter.coldfusionbeautifier')
def test_coldfusion_formatter_format(coldfusionbeautifier):

    coldfusionbeautifier.default_options = Mock(return_value={})
    coldfusionbeautifier.beautify = Mock(return_value='beautified text')
    from codeformatter.coldfusionformatter import ColdfusionFormatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {'codeformatter_coldfusion_options': {}}

    input_text = 'test'.encode('utf8')
    cff = ColdfusionFormatter(mocked_formatter)
    out, err = cff.format(input_text)

    assert coldfusionbeautifier.beautify.called_with(
        call(input_text.decode('utf-8'), {}))
    assert out == 'beautified text'
    assert err == ''


@patch('codeformatter.coldfusionformatter.coldfusionbeautifier')
def test_coldfusion_formatter_format_exception(coldfusionbeautifier):

    coldfusionbeautifier.beautify = Mock(
        side_effect=Exception('something is wrong'))
    from codeformatter.coldfusionformatter import ColdfusionFormatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {'codeformatter_coldfusion_options': {}}

    input_text = 'test'.encode('utf8')
    cff = ColdfusionFormatter(mocked_formatter)

    out, err = cff.format(input_text)
    assert out == ''
    assert err == 'something is wrong'


@patch('codeformatter.coldfusionformatter.coldfusionbeautifier')
def test_coldfusion_formatter_format_empty_exception(coldfusionbeautifier):

    coldfusionbeautifier.beautify = Mock(side_effect=Exception(''))
    from codeformatter.coldfusionformatter import ColdfusionFormatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {'codeformatter_coldfusion_options': {}}

    input_text = 'test'.encode('utf8')
    cff = ColdfusionFormatter(mocked_formatter)

    out, err = cff.format(input_text)
    assert out == ''
    assert err == 'Formatting error!'


@pytest.mark.parametrize('options,filename,expected', format_on_save_scenarios)
@patch('codeformatter.coldfusionformatter.coldfusionbeautifier')
def test_coldfusion_formatter_format_on_save_enabled(
    coldfusionbeautifier, options, filename, expected
):

    fake_options = type('fake_obj', (object,), {})
    coldfusionbeautifier.default_options = Mock(return_value=fake_options)
    from codeformatter.coldfusionformatter import ColdfusionFormatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {'codeformatter_coldfusion_options': options}

    cff = ColdfusionFormatter(mocked_formatter)
    res = cff.format_on_save_enabled(filename)
    assert res is expected
