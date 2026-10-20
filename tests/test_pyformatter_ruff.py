import os
import shutil

import pytest
from unittest.mock import Mock


RUFF = os.environ.get('CODEFORMATTER_TEST_RUFF') or shutil.which('ruff')

pytestmark = pytest.mark.skipif(not RUFF, reason='ruff not installed')

SOURCE = b'x = {"alpha": 1, "beta": 2}\n'


def format_in_project(tmp_path, monkeypatch, config, relative_path, text):
    from codeformatter.pyformatter import PyFormatter
    project = tmp_path / 'proj'
    path = project / relative_path
    path.parent.mkdir(parents=True)
    (project / 'ruff.toml').write_text(config)
    path.write_bytes(text)
    elsewhere = tmp_path / 'elsewhere'
    elsewhere.mkdir()
    monkeypatch.chdir(elsewhere)
    mocked_formatter = Mock()
    mocked_formatter.settings = {
        'codeformatter_python_options': {'ruff_path': RUFF}}
    mocked_formatter.file_name = str(path)
    mocked_formatter.platform = 'windows' if os.name == 'nt' else 'linux'
    return PyFormatter(mocked_formatter).format(text)


def test_ruff_applies_project_config(tmp_path, monkeypatch):
    result = format_in_project(
        tmp_path, monkeypatch, 'line-length = 20\n', 'pkg/m.py', SOURCE)

    assert result == (b'x = {\n    "alpha": 1,\n    "beta": 2,\n}\n', '')


def test_ruff_leaves_excluded_file_unchanged(tmp_path, monkeypatch):
    result = format_in_project(
        tmp_path, monkeypatch,
        'line-length = 20\nextend-exclude = ["vendor"]\n',
        'vendor/v.py', SOURCE)

    assert result == (b'x = {"alpha": 1, "beta": 2}\n', '')


def test_ruff_reports_invalid_config(tmp_path, monkeypatch):
    out, err = format_in_project(
        tmp_path, monkeypatch, 'line-length = "x"\n', 'pkg/m.py', b'x=1\n')

    assert out == ''
    assert err.startswith('ruff failed')
    assert 'ruff.toml' in err


def test_ruff_reports_syntax_error(tmp_path, monkeypatch):
    out, err = format_in_project(
        tmp_path, monkeypatch, 'line-length = 20\n', 'pkg/m.py',
        b'def (:\n')

    assert out == ''
    assert err.startswith('error: Failed to parse')
