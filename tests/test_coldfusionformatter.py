import sys
import pytest
from unittest.mock import patch, Mock, call

sys.modules['coldfusionbeautifier'] = Mock()


def test_coldfusion_formatter_instance():
    from codeformatter.coldfusionformatter import ColdfusionFormatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {
        'codeformatter_coldfusion_options': {'test': 'first'}}

    cff = ColdfusionFormatter(mocked_formatter)
    assert cff.formatter == mocked_formatter
    assert len(cff.opts.keys()) == 1
    assert cff.opts['test'] == 'first'


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
def test_coldfusion_formatter_instance_options(coldfusionbeautifier, options):

    # mock beautifier
    fake_options = type('fake_obj', (object,), {})
    coldfusionbeautifier.default_options = Mock(return_value=fake_options)
    from codeformatter.coldfusionformatter import ColdfusionFormatter

    mocked_formatter = Mock()
    curr_options = {}
    for k, v in options:
        curr_options[k] = v

    mocked_formatter.settings = {
        'codeformatter_coldfusion_options': curr_options}

    cff = ColdfusionFormatter(mocked_formatter)
    cff.format('test'.encode('utf8'))
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
    mocked_formatter.settings = {
        'codeformatter_coldfusion_options': {'test': '1'}}

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
    mocked_formatter.settings = {
        'codeformatter_coldfusion_options': {'test': '1'}}

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
    mocked_formatter.settings = {
        'codeformatter_coldfusion_options': {'test': '1'}}

    input_text = 'test'.encode('utf8')
    cff = ColdfusionFormatter(mocked_formatter)

    out, err = cff.format(input_text)
    assert out == ''
    assert err == 'Formatting error!'


def test_coldfusion_formatter_format_on_save_enabled_true():
    from codeformatter.coldfusionformatter import ColdfusionFormatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {
        'codeformatter_coldfusion_options': {'format_on_save': True}}

    cff = ColdfusionFormatter(mocked_formatter)
    res = cff.format_on_save_enabled('test')
    assert res is True


def test_coldfusion_formatter_format_on_save_enabled_false():
    from codeformatter.coldfusionformatter import ColdfusionFormatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {
        'codeformatter_coldfusion_options': {'format_on_save': False}}

    cff = ColdfusionFormatter(mocked_formatter)
    res = cff.format_on_save_enabled('test')
    assert res is False


def test_coldfusion_formatter_format_on_save_enabled_re_not_match():
    from codeformatter.coldfusionformatter import ColdfusionFormatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {
        'codeformatter_coldfusion_options': {'format_on_save': '$.test^'}}

    cff = ColdfusionFormatter(mocked_formatter)
    res = cff.format_on_save_enabled('test.txt')
    assert res is False


def test_coldfusion_formatter_format_on_save_enabled_re_match():
    from codeformatter.coldfusionformatter import ColdfusionFormatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {
        'codeformatter_coldfusion_options': {'format_on_save': '.test$'}}

    cff = ColdfusionFormatter(mocked_formatter)
    res = cff.format_on_save_enabled('file.test')
    assert res is True
